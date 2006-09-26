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
class v_3_0f extends CCUpdate
{
    function Update()
    {
        $this->_update_user();
        $this->_update_settings();
    }

    function _update_user()
    {
        $this->_check_for_field('cc_tbl_user', 'user_language', 'varchar(255) NOT NULL default \'\'');

        CCPage::Prompt("User Languages Installed");
    }

    function _update_settings()
    {
        $newtags = 
	    array('site-meta-description' => sprintf(
	                                     _("This is a %s based site."), 
					     CC_APP_NAME ),
	          'site-meta-keywords'    => join(", ", array(CC_APP_NAME, 
		                             _("remix"), _("sharing"), 
					     _("media") ) ) );
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('ttag',$newtags);

        CCPage::Prompt("Added Meta Template Tags");
    }
}

?>
