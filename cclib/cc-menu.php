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
* Module for handling menus
*
* @package cchost
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCMenu', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMenu', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMenu', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_TRANSLATE,    array( 'CCMenu', 'OnTranslate'));


/**
* API for handling menus 
*
*/
class CCMenu
{
    /**
    * Gets (and builds if it has to) the current main menu
    * 
    * The menu is built in two phases:
    *<ol><li>
    *Event: {@link CC_EVENT_MAIN_MENU} During this phase menus are
    * built for caching so all data is assumed to be static. Typical handler:
    *<code>
    *function OnBuildMenu()
    *{
    *    $items = array( 
    *        'submitforms' => array(   
    *                             'menu_text'  => _('Submit Files'),
    *                             'menu_group' => 'artist',
    *                             'access'     => CC_MUST_BE_LOGGED_IN,
    *                             'weight'     => 6,
    *                             'action'     => ccp('submit') 
    *                            ), 
    *        );
    *    
    *    CCMenu::AddItems($items);
    *}
    *</code></li>
    *<li>Event: {@link CC_EVENT_PATCH_MENU} This called per session and gives
    * an opportunity for dynamically changing values in the menu. Example:
    *<code>
    *function OnPatchMenu(&$menu)
    *{
    *    $current_user_name = $this->CurrentUserName();
    *    $menu['artist']['action']  =  str_replace('%login_name%',
    *                                              $current_user_name,
    *                                              $menu['artist']['action']);
    *}
    *</code>
    *</li>
    *</ol>
    * If $force is set to true this method will ignore any cached data and 
    * build the latest version of the menu
    *
    * @param boolean $force 
    */
    function GetMenu($force = false)
    {
        static $_menu;
        if( $force || !isset($_menu) )
            $_menu = CCMenu::_build_menu();
        return( $_menu );
    }

    /**
    * Build a 'local' menu of command links
    * 
    * This method invokes an event passing around a structure for the
    * respondants to fill in. The resulting menu is then returned to
    * the caller. As of this writing this only used for the menu
    * that is displayed with an upload.
    *
    * The menu is built in two phases:
    *
    * <ol><li>Event: {@link CC_EVENT_BUILD_UPLOAD_MENU} Use this event
    * to build up the menu filling everything possible that is context-free
    * (that is: you don't know the record the menu is for). Example:
    *<code>
    *function OnBuildUploadMenu(&$menu)
    *{
    *    $menu['editupload'] = 
    *                 array(  'menu_text'  => _('Edit'),
    *                         'weight'     => 100,
    *                         'group_name' => 'owner',
    *                         'id'         => 'editcommand',
    *                         'access'     => CC_DYNAMIC_MENU_ITEM );
    *
    *    $menu['managefiles'] = 
    *                 array(  'menu_text'  => _('Manage Files'),
    *                         'weight'     => 101,
    *                         'group_name' => 'owner',
    *                         'id'         => 'managecommand',
    *                         'access'     => CC_DYNAMIC_MENU_ITEM );
    *}
    *</code>
    *</li>
    *<li>Event: {@link CC_EVENT_UPLOAD_MENU} Use this event to add or
    * modify the menu items given a specific context. Example:
    *<code>
    *function OnUploadMenu(&$menu,&$record)
    *{
    *    $isowner = CCUser::CurrentUser() == $record['user_id'];
    *    $isadmin = CCUser::IsAdmin();
    *
    *    if( $isowner || $isadmin )
    *    {
    *        $menu['editupload']['access']  = CC_MUST_BE_LOGGED_IN;
    *        $menu['editupload']['action']  = ccl('files','edit',
    *                                                $record['user_name'],
    *                                                $record['upload_id']);
    *
    *        $menu['managefiles']['access']  = CC_MUST_BE_LOGGED_IN;
    *        $menu['managefiles']['action']  = ccl('file',
    *                                              'manage', 
    *                                              $record['upload_id']);
    *    }
    *}
    *</code>
    *</li></ol>
    *
    * The two steps are done for future features (caching and 
    * editing local menus)
    *
    * Example of calling this method:
    * <code>
    *$menu = CCMenu::GetLocalMenu( CC_EVENT_UPLOAD_MENU,
    *                              array(&$record),
    *                              CC_EVENT_BUILD_UPLOAD_MENU);
    *
    *$record['local_menu'] = $menu;
    * </code>
    * 
    * @param string $menuname The name of the menu (this is be invoked as an event for display)
    * @param array $args      References to all these will be passed to all event handlers
    * @param string $builder_event The name of the 'builder' event to be invoked before anything else
    */
    function GetLocalMenu($menuname,$args=array(),$builder_event='')
    {
        // Invoke the event....
        
        $allmenuitems = array();
        $r = array( &$allmenuitems );
        if( $builder_event )
        {
            CCEvents::Invoke($builder_event, $r );
        }
        $c = count($args);
        for( $i = 0; $i < $c; $i++ )
            $r[] =& $args[$i];

        CCEvents::Invoke($menuname, $r );

        // sort the results
        
        uasort($allmenuitems ,'cc_weight_sorter');

        // filter the results based on access permissions

        $mask = CCMenu::GetAccessMask();

        $menu = array();
        $count = count($allmenuitems);
        $keys = array_keys($allmenuitems);
        for( $i = 0; $i < $count; $i++ )
        {
            $key = $keys[$i];
            $access = $allmenuitems[$key]['access'];
            if( !($access & CC_DISABLED_MENU_ITEM) && (($access & $mask) != 0) )
                $menu[$key] = $allmenuitems[$key];
        }

        return( $menu );
    }

