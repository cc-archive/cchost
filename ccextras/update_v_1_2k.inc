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
class v_1_2k extends CCUpdate
{
    function Update()
    {
        $this->_install_reviews();
    }

    function _install_reviews()
    {
        if( $this->_table_exists('cc_tbl_topics') )
            return;

        $sql[] =<<<END

CREATE TABLE cc_tbl_topics
    (
      topic_id         int(11) unsigned  NOT NULL auto_increment,
      topic_upload     int(11) unsigned  NOT NULL,
      topic_user       int(11) unsigned  NOT NULL,
      topic_views      int(11) unsigned  NOT NULL,
      topic_type       varchar(100)      NOT NULL default '',
      topic_date       datetime          NOT NULL,
      topic_edited     datetime          NOT NULL,
      topic_deleted    int(2) unsigned   NOT NULL,

      topic_name       mediumtext       NOT NULL default '',
      topic_text       mediumtext       NOT NULL default '',
      topic_tags       mediumtext       NOT NULL default '',

      topic_forum      int(6) unsigned  NOT NULL,
      topic_thread     int(11) unsigned  NOT NULL,

      PRIMARY KEY topic_id (topic_id)
    )
END;

        $sql[] =<<<END
CREATE TABLE cc_tbl_topic_tree
    (
      topic_tree_id       int(11) unsigned  NOT NULL auto_increment,
      topic_tree_parent   int(11) unsigned  NOT NULL,
      topic_tree_child    int(11) unsigned  NOT NULL,

      PRIMARY KEY topic_tree_id (topic_tree_id)
    )
END;

        CCDatabase::Query($sql);

        $this->_check_for_field('cc_tbl_user','user_num_reviews',  'INT(7) unsigned');
        $this->_check_for_field('cc_tbl_user','user_num_reviewed', 'INT(7) unsigned');

        $this->_do_phpbb_migrate();

        CCPage::Prompt("Reviews installed");
        CCMenu::KillCache();
    }

    function _do_phpbb_migrate()
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;
    }
}

?>