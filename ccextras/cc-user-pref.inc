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


class CCUserPreferences
{
    function SetPreferences()
    {
        global $CC_GLOBALS;

        require_once('cclib/cc-form.php');
        CCPage::SetTitle('str_pref_title');

        $form = new CCGenericForm();

        /* default user tab */

        $tabs['uploads'] = 'str_uploads';
        $tabs['profile'] = 'str_profile';
        $record = array();
        CCEvents::Invoke( CC_EVENT_USER_PROFILE_TABS, array( &$tabs, &$record ) );

        $fields['settab'] = array(
                    'label' => 'str_pref_default_tab',
                    'form_tip' => 'str_pref_setting_this', 
                    'formatter' => 'select',
                    'options' => $tabs,
                    'flags' => CCFF_NONE
            );

        /* side bar preferences */

        require_once('cclib/cc-template.inc');
        $extras = CCTemplateAdmin::GetExtras(true);
        $prev = empty($CC_GLOBALS['user_extra']['prefs']['extras']) ? null : $CC_GLOBALS['user_extra']['prefs']['extras'];
        foreach( $extras as $file => $text )
        {
            $fields['extras['.$file.']'] = array(
                        'label' => $text,
                        'form_tip' => 'str_pref_sidebar', 
                        'formatter' => 'checkbox',
                        'value' => $prev && in_array($file,$prev),
                        'flags' => CCFF_POPULATE
                );
        }

        $form->AddFormFields($fields);
        
        if( empty($_POST['generic']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $prefs['extras'] = empty($_POST['extras']) ? array() : array_keys($_POST['extras']);
            $prefs['default_user_tab'] = $values['settab'];
            $user_id = CCUser::CurrentUser();
            $user_extra = CCDatabase::QueryItem('SELECT user_extra FROM cc_tbl_user WHERE user_id='.$user_id);
            $user_extra = unserialize($user_extra);
            $user_extra['prefs'] = $prefs;
            $args['user_extra'] = serialize($user_extra);
            $args['user_id'] = $user_id;
            $user = new CCUsers();
            $user->Update($args);
            CCUtil::SendBrowserTo(ccl('people',CCUser::CurrentUserName()));
        }

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('preferences'),  array( 'CCUserPreferences', 'SetPreferences'), 
                          CC_MUST_BE_LOGGED_IN, ccs(__FILE__) );
    }
}

?>
