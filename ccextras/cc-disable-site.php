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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCDisableSite',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCDisableSite' , 'OnAdminMenu') );

class CCDisableSiteForm extends CCEditConfigForm
{
    function CCDisableSiteForm()
    {
        $this->CCEditConfigForm('config');


        $fields['site-disabled'] =
               array( 'label'       => 'Disable Site',
                       'form_tip'   => 'Check this to disable your site from users',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE);
        $fields['enable-password'] =
               array( 'label'       => 'Admin Password',
                       'form_tip'   => 'This password will allow admins to continue passed disabling.',
                       'formatter'  => 'password',
                       'nomd5'      => true,
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        $fields['disabled-msg'] =
               array( 'label'       => 'Site Disabled Message',
                       'form_tip'   => 'This file is displayed when the admins have temporarily disabled the site.',
                       'value'      => 'disabled-msg.txt',
                       'formatter'  => 'localdir',
                       'flags'      => CCFF_POPULATE  );

        $this->AddFormFields($fields);
    }
}

/**
*
*
*/
class CCDisableSite
{
    function Admin()
    {
        CCPage::SetTitle(_('Disable Site'));
        $form = new CCDisableSiteForm();
        CCPage::AddForm($form->GenerateForm());
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
                'disablesite'   => array( 
                                 'menu_text'  => _('Disable Site'),
                                 'menu_group' => 'configure',
                                 'help' => 'Disable site when doing maintainence.',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 60,
                                 'action' =>  ccl('admin', 'disable')
                                 ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','disable'), array('CCDisableSite','Admin'), CC_ADMIN_ONLY,
                    ccs(__FILE__), '', _('Disable site from non-admin users'), CC_AG_MISC_ADMIN);
    }


}



?>