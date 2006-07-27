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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCAdmin' , 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCAdmin' , 'OnBuildMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCAdmin' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCAdmin' , 'OnGetConfigFields') );

/**
 * Derive from this class to let the user modify the app's config 
 *
 * There are many derivations of this, one for each group of config variables.
 * When you derive from this form, it will save the values here into the config
 * table for use in all subsequent sessions. The derivations
 * do not have to perform any action on user submit. The global config affects
 * all users so typically only administrators will see derivations of this form.
 *  
 * <code>
 *
 * // Derive from the base 
 *class CCMyAdminForm extends CCEditConfigForm
 *{
 *  function CCMyAdminForm()
 *  {
 *    $type_name = 'my-settings-type';
 *    $this->CCEditConfigForm($type_name); 
 *    $fields = array( 
 *     'mySettting' =>  // name of the setting
 *      array(  
 *       'label'     => 'Set this setting',
 *       'form_tip'  => 'make it good',
 *       'value'     => 'Admin',
 *       'formatter' => 'textedit',
 *       'flags'     => CCFF_POPULATE | CCFF_REQUIRED ),
 *      );
 *
 *    $this->AddFormFields($fields);
 *   }
 *}
 *
 *
 * // Then later in the code when it's time to call it up, simply do:
 *function ShowMyAdminForm()
 *{
 *  CCPage::SetTitle('My Admin Form');
 *  $form = new CCMyAdminForm();
 *  CCPage::AddForm( $form->GenerateForm() );
 *}
 *
 * // Still later you can retreive the user's setting:
 *function DoMyStuff()
 *{
 *  $configs =& CCConfigs::GetTable();
 *  $settings = $configs->GetConfig('my-settings-type');
 *
 *  $value = $settings['mySetting'];
 *
 *  ///...
 *}
 *</code>
 *
 *
 */
class CCEditConfigForm extends CCForm
{
    /**#@+
    /* @access private
    /* @var string
    */
    var $_typename;
    var $_scope;
    /**#@-*/

    /**
     * Constructor
     *
     * @param string $config_type The name of the settings group (i.e. 'menu')
     * @param string $scope CC_GLOBAL_SCOPE or a specific vroot (blank means current)
     */
    function CCEditConfigForm($config_type,$scope='')
    {
        $this->CCForm();
        $this->SetHandler( ccl('admin', 'save') );
        $classname = __CLASS__;
        $this->SetHiddenField( '_name', get_class($this), CCFF_HIDDEN | CCFF_NOUPDATE );
        $this->_typename = $config_type;
        $this->_scope = $scope;
    }

    /**
     * Overrides base class in order to populate fields with current contents of environment's config.
     *
     * @param boolean $hiddenonly 
     */
    function GenerateForm($hiddenonly = false)
    {
        $configs =& CCConfigs::GetTable();
        $values = $configs->GetConfig($this->_typename,$this->_scope);
        if( $values )
            $this->PopulateValues($values);
        return( parent::GenerateForm($hiddenonly) );
    }

    /**
    * Sets the config type (e.g. menu, settings, navpages, etc.) for the data (also done from ctor)
    * 
    * 
    * @param string $config_type The name of the settings group (i.e. 'menu')
    */
    function SetConfigType($config_type)
    {
        $this->_typename = $config_type;
    }

    /**
    * Saves this forms data to the proper type of the current scope
    * 
    * @see CCConfigs::SaveConfig()
    */
    function SaveToConfig()
    {
        $configs =& CCConfigs::GetTable();
        $this->GetFormValues($values);
        $configs->SaveConfig($this->_typename, $values, $this->_scope);
    }
}

