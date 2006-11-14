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
class v_3_2g_ti18n extends CCUpdate
{
    function Update()
    {
        $this->_update_topic_i18n();

        CCPage::Prompt(_('Topics i18n installed'));
   }


    function _update_topic_i18n()
    {
        if( $this->_table_exists('cc_tbl_topic_i18n') )
            return;

        $sql[] =<<<END

CREATE TABLE cc_tbl_topic_i18n
    (
      topic_i18n_topic       int(11) unsigned,
      topic_i18n_xlat_topic  int(11) unsigned,
      topic_i18n_language    varchar(100)      NOT NULL default '',
      
      KEY `topic_i18n_topic` (`topic_i18n_topic`)
    )
END;

        CCDatabase::Query($sql);

        $this->_check_for_field('cc_tbl_topics', 'topic_can_xlat', 
                        'INT(1) unsigned NOT NULL default 0');

    }

}

?>
