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
* Base configuration and initialization 
*
* @package cchost
* @subpackage core
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*  Wrapper for config table
*
*/
class CCConfigs extends CCTable
{
    /**
    * Constructor (should not be used, use GetTable() instead)
    *
    * @see GetTable
    */
    function CCConfigs()
    {
        $this->CCTable('cc_tbl_config','config_id');
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $table;
        if( !isset($table) )
            $table = new CCConfigs();
        return($table);
    }

    /*
    * @access private
    */
    function & _cache()
    {
        static $_cache = array();
        return $_cache;
    }

    /**
    * Get the configuration settings of a type for a given scope
    *
    * Configuration settings are grouped by 'type' within a given 'scope'
    *
    * Type can be 'config' (which only applies to the global scope) or
    * things like 'menu', 'licenses', 'formats-allowed', or whatever
    * a given module wants to store here.
    *
    * Scope is either CC_GLOBAL_SCOPE or a custom scope determined
    * by the user (typically the site's admin). The scope is determined
    * by the first part of the url after the base.
    *
    * http://example.com/myscope/somecommand/param
    *
    * In this case 'myscope' is the scope used to retrive the given
    * settings values.
    *
    * The global scope is called 'main' in the URL.
    *
    * If a given type is requested for a non-global scope then the
    * values for that type in CC_GLOBAL_SCOPE (main) is used.
    *
    * @param string $type Type of data being requested
    * @param string $scope Scope being requested. If null the current scope is used.
    * @returns array Array containing variables matching parameter's request
    */
    function GetConfig($type,$scope = '')
    {
        global $CC_CFG_ROOT;
        if( empty($scope) )
            $scope = $CC_CFG_ROOT;

        $cache =& $this->_cache();

        if( empty($cache[$scope][$type]) )
        {
            $where['config_type'] = $type;
            $where['config_scope'] = $scope;
            $arr = $this->QueryItem('config_data',$where);
            if( $arr )
            {
                $arr = unserialize($arr);
            }
            elseif( $scope != CC_GLOBAL_SCOPE )
            {
                $arr = $this->GetConfig($type,CC_GLOBAL_SCOPE);
            }
            else
            {
                $arr = array();
            }
            
            $arr = $this->_hash_to_string($type,$arr);

            $cache[$scope][$type] = $arr;
        }

        return $cache[$scope][$type];
    }

    /**
    * Save an array of settings of a given type and assign it to a scope
    *
    * @see GetConfig
    * 
    * @param string $type Type of data being saved (e.g. 'config', 'menu', etc.)
    * @param array  $arr  Name/value pairs in array to be saved
    * @param string $scope Scope to assigned to. If null the current scope is used. If $type is 'config' it is ALWAYS saved to CC_GLOBAL_SCOPE
    * @param boolean $merge true means merge this array with existing values, false means delete all previous settings.
    */
    function SaveConfig($type,$arr,$scope='',$merge = true)
    {
        global $CC_CFG_ROOT;

        if( $type == 'config' )
            $scope = CC_GLOBAL_SCOPE;
        elseif( empty($scope) )
            $scope = $CC_CFG_ROOT;

        $where['config_type'] = $type;
        $where['config_scope'] = $scope;
        $key = $this->QueryKey($where);
        $original = $arr;
        $arr = $this->_string_to_hash($type,$arr);
        $where['config_data'] = serialize($arr);
        if( $key )
        {
            $where['config_id'] = $key;
            if( $merge )
            {
                $old = $this->QueryItemFromKey('config_data', $key);
                $old = unserialize($old);
                $where['config_data'] = serialize(array_merge($old,$arr));
            }

            $this->Update($where);
        }
        else
        {
            $this->Insert($where);
        }

        $cache =& $this->_cache();
        if( $merge && !empty($cache[$where['config_scope']][$where['config_type']]))
        {
            $loc =& $cache[$where['config_scope']][$where['config_type']];
            $loc = array_merge($loc,$original);
        }
        else
        {
            $cache[$where['config_scope']][$where['config_type']] = $original;
        }
           
    }

