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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCFileMan',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCFileMan' , 'OnAdminMenu') );



/**
*
*
*/
class CCFileMan
{
    function Add($subdir='')
    {
        include('ccextras/cc-fileman.inc');
        $fileman = new CCFileManAdmin();
        $fileman->Add($subdir);
    }

    function Manage($tab='')
    {
        include('ccextras/cc-fileman.inc');
        $fileman = new CCFileManAdmin();
        $fileman->Manage($tab);
    }

    function Files()
    {
        include('ccextras/cc-fileman.inc');
        $fileman = new CCFileManAdmin();
        $fileman->Files();
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','files','manage'), array('CCFileMan','Manage'), CC_ADMIN_ONLY);
        CCEvents::MapUrl( ccp('admin','files'),          array('CCFileMan','Files'),  CC_ADMIN_ONLY);
        CCEvents::MapUrl( ccp('admin','addfiles'),       array('CCFileMan','Add'),    CC_ADMIN_ONLY);
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            global $CC_GLOBALS;

            $items += array(
                'fileman'   => array( 
                                 'menu_text'  => 'Manage files',
                                 'menu_group' => 'configure',
                                 'help' => 'Manage files in the ' . $CC_GLOBALS['files-root'] . ' directory',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 60,
                                 'action' =>  ccl('admin','files','manage')
                                 ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['files-root'] =
               array(  'label'      => 'Admin files',
                       'form_tip'   => 'This is where the viewfile/ url looks for .xml files',
                       'value'      => 0,
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}



?>