/**
* Displays global configuration options.
*
*/
class CCAdminConfigForm extends CCEditConfigForm
{
    /**
    * Constructor
    * 
    * This will invoke the CC_EVENT_GET_CONFIG_FIELDS 
    * with CC_GLOBAL_SCOPE to allow any
    * module to store/retrieve/edit site-wide settings 
    * 
    */
    function CCAdminConfigForm()
    {
        $this->CCEditConfigForm('config');
        $fields = array();
        CCEvents::Invoke( CC_EVENT_GET_CONFIG_FIELDS, array( CC_GLOBAL_SCOPE, &$fields ) );
        $this->AddFormFields($fields);
   }
}

/**
* Displays local configuration options.
*
*/
class CCAdminSettingsForm extends CCEditConfigForm
{
    /**
    * Constructor
    * 
    * This will invoke the CC_EVENT_GET_CONFIG_FIELDS 
    * with CC_LOCAL_SCOPE to allow any
    * module to store/retrieve/edit settings in just this config root
    * 
    */
    function CCAdminSettingsForm()
    {
        $this->CCEditConfigForm('settings');
        $fields = array();
        CCEvents::Invoke( CC_EVENT_GET_CONFIG_FIELDS, array( CC_LOCAL_SCOPE, &$fields ) );
        $this->AddFormFields($fields);
   }
}

/**
* Allows admins to create new virtual/config roots
*
*/
class CCAdminMakeCfgRootForm extends CCForm
{
    /**
    * Constructor
    *
    */
    function CCAdminMakeCfgRootForm()
    {
        $this->CCForm();

        $fields = array(
            'newcfgroot' => array(
                'label'       => 'Name of virtual root',
                'form_tip'    =>  'Must be characters and numbers only',
                 'class'      => 'cc_form_input_short',
                 'flags'      => CCFF_REQUIRED,
                 'formatter'  => 'cfg_root' ),
             );

        $this->AddFormFields($fields);
        $url = ccl( 'viewfile', 'howtovirtual.xml' );
        $this->SetHelpText("Read more about why you would want to do this and what the consequences are <a href=\"$url\">here</a>.");
    }

    /**
    * noop generator, required because we have a special validator
    * 
    * @see CCAdminMakeCfgRootForm::validataor_cfg_root()
    * @see CCForm::generator_textedit()
    *
    * @param string $varname Name of the HTML field
    * @param string $value   value to be published into the field
    * @param string $class   CSS class (rarely used)
    * @return string $html HTML that represents the field
    */
    function generator_cfg_root($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }

    /**
    * Handles validator for HTML field, called during ValidateFields()
    * 
    * Use the 'maxlenghth' field to limit user's input
    * 
    * On user input error this method will set the proper error message
    * into the form
    * 
    * @see CCForm::ValidateFields()
    * 
    * @param string $fieldname Name of the field will be passed in.
    * @return bool $ok true means field validates, false means there were errors in user input
    */
    function validator_cfg_root($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);
            if( preg_match('/[^a-z0-9]/i',$value) || (strlen($value) > 25) )
            {
                $this->SetFieldError($fieldname, 'Must be characters and numbers, no more than 25');
                return(false);
            }
            $configs =& CCConfigs::GetTable();
            $where['config_scope'] = $value;
            if( file_exists($value) || $configs->CountRows($where) > 0 )
            {
                $this->SetFieldError($fieldname, 'That virtual root already exists');
                return(false);
            }
            return(true);
        }
        return(false);
    }
}

/**
* This form edits the raw configation data
*
* This is not on any menu, admins can reach it via /main/admin/edit
*
*/
class CCAdminRawForm extends CCGridForm
{
    /**
    * Constructor
    *
    **/
    function CCAdminRawForm()
    {
        $this->CCGridForm();

        $heads = array( "Setting", "Value" );
        $this->SetColumnHeader($heads);

        $configs =& CCConfigs::GetTable();
        $configs->SetSort('config_scope,config_type');
        $rows = $configs->QueryRows('');

        foreach( $rows as $row )
        {
            $id   = $row['config_id'];
            $arr  = unserialize( $row['config_data'] );
            $c    = count($arr);
            $keys = array_keys($arr);

            if( !$keys || (is_array( $arr[$keys[0]] ) ||  is_object( $arr[$keys[0]] )) )
                continue;

            for( $i = 0; $i < $c; $i++ )
            {
                $name = $keys[$i];
                $a = $this->_make_field($row,$id,$i,$name,$arr[$name]);
                $uid = $name . '_' . $id . '_' . $i;
                $this->AddGridRow( $uid, $a );
            }
        }

        $this->SetSubmitText('Save Configuration');
        $this->SetHelpText("Just be careful what you do here, it's easy to 'break the site'");
    }