    /**
    * Internal helper for initializes globals
    *
    */
    function Init()
    {
        global $CC_GLOBALS, $CC_CFG_ROOT;

        $configs =& CCConfigs::GetTable();

        $CC_GLOBALS = $configs->GetConfig('config', CC_GLOBAL_SCOPE);

        // First argument in ccm is the current virtual root (aka config root)
        //
        // note: ?ccm= will be in _REQUEST even if pretty urls are turned on,
        // the pretty urls are translated before executing php
        //
        $regex = '%/([^/\?]+)%';
        preg_match_all($regex,CCUtil::StripText($_REQUEST['ccm']),$a);
        $A =& $a[1];

        $configs->SetCfgRoot( empty($A[0]) ? CC_GLOBAL_SCOPE : $A[0] );

        $CC_GLOBALS['home-url'] = ccl();

        $settings = $configs->GetConfig('settings');
        $CC_GLOBALS['skin']      = $settings['skin'];
        $CC_GLOBALS['skin-file'] = $settings['skin-file'];
        $CC_GLOBALS['skin-map']  = preg_replace( '/(skin-[^\.]+)(\.xml)?\.php/', '$1-map$2.php', $CC_GLOBALS['skin-file'] );

        // allow admins to turn off user interface
        //
        if( $CC_GLOBALS['site-disabled'] )
            cc_check_site_enabled();
    }

    /**
    * Sets the global $CC_CFG_ROOT and browser cookie to a valid root.
    * 
    * If $rootname is blank sets the config root to CC_GLOBAL_SCOPE
    * (which is also the default root). Validates that the parameter
    * is a valid root created by an admin.
    * 
    * The vast majority of the time the name comes from the calling
    * URL (e.g. Given the URL http://cchost.org/thevroot/viewfile/home.xml
    * this method will be called with 'thevroot'). However there are
    * times where it must be set programmatically, especially when
    * interfacing with third party software.
    * 
    * This function has to be set very early in the processing of
    * of the site since much of the site depends on configuration
    * information locked into the 'current' config root.
    * 
    * The cookie is set so that 3rd party software (like blogs ands
    * forums) running on this server can get a hint where ccHost is
    * at.
    * 
    * @param string $rootname Name of config root to set to
    */
    function SetCfgRoot($rootname)
    {
        global $CC_CFG_ROOT;
        if( empty($rootname) )
        {
            $rootname = CC_GLOBAL_SCOPE;
        }
        else
        {
            $where['config_scope'] = $rootname;
            $rows = $this->QueryRows($where, 'config_scope');
            if( empty($rows) || ($rows[0]['config_scope'] != $rootname) ) // check for case
            {
                CCUtil::Send404(true);
            }
        }
        $CC_CFG_ROOT = $rootname;
        cc_setcookie('cc_cfg_root',$rootname,null);
    }

    /**
    * Sets a specific values into a particular configuration.
    * 
    * (I think this method may be a bug waiting to happen)
    * 
    * If scope is not specified this value will be pushed
    * into ALL the configurations for the given 'type'.
    * 
    * @param string $type Category of setting (i.e. menu)
    * @param string $name Name of the setting
    * @param mixed  $value Value to be set
    * @param string $scope Specific scope to modify, or if null, ALL scopes
    */
    function SetValue($type,$name,$value,$scope='')
    {
        // If you don't do this through SaveConfig, you
        // bypass this session's config cache and translations

        $arr[$name] = $value;
        $this->SaveConfig($type,$arr,$scope,true); // true means merge
    }

    /**
    * Check if a particular scope has a category of config settings.
    *
    * This method helps determine if a given setting will override
    * the global settings.
    * 
    * @param string $type Category of config setting (i.e. menu)
    * @param string $scope Scope to be checked
    * @returns bool $has_type true/false
    */
    function ScopeHasType($type,$scope)
    {
        $where['config_scope'] = $scope;
        $where['config_type']  = $type;
        $count = $this->CountRows($where);
        return !empty($count);
    }

    /** 
    * Nuke configuration settings of a given type for a specific scope
    *
    * @param string $type Category of config setting (i.e. menu)
    * @param string $scope Scope to be checked
    */
    function DeleteType($type,$scope)
    {
        $where['config_scope'] = $scope;
        $where['config_type']  = $type;
        $this->DeleteWhere($where);
    }

