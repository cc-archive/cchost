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
* @subpackage upload
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* @package cchost
* @subpackage admin
*/

require_once('cchost_lib/cc-form.php');

class CCAdminSubmitFormForm extends CCUploadForm
{
    function CCAdminSubmitFormForm()
    {
        global $CC_GLOBALS;

        $this->CCUploadForm();
        $fields = array( 
                    'enabled' =>
                        array( 'label'      => _('Enable'),
                               'form_tip'   => _('Uncheck this to make this form type invisible to the user'),
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE ),

                    'submit_type' =>
                        array( 'label' => _('Label'),
                               'form_tip'   => _('e.g. Home Movie'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
                            
                    'text' =>
                        array( 'label'      => _('Caption'),
                               'form_tip'   => _('e.g. Submit a Home Movie'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'logo' =>
                       array(  'label'      => _('Logo'),
                               'formatter'  => 'avatar',
                               'form_tip'   => _('Image file'),
                               'upload_dir' => $CC_GLOBALS['image-upload-dir'],     
                               'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL  ),


                    'help' =>
                        array( 'label' => _('Description'),
                               'form_tip' => _('This is the description shown when displaying all form types'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'tags' =>
                        array( 'label' => _('Tags'),
                               'form_tip'   => 'Comma separted list of tags that will be automatically associated with uploads. (e.g. home_movie, super8)',
                               'formatter'  => 'tagsedit',
                               'isarray'    => true,
                               'flags'      => CCFF_POPULATE ),

                    'suggested_tags' =>
                        array( 'label' => _('Suggested Tags'),
                               'form_tip'   => 'Comma separted list of tags that will the user can optionally attach to the submission.',
                               'formatter'  => 'tagsedit',
                               'isarray'    => true,
                               'flags'      => CCFF_POPULATE ),

                    'weight' =>
                        array( 'label' => _('Position'),
                               'form_tip'   => _('Lower number means further up on the submit page, higher number means more toward the bottom.'),
                               'class'      => 'cc_form_input_short',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'form_help' =>
                        array( 'label' => _('Form Help Message'),
                                'form_tip' => _('This is a message displayed at the top of the form.'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'isremix' =>
                        array( 'label'      => _('Enable Remix Search'),
                               'form_tip'   => _('Check this if you want the form to include a remix search box'),
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE ),

                    'media_types' =>
                        array( 'label' => _('Media Type Allows'),
                               'form_tip'   => _("Comma separted list of allowable file type. Valid types are 'audio', 'video', 'image', 'archive'"),
                               'isarray'    => true,
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'action' =>
                        array( 'label' => _('Handler URL'),
                               'form_tip'   => _('Redirect this submission from the default Submit Form handler (advanced usage)'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
                    'delete' =>
                        array( 'label' => _('Delete'),
                               'form_tip'   => _('Delete this submit form type (This is no UNDO!)'),
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_NONE ),
                  );

    
        $this->AddFormFields( $fields );
        $this->EnableSubmitMessage(false);
    }

}

/**
* Event handlers for Submit forms
*/
class CCSubmit
{
    /*-----------------------------
        MAPPED TO URLS
    -------------------------------*/

    /**
    * Handles /remix URL
    *
    * Displays and process a form to handle remix uploads
    *
    * @param integer $remix_this_id OPTIONAL: Prepopulate the 'search' box with this upload
    */
    function Remix( $remix_this_id = '' )
    {
        $this->Submit('remix', '', $remix_this_id);
    }

    function GetSubmitTypes()
    {
        $types = $this->_get_form_types(true);
        CCEvents::Invoke(CC_EVENT_UPLOAD_ALLOWED, array( &$types ) );
        return $types;
    }

    /**
    * Handles URL files/submit
    *
    * Displays and process new submission form
    *
    * @param string $username Login name of user doing the upload
    */
    function Submit($formtype='',$username='',$extra='')
    {
        $types =& $this->_get_form_types(true);

        if( empty($formtype) )
        {
            $allowed =& $types;
        }
        else
        {
            if( empty($types[$formtype]) )
                return;
            $allowed[$formtype] =& $types[$formtype];
        }

        CCEvents::Invoke(CC_EVENT_UPLOAD_ALLOWED, array( &$allowed ) );

        if( empty($formtype) )
        {
            $this->ShowSubmitTypes($types);
            return;
        }

        $type =& $allowed[$formtype];

        if( empty($type['quota_reached']) && !empty($type['enabled']))
        {
            $etc = array();

            if( !empty($extra) )
            {
                $etc['url_extra'] = $extra;
            }

            $etc['suggested_tags'] = empty($type['suggested_tags']) ? '' : $type['suggested_tags'];

            require_once('cchost_lib/cc-mediahost.php');
            $api = new CCMediaHost();
            if( $type['isremix'] )
            {
                $api->SubmitRemix( $type['text'], $type['tags'], $type['form_help'], $etc );
            }
            else
            {
                $api->SubmitOriginal( $type['text'], $type['tags'], $type['form_help'], $username, $etc  );
            }
        }
        else
        {
            CCUtil::SendBrowserTo(ccl('submit'));
        }
        

    }

    function ShowSubmitTypes($types)
    {
        global $CC_GLOBALS;

        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle('str_pick_submission_type');
        $keys = array_keys($types);
        $sorted = array();
        foreach( $keys as $key )
        {
            if( empty($types[$key]['action']) )
                $types[$key]['action'] = ccl('submit',$key);

            if( !empty($types[$key]['logo']) )
            {
                $img = $CC_GLOBALS['image-upload-dir'] . basename($types[$key]['logo']);
                if( file_exists($img ) )
                {
                    $types[$key]['logo'] = $img;
                }
                else
                {
                    unset($types[$key]['logo']);
                }
            }
            $sorted[ $types[$key]['weight'] ] = $types[$key];
        }
        ksort($sorted);
        CCPage::PageArg('submit_form_infos', $sorted, 'html_form.php/submit_forms');
    }


    function & _get_form_types($honor_enabled)
    {
        $configs =& CCConfigs::GetTable();
        $form_types = $configs->GetConfig('submit_forms');
        if( empty($form_types) )
        {
            $form_types = $this->_default_form_types();
            $configs->SaveConfig('submit_forms',$form_types);
        }
        else
        {
            if( $honor_enabled )
            {
                $keys = array_keys($form_types);
                foreach( $keys as $key )
                {
                    if( !$form_types[$key]['enabled'] )
                        unset($form_types[$key]);
                }
            }
            $sorted = array();
            return $form_types;
        }


        return $form_types;
    }

    function _default_form_types()
    {
        $form_types = 
            array (
                'remix' => array (
                    'enabled' => 1,
                    'submit_type' => 'str_submit_remix',
                    'text' => 'str_submit_a_remix',
                    'help' => 'str_submit_remix_help',
                    'tags' => array (
                        0 => 'media',
                        1 => 'remix',
                        ),
                    'suggested_tags' => '',
                    'weight' => 1,
                    'form_help' => 'str_submit_remix_line',
                    'isremix' => 1,
                    'media_types' => 'audio',
                    'action' => '',
                    'logo' => 'ccskins/shared/images/submit-remix.gif',
                    'type_key' => 'remix',
                    ),
                'samples' => array (
                    'enabled' => 1,
                    'submit_type' => 'str_submit_sample',
                    'text' => 'str_submit_samples',
                    'help' => 'str_submit_samples_help',
                    'tags' => array (
                        0 => 'sample',
                        1 => 'media',
                        ),
                    'suggested_tags' => '',
                    'weight' => 15,
                    'form_help' => 'str_submit_samples_help_line',
                    'isremix' => '',
                    'media_types' => 'audio,archive',
                    'action' => '',
                    'logo' => 'ccskins/shared/images/submit-sample.gif',
                    'type_key' => 'samples',
                    ),
                'fullmix' => array (
                    'enabled' => '1',
                    'submit_type' => 'str_submit_original',
                    'text' => 'str_submit_an_original',
                    'help' => 'str_submit_original_help',
                    'tags' => array (
                        0 => 'media',
                        1 => 'original',
                        ),
                    'suggested_tags' => '',
                    'weight' => 50,
                    'form_help' => 'str_submit_original_help_line',
                    'isremix' => '',
                    'media_types' => 'audio',
                    'action' => '',
                    'logo' => 'ccskins/shared/images/submit-original.gif',
                    'type_key' => 'fullmix',
                    ),
            );
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('submit_forms',$form_types,'',false);
        return $form_types;
    }

    function & _sort_form_types(&$form_types,$honor_enabled)
    {
        $sorted_types = array();
        foreach( $form_types as $type_key => $type )
        {
            if( !$honor_enabled || $type['enabled'] )
            {
                $type['type_key'] = $type_key;
                $i = empty($type['weight']) ? 1 : $type['weight'];
                while( !empty($sorted_types[$i]) )
                    $i++;
                $sorted_types[$i] = $type;
            }
        }

      //  CCDebug::PrintVar($sorted_types);

        ksort($sorted_types);
        $form_types = array();
        foreach( $sorted_types as $type )
        {
            $form_types[$type['type_key']] = $type;
        }

        return $form_types;
    }

    function Admin($cmd='')
    {
        global $CC_CFG_ROOT;

        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail('');
        CCPage::SetTitle(_('Manage Submit Forms'));
        if( $cmd == 'revert' )
        {
            $configs =& CCConfigs::GetTable();
            $where['config_scope'] = $CC_CFG_ROOT;
            $where['config_type'] = 'submit_forms';
            $configs->DeleteWhere($where);
            CCPage::Prompt(_('Submit forms have been reverted to global settings'));
        }

        $form_types = $this->_get_form_types(false);

        $args = array();
        foreach( $form_types as $key => $data)
        {
            $args[] = array( 'action' => ccl('admin','editsubmitform',$key ),
                             'menu_text' => _('Edit'),
                             'help' => $data['submit_type'] );
        }

        $prompt = '';

        //if( ($cmd != 'revert') && ($CC_CFG_ROOT != CC_GLOBAL_SCOPE) )
        {
            $url = ccl('admin','submit','revert');
            $link1 = "<a href=\"$url\">";
            $link2 = '</a>';

            $prompt .= '<p>' . sprintf( _('If you wish to remove all submit form changes for %s and revert to the global settings %sclick here%s. WARNING: there is no undo.'), 
                '<b>'.$CC_CFG_ROOT.'</b>', $link1, $link2 ) . '</p>';
        }

        $url = ccl('admin','newsubmitform');
        $prompt .= "<p><a class=\"cc_gen_button\" style=\"float:left;margin-bottom:8px;\" href=\"$url\"><span>" 
                    . _('Add a new form type...') . '</span></a><div style=\"clear:both;\">&nbsp;</div></p>';

        CCPage::PageArg('client_menu',$args,'print_client_menu');
        CCPage::PageArg('client_menu_help',$prompt);
    }

    function EditForm($form_type_key)
    {
        global $CC_GLOBALS;

        $form_types = $this->_get_form_types(false);
        if( empty($form_types[$form_type_key]) )
            return;

        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail(_('Edit Submit Form'));
        $msg = sprintf(_('Editing Submit Form for: %s'), $form_types[$form_type_key]['submit_type'] );
        CCPage::SetTitle($msg);
        $ok = false;

        $form = new CCAdminSubmitFormForm();
        if( empty($_POST['adminsubmitform']) )
        {
            $this->_ensure_private_logo($form_types,$form_type_key);
            $form->PopulateValues($form_types[$form_type_key]);
        }
        elseif( array_key_exists('delete',$_POST) )
        {
            $form_name = $form_types[$form_type_key]['submit_type'];
            $configs =& CCConfigs::GetTable();
            $forms_temp = $configs->GetConfig('submit_forms');
            unset($forms_temp[$form_type_key]);
            $configs->SaveConfig('submit_forms',$forms_temp,'',false);
            $urlx = ccl('admin','submit');
            $urly = ccl('submit');
            $link1 = "<a href=\"$urlx\">";
            CCPage::Prompt(sprintf( _('Submit form "%s" has been deleted.'), $form_name ));
            $ok = true;
        }
        elseif ( $form->ValidateFields() )
        {
            $form->FinalizeAvatarUpload('logo', $CC_GLOBALS['image-upload-dir'] );
            $form->GetFormValues($values);
            // this ensures that the logo isn't wiped. (sigh)
            if( empty($values['logo']) )
                $values['logo'] = $form_types[$form_type_key]['logo'];
            $form_types = $this->SaveFormType($values,$form_type_key,$form_types);
            CCPage::Prompt( _('Submit form changes saved') );
            $ok = true;
        }

        if( !$ok )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function _ensure_private_logo(&$form_types,&$form_type_key)
    {
        global $CC_GLOBALS;

        // 
        // We copy the image to the user's upload dir so they
        // don't edit the 'system' copy
        //
        // This is all a little heavy handed but it's solid
        // and we take no chances of screwing things up
        // down the line
        //
        if( !isset($form_types[$form_type_key]['logo']) )
        {
            $form_types[$form_type_key]['logo'] = '';
            return;
        }

        if( strstr($form_types[$form_type_key]['logo'],'ccskins') === false )
            return;

        global $CC_GLOBALS;
        $fullpath = realpath($form_types[$form_type_key]['logo']);
        $filename = basename($fullpath);
        $dest_path = $CC_GLOBALS['image-upload-dir'] . $filename;
        if( file_exists($dest_path) )
        {
            $ret = true;
        }
        else
        {
            $ret = copy($fullpath,$dest_path);
        }
        if( $ret )
        {
            // we'll write this back to the form type (might as well)
            //
            // we get a fresh copy 
            $configs =& CCConfigs::GetTable();
            $forms_temp = $configs->GetConfig('submit_forms');
            $forms_temp[$form_type_key]['logo'] = $filename;
            $configs->SaveConfig('submit_forms',$forms_temp,'',false);
        }
        else
        {
            $msg = sprintf(_('Your Graphics Upload Path (%s) must be writable to work on Submit Forms.'),$CC_GLOBALS['image-upload-dir']);
            CCPage::Prompt($msg);
            return;
        }
        $form_types[$form_type_key]['logo'] = $filename;
    }

    function NewForm()
    {
        global $CC_GLOBALS;

        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail(_('New Submit Form'));
        CCPage::SetTitle( _('Create a New Submit Form') );

        $form = new CCAdminSubmitFormForm();
        if( !empty($_POST['adminsubmitform']) && $form->ValidateFields() )
        {
            /* Get a 'safe' form label */
            $form_types = $this->_get_form_types(false);
            $form_label = strtolower($form->GetFormValue('submit_type'));
            $form_label = preg_replace('/[^a-z]+/', '', $form_label);
            if( empty($form_label) )
                $form_label = 'userform';
            $i = 1;
            $keys = array_keys($form_types);
            $safe_form_label = $form_label;
            while( in_array( $safe_form_label, $keys ) )
                $safe_form_label = $form_label . $i++;
            $form_type_key = $safe_form_label;

            $form->FinalizeAvatarUpload('logo', $CC_GLOBALS['image-upload-dir'] );
            $form->GetFormValues($values);
            $form_types = $this->SaveFormType($values,$form_type_key,$form_types);
            CCPage::Prompt( _('New Form type saved.') );
        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function SaveFormType($values,$form_type_key,$form_types='')
    {
        global $CC_GLOBALS;

        if( empty($form_types) )
            $form_types = $this->_get_form_types(false);

        if( empty($values['tags']) )
        {
            $values['tags'] = 'media';
        }
        else
        {
            if( is_string($values['tags']) )
            {
                require_once('cchost_lib/cc-tags.php');
                $values['tags'] = CCTag::TagSplit($values['tags']);
            }
            if( !in_array( 'media', $values['tags'] ) )
                $values['tags'][] = 'media';
        }

        if( empty($values['weight']) )
            $values['weight'] = 1;

        if( !empty($values['action']) )
        {
            if( substr($values['action'],0,7) != 'http://' )
            {
                // shouldn't this be ('#^/#', '', ...) ???

                $url = preg_replace('#/?(.*)$#','\1',$values['action']);
                $values['action'] = ccl($url);
            }
        }

        $form_types[$form_type_key] = $values;
        $form_types = $this->_sort_form_types($form_types,false);
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('submit_forms',$form_types,'',false);
        return $form_types;
    }

    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $items = array( 
            'submitforms' => array(   
                                 'menu_text'  => _('Submit Files'),
                                 'menu_group' => 'artist',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'weight'     => 6,
                                 'action'     => ccp('submit') 
                                ), 
            );
        
        CCMenu::AddItems($items);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('files','remix'),  array('CCSubmit','Remix'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '[upload_id]', 
            _("Display 'Submit a Remix' form. Using upload_id will prefill search results. " .
               "This is how 'I Sampled This...' is done."), CC_AG_SUBMIT_FORM );

        CCEvents::MapUrl( ccp('submit'),         array('CCSubmit','Submit') ,   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '[form_type]/[user_name]', 
            _('Display submit form types or submit an upload'), CC_AG_SUBMIT_FORM );

        CCEvents::MapUrl( ccp('admin','submit'), array('CCSubmit','Admin'),    
            CC_ADMIN_ONLY, ccs(__FILE__), '', _("Dislays 'Manage Submit Forms' form"), 
            CC_AG_SUBMIT_FORM );

        CCEvents::MapUrl( ccp('admin','editsubmitform'), array('CCSubmit','EditForm'),    
            CC_ADMIN_ONLY, ccs(__FILE__), '{form_type}', _('Edit a submit form type'), 
            CC_AG_SUBMIT_FORM );

        CCEvents::MapUrl( ccp('admin','newsubmitform'), array('CCSubmit','NewForm'),    
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Create a new submit form type'), CC_AG_SUBMIT_FORM );
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $items += array(
                'submit_forms'   => array( 
                                 'menu_text'  => _('Submit Forms'),
                                 'menu_group' => 'configure',
                                 'help' => 'Edit what kind of submit forms the user can see',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 40,
                                 'action' =>  ccl('admin','submit')
                                 ),
                );
        }
    }

    /**
    * @access private
    */
    function _build_bread_crumb_trail($text)
    {
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );
        
        $trail[] = array( 'url' => ccl('admin','site'), 'text' => _('Settings') );
        if( empty($text) )
        {
            $trail[] = array( 'url' => '', 'text' => _('Manage Submit Forms') );
        }
        else
        {
            $trail[] = array( 'url' => ccl('admin','submit'), 'text' => _('Manage Submit Forms') );
            $trail[] = array( 'url' => '', 'text' => $text );
        }

        CCPage::AddBreadCrumbs($trail);
    }
}


?>
