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
class v_5_1_button extends CCUpdate
{
    function Update()
    {
        require_once('cchost_lib/cc-page.php');
        $cfg = new CCConfigs();
        $skin_settings = $cfg->GetConfig('skin-settings');
        $page =& CCPage::GetPage();        
        foreach( array('formfields_layout' => 'layouts/form_fields_next.php',
                'gridform_layout' => 'layouts/gridform_tabbed.php',
                'button_style'=> 'layouts/button_browser.php',) as $K => $V )
        {
            $this->_set_all_props($page,$skin_settings,$K,$V);
        }
        $cfg->SaveConfig( 'skin-settings', $skin_settings,'',false );
        CCPage::Prompt( sprintf(_('Installed new buttons, etc. styles') ) );
    }

    function _set_all_props(&$page,&$skin_settings,$key,$value)
    {
        global $CC_GLOBALS;
        $page->vars[$key] =   /* the page is probably already built, so that will be used in cached headers */
        $CC_GLOBALS[$key] =    /* just to be safe, put it in the globals */
        $skin_settings[$key] = $value; /* this we'll write to disk */
    }
}

?>