    /*
    * Return an array of known virtual roots
    *
    * @return array $root_records 
    */
    function GetConfigRoots()
    {
        $sql = $this->_get_select('','DISTINCT config_scope');
        $roots = CCDatabase::QueryRows($sql);
        $count = count($roots);
        for( $i = 0; $i < $count; $i++ )
        {
            $scope = $roots[$i]['config_scope'];
            $where['config_scope'] = $scope;
            $where['config_type']  = 'ttag';
            $tags = $this->QueryItem('config_data',$where);
            if( !empty($tags) )
            {
                $tags = $this->CfgUnserialize('ttag',$tags);
                $title = $tags['site-title'];
            }
            else
            {
                $title = $scope;
            }
            $roots[$i]['scope_name'] = $title;
        }
        return $roots;
    }

    /*
    * Make sure the global cc-host-version is up to date
    * 
    * @param string $config_in_db Version string to check against code
    */
    function _check_version($config_in_db)
    {
        global $CC_GLOBALS;

        if( version_compare($config_in_db, CC_HOST_VERSION) == 0 )
            return;

        $this->SetValue('config', 'cc-host-version', CC_HOST_VERSION, CC_GLOBAL_SCOPE);
        $CC_GLOBALS['cc-host-version']  = CC_HOST_VERSION;
    }

    /*
    * @access private
    */
    function _hash($force=false)
    {
        static $_hash;
        if( $force || !isset($_hash) )
        {
            $where['config_type'] = 'strhash';
            $where['config_scope'] = CC_GLOBAL_SCOPE;
            $row = $this->QueryRow($where);
            if( empty($row) )
            {
                $_hash = array();
            }
            else
            {
                $_hash = unserialize($row['config_data']);

                // run 'em through gettext()
                $keys = array_keys($_hash);
                $count = count($_hash);
                for( $i = 0; $i < $count; $i++ )
                    $_hash[$keys[$i]] = _($_hash[$keys[$i]]);
            }
        }

        return $_hash;
    }

    /**
    * Mark config strings as needed translation.
    *
    * Call this method if you are repsonsible for a configuration 
    * structure and that structure has strings in them that need 
    * to be translated. This includes menu texts or any message
    * that is displayed or email out from your site.
    *<code>
    $fields['config_type'] =
    ( 
      'field_to_translate'       => true,    // string: form label
    );
    *
    </code>
    *
    *
    * In some cases the strings might be nested inside arrays. For
    * that case you should use an asterisk (*) at the array level:
    * 
    *<code>
    $fields['config_type'] =
    ( 
        '*' => 
            array(
                'field_to_translate'       => true,    // string: form label
                )
    );
    *
    </code>
    *
    * You can nest '*' arrays as many times as you need.
    *
    * @param array $fields Config string map segment
    */ 
    function AddCfgStringMap($fields)
    {
        $where['config_type'] = 'clangmap';
        $where['config_scope'] = CC_GLOBAL_SCOPE;
        $row = $this->QueryRow($where);
        if( empty($row) )
        {
            $where['config_data'] = serialize($fields);
            $this->Insert($where);
        }
        else
        {
            $data = unserialize($row['config_data']);
            $data = array_merge($data,$fields);
            $row['config_data'] = serialize($data);
            $this->Update($row);
        }
    }

    /*
    * @access private
    */
    function _get_clang_map()
    {
        $where['config_type'] = 'clangmap';
        $where['config_scope'] = CC_GLOBAL_SCOPE;
        $row = $this->QueryRow($where);
        if( empty($row) )
        {
            return array();
        }
        else
        {
            return unserialize($row['config_data']);
        }
    }

    /*
    * For manually converting config data using i18n smarts
    *
    * This is unsed only by modules that manipulate raw config 
    * data (which is very rare).
    *
    */
    function CfgSerialize($config_type,$data)
    {
        $data = $this->_string_to_hash($config_type,$data);
        return serialize($data);
    }

    /*
    * For manually converting config data using i18n smarts
    *
    * This is unsed only by modules that manipulate raw config 
    * data (which is very rare).
    *
    */
    function CfgUnserialize($config_type,$data)
    {
        if( is_string($data) )
            $data = unserialize($data);
        return $this->_hash_to_string($config_type,$data);
    }

