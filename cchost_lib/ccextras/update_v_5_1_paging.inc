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
class v_5_1_paging extends CCUpdate
{
    function Update()
    {
        global $CC_GLOBALS;
        $cfg = new CCConfigs();
        $skin_settings = $cfg->GetConfig('skin-settings');
        $CC_GLOBALS['paging_style'] = $skin_settings['paging_style'] = 'ccskins/shared/layouts/paging_basic.php';
        $cfg->SaveConfig( 'skin-settings', $skin_settings,'',false );
        CCPage::Prompt( sprintf(_('Installed new paging styles') ) );
    }
}

?>
