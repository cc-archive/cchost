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
* Base classes and general user admin interface
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-admin.php');

class CCSkinProfilesForm extends CCEditConfigForm
{
    function CCSkinProfilesForm()
    {
        $this->CCEditConfigForm('skin-settings');

        require_once('cclib/cc-template.inc');

        $fields['skin_profile'] = array(
                'label'     => _('Skin Profile'),
                'formatter' => 'select',
                'options'   => CCTemplateAdmin::GetProfiles(),
                'flags'     => CCFF_POPULATE,
                );

        $this->SetHandler( ccl('admin','skins','profiles') ); // reset the handler back to us
        $this->SetHelpText(_('Selecting a new profile here will destroy any skin settings you have not saved to the current profile'));
        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Load this Skin Profile'));
    }
}

class CCSkinProfileSaveForm extends CCForm
{
    function CCSkinProfileSaveForm()
    {
        $this->CCForm();

        require_once('cclib/cc-template.inc');
        $user_paths = CCTemplateAdmin::GetUserPaths('profiles');

        $fields['profile-name'] = array(
                'label'     => _('Name'),
                'form_tip'  => _('Your current skin profile settings will be saved to this name (file safe characters only)'),
                'formatter' => 'textedit',
                'flags'     => CCFF_REQUIRED,
                );
        $fields['desc'] = array(
                'label'     => _('Description'),
                'form_tip'  => _('A one-line description of this skin profile'),
                'formatter' => 'textedit',
                'flags'     => CCFF_REQUIRED,
                );
        $fields['target_dir'] = array(
                'label'     => _('Target Directory'),
                'form_tip'  => _('This profile will be saved here'),
                'formatter' => 'select',
                'options'   => $user_paths,
                'flags'     => CCFF_NONE,
                );

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Save this Skin Profile'));
    }
}

class CCSkinCreateForm extends CCForm
{
    function CCSkinCreateForm()
    {
        $this->CCForm();

        require_once('cclib/cc-template.inc');
        $skins      = CCTemplateAdmin::GetSkins(true);
        $user_paths = CCTemplateAdmin::GetUserPaths();

        $fields['skin-file'] =
            array( 'label'       => _('Clone this Skin Template'),
                   'form_tip'    => _('Your new skin will start as a clone of this.'),
                   'formatter'   => 'select',
                   'options'     => $skins,
                   'flags'       => CCFF_POPULATE );
        $fields['target-dir'] =
            array( 'label'       => _('Target Directory'),
                   'form_tip'    => _('Your new skin will be created here.'),
                   'formatter'   => 'select',
                   'options'     => $user_paths,
                   'flags'       => CCFF_POPULATE );
        $fields['skin-name'] =
            array( 'label'       => _('Name'),
                   'form_tip'    => _('The name of your new skin will be called.'),
                   'formatter'   => 'textedit',
                   'flags'       => CCFF_POPULATE | CCFF_REQUIRED);

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Create new skin'));
    }
}