    /**
    * Occasionally the menu needs to be reset (e.g. a user logs out)
    *
    */
    function Reset()
    {
        CCMenu::_menu_data(true);
        CCMenu::GetMenu(true);
    }

    /**
    * Force the cached url maps and menus to be rebuilt
    *
    * You can invoke by URL: ?ccm=/media/admin/menu/killcache
    */
    function KillCache()
    {
        $configs =& CCConfigs::GetTable();
        $configs->DeleteType('urlmap',CC_GLOBAL_SCOPE);
        CCEvents::GetUrlMap(true);
        CCMenu::Reset();
        CCPage::Prompt("Menu/URL cache has been cleared");
    }

    /**
    * Add items to the main menu for the current virtual config root
    *
    * Typically called during a handler {@link CC_EVENT_BUILD_MENU} event,
    * in which case you do NOT want to set save_now to true.
    *
    * If calling outside a build menu event (e.g. installing a 
    * plug-in) then save_now must be set to 'true' to preserve
    * your changes between sessions.
    *
    * @param array $items Array of menu items
    * @param bool $save_now Writes the menu items to current configs
    */
    function AddItems( $items, $save_now = false )
    {
        global $CC_CFG_ROOT;

        $menu_items =& CCMenu::_menu_items();
        $menu_items = array_merge($menu_items,$items);
        if( $save_now )
        {
            $configs =& CCConfigs::GetTable();

            // we don't want to add items to a config that
            // doesn't have a menu
            if( ($CC_CFG_ROOT != CC_GLOBAL_SCOPE) && 
                !$configs->ScopeHasType( 'menu', $CC_CFG_ROOT ) )
            {
                $menu = $configs->GetConfig('menu', CC_GLOBAL_SCOPE );
                $items = array_merge($menu,$items);
            }

            $configs->SaveConfig( 'menu',   $items,  '',  true);
        }
    }

    /**
    * Add group items to the main menu for the current virtual config root
    *
    * Typically called during a handler CC_EVENT_BUILD_MENU event,
    * in which case you do NOT want to set save_now to true.
    *
    * If calling outside a build menu event (e.g. installing a 
    * plug-in) then save_now must be set to 'true' to preserve
    * your changes between sessions.
    *
    * @param array $items Array of group
    * @param bool $save_now Writes the menu items to current configs
    */
    function AddGroups($items,$save_now = false)
    {
        $groups =& CCMenu::_menu_groups();
        $groups = array_merge($groups,$items);
        if( $save_now )
        {
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig( 'groups',   $items,  '',  true);
        }
    }

