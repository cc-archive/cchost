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

class CCSkinCreateForm extends CCForm
{
    function CCSkinCreateForm()
    {
        $this->CCForm();
        global $CC_GLOBALS;

        require_once('cclib/cc-template.inc');
        $skins   = CCTemplateAdmin::GetSkins(true);

        $use_paths = CCUtil::SplitPaths( $CC_GLOBALS['template-root']);
        $paths = array();
        foreach( $use_paths as $P )
        {
            if( strstr($P,'ccskins') )
                continue;
            $paths[$P] = $P;
        }

        $fields['skin-file'] =
            array( 'label'       => _('Clone this Skin'),
                   'form_tip'    => _('Your new skin will be a clone of this.'),
                   'formatter'   => 'select',
                   'options'     => $skins,
                   'flags'       => CCFF_POPULATE );
        $fields['target-dir'] =
            array( 'label'       => _('Target Directory'),
                   'form_tip'    => _('Your new skin will be created here.'),
                   'formatter'   => 'select',
                   'options'     => $paths,
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
        $this->CCEditConfigForm('settings');

        require_once('cclib/cc-template.inc');
        $skins   = CCTemplateAdmin::GetSkins(true);
        $format_files = CCTemplateAdmin::GetFormats();
        foreach($format_files as $ffile)
            $formats[$ffile] = 'formats/' . preg_replace( '/\..*$/', '', basename($ffile) );
        $str_profiles = CCSkinAdmin::_read_string_profiles();
        foreach($str_profiles as $prof)
            $options[$prof['file']] = $prof['caption'];

        $fields['skin-file'] =
            array( 'label'       => _('Skin'),
                   'form_tip'    => _('Default skin for this view'),
                   'formatter'   => 'select',
                   'options'     => $skins,
                   'flags'       => CCFF_POPULATE );
        $fields['string_profile'] =
            array( 'label'       => _('String Profile'),
                   'form_tip'    => _('Default profile for display strings'),
                   'formatter'   => 'select',
                   'options'     => $options,
                   'flags'       => CCFF_POPULATE );
        $fields['list_files'] =
            array( 'label'       => _('Upload Listing Format'),
                   'form_tip'    => _('Use this template when listing multiple files'),
                   'formatter'   => 'select',
                   'value'       => 'ccskins/shared/formats/upload_list.php',
                   'options'     => $formats,
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
        $fields['list_file'] =
            array( 'label'       => _('Upload Page Format'),
                   'form_tip'    => _('Use this template when showing a single upload page'),
                   'formatter'   => 'select',
                   'value'       => 'ccskins/shared/formats/upload_page.php',
                   'options'     => $formats,
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
        $fields['html_form'] =
            array( 'label'       => _('Default Form Template'),
                   'form_tip'    => _('Default template for displaying forms'),
                   'formatter'   => 'select',
                   'value'       => 'html_form.php/html_form',
                   'options'     => array( 'html_form.php/html_form' => 'html_form.php/html_form' ),
                   'flags'       => CCFF_POPULATE_WITH_DEFAULT );
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
        $fields['max-listing'] =
            array( 'label'       => _('Max Items Per Page'),
                   'form_tip'    => _('Maximum number of uploads, users in a listing'),
                   'class'       => 'cc_form_input_short',
                   'formatter'   => 'textedit',
                   'flags'       => CCFF_POPULATE | CCFF_REQUIRED);

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Skin Options Changes'));
        $this->SetModule(ccs(__FILE__));
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
        $this->CCEditConfigForm('skin-properties');

        $config =& CCConfigs::GetTable();
        $props = $config->GetConfig('skin-design');

        $fields = array();
        foreach( $props as $id => $value )
        {
            $fields[$id] = array(
                    'label'     => $value['label'],
                    'formatter' => 'skin_prop',
                    'macro'     => $value['editor'],
                    'scroll'    => !empty($value['scroll']),
                    'props'     => $value['properties'],
                    'flags'     => CCFF_POPULATE,
                    );
        }

        $this->SetHiddenField( 'properties', '', CCFF_HIDDEN );
        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Skin Layout Changes'));
        $this->SetModule(ccs(__FILE__));

        CCPage::AddScriptLink('js/skin_editor.js',true);
    }

    function generator_skin_prop($varname,$value,$class='')
    {
        return $this->generator_metalmacro($varname,$value,$class);
    }

    function validator_skin_prop($fieldname)
    {
        $props = $this->GetFormFieldItem($fieldname,'props');
        $valname = empty($_POST[$fieldname]) ? $props[0]['id'] : $_POST[$fieldname];
        $propval = null;
        foreach( $props as $P )
        {
            if( $P['id'] == $valname )
            {
                $propval = $P;
                break;
            }
        }
        $config_props = $this->GetFormValue('properties');
        $config_props[$fieldname] = $propval;
        $this->SetFormValue('properties',$config_props);
        return true;
    }
}


/**
 *
 */
class CCAdminColorSchemesForm extends CCGridForm
{
    /**
     * Constructor
     */
    function CCAdminColorSchemesForm($schemes)
    {
        $this->CCGridForm();

        $heads = array( _('Display'), _('Internal'), _('Scheme') );
        $this->SetColumnHeader($heads);


        foreach( $schemes['properties'] as $scheme)
        {
            $keyname = $scheme['id'];
            $a = array(
                  array(
                    'element_name'  => "grp[$keyname][caption]",
                    'value'      => $scheme['caption'],
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][id]",
                    'value'      => $scheme['id'],
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][css]",
                    'value'      => $scheme['css'],
                    'formatter'  => 'textarea',
                    'expanded'   => true,
                    'flags'      => CCFF_REQUIRED ),
                );

            $this->AddGridRow( $keyname, $a );
        }

        $S = 'new[%i%]';
        $a = array(
              array(
                'element_name'  => $S . '[caption]',
                'value'      => 'Friendly name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
              array(
                'element_name'  => $S . '[id]',
                'value'      => 'system_name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[css]',
                'value'      => '',
                'expanded'   => true,
                'formatter'  => 'textarea',
                'flags'      => CCFF_POPULATE ),
            );

        $this->AddMetaRow($a, _('Add Scheme') );
        $this->SetSubmitText(_('Submit Scheme Changes'));
    }

}

class CCConfirmImportForm extends CCForm
{
    function CCConfirmImportForm()
    {
        $this->CCForm();
        $this->SetHelpText(_('Importing from properties.xml will destroy custom color schemes and other settings.') .
                           _('This action can not be reversed...'));
        $this->SetSubmitText( _("Are you sure you want to import?"));
        $this->SetHandler( ccl('admin','skins','import','confirm') );
    }
}

/**
* Edit and maintain color schemes
* 
*/
class CCSkinAdmin
{
    function Admin()
    {
        CCPage::SetTitle(_('Configure Skins'));

        global $CC_CFG_ROOT;
        if( $CC_CFG_ROOT != CC_GLOBAL_SCOPE )
        {
            $star = '<span style="color:red;font-size: 13px;">*</span> ';
            $help = sprintf( _('Items marked %sare global settings. Otherwise only %s is affected'), $star, '<b>' . $CC_CFG_ROOT . '</b>' );
            CCPage::PageArg('client_menu_help', $help );
        }
        else
        {
            $star = '';
        }

        $args[] = array( 'action'    => ccl('admin','skins','settings'),
                         'menu_text' => _('Settings'),
                         'help'      => _('Pick a skin, theme, listing choices, etc.') );

        $args[] = array( 'action'    => ccl('admin','skins','layout'),
                         'menu_text' => _('Layouts'),
                         'help'      => _('Pick a fonts, color scheme, layouts, tab placement, etc.') );

        $args[] = array( 'action'    => ccl('admin','colors'),
                         'menu_text' => $star . _('Manage Color Schemes'),
                         'help'      => _('Create and manage color schemes') );

        $args[] = array( 'action'    => ccl('admin','skins','create' ),
                         'menu_text' => $star . _('Create Skin'),
                         'help'      => _('Create a new skin') );

        $args[] = array( 'action'    => ccl('admin','skins','import' ),
                         'menu_text' => $star . _('Import Layouts'),
                         'help'      => _('Import layouts from \'properties.xml\' in your skins path (destructive!)') );

        CCPage::PageArg('client_menu',$args,'print_client_menu');

    }

    function Import($confirm='')
    {
        if( empty($confirm) )
        {
            $form = new CCConfirmImportForm();
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->_read_properties(true);
            CCPage::SetTitle(_('Import Skins Properties'));
            CCPage::Prompt(_('Skin properties imported'));
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
        CCPage::SetTitle(_('Create a Skin'));
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
        $config =& CCConfigs::GetTable();
        $schemes = $config->GetConfig('skin-design');
        $form = new CCAdminColorSchemesForm($schemes['color-scheme']);
        if( empty($_POST['admincolorschemes']) || !$form->ValidateFields())
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            /* POST ---------------
                [grp] => Array
                        (
                            [mono] => Array
                                (
                                    [css] => .light_bg { background-color: #FFFFFF; }
                                             .light_border { border-colo

                $schemes-----------
                [color-scheme] => Array
                    (
                        [properties] => Array
                            (
                                [0] => Array
                                    (
                                        [caption] => Black and White
                                        [id] => mono
                                        [css] => .light_bg { background-color: #FFFFFF; }
                                                 .light_border { border-color: #FFFFFF; }
            */
            CCUtil::Strip($_POST);

            foreach( $schemes['color-scheme']['properties'] as $k => $v )
            {
                if( !empty($_POST['grp'][$v['id']]) )
                    $schemes['color-scheme']['properties'][$k]['css'] = $_POST['grp'][$v['id']]['css'];
            }

            if( !empty($_POST['new']) )
            {
                $schemes['color-scheme']['properties'] += $_POST['new'];
            }
            
            $config->SaveConfig('color-design',$schemes,CC_GLOBAL_SCOPE,false);

            CCPage::Prompt(_('Color scheme changes saved'));
        }
    }


    function OnAdminMenu( &$items, $scope )
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'skin-properties'   => array( 'menu_text'  => _('Skin'),
                             'menu_group' => 'configure',
                             'help'      => _('Choose a skin, theme, layout, colors, fonts, etc.'),
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 3,
                             'action' =>  ccl('admin','skins')
                             ),
            );
    }

