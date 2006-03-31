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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCMenu', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMenu', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMenu', 'OnMapUrls'));

/**
* Admin form for editing menus
*/
class CCAdminMenuForm extends CCGridForm
{
    /**
    * Constructor
    */
    function CCAdminMenuForm($menu,$groups)
    {
        $this->CCGridForm();

        global $CC_CFG_ROOT;

        $configs =& CCConfigs::GetTable();
        if( $configs->ScopeHasType('menu',$CC_CFG_ROOT) )
        {
            $revert_link = ccl('admin','menu','revert');

            if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
            {
                $help = "You can revert to factory defaults menu by clicking <a href=\"$revert_link\">here</a> ".
                        "(This is will erase the custumization you've done to the main menu, but not affect any virtual ccHosts" .
                        " that you have customized menus for.)";
            }
            else
            {
                $help = "You can revert to your main configuration menu by clicking <a href=\"$revert_link\">here</a> ".
                $extra = "(This is will erase the custumization you've done to the <b>$CC_CFG_ROOT</b> menu.)";
            }
        }
        else
        {
            if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
            {
                $help = "You're now editing the menu for the main configuration ('main')".
                        "(This menu will be used by any virtual ccHost that you haven't customized.)";
            }
            else
            {
                $help = "You're now editing the menu for the <b>$CC_CFG_ROOT</b> virtual CCHost. "
                       . "Any changes here will only be reflected in <b>$CC_CFG_ROOT</b>.";
            }
        }


        $this->SetHelpText($help);

        uasort($menu,'cc_sort_user_menu');
        uasort($groups,'cc_weight_sorter');

        $heads = array( "Menu Text", "Group", "Weight", "Action", "Access" );
        $this->SetColumnHeader($heads);

        $group_select = array();
        foreach( $groups as $groupname => $groupinfo )
            $group_select[$groupname] = $groupinfo['group_name'];

        foreach( $menu as $keyname => $menuitem )
        {
            $a = array(
                  array(
                    'element_name'  => "mi[$keyname][menu_text]",
                    'value'      => $menuitem['menu_text'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][menu_group]",
                    'value'      => $menuitem['menu_group'],
                    'formatter'  => 'select',
                    'options'    => &$group_select,
                    'flags'      => CCFF_NONE ),
                  array(
                    'element_name'  => "mi[$keyname][weight]",
                    'value'      => $menuitem['weight'],
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][action]",
                    'value'      => htmlspecialchars($menuitem['action']),
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][access]",
                    'value'      => $menuitem['access'],
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only",
                                           CC_DISABLED_MENU_ITEM  => "Hide" ),
                    'flags'      => CCFF_NONE ),
                );

            $this->AddGridRow( $keyname, $a );
            /*
            $this->SetHiddenField( "mi[$keyname][action]", 
                                htmlspecialchars(urlencode($menuitem['action'])) );
            */
        }

        $this->SetSubmitText('Submit Menu Changes');
    }
}

/**
* Admin form for editing Menu groups
*/
class CCAdminMenuGroupsForm extends CCGridForm
{
    /**
    * Constructor
    *
    */
    function CCAdminMenuGroupsForm($groups)
    {
        $this->CCGridForm();

        $heads = array( "Group Name", "Weight" );
        $this->SetColumnHeader($heads);

        foreach( $groups as $keyname => $group )
        {
            $a = array(
                  array(
                    'element_name'  => "grp[$keyname][group_name]",
                    'value'      => $group['group_name'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][weight]",
                    'value'      => $group['weight'],
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short',
                    'flags'      => CCFF_REQUIRED ),
                );

            $this->AddGridRow( $keyname, $a );
        }
        
        $this->SetSubmitText('Submit Group Changes');
    }
}

