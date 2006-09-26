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
class v_3_0c extends CCUpdate
{
    function Update()
    {
        $this->_install_forums();
    }

    function _install_forums()
    {
        $sql = array();

        if( !$this->_table_exists('cc_tbl_forums') )
        {
            $sql[] =<<<END

CREATE TABLE cc_tbl_forums
    (
      forum_id              int(6) unsigned  NOT NULL auto_increment,
      forum_post_access     int(4) unsigned  NOT NULL,
      forum_read_access     int(4) unsigned  NOT NULL,
      forum_weight          int(4) unsigned  NOT NULL,
      forum_name            varchar(255) NOT NULL,
      forum_description     varchar(255) NOT NULL,
      forum_group           int(4) NOT NULL,

      PRIMARY KEY forum_id (forum_id)
    )
END;
        }

        if( !$this->_table_exists('cc_tbl_forum_groups') )
        {
            $sql[] =<<<END

CREATE TABLE cc_tbl_forum_groups
    (
      forum_group_id         int(4) unsigned  NOT NULL auto_increment,
      forum_group_name       varchar(255) NOT NULL,
      forum_group_weight     int(4) unsigned  NOT NULL,

      PRIMARY KEY forum_group_id (forum_group_id)
    )
END;
        }

        if( !$this->_table_exists('cc_tbl_forum_threads') )
        {
            $sql[] =<<<END

CREATE TABLE cc_tbl_forum_threads
    (
      forum_thread_id         int(11) unsigned  NOT NULL auto_increment,
      forum_thread_forum      int(6)  unsigned  NOT NULL,
      forum_thread_user       int(11) unsigned  NOT NULL,
      forum_thread_oldest     int(11) unsigned  NOT NULL,
      forum_thread_newest     int(11) unsigned  NOT NULL,
      forum_thread_date       datetime        NOT NULL,
      forum_thread_extra      mediumtext NOT NULL default '',
      forum_thread_sticky     int(2) unsigned  NOT NULL,
      forum_thread_closed     int(2) unsigned  NOT NULL,

      PRIMARY KEY forum_thread_id (forum_thread_id)
    )
END;
        }

        CCDatabase::Query($sql);

        $this->_check_for_field('cc_tbl_topics', 'topic_forum',    'INT(6) unsigned');
        $this->_check_for_field('cc_tbl_topics', 'topic_thread',   'INT(11) unsigned');
        $this->_check_for_field('cc_tbl_user',   'user_num_posts', 'INT(11) unsigned');

        CCPage::Prompt("Forums installed");
    }
}

?>
