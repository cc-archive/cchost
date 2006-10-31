<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

/**
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
class v_1_2h extends CCUpdate
{
    function Update()
    {
        $this->update_remixes();
        $this->update_ratings();
        $this->calc_rankings();
    }

    function calc_rankings()
    {
        $configs =& CCConfigs::GetTable();
        $chart = $configs->GetConfig('chart');
        $uploads = new CCTable('cc_tbl_uploads','upload_id');
        $rows = $uploads->QueryRows('upload_score > 0');
        foreach($rows as $row)
        {
            CCSync::_calc_rank($chart,$row);
            $where['upload_id'] = $row['upload_id'];
            $where['upload_rank'] = $row['upload_rank'];
            $uploads->Update($where);
        }

        $sql =<<<END
            SELECT users.*
               FROM cc_tbl_uploads
               JOIN cc_tbl_user users ON user_id = upload_user
               GROUP BY upload_user
END;

        $users = new CCTable('cc_tbl_user','user_id');
        $rows = CCDatabase::QueryRows($sql);
        $configs =& CCConfigs::GetTable();
        $chart = $configs->GetConfig('chart');
        foreach( $rows as $row )
        {
            CCSync::_calc_rank($chart,$row,'user');
            $u2args['user_id'] = $row['user_id'];
            $u2args['user_rank'] = $row['user_rank'];
            $users->Update($u2args);
        }

    }

    function update_remixes()
    {
        $this->_check_for_field('cc_tbl_uploads','upload_num_remixes',      'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_uploads','upload_num_pool_remixes', 'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_uploads','upload_num_sources',      'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_uploads','upload_num_pool_sources', 'INT(7) unsigned');

        $this->_check_for_field('cc_tbl_user','user_num_remixes', 'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_user','user_num_remixed', 'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_user','user_num_uploads', 'INT(7) unsigned');

        $sql[] =<<<END
            SELECT COUNT(*) as upload_num_remixes, tree_parent as upload_id
               FROM cc_tbl_tree
               GROUP BY tree_parent
END;
        $sql[] =<<<END
            SELECT COUNT(*) as upload_num_sources, tree_child as upload_id
               FROM cc_tbl_tree
               GROUP BY tree_child
END;
        $sql[] =<<<END
            SELECT COUNT(*) as upload_num_pool_sources, pool_tree_child as upload_id
               FROM cc_tbl_pool_tree
               GROUP BY pool_tree_child
END;


        $uploads = new CCTable('cc_tbl_uploads','upload_id');
        
        foreach( $sql as $s )
        {
            $rows = CCDatabase::QueryRows($s);
            foreach($rows as $row)
                $uploads->Update($row);
        }

        $sql = array();

        $sql[] =<<<END
            SELECT COUNT(*) as user_num_uploads, upload_user as user_id
                FROM cc_tbl_uploads
                GROUP BY upload_user
END;

        $sql[] =<<<END
            SELECT COUNT(*) as user_num_remixes, upload_user as user_id
                FROM cc_tbl_uploads
                WHERE (upload_num_sources > 0) OR (upload_num_pool_sources > 0)
                GROUP BY upload_user
END;

        $sql[] =<<<END
            SELECT SUM(upload_num_remixes) as user_num_remixed, upload_user as user_id
                FROM cc_tbl_uploads
                GROUP BY upload_user
END;

        $users = new CCTable('cc_tbl_user','user_id');
        
        foreach( $sql as $s )
        {
            $rows = CCDatabase::QueryRows($s);
            foreach($rows as $row)
                $users->Update($row);
        }

        CCPage::Prompt(_("Upgraded remix fields"));
    }

    function update_ratings()
    {
        $this->_check_for_field('cc_tbl_uploads','upload_score',      'INT(11) unsigned');
        $this->_check_for_field('cc_tbl_uploads','upload_num_scores', 'INT(11) unsigned');
        $this->_check_for_field('cc_tbl_uploads','upload_rank',       'INT(11) unsigned');

        $this->_check_for_field('cc_tbl_user',   'user_score',        'INT(11) unsigned');
        $this->_check_for_field('cc_tbl_user',   'user_num_scores',   'INT(11) unsigned');
        $this->_check_for_field('cc_tbl_user',   'user_rank',         'INT(11) unsigned');

        $sql =<<<END
            SELECT AVG(ratings_score)   as upload_score, 
                   COUNT(ratings_score) as upload_num_scores, 
                   ratings_upload       as upload_id
               FROM cc_tbl_ratings
               GROUP BY ratings_upload
END;
        $rows = CCDatabase::QueryRows($sql);
        $uploads =& CCUploads::GetTable();

        foreach( $rows as $row )
        {
            $uploads->Update($row);
        }

        $sql = array();

        $sql[] =<<<END
            SELECT AVG(upload_score)   as user_score, 
                   upload_user as user_id
               FROM cc_tbl_uploads
               GROUP BY upload_user
END;

        $sql[] =<<<END
            SELECT SUM(upload_num_scores)   as user_num_scores, 
                   upload_user as user_id
               FROM cc_tbl_uploads
               GROUP BY upload_user
END;

        $users = new CCTable('cc_tbl_user','user_id');
        foreach( $sql as $s )
        {
            $rows = CCDatabase::QueryRows($s);
            foreach( $rows as $row )
                $users->Update($row);
        }

        CCPage::Prompt(_("Ratings info updated"));
    }
}

?>