    function & _read_string_profiles()
    {
        $file = CCTemplate::Search('strings.xml',true) or die('Can\'t find string profiles.xml');
        $text = file_get_contents($file);
        $map = array( '/<string_profiles>/' => '$profiles = array(' . "\n",
                      '#<(id|file|caption)>(.*)</\1>#Ums' => "'$1' => '$2',\n",
                      '#<profile>#' => ' array ( ',
                      '#</profile>#' => ' ), ',
                      '#</string_profiles>#' => ' ); '
                    );

        $php = preg_replace(array_keys($map),array_values($map),$text);
        eval($php);
        return $profiles;
    }

    function & _read_properties($save=false)
    {
        $file = CCTemplate::Search('properties.xml',true) or die('Can\'t find properties.xml');
        $text = file_get_contents($file);
        $map = array( '/<properties>/' => '$sections = array(' . "\n",
                      '/<section type="([^"]+)"\s+label="([^"]+)"\s+editor="([^"]+)"\s+scroll="([^"]+)">/U' => 
                                "  '$1' => array(\n   'label' => '$2',\n   'editor' => '$3',\n   'scroll' => '$4',\n'properties' => array( \n",
                      '/<property>/' => '    array(' . "\n",
                      '#<caption>(.*)</caption>#U' => "   'caption' => '$1',\n",
                      '#<image>(.*)</image>#U' => "   'img' => '$1',\n",
                      '#<id>(.*)</id>#U' => "   'id' => '$1',\n",
                      '#</?markup>#' => '',
                      '#<(css|php|scriptlink|script)>(.*)</\1>#Ums' => "'$1' => '$2',\n",
                      '#</property>#' => ' ), ',
                      '#</section>#' => ' ), ), ',
                      '#</properties>#' => ' ); '
                    );

        $php = preg_replace(array_keys($map),array_values($map),$text);
        eval($php);
        if( $save )
        {
            $config =& CCConfigs::GetTable();
            $config->SaveConfig('skin-design',$sections,CC_GLOBAL_SCOPE,false);
        }
        //$x = split("\n",$php);
        //CCDebug::PrintVar($x);
        return $sections;
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
        CCEvents::MapUrl( 'admin/skins/import',     array('CCSkinAdmin', 'Import'),
            CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}


?>