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

$CC_GLOBALS   = array();
$CC_CFG_ROOT  = '';

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
            $loc = array_merge($loc,$arr);
        }
        else
        {
            $cache[$where['config_scope']][$where['config_type']] = $arr;
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

        $regex = '%/([^/\?]+)%';
        preg_match_all($regex,CCUtil::StripText($_REQUEST['ccm']),$a);
        $A =& $a[1];

        $configs->SetCfgRoot( empty($A[0]) ? CC_GLOBAL_SCOPE : $A[0] );

        $hvers = $CC_GLOBALS['cc-host-version'] ; 

        // a temp hack to allow UI disabled beta sites to process api calls

        if( $CC_GLOBALS['site-disabled'] && (strstr($_SERVER['REQUEST_URI'],'/api/') === false) )
            cc_check_site_enabled();
        
        $configs->_upgrade( $hvers );

        $CC_GLOBALS['home-url'] = ccl();

        $settings = $configs->GetConfig('settings');
        $CC_GLOBALS['skin-name']  = $settings['style-sheet'];
        $CC_GLOBALS['skin']       = preg_replace('/.*skin-(.*)\.css/', '\1', $settings['style-sheet']);
        $CC_GLOBALS['skin-map']   = $CC_GLOBALS['template-root'] . 'skin-' . $CC_GLOBALS['skin'] . '-map.xml';
        $CC_GLOBALS['skin-page']  = $CC_GLOBALS['template-root'] . 'skin-' . $CC_GLOBALS['skin'] . '.xml';

        cc_setcookie('cc_purls', "'" . $CC_GLOBALS['pretty-urls'] . "'" ,null);
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
            if( !$this->CountRows($where) )
                $rootname = CC_GLOBAL_SCOPE;
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
        $where = array();
        if( !empty($scope) )
            $where['config_scope'] = $scope;
        $where['config_type'] = $type;
        $rows = $this->QueryRows($where);
        foreach( $rows as $row )
        {
            $arr = $row['config_data'];
            $arr = unserialize($arr);
            $arr[$name] = $value;
            $args['config_id']   = $row['config_id'];
            $args['config_data'] = serialize($arr);
            $this->Update($args);
        }
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
        return( !empty($count) );
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
                $tags = unserialize($tags);
                $title = $tags['site-title'];
            }
            else
            {
                $title = $scope;
            }
            $roots[$i]['scope_name'] = $title;
        }
        return($roots);
    }

    /*
    * @access private
    * @deprecated See {@link cc-upload.php}
    * @param string $config_in_db Version string to check against code
    */
    function _upgrade($config_in_db)
    {
        global $CC_GLOBALS, $CC_CFG_ROOT;

        if( version_compare($config_in_db, CC_HOST_VERSION) == 0 )
            return;

        if( version_compare($config_in_db, '0.4.0') < 0 )
        {
            print('sorry but a re-install is required to upgrade to this version');
            exit;
        }

        // just assume the map has changed
        CCEvents::GetUrlMap(true);
        CCMenu::GetMenu(true);

        $this->SetValue('config', 'cc-host-version', CC_HOST_VERSION, CC_GLOBAL_SCOPE);
        $CC_GLOBALS = array_merge($CC_GLOBALS,$this->GetConfig('config', CC_GLOBAL_SCOPE));
        CCPage::Prompt("ccHOST UPGRADED TO: " . CC_HOST_VERSION );
    }

}
 
?>