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
class v_2_1a extends CCUpdate
{
    function Update()
    {
        $this->_install_notifications();
    }

    function _install_notifications()
    {
        if( $this->_table_exists('cc_tbl_notifications') )
            return;

        $sql[] =<<<END

CREATE TABLE cc_tbl_notifications
    (
      notify_id         int(11) unsigned  NOT NULL auto_increment,
      notify_user       int(11) unsigned  NOT NULL,
      notify_other_user int(11) unsigned  NOT NULL,
      notify_mask       int(4)  unsigned  NOT NULL,

      PRIMARY KEY notify_id (notify_id)
    )
END;

        CCDatabase::Query($sql);

        CCPage::Prompt("Notifications installed");
    }
}

?>