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
* @subpackage extras
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


class CCDefaultUserTab
{
    function SetDefaultUserTab()
    {
        require_once('cclib/cc-form.php');
        CCPage::SetTitle(_('Set Default User Tab'));

        $tabs['uploads'] = 'Uploads';
        $tabs['profile'] = 'Profile';
        $record = array();
        CCEvents::Invoke( CC_EVENT_USER_PROFILE_TABS, array( &$tabs, &$record ) );

        $form = new CCGenericForm();
        $fields = array(
            'settab' => array(
                    'label' => _('Default User Tab'),
                    'form_tip' => _('Setting this will determine which tab is the default when visiting an artist\'s page'),
                    'formatter' => 'select',
                    'options' => $tabs,
                    'flags' => CCFF_NONE
            ) );

        $form->AddFormFields($fields);
        
        if( empty($_POST['generic']) )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            cc_setcookie('default_user_tab',$_POST['settab'],time()+60*60*24*30);
            CCPage::Prompt(_('Default user tab is now set'));
        }

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('config','setdefusertab'),  array( 'CCDefaultUserTab', 'SetDefaultUserTab'), 
                          CC_MUST_BE_LOGGED_IN, ccs(__FILE__) );
    }
}

?>