class CCSkinSettingsForm extends CCEditConfigForm
{
    function CCSkinSettingsForm()
    {
        $this->CCEditConfigForm('skin-settings');

        require_once('cclib/cc-template.inc');
        $skins             = CCTemplateAdmin::GetSkins(true);
        $list_format_files = CCTemplateAdmin::GetFormats('list');
        $page_format_files = CCTemplateAdmin::GetFormats('page');
        $str_profiles      = CCTemplateAdmin::GetStringProfiles();

        $fields['string_profile'] =
            array( 'label'       => _('String Profile'),
                   'form_tip'    => _('Default profile for display strings'),
                   'formatter'   => 'select',
                   'options'     => $str_profiles,
                   'flags'       => CCFF_POPULATE );
        $fields['list_file'] =
            array( 'label'       => _('Upload Page Format'),
                   'form_tip'    => _('Use this template when showing a single upload page'),
                   'formatter'   => 'raw_select',
                   'options'     => $page_format_files,
                   'value'       => 'ccskins/shared/formats/upload_page.php',
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
        $fields['list_files'] =
            array( 'label'       => _('Upload Listing Format'),
                   'form_tip'    => _('Use this template when listing multiple files'),
                   'formatter'   => 'raw_select',
                   'options'     => $list_format_files,
                   'value'       => 'ccskins/shared/formats/upload_list.php',
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
        $fields['max-listing'] =
            array( 'label'       => _('Max Items Per Page'),
                   'form_tip'    => _('Maximum number of uploads, users in a listing'),
                   'class'       => 'cc_form_input_short',
                   'formatter'   => 'textedit',
                   'flags'       => CCFF_POPULATE | CCFF_REQUIRED);
        $fields['skin-file'] =
            array( 'label'       => _('Base Skin Template'),
                   'form_tip'    => _('Default skin template for this profile'),
                   'formatter'   => 'select',
                   'options'     => $skins,
                   'flags'       => CCFF_POPULATE );

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Basic Skin Settings'));
        $this->SetModule(ccs(__FILE__));
        CCPage::AddScriptLink('js/skin_editor.js');
    }
}

/**
 *
 */
class CCSkinLayoutForm extends CCEditConfigForm
{
    /**
     * Constructor
     */
    function CCSkinLayoutForm()
    {
        $this->CCEditConfigForm('skin-settings');

        require_once('cclib/cc-template.inc');

        $fields = array();

/*    
        $fields['html_form'] =
            array( 'label'       => _('Default Form Template'),
                   'form_tip'    => _('Default template for displaying forms'),
                   'formatter'   => 'select',
                   'value'       => 'html_form.php/html_form',
                   'options'     => array( 'html_form.php/html_form' => 'html_form.php/html_form' ),
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
*/ 
        $fields['form_fields'] =
            array( 'label'       => _('Form Fields Style'),
                   'form_tip'    => _('Choice the formatting of regular forms'),
                   'formatter'   => 'select',
                   'value'       => 'form_fields.tpl/form_fields',
                   'options'     => array(
                                        'form_fields.tpl/form_fields' => _('Field labels next to fields'),
                                        'form_fields.tpl/stacked_form_fields' => _('Field labels above fields'),
                                        'form_fields.tpl/fieldset_form_fields' => _('Field sets'),
                                        ),
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );

        $fields['grid_form_fields'] =
            array( 'label'       => _('Grid Form Fields Style'),
                   'form_tip'    => _('Choice the formatting of grid forms'),
                   'formatter'   => 'select',
                   'value'       => 'form_fields.tpl/grid_form_fields',
                   'options'     => array(
                                        'form_fields.tpl/flat_grid_form_fields' => _('Matrix grid (all fields on one screen)'),
                                        'form_fields.tpl/grid_form_fields' => _('Tab style (recommended for narrow layouts)'),
                                        ),
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );

        $fields['tab_pos'] = array(
                'label'     => _('Tab Positions'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_layouts',
                'scroll'    => false,
                'props'     => CCTemplateAdmin::GetLayouts('tab_pos'),
                'flags'     => CCFF_POPULATE,
                );

        $fields['box_shape'] = array(
                'label'     => _('Box Shapes'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_layouts',
                'scroll'    => false,
                'props'     => CCTemplateAdmin::GetLayouts('box_shape'),
                'flags'     => CCFF_POPULATE,
                );

        $fields['page_layout'] = array(
                'label'     => _('Page Layout'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_layouts',
                'scroll'    => true,
                'props'     => CCTemplateAdmin::GetLayouts('layout'),
                'flags'     => CCFF_POPULATE,
                );

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Skin Layout Changes'));
        $this->SetModule(ccs(__FILE__));

        CCPage::AddScriptLink('js/skin_editor.js',true);
    }
}

function generator_skin_prop($form,$varname,$value,$class='')
{
    return $form->generator_metalmacro($varname,$value,$class);
}

function validator_skin_prop($form,$fieldname)
{
    return true;
}


/**
 *
 */
class CCAdminColorSchemesForm extends CCEditConfigForm
{
    /**
     * Constructor
     */
    function CCAdminColorSchemesForm()
    {
        $this->CCEditConfigForm('skin-settings');

        require_once('cclib/cc-template.inc');

        $fields['font_scheme'] = array(
                'label'     => _('Fonts'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_font_schemes',
                'scroll'    => false,
                'props'     => CCTemplateAdmin::GetFonts(),
                'flags'     => CCFF_POPULATE,
                );

        $fields['font_size'] = array(
                'label'     => _('Font Size'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_font_schemes',
                'scroll'    => false,
                'props'     => CCTemplateAdmin::GetFontSizes(),
                'flags'     => CCFF_POPULATE,
                );

        $fields['color_scheme'] = array(
                'label'     => _('Color Scheme'),
                'formatter' => 'skin_prop',
                'macro'     => 'skin_editor.php/edit_color_schemes',
                'scroll'    => true,
                'props'     => CCTemplateAdmin::GetColors(),
                'flags'     => CCFF_POPULATE,
                );

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Skin Appearance Changes'));
        $this->SetModule(ccs(__FILE__));

        CCPage::AddScriptLink('js/skin_editor.js',true);
    }

}


/**
* Edit and maintain color schemes
* 
*/
class CCSkinAdmin
{
    function Admin($profile='',$save='')
    {
        if( !empty($profile) && $profile == 'profiles')
            return $this->Profiles();

        if( !empty($save) && !empty($profile) && $profile == 'profile' && $save == 'save' )
            return $this->ProfileSave();

        require_once('cclib/cc-template.inc');
        $config =& CCConfigs::GetTable();
        $skin_settings = $config->GetConfig('skin-settings');
        if( empty($skin_settings['skin_profile']) )
        {
            $msg = _('There is no profile skin set, this is probably a bad thing');
        }
        else
        {
            $props = CCTemplateAdmin::_get_format_props($skin_settings['skin_profile']);
            $msg = sprintf(_('Current skin profile: %s'), '<b>' . $props['desc'] . '</b>' );
        }

        CCPage::SetTitle(_('Configure Skins'));

        $args[] = array( 'action'    => ccl('admin','skins','profiles'),
                         'menu_text' => _('Load a Profile'),
                         'help'      => _('Start here to pick from an existing profile') );

        $args[] = array( 'action'    => ccl('admin','skins','settings'),
                         'menu_text' => _('Basic Settings'),
                         'help'      => _('Message types, listing choices, etc.') );

        $args[] = array( 'action'    => ccl('admin','skins','layout'),
                         'menu_text' => _('Layouts'),
                         'help'      => _('Page layouts, tab placement, box shapes, etc.') );

        $args[] = array( 'action'    => ccl('admin','colors'),
                         'menu_text' => _('Color, Font, Text size'),
                         'help'      => _('Fonts and colors') );

        $args[] = array( 'action'    => ccl('admin','skins','profile', 'save'),
                         'menu_text' => _('Save this Profile'),
                         'help'      => _('Save the current settings to your own profile') );

        $args[] = array( 'action'    => ccl('admin','skins','create' ),
                         'menu_text' => _('Create Skin Template'),
                         'help'      => _('For web developers: Sets up a new skin template') );

        CCPage::PageArg('client_menu_help', $msg );
        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }

    function Profiles()
    {
        $form = new CCSkinProfilesForm();
        if( empty($_POST['skinprofiles']) || !$form->ValidateFields() )
        {
            CCPage::SetTitle(_('Select a New Skin Profile'));
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $form->GetFormValues($values);
            $this->_set_profile($values['skin_profile']);
            CCUtil::SendBrowserTo(ccl('admin','skins'));
        }
    }

    function _set_profile($skin_profile)
    {
        require_once('cclib/cc-template.inc');
        $props = CCTemplateAdmin::_get_format_props($skin_profile);
        unset($props['type']);
        unset($props['desc']);
        $props['skin_profile'] = $skin_profile;
        $config =& CCConfigs::GetTable();
        $config->SaveConfig('skin-settings',$props);
    }

    function ProfileSave()
    {
        CCPage::SetTitle(_('Save Skin Profile'));
        $form = new CCSkinProfileSaveForm();
        if( empty($_POST['skinprofilesave']) || !$form->ValidateFields() )
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            /*
                just to be different:

                profiles are saved to files, not config. we only save the name here.
            */
            $form->GetFormValues($values);
            $config =& CCConfigs::GetTable();
            $skin_settings = $config->GetConfig('skin-settings');
            $text = '<?/*' . "\n[meta]\n    type = profile\n    desc = _('{$values['desc']}')";
            foreach( array( 'max-listing', 'properties', 'skin_profile') as $outme )
                if( isset($skin_settings[$outme]) )
                    unset($skin_settings[$outme]);

            foreach( $skin_settings as $K => $V )
            {
                $text .= "\n    $K  = $V";
            }
            $text .= "\n[/meta]\n*/?" . '>' . "\n";
            CCUtil::MakeSubdirs($values['target_dir']);
            $fname = preg_replace('/[^a-z]+/','',strtolower($values['profile-name']));
            if( empty($fname) )
                $fname = 'madeup_' . rand();
            $fname = 'profile_' . $fname . '.php';
            $target = $values['target_dir'] . '/' . $fname;
            $f = fopen($target,'w');
            fwrite($f,$text);
            fclose($f);
            $skin_settings['skin_profile'] = $target;
            $config->SaveConfig('skin-settings',$skin_settings);
            CCUtil::SendBrowserTo(ccl('admin','skins'));
        }
    }

    function Layout()
    {
        CCPage::SetTitle(_('Configure Skins Layouts'));
        $form = new CCSkinLayoutForm();
        CCPage::AddForm($form->GenerateForm());
    }

    function Settings()
    {
        CCPage::SetTitle(_('Configure Skins Settings'));
        $form = new CCSkinSettingsForm();
        CCPage::AddForm($form->GenerateForm());
    }

    function Create()
    {
        CCPage::SetTitle(_('Create a Skin Template'));
        $form = new CCSkinCreateForm();
        if( empty($_POST['skincreate']) || !$form->ValidateFields() )
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $form->GetFormValues($values);
            $src = dirname($values['skin-file']);
            $safe_name = strtolower(preg_replace('/[^a-z0-9_-]/','',$values['skin-name']));
            $target = $values['target-dir'] . '/' . $safe_name;
            if( file_exists($target) )
            {
                $form->SetFieldError('skin-name',_('A directory with that name already exists'));
                CCPage::AddForm($form->GenerateForm());
            }
            else
            {
                $this->_deep_copy($src,$target);
                $msg = sprintf(_('The skin %s has been created sucessfully'),'<b>' . $target . '</b>');
                $msg .= '<p>' . sprintf(_('Return to %sSkin Settings%.'),'<a href="' . ccl('admin','skins') .'">', '</a>') . '</p>';
                CCPage::Prompt($msg);
            }
        }
    }

    function _deep_copy($src,$target)
    {
        if( !file_exists($target) )
        {
            //print("making dir: $target<br />");
            CCUtil::MakeSubdirs($target,0777);
        }

        $dirs = glob($src . '/*', GLOB_ONLYDIR );
        foreach( $dirs as $dir )
        {
            $sub_dir = basename($dir);
            $this->_deep_copy($src . '/' . $sub_dir, $target . '/' . $sub_dir );
        }

        $files = glob($src . '/*.*');
        foreach( $files as $file )
        {
            $base = basename($file);
            $t = $target . '/' . $base;
            copy( $file, $t );
            chmod( $t, 0777 );
        }
    }

    function ColorSchemes()
    {
        CCPage::SetTitle(_('Manage Color Schemes'));
        $form = new CCAdminColorSchemesForm();
        CCPage::AddForm($form->GenerateForm());
    }


    function OnAdminMenu( &$items, $scope )
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'skin-settings'   => array( 'menu_text'  => _('Skin'),
                             'menu_group' => 'configure',
                             'help'      => _('Choose a skin, theme, layout, colors, fonts, etc.'),
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 3,
                             'action' =>  ccl('admin','skins')
                             ),
            );
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/skins',     array('CCSkinAdmin', 'Admin'),
            CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( 'admin/skins/settings',     array('CCSkinAdmin', 'Settings'),
            CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( 'admin/skins/layout',     array('CCSkinAdmin', 'Layout'),
            CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( 'admin/colors',     array('CCSkinAdmin', 'ColorSchemes'),       
            CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( 'admin/skins/create', array('CCSkinAdmin', 'Create'),
            CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}


?>