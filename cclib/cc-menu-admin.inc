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
* Module for admin UI for menus
*
* This module is <i>not</i> automatically included.
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

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
                $help = "You can revert to factory defaults menu by clicking <a href=\"$revert_link\"><b><u>here</u></b></a> ".
                        "(This will erase the customization you've done to the main menu, but not affect any virtual ccHosts" .
                        " that you have customized menus for.)";
            }
            else
            {
                $help = "You can revert to your main configuration menu by clicking <a href=\"$revert_link\"><b><u>here</u></b></a> ".
                $extra = "(This will erase the customization you've done to the <b>$CC_CFG_ROOT</b> menu.)";
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

        $addurl = ccl('admin', 'menu', 'additems', '3');
        $help .= "<br />To add items click <a href=\"$addurl\"><b><u>here</u></b></a> (This will not save any of the work you've done below.)";

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
* API for handling menus of links
*
*/
class CCMenuAdmin
{
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
    * Displays and processes a form that allows admins to edit the main menu
    */
    function Admin($menu_api, $revert='')
    {
        if( !empty($revert) && ($revert == 'revert') )
        {
            $menu_api->RevertToParent();
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
        
            $menu_api->Reset();
        }            

    }

    /**
    * Displays and processes a form that allows admins to edit the main menu's groups
    */
    function AdminGroup($menu_api)
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

        $menu_api->Reset();
    }

    function OnTranslate($menu_api)
    {
        $configs =& CCConfigs::GetTable();
        $roots = $configs->GetConfigRoots();
        foreach( $roots as $aroot )
        {
            $root = $aroot['config_scope'];
            $menu = $configs->GetConfig('menu',$root);
            $new_menu = array();
            foreach( $menu as $key => $item )
            {
                cc_lang_translate($item,'menu_text');
                $new_menu[$key] = $item;
            }
            $configs->SaveConfig('menu',$new_menu,$root,false);

            $groups = $configs->GetConfig('groups',$root);
            $new_groups = array();
            foreach( $groups as $key => $group )
            {
                cc_lang_translate($group,'group_name');
                $new_groups[$key] = $group;
            }
            $configs->SaveConfig('groups',$new_groups,$root,false);
        }
    }
}

?>