    /*
    * @private
    */
    function _hash_to_string($type,$data)
    {
        if( $type == 'clangmap' || $type == 'strhash')
            return $data;

        $cmap = $this->_get_clang_map();
        $hash = $this->_hash();
        if( !empty($cmap[$type]) && !empty($hash) )
        {
            $h2s = new CCConfigHashToString($cmap[$type],$data,$hash);
            $data = $h2s->Run();
        }
        return $data;
    }

    /*
    * @private
    */
    function _string_to_hash($type,$data)
    {
        if( $type == 'clangmap' || $type == 'strhash')
            return $data;

        $cmap = $this->_get_clang_map();
        $hash = $this->_hash();
        if( !empty($cmap[$type]) )
        {
            $s2h = new CCConfigStringToHash($cmap[$type],$data,$hash);
            $data = $s2h->Run();
            $newhash = $s2h->GetNewHash();
            if( $newhash )
            {
                $args['config_type'] = 'strhash';
                $args['config_scope'] = CC_GLOBAL_SCOPE;
                $id = $this->QueryKey($args);
                $args['config_data'] = serialize($newhash);
                if( empty($id) )
                {
                    $args['config_id'] = $this->NextID();
                    $this->Insert($args);
                }
                else
                {
                    $args['config_id'] = $id;
                    $this->Update($args);
                }
                $this->_hash(true); // reset in memory cache
            }
        }
        return $data;
    }

    /**
    * Stores the system's default language map
    *
    * Basically this exists to satify new installs and 
    * update v_3_2f_vs3
    *
    * FWIW this is WAY too much special knowledge for this
    * to be here, but posting an event can't be done during
    * install. For now we'll have to rely on future updates
    * to call AddCfgStringMap.
    * 
    * 
    */
    function SaveDefaultCfgStringMap()
    {
        $map = array ( 

            'tab_pages' =>
            array( 
                    '*' => array (
                        '*' => array (
                            'text' => true,
                            'help'=> true,
                            ),
                        ),
             ),

            'config' =>  // config_type
             array( 
                    'ban-message'=> true,
                    'flag_msg' => true,
                    'ban-email' => true,
                 ),

            'channels' =>
                array(
                    '*' => array(
                        'text' => true,
                        )
                     ),

            'id3-tag-masks' =>
            array( 
                    'copyright'=> true,
             ),

            'throttle' =>
            array( 
                    'quota-msg'=> true,
             ),

            'ttag' =>
            array( 
                    'site-title'=> true,
                    'banner-html'=> true,
                    'site-description'=> true,
                    'footer'=> true,
                    'site-license'=> true,
                    'site-meta-description'=> true,
                    'site-meta-keywords'=> true,
                    'beta_message'=> true,
             ),

            'submit_forms' =>
            array( 
                    '*' => array (
                        'text'=> true,
                        'submit_type'=> true,
                        'help'=> true,
                        'form_help'=> true,
                        ),
             ),

            'groups' =>
            array( 
                    '*' => array (
                        'group_name'=> true,
                        ),
             ),

            'menu' =>
            array( 
                    '*' => array (
                        'menu_text'=> true,
                        ),
             ),
        ); 

        $this->SaveConfig('clangmap', $map,
                               CC_GLOBAL_SCOPE, false );

    }
}

/*
* Class used by CCConfig to i18n strings
*/
class CCConfigHashToString extends CCConfigi18nParser
{
    var $_data, $_hash, $_map;

    function CCConfigHashToString($map,$data,$hash)
    {
        $this->CCConfigi18nParser();
        $this->_data = $data;
        $this->_hash = $hash;
        $this->_map  = $map;
    }

    function Run()
    {
        $this->_do_level( '', '', $this->_map, $this->_data, array() );
        return $this->_data;
    }

    function OnConfigString( $d1, $d2, $data, $fieldname, $stack )
    {
        if( empty($this->_hash[$data[$fieldname]]) )
            return;
        $str = str_replace('\'', '\\\'', $this->_hash[$data[$fieldname]]);
        $stack[] = $fieldname;
        $index = "['" . join("']['",$stack) . "']";
        $estring = "\$this->_data{$index} = '$str';";
        eval($estring);
    }

}