    /**
    * Local helper
    *
    * @access private
    */
    function _make_field($row,$id,$i,$name,$value)
    {
        if( strchr($value,"\n") )
        {
            $formatter = 'textarea';
        }
        else
        {
            $formatter = 'textedit';
        }
        $tname = $row['config_scope'] . '::' . $row['config_type'] . '[' . $name . ']';
        $class = intval($value) ? 'cc_form_input_short' : '';
        $a = array(
                  array(
                    'element_name'  => 'cfg_' . $id . '_' . $i,
                    'value'      => $tname,
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_STATIC ),
                  array(
                    'element_name'  => "cfg[$id][$name]",
                    'value'      => htmlspecialchars($value),
                    'formatter'  => $formatter,
                    'class'      => $class,
                    'flags'      => CCFF_NONE ),
                );

        return($a);
    }
}


/**
* Basic admin API and system event watcher.
* 
*/
class CCAdmin
{
    function Site()
    {
        global $CC_CFG_ROOT;

        $global_items = array();
        CCEvents::Invoke(CC_EVENT_ADMIN_MENU, array( &$global_items, CC_GLOBAL_SCOPE ) );
        $local_items = array();
        CCEvents::Invoke(CC_EVENT_ADMIN_MENU, array( &$local_items, CC_LOCAL_SCOPE) );
        CCPage::SetTitle("Administer ccHost Site");
        $args['global_title'] = 'Global Site Settings';
        $args['global_help']  = 'These settings affect the entire site';
        $args['global_items'] = $global_items;
        $args['local_title'] = 'Virtual Root Settings';
        $configs =& CCConfigs::GetTable();
        $roots = $configs->GetConfigRoots();
        $root_list = array();
        foreach( $roots as $root )
        {
            $root_list[] = array( 'text' => $root['scope_name'] . ' (' . $root['config_scope'] . ')' ,
                                  'cfg' => $root['config_scope'],
                                  'selected' => $root['config_scope'] == $CC_CFG_ROOT );
        }
        $args['config_roots'] = $root_list;
        $args['local_help']  = 'Edit the settings for virtual root: ';
        if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
        {
            $args['local_hint'] = 'Some of these settings may have been over written in other virtual roots';
        }
        else
        {
            $config_names = array_keys($local_items);
            $star = ' <span style="color:red;font-size:larger;">*</span>';
            foreach( $config_names as $config_name )
            {
                $where['config_type'] = $config_name;
                $where['config_scope'] = $CC_CFG_ROOT;
                if( $configs->CountRows($where) )
                    $local_items[$config_name]['menu_text'] .= $star;
            }
            $args['local_hint'] = $star . ' means this setting over writes the main site\'s values';
        }

        $args['local_items'] = $local_items;
        CCPage::PageArg('admin_menu', $args, 'admin_menu_page');
    }

    /**
    * This form edits the raw configation data
    *
    * This is not on any menu, admins can reach it via /main/admin/edit
    *
    * @see CCAdminRawForm::CCAdminRawForm()
    */
    function Deep()
    {
        CCPage::SetTitle("Edit Raw Configuation Data");
        if( empty($_POST['adminraw']) )
        {
            $form = new CCAdminRawForm();
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $cfgs = $_POST['cfg'];
            $configs =& CCConfigs::GetTable();
            foreach( $cfgs as $id => $data )
            {
                CCUtil::StripSlash($data);
                $where['config_id'] = $id;
                $where['config_data'] = serialize($data);
                $configs->Update($where);
            }

            CCPage::Prompt("Configuration Changes Saved");
        }
    }