/**
* Admin form for editing the 'Links' group of menu items
*/
class CCEditLinksForm extends CCGridForm
{
    /**
    * Constructor
    *
    */
    function CCEditLinksForm($menu)
    {
        $this->CCGridForm();

        if( !empty($menu) )
        {
            $heads = array( "Delete", "Menu Text", "Action", "Weight", "Access" );
            $this->SetColumnHeader($heads);

            foreach( $menu as $keyname => $menuitem )
            {
                $a = array(
                      array(
                        'element_name'  => "mi[$keyname][delete]",
                        'formatter'  => 'checkbox',
                        'flags'      => CCFF_NONE ),
                      array(
                        'element_name'  => "mi[$keyname][menu_text]",
                        'class'      => 'cc_form_input_short',
                        'value'      => $menuitem['menu_text'],
                        'formatter'  => 'textedit',
                        'flags'      => CCFF_REQUIRED ),
                      array(
                        'element_name'  => "mi[$keyname][action]",
                        'value'      => htmlspecialchars($menuitem['action']),
                        'formatter'  => 'textedit',
                        'flags'      => CCFF_REQUIRED ),
                      array(
                        'element_name'  => "mi[$keyname][weight]",
                        'value'      => $menuitem['weight'],
                        'formatter'  => 'textedit',
                        'class'      => 'cc_form_input_short',
                        'flags'      => CCFF_REQUIRED ),
                      array(
                        'element_name'  => "mi[$keyname][access]",
                        'value'      => $menuitem['access'],
                        'formatter'  => 'select',
                        'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                               CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                               CC_DONT_CARE_LOGGED_IN => "Everyone",
                                               CC_ADMIN_ONLY          => "Administrators only" ),
                        'flags'      => CCFF_NONE ),
                    );

                $this->AddGridRow( $keyname, $a );
            }
        }
        else
        {
            $this->SetHelpText('There are no links to edit yet, use the Add Links menu item');
            $this->SetSubmitText('');
        }
    }
}

/**
* Admin form for adding an item to the 'Links' menu group
*
*/
class CCAddLinkForm extends CCForm
{
    /**
    * Constructor
    *
    */
    function CCAddLinkForm()
    {
        $this->CCForm();

        $fields = array(
            'menu_text' => array(
                'label'  => 'Text',
                 'flags'     => CCFF_REQUIRED,
                 'formatter' => 'textedit' ),
            'action'     => array(
                 'label'  => 'URL',
                 'flags'      => CCFF_REQUIRED,
                 'formatter' => 'textedit' ),
            'weight'     => array(
                 'label'  => 'Weight',
                 'flags'      => CCFF_REQUIRED,
                 'formatter'  => 'textedit',
                 'class'      => 'cc_form_input_short' ),
             'access' => array(
                 'label' => 'Permissions',
                 'flags'    => CCFF_NONE,
                 'value'      => CC_DONT_CARE_LOGGED_IN,
                 'formatter'  => 'select',
                 'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                        CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                        CC_DONT_CARE_LOGGED_IN => "Everyone",
                                        CC_ADMIN_ONLY          => "Administrators only" ) ),
             );

        $this->AddFormFields($fields);
    }
}

/**
* API for handling menus of links
*
*/
class CCMenu
{
    /**
    * Gets (and builds if it has to) the current main menu
    * 
    * @param bool $force If true this method will ignore any cached data and build the latest version of the menu
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
    * the caller.
    *
    * NOTE: as of this writing there is no way for the admin 
    * to edit the contents of a local menu (without messing with
    * code). This code represents a halfway point to an implementation
    * of that where local menus are built using one event, and displayed
    * using another. Sometime in the future the 'build' step will be
    * offlined somewhere else so some kind of editing UI can be 
    * intejected into the process and the results preserved across
    * sessions. For now, however, both build and display steps are
    * done here, as one.
    *
    * The idea behind the above proposal is to have a menu
    * builder event that actually just retieves the static 
    * portion of the menu from a cache.
    *
    * In the course of the build step a menu structure array is 
    * passed around and the event handlers fill it in similar to
    * this:
    * 
    * <code>

    function OnBuildUploadMenu(&$menu)
    {
        $menu['deleteupload'] = 
                     array(  'menu_text'  => 'Delete',
                             'weight'     => 11,
                             'id'         => 'deletecommand',
                             'access'     => CC_DYNAMIC_MENU_ITEM );

    * </code>
    * 
    * Note there is a missing [action] field in the second item because it is marked as DYNAMIC. It is
    * filled in by the display menu event handler
    * 
    * <code>

    function OnUploadMenu(&$menu,&$record)
    {
        $isowner = CCUser::CurrentUser() == $record['user_id'];

        if( !empty($record['upload_banned']) )
        {
            if( $isowner || CCUser::IsAdmin() )
            {
                $menu['deleteupload']['action'] = ccl( 'files', 'delete', $record['upload_id']);
                $menu['deleteupload']['access']  |= CC_MUST_BE_LOGGED_IN;
            }
            else
            {
                $menu['deleteupload']['access'] = CC_DISABLED_MENU_ITEM;
            }

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

    function KillCache()
    {
        // Should allow anybody to do this so plug in developers
        // can tell people to do it.

        // Later: that's lame. Plug in writers should write
        //        install scripts that check for their menu
        //        items and nuke the cache themselves

        // if( !CCUser::Admin() )
        //    return;
        CCEvents::GetUrlMap(true);
        CCMenu::Reset();
        CCPage::Prompt("Menu cache has been cleared");
    }

    /**
    * Checks for the existance of a menu item
    * 
    * Allows 3rd party plug in writers to see if their
    * menu items are in the current configuration.
    * 
    * @param string $menu_item_name Name of the menu item
    * @returns bool $yes true if menu item is in the current configuration
    */
    function ItemExists($menu_item_name)
    {
        $menu_items =& CCMenu::_menu_items();
        return( array_key_exists($menu_item_name,$menu_items) );
    }