/*
* Class used by CCConfig to i18n strings
*/
class CCConfigStringToHash extends CCConfigi18nParser
{
    var $_data, $_hash, $_map, $_changed;

    function CCConfigStringToHash($map,$data,$hash)
    {
        $this->CCConfigi18nParser();
        $this->_data = $data;
        $this->_hash = $hash;
        $this->_map  = $map;
        $this->_changed = false;
    }

    function Run()
    {
        $this->_do_level( '', '', $this->_map, $this->_data, array() );
        return $this->_data;
    }

    function GetNewHash()
    {
        return $this->_changed ? $this->_hash : array();
    }

    function OnConfigString( $d1, $d2, $data, $fieldname, $stack )
    {
        $str = $data[$fieldname];
        $hash = CCUtil::HashString($str);
        if( empty($this->_hash[$hash]) )
        {
            $this->_hash[$hash] = $str;
            $this->_changed = true;
        }
        $stack[] = $fieldname;
        $index = "['" . join("']['",$stack) . "']";
        $estring = "\$this->_data{$index} = '$hash';";
        eval($estring);
    }

}

/*
* Class used by CCConfig to i18n strings
*
* This class will walk a config structure and call 
* OnConfigStrings for every match in the language
* map
*
*/
class CCConfigi18nParser
{
    function CCConfigi18nParser()
    {
        $this->_stop = false;
    }


    function OnConfigString(                //  ex.1         ex.2
                            $vroot,         // media        magnatune
                            $config_type,   // config       tab_pages
                            $data,          // 
                            $fieldname,     // ban_message  text
                            $stack          // empty()      array( 'view', 'home' )

                                            //
                                            // Path from root is
                                            // stack+fieldname:
                                            //
                                            // ex.1: $data['ban_message']
                                            //
                                            // ex.2: $data['view']['home']['text']
                            )
    {
    }

    function Stop()
    {
        $this->_stop = true;
    }

    function Run()
    {
        $configs =& CCConfigs::GetTable();
        $cmap = $configs->GetConfig('clangmap');
        $vroots = $configs->GetConfigRoots();
        foreach( $vroots as $vroot_info )
        {
            $vroot = $vroot_info['config_scope'];
            foreach( $cmap as $config_type => $map)
            {
                $data = $configs->GetConfig($config_type,$vroot);
                $this->_do_level( $vroot,$config_type, $map, $data, array() );
                if( $this->_stop )
                    return;
            }
        }

    }


    function _do_level( $vroot,$config_type, $map, $data, $stack )
    {
        foreach( $map as $fieldname => $ops )
        {
            if( $fieldname == '*' )
            {
                foreach( $data as $dname => $ddata )
                {
                    $cstack = $stack;
                    $cstack[] = $dname;
                    $this->_do_level($vroot, $config_type, $ops, $ddata, $cstack);
                }
            }
            else
            {
                if( !empty($data[$fieldname]) )
                {
                    $this->OnConfigString(
                            $vroot,
                            $config_type,
                            $data,
                            $fieldname,
                            $stack
                            );

                    if( $this->_stop )
                        return;
                }
            }
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

    if( !empty($CC_GLOBALS['disabled-msg']) && file_exists($CC_GLOBALS['disabled-msg']) )
    {
        $msgtext = file_get_contents($CC_GLOBALS['disabled-msg']);
    }
    else
    {
        // Do NOT internalize this string, config is not fully
        // intialized, see the ccadmin installer

        $msgtext = 'Site is under construction.';
    }

    if( !empty($CC_GLOBALS['skin']) )
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $css = ccd($settings['style-sheet']);
        $css_link =<<<END
            <link rel="stylesheet" type="text/css" href="$css" title="Default Style"/>
END;
    }
    else
    {
        $css_link = '';
    }

    $name = CC_ENABLE_KEY;
    $self = $_SERVER['PHP_SELF'];
    $html = "";
    $html .=<<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>ccHost</title>
    $css_link
</head>
<body>
<div class="cc_all_content" >
    <div class="cc_content">
        <div class="cc_form_about">
    $msgtext        
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