    /**
    * Prompts the user for and creates a new config root.
    *
    */
    function NewConfigRoot()
    {
        if( !CCUser::IsAdmin() )
            return;

        CCPage::SetTitle("Create New Virtual Root");
        $form = new CCAdminMakeCfgRootForm();
        if( !empty($_POST['adminmakecfgroot']) && $form->ValidateFields() )
        {
            $form->GetFormValues($fields);
            $new_cfg_root = $fields['newcfgroot'];
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $configs->SaveConfig('settings',$settings,$new_cfg_root);
            CCUtil::SendBrowserTo( ccc( $new_cfg_root, 'admin', 'settings' ) );
        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * @access private
    */
    function _wheres_home()
    {
        global $CC_CFG_ROOT;
        
        $me = $_SERVER['SCRIPT_URL'];
        if( !empty($me) )
        {
            if( preg_match( "%^(.+/)$CC_CFG_ROOT%", $me, $m ) )
            {
                $base = $m[1];
            }
        }

        if( empty($base) )
            $base = '/';

        return $base;
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $surl = CCAdmin::_wheres_home();

            $pretty_help =<<<END
In order to enable Rewrite rules ('pretty URLs') you must include the following lines
in your Apache configuration (virtual host or .htaccess):
<div style="white-space:pre;font-family:Courier New, courier, serif">
RewriteEngine On
RewriteBase $surl
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ {$surl}index.php?ccm=/$1 [L,QSA]
</div>
END;
    
            $perms = array(
                    0777 => 'World can access (0777)',
                    0775 => 'Owners and group only (0775)',
                    0755 => 'Owners only (0755)' );

            $fields['cookie-domain'] =
               array( 'label'       => 'Cookie Domain',
                       'form_tip'      => 'This is the name used to set cookies on the client machine. Recommend  to leave this blank unless you are having problems.',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE  ); // do NOT require cookie domain, blank is legit

            $fields['file-perms'] =
               array( 'label'       => 'Default File Permissions',
                       'form_tip'      => 
                                 'chmod() access mask to use when writing new files. Do not edit this ' .
                                 'unless you understand UNIX permissions, Apache, PHP CGI mode, ' .
                                 'etc. Changing this value will affect future writes.',
                       'value'      => '',
                       'options'    => $perms,
                       'formatter'  => 'select',
                       'flags'      => CCFF_POPULATE  ); // do NOT require cookie domain, blank is legit

            $fields['pretty-urls-help'] = 
               array( 'label'       => '',
                       'value'      => $pretty_help,
                       'formatter'  => 'statictext',
                       'flags'      => CCFF_STATIC | CCFF_NOUPDATE);

            $fields['pretty-urls'] = 
               array( 'label'       => 'Use URL Rewrite Rules',
                       'form_tip' 
                               => 'Check this if you want to use mod_rewrite for \'pretty\' URLs.',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE);
        }
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
            $items += array( 
                'adminhelp'   => array( 'menu_text'  => 'Admin Help',
                                 'menu_group' => 'configure',
                                 'help'      => 'Help on configuring the site',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10002,
                                 'action' =>  ccl('viewfile','adminhelp.xml')
                                 ),
                'virtualhost'   => array( 'menu_text'  => 'Virtual ccHost',
                                 'menu_group' => 'configure',
                                 'help' => 'Create a new virtual root',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10001,
                                 'action' =>  ccl('admin','cfgroot')
                                 ),
                'adminadvanced'   => array( 'menu_text'  => 'Global Setup',
                                 'menu_group' => 'configure',
                                 'help'  => 'Cookies, ban message, admin email, 3rd party add ins, etc.',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10000,
                                 'action' =>  ccl('admin','setup')
                                 ),
                    );
        }
        else
        {
            $items += array(
                'settings'   => array( 'menu_text'  => 'Settings',
                                 'menu_group' => 'configure',
                                 'help' => 'Style sheets, admins, home page, etc.',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 1,
                                 'action' =>  ccl('admin','settings')
                                 ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $items = array( 
            'configpage'   => array( 'menu_text'  => 'Manage Site',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 1,
                             'action' =>  ccp('admin','site')
                             ),
                );

        CCMenu::AddItems($items);

        $groups = array(
                    'configure' => array( 'group_name' => 'Admin',
                                          'weight'    => 100 ),
                    );

        CCMenu::AddGroups($groups);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/site',     array('CCAdmin', 'Site'),       CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/save',     array('CCAdmin', 'SaveConfig'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/setup',    array('CCAdmin', 'Setup'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/settings', array('CCAdmin', 'Settings'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/edit',     array('CCAdmin', 'Deep'),       CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/cfgroot',  array('CCAdmin', 'NewConfigRoot'),       CC_ADMIN_ONLY );
    }

    /**
    * Handler for /admin/setup
    *
    * @see CCAdminConfigForm::CCAdminConfigForm()
    */
    function Setup()
    {
        CCPage::SetTitle('Global Site Setup');
        $form = new CCAdminConfigForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handler for /admin/settings
    *
    * @see CCAdminConfigForm::CCAdminConfigForm()
    */
    function Settings()
    {
        global $CC_CFG_ROOT;

        CCPage::SetTitle("Settings for '$CC_CFG_ROOT'");
        $form = new CCAdminSettingsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Method called when the user submits a config editing form.
    *
    * On rare occasions you may want to do special processing on user 
    * submit of an admin/config. At some point you call this to
    * save the new config values. 
    * @see CCEditConfigForm::CCEditConfigForm()
    */
    function SaveConfig($form = '')
    {
        CCPage::SetTitle("Saving Configuration");
        if( empty($form) )
        {
            $form_name = CCUtil::StripText($_REQUEST['_name']);
            if( !class_exists($form_name) )
            {
                $file = CCUtil::StripText($_REQUEST['_file']);
                require_once($file);
            }
            $form = new $form_name();
        }

        if( $form->ValidateFields() )
        {
            $form->SaveToConfig();
            CCPage::Prompt("Changes saved");
        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

}


/**
* @access private
*/
function cc_check_site_enabled()
{
    global $CC_GLOBALS;

    $enable_password = $CC_GLOBALS['enable-password'];

    if( !empty($_COOKIE[CC_ENABLE_KEY]) )
    {
        if( $_COOKIE[CC_ENABLE_KEY] == $enable_password  )
        {
            return;
        }
    }

    if( !empty($_POST[CC_ENABLE_KEY]) )
    {
        if( $_POST[CC_ENABLE_KEY] == $enable_password  )
        {
            setcookie( CC_ENABLE_KEY, $enable_password , time()+60*60*24*14, '/' );
            return;
        }
    }

    $name = CC_ENABLE_KEY;
    $self = $_SERVER['PHP_SELF'];
    $html = "";
    $html .=<<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>ccHost</title>
        <link rel="stylesheet" type="text/css" href="cctemplates/skin-ccmixter.css" title="Default Style"/>
</head>
<body>
<div class="cc_all_content" >
    <div class="cc_content">
        <div class="cc_form_about">
        Site is under construction. 
        </div>
<form action="$self" method="post" class="cc_form" >
<table class="cc_form_table">
    <tr class="cc_form_row">
        <td class="cc_form_label">Admin password:</td>
        <td class="cc_form_element">
            <input type='password' id="$name" name="$name" /></td>
    </tr>
    <tr class="cc_form_row">
        <td class="cc_form_label"></td>
        <td class="cc_form_element">
            <input type='submit' value="submit" /></td>
    </tr>
</table>
</form></div></div>
</body>
</html>
END;
    print($html);
    exit;
}

?>