    /**
    * Checks for the existance of a menu group
    * 
    * Allows 3rd party plug in writers to see if their
    * menu groups are in the current configuration.
    * 
    * @param string $group_name Name of the group (internal name)
    * @returns bool $yes true if menu item is in the current configuration
    */
    function GroupExists($group_name)
    {
        $groups =& CCMenu::_menu_groups();
        return( array_key_exists($group_name,$groups) );
    }

    /**
    * Add items to the main menu for the current virtual config root
    *
    * Typically called during a handler CC_EVENT_BUILD_MENU event,
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
        $menu_items =& CCMenu::_menu_items();
        $menu_items = array_merge($menu_items,$items);
        if( $save_now )
        {
            $configs =& CCConfigs::GetTable();
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
        }
        else
        {
            $mask = CC_ONLY_NOT_LOGGED_IN | CC_DONT_CARE_LOGGED_IN;
        }
        return( $mask );
    }

    /**
    * Internal: go out there and build the main menu
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
    */
    function & _menu_items()
    {
        $data =& CCMenu::_menu_data();
        $items =& $data['items'];
        return($items);
    }

    /**
    * Internal goody
    */
    function & _menu_groups()
    {
        $data =& CCMenu::_menu_data();
        $groups =& $data['groups'];
        return($groups);
    }

