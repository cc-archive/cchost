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
* @subpackage lang
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCLanguageEditorAPI', 'OnMapUrls' ));

class CCLanguageEditorAPI
{
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','terms'),  
                            array( 'CCLanguageEditor', 'Language'), CC_ADMIN_ONLY, 
                            'ccextras/cc-lang-editor.inc');

        CCEvents::MapUrl( ccp('admin','language','edit'),  
                            array( 'CCLanguageEditor', 'EditString'), CC_ADMIN_ONLY, 
                            'ccextras/cc-lang-editor.inc');

        CCEvents::MapUrl( ccp('admin','language','getstring'),  
                            array( 'CCLanguageEditor', 'GetString'), CC_ADMIN_ONLY, 
                            'ccextras/cc-lang-editor.inc');

        CCEvents::MapUrl( ccp('admin','language','translate'),  
                            array( 'CCLanguageEditor', 'Translate'), CC_ADMIN_ONLY, 
                            'ccextras/cc-lang-editor.inc'); 
    }

}


?>