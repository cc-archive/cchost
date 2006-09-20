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
class v_3_1b extends CCUpdate
{
    function Update()
    {
        $this->_update_opt_pools();
        $this->_update_mail_opts();

        CCPage::Prompt('Sample pool optimizations and Email rules installed');
    }

    function _update_mail_opts()
    {
        $vars['mail_anon'] = CC_MAIL_THROTTLED;
        $vars['mail_uploaders'] = CC_MAIL_THROTTLED;
        $vars['mail_registered'] = CC_MAIL_THROTTLED;
        $vars['mail_throttle'] = 3;
        $vars['mail_to_admin'] = CC_DONT_CARE_LOGGED_IN;
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('config',$vars);
    }

    function _update_opt_pools()
    {
        $this->_check_for_field('cc_tbl_pool_item', 'pool_item_num_remixes',    'INT(6) unsigned');
        $this->_check_for_field('cc_tbl_pool_item', 'pool_item_num_sources',    'INT(6) unsigned');

        $pool_items = new CCPoolItems();
        $sqls = array();

        $sql =<<<END
SELECT COUNT( * ) c, pool_tree_pool_parent id
FROM `cc_tbl_pool_tree`
GROUP BY pool_tree_pool_parent
END;

        $cnt = CCDatabase::QueryRows($sql);
        $c = count($cnt);
        for( $i = 0; $i < $c; $i++ )
        {
            $ID = $cnt[$i]['id'];
            if( empty($ID) )
                continue;
            $sqls[] = "UPDATE cc_tbl_pool_item SET pool_item_num_remixes = {$cnt[$i]['c']}  " .
                        "WHERE pool_item_id = $ID";
        }

        $sql =<<<END
SELECT COUNT( * ) c, pool_tree_pool_parent id
FROM `cc_tbl_pool_tree`
GROUP BY pool_tree_pool_child
END;

        $cnt = CCDatabase::QueryRows($sql);
        $c = count($cnt);
        for( $i = 0; $i < $c; $i++ )
        {
            $ID = $cnt[$i]['id'];
            if( empty($ID) )
                continue;
            $sqls[] = "UPDATE cc_tbl_pool_item SET pool_item_num_sources = {$cnt[$i]['c']}  " .
                        "WHERE pool_item_id = $ID";
        }

        CCDatabase::Query($sqls);

    }

}

?>