    /**
    * Event handler for building admin menus
    *
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
/*
            'addlink' => array( 'menu_text'  => 'Add Link',
                             'menu_group' => 'links',
                             'access' => CC_ADMIN_ONLY,
                             'help' => 'Add a menu item to the "Links" menu group',
                             'weight' => 62,
                             'action' =>  ccl( 'admin','addlink' )
                             ),
            'editlinks' => array( 'menu_text'  => 'Manage Links',
                             'menu_group' => 'links',
                             'help' => 'Edit the current menu items in the "Links" menu group',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 63,
                             'action' =>  ccl( 'admin','editlinks' )
                             ),
*/
            );
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $groups = array(
                    'links' => array( 'group_name' => 'Links',
                                      'weight'    => 3 ),
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
    * Displays and processes a form that allows admins to edit the Links group menu item
    */
    function EditLinks()
    {
        CCPage::SetTitle("Edit Custom Link Items");

        $configs =& CCConfigs::GetTable();
        $menu_items  = $configs->GetConfig('links_menu');

        $form = new CCEditLinksForm($menu_items);
        if( empty($_POST['editlinks']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $menu_items = $_POST['mi'];
            CCUtil::StripSlash($menu_items);
            $copy = array();
            foreach( $menu_items as $name => $edits )
            {
                if( array_key_exists('delete',$menu_items[$name]) )
                {
                    continue;
                }

                $copy[$name]['menu_text']  = CCUtil::StripText($edits['menu_text']);
                $copy[$name]['menu_group'] = 'links';
                $copy[$name]['weight']     = CCUtil::StripText($edits['weight']);
                $copy[$name]['access']     = CCUtil::StripText($edits['access']) ;
                $copy[$name]['action']     = htmlspecialchars(urldecode($edits['action'])) ;
            }

            $configs->SaveConfig('links_menu',$copy,'',false);

            CCPage::Prompt("Menu changes have been saved");

            $this->Reset();
        }
    }

    /**
    * Displays and processes a form that allows admins to add Links group menu items
    */
    function AddLink()
    { 
        CCPage::SetTitle("Add Menu Link Item");
        $form = new CCAddLinkForm();
        if( empty($_POST['addlink']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($newitem);
            CCMenu::AddLinkNoUI($newitem);
            CCPage::Prompt("Menu changes have been saved");
            $this->Reset();
        }
    }


    /**
    * Add a menu item to the 'Links' menu
    *
    * Examples:
    *
    * <code>
    
// Add an external link

$menu_item = array( 'menu_text' => 'Hotmail',
                    'action'    => 'http://hotmail.com', // juse use full URL
                    'wieght'    => 50,
                    'access'    => CC_DONT_CARE_LOGGED_IN );

CCMenu::AddLinkNoUI($menu_item);

// Add a link to an internal url

$menu_item = array( 'menu_text' => 'Submit a Podcast',
                    'action'    => ccl('podcast','submit'), // use ccl() macro
                    'wieght'    => 50,
                    'access'    => CC_MUST_BE_LOGGED_IN );

CCMenu::AddLinkNoUI($menu_item);
    
    * 
    * </code>
    *
    * @param array $newitem Array containing meta data about the menu item
    */
    function AddLinkNoUI($newitem)
    {
        $configs =& CCConfigs::GetTable();
        $menu_items  = $configs->GetConfig('links_menu');

        // find a unique name for the new item
        $i = 0;
        while( array_key_exists( 'link' . $i, $menu_items ) )
            $i++;

        $newitem['menu_group'] = 'links';
        $menu_items['link' . $i] = $newitem;
        $configs->SaveConfig('links_menu',$menu_items,'',false);
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/menu',            array('CCMenu', 'Admin'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/menu/killcache',  array('CCMenu', 'KillCache'),  CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'admin/menugroup',       array('CCMenu', 'AdminGroup'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/addlink',         array('CCMenu', 'AddLink'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/editlinks',       array('CCMenu', 'EditLinks'),  CC_ADMIN_ONLY );
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
        if( !empty($revert) && ($revert == 'revert') )
        {
            $this->RevertToParent();
            return;
        }

        CCPage::SetTitle("Edit Menus");

        $configs =& CCConfigs::GetTable();

        if( empty($_POST['adminmenu']) )
        {
            $groups      =  $configs->GetConfig('groups');
            $menu_items  =  $configs->GetConfig('menu');
            $form = new CCAdminMenuForm($menu_items,$groups);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $menu_items = $_POST['mi'];
            CCUtil::StripSlash($menu_items);
            $copy = array();
            foreach( $menu_items as $name => $edits )
            {
                $copy[$name]['menu_text']  = CCUtil::StripText($edits['menu_text']);
                $copy[$name]['menu_group'] = CCUtil::StripText($edits['menu_group']);
                $copy[$name]['weight']     = CCUtil::StripText($edits['weight']);
                $copy[$name]['access']     = CCUtil::StripText($edits['access']) ;
                $copy[$name]['action']     = htmlspecialchars(urldecode($edits['action'])) ;
            }

            $configs->SaveConfig( 'menu', $copy, '', false);

            CCPage::Prompt("Menu changes have been saved");
        
            $this->Reset();
        }            

    }

    /**
    * Displays and processes a form that allows admins to edit the main menu's groups
    */
    function AdminGroup()
    {
        CCPage::SetTitle("Edit Menu Groups");

        $configs =& CCConfigs::GetTable();

        if( empty($_POST['adminmenugroups']) )
        {
            $groups  =  $configs->GetConfig('groups');
            $form = new CCAdminMenuGroupsForm($groups);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $groups = $_POST['grp'];
            array_walk($groups,'cc_strip_groups');
            $configs->SaveConfig('groups',$groups,'',false);
            CCPage::Prompt("Menu group changes have been saved");
        }            

        $this->Reset();

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