    /**
    * Removed a menu item from current virtual config root
    * 
    * @param string $item_name The name of the menu item to remove
    * @param bool   $permanent Write this change to the config
    * @return bool $removed true = menuitem was found and removed, false = menu item not found
    */
    function RemoveItem( $item_name, $permanent = true )
    {
        $configs =& CCConfigs::GetTable();
        $menu = $configs->GetConfig( 'menu ');
        if( !empty($menu[$item_name]) )
        {
            unset($menu[$item_name]);
            if( $permanent )
                $configs->SaveConfig( 'menu', $menu, '', false );
            return(true);
        }
        return(false);
    }

    /**
    * Returns a mask of bits that represents the current user's access level
    *
    * @returns integer $mask Mask of CC_ bits (e.g. CC_MUST_BE_LOGGED_IN)
    */
    function GetAccessMask()
    {
        if( CCUser::IsLoggedIn() )
        {
            $mask = CC_MUST_BE_LOGGED_IN | CC_DONT_CARE_LOGGED_IN;

            if( CCUser::IsAdmin() )
                $mask |= CC_ADMIN_ONLY;

            if( CCUser::IsSuper() )
                $mask |= CC_SUPER_ONLY;
        }
        else
        {
            $mask = CC_ONLY_NOT_LOGGED_IN | CC_DONT_CARE_LOGGED_IN;
        }
        return( $mask );
    }

    /**
    * Internal: go out there and build the main menu
    * @access private
    */
    function _build_menu()
    {
        $mask        =  CCMenu::GetAccessMask();
        $groups      =  CCMenu::_menu_groups();
        $menu_items  =& CCMenu::_menu_items(); 

        foreach( $menu_items as $name => $item )
        {
            if( ($item['access'] & $mask) != 0 )
            {
                if( strpos($item['action'],'http://',0) === false )
                    $item['action'] = ccl($item['action']);
                $groups[$item['menu_group']]['menu_items'][] = $item;
            }
        }

        $menu = array();
        foreach( $groups as $groupname => $group  )
        {
            if( !empty($group['menu_items']) )
            {
                usort( $group['menu_items'], 'cc_weight_sorter' );
                $group['group_id'] = $groupname . "_group";
                $menu[] = $group;
            }
        }

        return( $menu );
    }

    /**
    * Internal: get the menu from the cache and apply dynamic pathes to it
    * @access private
    */
    function &_menu_data($force = false, $action = CC_MENU_DISPLAY )
    {
        static $_menu_data;
        if( $force || !isset($_menu_data) )
        {
            $configs =& CCConfigs::GetTable();
            $_menu_data['items']  = $configs->GetConfig('menu');
            $_menu_data['groups'] = $configs->GetConfig('groups');

            if( empty($_menu_data['items']) )
            {
                //
                // ::::: Weirdass side effect warning :::::
                //
                // event handlers responding to this event will
                // fill the _menu_data var through calls to
                // CCMenu::AddMenuItem()
                //
                CCEvents::Invoke(CC_EVENT_MAIN_MENU, array( $action ));
                uasort($_menu_data['groups'],'cc_weight_sorter');
                $configs->SaveConfig( 'menu',   $_menu_data['items'],  '',  false);
                $configs->SaveConfig( 'groups', $_menu_data['groups'], '',  false);
            }

            CCEvents::Invoke(CC_EVENT_PATCH_MENU, array( &$_menu_data['items'] ));

            $links_menu = $configs->GetConfig('links_menu'); 
            if( !empty($links_menu) )
                $_menu_data['items'] += $links_menu;
        }

        return( $_menu_data );
    }

    /**
    * Internal goody
    * @access private
    */
    function & _menu_items()
    {
        $data =& CCMenu::_menu_data();
        $items =& $data['items'];
        return($items);
    }

