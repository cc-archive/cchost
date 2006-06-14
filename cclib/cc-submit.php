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
*/
define('CC_FORM_TYPE_LOGO_DIR', 'ccimages/form_types' );

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCSubmit',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCSubmit',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCSubmit', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_TRANSLATE,    array( 'CCSubmit', 'OnTranslate'));

/**
* @package cchost
* @subpackage admin
*/
class CCAdminSubmitFormForm extends CCUploadForm
{
    function CCAdminSubmitFormForm()
    {
        $this->CCUploadForm();
        $fields = array( 
                    'enabled' =>
                        array( 'label'      => 'Enable',
                               'form_tip'   => 'Uncheck this to make this form type invisible to the user',
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE ),

                    'submit_type' =>
                        array( 'label' => 'Label',
                               'form_tip'   => 'e.g. Home Movie',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
                            
                    'text' =>
                        array( 'label'      => 'Caption',
                               'form_tip'   => 'e.g. Submit a Home Movie',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'logo' =>
                       array(  'label'      => 'Logo',
                               'formatter'  => 'avatar',
                               'form_tip'   => 'Image file',
                               'upload_dir' => CC_FORM_TYPE_LOGO_DIR,
                               'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL  ),

                    'help' =>
                        array( 'label' => 'Description',
                               'form_tip' => 'This is the description shown when displaying all form types',
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'tags' =>
                        array( 'label' => 'Tags',
                               'form_tip'   => 'Comma separted list of tags that will be automatically associated with uploads. (e.g. home_movie, super8)',
                               'formatter'  => 'tagsedit',
                               'isarray'    => true,
                               'flags'      => CCFF_POPULATE ),

                    'suggested_tags' =>
                        array( 'label' => 'Suggested Tags',
                               'form_tip'   => 'Comma separted list of tags that will the user can optionally attach to the submission.',
                               'formatter'  => 'tagsedit',
                               'isarray'    => true,
                               'flags'      => CCFF_POPULATE ),

                    'weight' =>
                        array( 'label' => 'Position',
                               'form_tip'   => 'Lower number means further up on the submit page, higher number means more toward the bottom.',
                               'class'      => 'cc_form_input_short',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'form_help' =>
                        array( 'label' => 'Form Help Message',
                                'form_tip' => 'This is a message displayed at the top of the form.',
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'isremix' =>
                        array( 'label'      => 'Enable Remix Search',
                               'form_tip'   => 'Check this if you want the form to include a remix search box',
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE ),

                    'media_types' =>
                        array( 'label' => 'Media Type Allows',
                               'form_tip'   => "Comma separted list of allowable file type. Valid types are 'audio', 'video', 'image', 'archive'",
                               'isarray'    => true,
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'action' =>
                        array( 'label' => 'Handler URL',
                               'form_tip'   => 'Redirect this submission from the default handler (advanced usage)',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
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
            $allowed[] =& $types[$formtype];
        }

        CCEvents::Invoke(CC_EVENT_UPLOAD_ALLOWED, array( &$allowed ) );

        if( empty($formtype) )
        {
            $this->ShowSubmitTypes($types);
            return;
        }

        $type =& $allowed[0];

        if( empty($type['quota_reached']) && !empty($type['enabled']))
        {
            $etc = array();

            if( !empty($extra) )
            {
                $etc['url_extra'] = $extra;
            }

            $etc['suggested_tags'] = empty($type['suggested_tags']) ? '' : $type['suggested_tags'];

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

    }

    function ShowSubmitTypes($types)
    {
        CCPage::SetTitle(_('Pick Submission Type'));
        $keys = array_keys($types);
        foreach( $keys as $key )
        {
            if( !empty($types[$key]['logo']) )
                $types[$key]['logo'] = ccd( CC_FORM_TYPE_LOGO_DIR, $types[$key]['logo'] );
            if( empty($types[$key]['action']) )
                $types[$key]['action'] = ccl('submit',$key);
        }
        CCPage::PageArg('submit_form_infos', $types, 'submit_forms');
    }


    function & _get_form_types($honor_enabled)
    {
        $configs =& CCConfigs::GetTable();
        $form_types = $configs->GetConfig('submit_forms');
        if( !empty($form_types) )
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
            return $form_types;
        }

        $form_types = array();
        CCEvents::Invoke( CC_EVENT_SUBMIT_FORM_TYPES, array( &$form_types ) );

        $form_types = $this->_sort_form_types($form_types,$honor_enabled);

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

        CCPage::SetTitle("Manage Submit Forms");
        $form_types = $this->_get_form_types(false);

        if( $cmd == 'revert' )
        {
            $configs =& CCConfigs::GetTable();
            $where['config_scope'] = $CC_CFG_ROOT;
            $where['config_type'] = 'submit_forms';
            $configs->DeleteWhere($where);
            if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
                CCPage::Prompt("Submit forms have been reverted to factory defaults");
            else
                CCPage::Prompt("Submit forms have been reverted to global settings");

        }
        else
        {
            $args = array();
            foreach( $form_types as $key => $data)
            {
                $args[] = array( 'action' => ccl('admin','editsubmitform',$key ),
                                 'menu_text' => 'Edit',
                                 'help' => $data['submit_type'] );
            }
            CCPage::PageArg('link_table_items',$args,'link_table');

            $url = ccl('admin','submit','revert');
            if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
                CCPage::Prompt("If you wish to revert all changes to factory defaults <a href=\"$url\">click here</a> WARNING: there is no undo.");
            else
                CCPage::Prompt("If you wish to remove all submit form changes for $CC_CFG_ROOT and revert to the global settings <a href=\"$url\">click here</a>. WARNING: there is no undo.");
            $url = ccl('admin','newsubmitform');
            CCPage::Prompt("<a href=\"$url\">Add a new form type...</a>");
        }
    }

    function EditForm($form_type_key)
    {
        $form_types = $this->_get_form_types(false);
        if( empty($form_types[$form_type_key]) )
            return;

        $msg = "Editing Submit Form for: " . $form_types[$form_type_key]['submit_type'];
        CCPage::SetTitle($msg);
        $ok = false;

        $form = new CCAdminSubmitFormForm();
        if( empty($_POST['adminsubmitform']) )
        {
            $form->PopulateValues($form_types[$form_type_key]);
        }
        elseif ( $form->ValidateFields() )
        {
            // CCDebug::PrintVar($form_types);
            $this->_save_form($form,$form_type_key,$form_types);
            $urlx = ccl('admin','submit');
            $urly = ccl('submit');
            $form_name = $form_types[$form_type_key]['submit_type'];
            CCPage::Prompt("Submit form changes saved. Go back to <a href=\"$urlx\">Manage Submit Forms</a> or see the <a href=\"$urly\">$form_name</a>");
            $ok = true;
        }

        if( !$ok )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function NewForm()
    {
        CCPage::SetTitle("Create a New Submit Form");

        $form = new CCAdminSubmitFormForm();
        if( !empty($_POST['adminsubmitform']) && $form->ValidateFields() )
        {
            $form_types = $this->_get_form_types(false);
            $i = 1;
            $keys = array_keys($form_types);
            while( in_array( 'userform' . $i, $keys ) )
                $i++;
            $form_type_key = 'userform' . $i;
            $this->_save_form($form,$form_type_key,$form_types);
            $form_name = $form_types[$form_type_key]['submit_type'];
            $urlz = ccl('admin','submit');
            $urlf = ccl('submit');
            CCPage::Prompt("New form type saved. Go back to <a href=\"$urlz\">Manage Submit Forms</a> or see the <a href=\"$urlf\">$form_name</a>");
        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function _save_form(&$form,$form_type_key,&$form_types)
    {
        $form->FinalizeAvatarUpload('logo', CC_FORM_TYPE_LOGO_DIR );
        $form->GetFormValues($values);
        if( empty($values['logo']) && !empty($form_types[$form_type_key]['logo']) )
             $values['logo'] = $form_types[$form_type_key]['logo']; // wtf
        if( empty($values['tags']) )
        {
            $values['tags'] = 'media';
        }
        else
        {
            if( is_string($values['tags']) )
                $values['tags'] = CCTag::TagSplit($values['tags']);
            if( !in_array( 'media', $values['tags'] ) )
                $values['tags'][] = 'media';
        }
        if( empty($values['weight']) )
            $values['weight'] = 1;
        if( !empty($values['action']) )
        {
            if( substr($values['action'],0,7) != 'http://' )
            {
                $url = preg_replace('#/?(.*)$#','\1',$values['action']);
                $values['action'] = ccl($url);
            }
        }
        $form_types[$form_type_key] = $values;
        $form_types = $this->_sort_form_types($form_types,false);
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('submit_forms',$form_types,'',false);
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
        CCEvents::MapUrl( ccp('files','remix'),  array('CCSubmit','Remix'),   CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( ccp('submit'),         array('CCSubmit','Submit'),   CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( ccp('admin','submit'), array('CCSubmit','Admin'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','editsubmitform'), array('CCSubmit','EditForm'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','newsubmitform'), array('CCSubmit','NewForm'),    CC_ADMIN_ONLY );
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
    * Event handler for {@link CC_EVENT_TRANSLATE}
    */
    function OnTranslate()
    {
        $configs =& CCConfigs::GetTable();
        $roots = $configs->GetConfigRoots();
        foreach( $roots as $aroot )
        {
            $root = $aroot['config_scope'];
            $forms = $configs->GetConfig('submit_forms',$root);
            $new_forms = array();
            foreach( $forms as $key => $form )
            {
                cc_lang_translate($form,'text');
                cc_lang_translate($form,'help');
                cc_lang_translate($form,'form_help');
                $new_forms[$key] = $form;
            }
            $configs->SaveConfig('submit_forms',$new_forms,$root,false);
        }
    }

}


?>