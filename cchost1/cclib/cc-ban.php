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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCBan',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCBan',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCBan',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCBan',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCBan',  'OnGetConfigFields') );

/**
* Ban API used by admins to moderate uploads
*
*/
class CCBan
{
    /**
    * Handles amdin/ban URLs
    * 
    * Toggles the ban/unban flag on an upload record.
    *
    * @param integer $upload_id File id to ban
    */
    function Ban($upload_id)
    {
        if( !CCUser::IsAdmin() )
            return;

        $uploads =& CCUploads::GetTable();
        $row = $uploads->QueryKeyRow($upload_id);
        $new_ban_flag = $row['upload_banned'] ^= 1;
        $args['upload_id'] = $upload_id;
        $args['upload_banned'] = $new_ban_flag;
        $uploads->Update($args);
        CCPage::SetTitle("Banning upload: '" . $row['upload_name'] . "'");
        $yn = array( "no longer banned",
                     "banned" );
        CCPage::Prompt("The upload has been marked as " . $yn[ $new_ban_flag ] );
    }
    
    /**
    * Callback for GET_CONFIG_FIELDS event
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
            $fields['ban-message'] =
               array(  'label'      => 'Ban Message',
                       'form_tip'   => 'Message displayed to owner of a banned upload',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE);
        }
    }

    /**
    * Event handler for CC_EVENT_BUILD_UPLOAD_MENU
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['ban'] = 
                     array(  'menu_text'  => 'Ban',
                             'weight'     => 1001,
                             'group_name' => 'admin',
                             'id'         => 'bancommand',
                             'access'     => CC_ADMIN_ONLY );
    }

    /**
    * Event handler for CC_EVENT_UPLOAD_MENU
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu
    */
    function OnUploadMenu(&$menu,&$record)
    {
        $isowner = CCUser::CurrentUser() == $record['user_id'];

        if( CCUser::IsAdmin() )
        {
            if( $record['upload_banned'] > 0 )
                $menu['ban']['menu_text'] = 'UnBan';

            $menu['ban']['action']  = ccl('admin','ban', $record['upload_id']);
        }
    }
    
    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow( &$record )
    {
        if( !empty($record['upload_banned']) )
        {
            global $CC_GLOBALS;

            $record['banned_message'] =  $CC_GLOBALS['ban-message'];
            $record['file_macros'][] = 'upload_banned';
        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/ban',   array('CCBan','Ban'),  CC_ADMIN_ONLY );
    }
}





?>