    /**
    * Internal goody
    * @access private
    */
    function & _menu_groups()
    {
        $data =& CCMenu::_menu_data();
        $groups =& $data['groups'];
        return($groups);
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
            return;

        $items += array( 
            'menu'   => array( 'menu_text'  => 'Menus',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 60,
                             'help' => 'Edit the menus',
                             'action' =>  ccl('admin','menu')
                             ),
            'groups' => array( 'menu_text'  => 'Menu Groups',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'help'  => 'Edit the menu groups',
                             'weight' => 61,
                             'action' =>  ccl('admin','menugroup')
                             ),
            );
    }

    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $groups = array(
                    'extra1' => array( 'group_name' => 'Extra1 (rename me)',
                                      'weight'    => 4 ),
                    'extra2' => array( 'group_name' => 'Extra2 (rename me)',
                                      'weight'    => 4 ),
                    'extra3' => array( 'group_name' => 'Extra3 (rename me)',
                                      'weight'    => 4 ),
                    );

        CCMenu::AddGroups($groups);

    }

    /**
    * Add menu items to the main menu (experimental)
    *
    * Maps to URL ?ccm=/media/admin/menu/additems[/numitems]
    * 
    * @param integer $num Number of items to add
    */
    function AddMenuItems($num=1)
    {
        $num = empty($num) ? 1 : CCUtil::StripText($num);
        for( $i = 0; $i < $num; $i++ )
        {
            $rand = rand();
            $items['additem' . $rand] = 
                array( 'menu_text'  => 'Extra Item ' . $rand ,
                    'menu_group' => 'artist',
                    'weight' => 1,
                    'action' =>  ccp('replace','me'),
                    'access' => CC_ADMIN_ONLY
                    );
        }

        CCMenu::AddItems($items,true);
        CCUtil::SendBrowserTo(ccl('admin','menu'));
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/menu',            array('CCMenu', 'Admin'),        
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _('Display admin menu form'), CC_AG_CONFIG );

        CCEvents::MapUrl( 'admin/menu/killcache',  array('CCMenu', 'KillCache'),         
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', 
            _('Remove menu/url cache'), CC_AG_CONFIG );

        CCEvents::MapUrl( 'admin/menu/additems',   array('CCMenu', 'AddMenuItems'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _('Add menu items'), CC_AG_CONFIG );

        CCEvents::MapUrl( 'admin/menugroup',       array('CCMenu', 'AdminGroup'),    
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _('Display admin menu groups form'), CC_AG_CONFIG  );
    }

    /**
    * Deletes the menu for the current configuration (DESTROYS USRS'S CHANGES!)
    *
    * This will trigger a re-build the next time somebody requests a menu.
    */
    function RevertToParent()
    {
        global $CC_CFG_ROOT;
        $configs =& CCConfigs::GetTable();
        $configs->DeleteType('menu',$CC_CFG_ROOT);
        $this->Reset();
        CCPage::Prompt("Menus have been reset for <b>$CC_CFG_ROOT</b>");
        CCPage::SetTitle("Reset Menus");
    }

    /**
    * Displays and processes a form that allows admins to edit the main menu
    */
    function Admin($revert='')
    {
        require_once('cclib/cc-menu-admin.inc');
        $menu_admin = new CCMenuAdmin();
        $menu_admin->Admin($this,$revert);
    }

    /**
    * Displays and processes a form that allows admins to edit the main menu's groups
    */
    function AdminGroup()
    {
        require_once('cclib/cc-menu-admin.inc');
        $menu_admin = new CCMenuAdmin();
        $menu_admin->AdminGroup($this);
    }

    /**
    * Event handler for {@link CC_EVENT_TRANSLATE}
    */
    function OnTranslate()
    {
        require_once('cclib/cc-menu-admin.inc');
        $menu_admin = new CCMenuAdmin();
        $menu_admin->OnTranslate($this);
    }

}

function cc_weight_sorter($a, $b)
{
   return( $a['weight'] > $b['weight'] ? 1 : -1 );
}

function cc_sort_user_menu($a, $b)
{
    if( $a['menu_group'] == $b['menu_group'] )
        return( cc_weight_sorter($a,$b) );
    return( cc_weight_sorter($a['menu_group'],$b['menu_group']) );
}

function cc_strip_groups(&$i,$k)
{
    $i['group_name']   = CCUtil::StripText($i['group_name']);
    $i['weight'] = CCUtil::StripText($i['weight']);
}
?>