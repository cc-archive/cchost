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
class v_5_collab extends CCUpdate
{
    function Update()
    {
        $this->_update_collab();
    }

    function _update_collab()
    {
        if( $this->_table_exists('cc_tbl_collab_uploads') )
            return;

        $sql[] = <<<END
CREATE TABLE `cc_tbl_collab_uploads` (
  `collab_upload_collab` int(11) NOT NULL,
  `collab_upload_upload` int(11) NOT NULL,
  `collab_upload_role` varchar(20) NOT NULL
);
END;
        
        $sql[] = <<<END
CREATE TABLE `cc_tbl_collab_users` (
  `collab_user_collab` int(11) NOT NULL,
  `collab_user_user` int(11) NOT NULL,
  `collab_user_role` varchar(100) NOT NULL,
  `collab_user_credit` varchar(100) NOT NULL
);
END;

        $sql[] = <<<END
CREATE TABLE `cc_tbl_collabs` (
  `collab_id` int(11) NOT NULL auto_increment,
  `collab_name` varchar(255) NOT NULL,
  `collab_desc` text NOT NULL,
  `collab_user` int(11) NOT NULL,
  `collab_date` datetime NOT NULL,
  PRIMARY KEY  (`collab_id`)
);
END;

        CCDatabase::Query($sql);
        CCPage::Prompt(_('Collaboration installed'));
    }


}

?>
