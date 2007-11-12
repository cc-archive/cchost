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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('CC_DEFAULT_SKIN_SEARCH_PATHS', 'ccskins/shared' );

/**
*/
class CCSkin
{
    /**
    * Initialize a skin with either the main skin page or the macro map file
    *
    * @param string $template Name of either the main skin page or the macro map file
    * @param bool $html_mode Set to false when the output is non-html (like json or xml feed)
    */
    function CCSkin($template, $html_mode = true)
    {
        global $CC_GLOBALS;

        $this->filename  = $template;
        $this->html_mode = $html_mode;

        $configs =& CCConfigs::GetTable();

        $this->vars = $configs->GetConfig('ttag');

        $this->vars['q']            = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $this->vars['get']          = $_GET;
        $this->vars['site-root']    = preg_replace('#http://[^/]+/?#','/',ccd());
        $this->vars['install_done'] = false;
        $this->vars['noproto']      = false;
        $this->vars['ajax']         = !empty($_REQUEST['ajax']);

        $this->template_stack = array();
        $this->map_stack = array();
        $this->files = array();
        $this->search_cache = array();
    }

    /**
    * Make a variable available to the page when rendering
    *
    * @param string $name The name of the variable as will be seen in the template
    * @param mixed  $value The value that will be substituted for the 'name'
    * @param string $macroname The name of a specific macro to invoke during template generation
    */
    function SetArg($name,$value='',$macroname='')
    {
        $this->vars[$name] = $value;

        if( !empty($macroname) )
            $this->vars['macro_names'][] = $macroname;
    }

    /**
    * Trigger a macro during page execution, typically in the middle of the client area
    *
    * @param string $macro The name of a specific macro to invoke during template generation
    */
    function AddMacro($macro)
    {
        $this->vars['macro_names'][] = $macro;
    }

    /**
    * Parse and return the results of the this session
    *
    * @param mixed $args Last minute arguments to pump into the rendering
    * @return string $text Results of parsing
    */
    function & SetAllAndParse($args)
    {
        ob_start();
        $this->SetAllAndPrint($args);
        $t = ob_get_contents();
        ob_end_clean();
        return($t);
    }

    /**
    * Print the current page/macro
    *
    * @param mixed $args Last minute arguments to pump into the rendering
    */
    function SetAllAndPrint($args)
    {
        global $CC_GLOBALS;

        $snapshot = $this->vars; // make this instance reusable (this is not tested)

        $this->vars = array_merge($CC_GLOBALS,$this->vars,$args);

        if( CCUser::IsLoggedIn() )
        {
            $this->vars['logged_in_as'] = CCUser::CurrentUserName();
            $this->vars['logout_url'] = ccl('logout');
            $this->vars['is_logged_in'] = 1;
        } else {
            $this->vars['is_logged_in'] = 0;
        }
        $this->vars['is_admin']  = CCUser::IsAdmin();
        $this->vars['not_admin'] = !$this->vars['is_admin'];

        if( $this->html_mode )
        {
            // Force UTF-8 necessary for some languages (chinese,japanese,etc)
            header('Content-type: text/html; charset=' . CC_ENCODING) ;
        }

        $this->Call($this->filename);

        if( !empty($this->vars['auto_execute']) )
        {
            $this->_push_path($this->filename);
            foreach( $this->vars['auto_execute'] as $exec )
                $this->Call($exec);
            $this->_pop_path();
        }

        $this->vars = $snapshot;
    }

    function AddCustomizations()
    {
        $config =& CCConfigs::GetTable();
        $skin_settings = $config->GetConfig('skin-settings');
        foreach( array( 'string_profile', 'tab_pos', 'box_shape', 'page_layout', 'color_scheme', 'font_scheme' ) as $inc )
        {
            $T = $this;
            $A =& $this->vars;
            if( !empty($skin_settings[$inc]) && file_exists($skin_settings[$inc]))
                require_once($skin_settings[$inc]);
        }
    }

    function String($args)
    {
        global $CC_GLOBALS;

        if( empty($args) )
        {
            $text = '';
        }
        else
        {
            if( is_string($args) && !empty($this->vars[$args]) )
            {
                $var = $args;
                $args = $this->vars[$args];
            }

            if( is_array($args) )
            {
                $fmt = array_shift($args);
                if( !empty($this->vars[$fmt]) )
                {
                    $var = $fmt;
                    $fmt = $this->vars[$fmt];
                }
                if( !empty($GLOBALS[$fmt]) )
                {
                    $id = $fmt;
                    $fmt =  $GLOBALS[$fmt];
                }
                if( is_array($args[0]) )
                    $args = $args[0];
                $text = vsprintf($fmt,$args);
            }
            else
            {
                if( empty($GLOBALS[$args]) )
                {
                    $text = $args;
                }
                else
                {
                    $id = $args;
                    $text = $GLOBALS[$args];
                }
            }
        }

        if( CCUser::IsAdmin() && (!empty($var) || !empty($id)) )
        {
            $var = empty($var) ? '' : "var: $var";
            $id  = empty($id)  ? '' : "str: $id";
            $text = "<!-- $id $var -->" . $text;
        }

        return $text;
    }

    /**
    * Execute a template macro or file
    *
    * This is called from within templates
    *
    * forms of calling:
    * 
    * [path_to][file][macroname]
    * 
    * 'macroname' (alone)   - look this up in map
    * 'file.ext/macroname'  - load file, execute macro
    * 'file.ext'            - load file
    * 
    * Uses GetTemplate to search for files
    *
    * @see CCSkin::GetTemplate
    * @param string $name The name of the variable as will be seen in the template
    * @param mixed  $value The value that will be substituted for the 'name'
    * @param string $macroname The name of a specific macro to invoke during template generation
    */
    function Call($file)
    {
        global $CC_GLOBALS;

        //CCDebug::Log("TCall: $file");

        $funcname = '';

        if( !preg_match('#[\./]#',$file) && !empty($this->vars[$file]) )
            $file = $this->vars[$file];

        if( preg_match( '/\.(xml|html?|php|inc|tpl)$/', $file, $m ) )
        {
            // this is no macro on the end

            if( is_file($file) )
            {
                // a full path was passed in, we're done
                $this->_inner_include($file);
                return;
            }

            $filename = $file;
        }
        else
        {
            $macro  = basename($file);
            $filename = dirname($file); // call dirname to strip off the macro this is the filepart 

            if( !empty($filename) && ($filename{0} != '.') )
            {
                $funcname = '_t_' . preg_replace( '/((?:\.)[^\.]+$|[^a-zA-Z0-9\.]+)/', '_', basename($filename)) . $macro;
                if( function_exists($funcname) )
                {
                    // the file is already in memory, just call the function
                    $funcname($this,$this->vars);
                    return;
                }
            }
            else
            {
                $filename = $file;
            }
        }

        if( !empty($filename) )
            $path = $this->GetTemplate($filename,true);

        //CCDebug::LogVar('tpath',$path);

        if( empty($path) )
        {
            print( "<h3>Can't find template: <span style='color:red'>$file</span></h3>");
            CCDebug::PrintVar($this);
        }

        $this->_inner_include($path,$funcname);
    }


    /**
    * Search along skin search path for a file
    *
    * Use this when you don't know the exact path or even the extension of
    * the file you are looking for
    *
    * If you leave off the extension, this method add .tpl, .php, .htm/l while looking
    *
    * This method can be called statically but will return different results depending
    * on whether you 
    *
    * @param string $filename Partial filename to search for
    * @param bool   $real_path True means returns the full local (server) path
    * @return mixed $path_to_template string if found or bool(false) if not found
    */
    function GetTemplate($filename,$real_path=true)
    {
        $files = CCSkin::GetFilenameGuesses($filename);
        return CCSkin::Search($files,$real_path);
    }

    /**
    * Return standard variations of a file
    *
    * If you leave off the extension, this method add .tpl, .php, .htm/l while looking
    *
    * @param string $filename Partial filename to search for
    * @param mixed  $value The value that will be substituted for the 'name'
    * @param string $macroname The name of a specific macro to invoke during template generation
    * @return array $guesses Array of guesses, pass this to Search
    */
    function GetFilenameGuesses($filename)
    {
        if( preg_match('/\.(xml|tpl)$/',$filename,$m) )
        {
            // it's a legacy template

            if( $m[1] == 'xml' )
                return array(   
                            str_replace('.xml','.php',$filename), 
                            str_replace('.xml','.tpl',$filename), 
                            $filename . '.php'
                         );

            // if there is a compiled php version, then we
            // want to favor that

            return array( str_replace('.tpl', '.php', $filename ), 
                          $filename );
        }

        if( !preg_match( '/\.[a-zA-Z]{1,4}$/', $filename ) )
            return array(   $filename . '.php',
                            $filename . '.tpl',
                            $filename . '.xml.php'
                            );

         return array( $filename );

    }

    /**
    * Return all directories in the current scope, perfect for searching 
    *
    * This is a non-static function that takes the current executing page
    * or macro into account. The returned array are the directories in the
    * following order:
    *
    *    Current cchost dir ('./') This allows for relative dirs from the root
    *    priority (e.g. 'ccskins/plain/html_forms.tpl')
    *
    *    Directory of currently executing template and it's callers in reverse order.
    *
    *            For example: If a template foo/template.php is currently executing then
    *                         'foo' is the first directory. If that template calls another
    *                         macro in the file bar/template2.php then 'bar' becomes the
    *                         first directory, followed by 'foo'
    *
    *    Directory of current map file and all the maps it imported in the order they
    *    were imported.
    *
    *            For example: If the current skin's map file is fee/map.tpl and it imports
    *                         baz/map.tpl then fee will come first, followed by baz
    *
    *    Directories entered by admin in 'Skins Path' admin screens.
    *    The directory defined by CC_DEFAULT_SKIN_SEARCH_PATHS
    *
    * @param string $filename Partial filename to search for
    * @param mixed  $value The value that will be substituted for the 'name'
    * @param string $macroname The name of a specific macro to invoke during template generation
    * @return array $guesses Array of guesses, pass this to Search
    */
    function GetTemplatePath()
    {
        global $CC_GLOBALS;
        $arr = array_unique(array_merge( array('./'),
                                         $this->template_stack,
                                         $this->map_stack, 
                                         CCUtil::SplitPaths( $CC_GLOBALS['template-root'], CC_DEFAULT_SKIN_SEARCH_PATHS ) ));

        return $arr;
    }

    /**
    * Search the current template scope and return a full URL
    *
    * @param string $partial relative path to file (e.g. 'css/foo.css')
    * @return string $url Full URL 
    */
    function URL($partial)
    {
        if( substr($partial,0,7) == 'http://' )
            return $partial;
        $path = $this->Search($partial,false);
        if( empty($path) )
            die("Can't find: $partial");
        return ccd($path);
    }

    /**
    * Search the template paths for a file
    *
    * This method returns potentially different results depending on context. If not currently
    * executing (printing to client) or called statically the method will search in the 
    * admin 
    * 
    *
    * @param string $filename Partial filename to search for
    * @param bool $real_path Set to true to return full local path 
    * @return string Path to requested file or bool(false)
    */
    function Search($file, $real_path = false)
    {
        // CCDebug::LogVar('search',$file);

        if( empty($this) || ((strtolower(get_class($this)) != 'ccskin') && 
                                  !is_subclass_of($this,'CCSkin') ) )
        {
            global $CC_GLOBALS;
            return CCUtil::SearchPath( $file, $CC_GLOBALS['template-root'], CC_DEFAULT_SKIN_SEARCH_PATHS, $real_path);
        }
        
        $sfile = is_array($file) ? md5($file[0]) : md5($file);
        if( empty($this->search_cache[$sfile]) )
        {
            $dirs = $this->GetTemplatePath();
            //if( is_string($file) && strstr($file,'playlist.js') ) { $x[] = $file; $x[] = $dirs; CCDebug::PrintVar($x); }
            if( !empty($reject_if_match) )
                die( 'wups, reject search doesn\'t work' );
            $found = CCUtil::SearchPath( $file, $dirs, '', $real_path, CC_SEARCH_RECURSE_DEFAULT);
            $this->search_cache[$sfile] = $found;
            return $found;
        }

        return $this->search_cache[$sfile];
    }


    function ImportSkin($dir)
    {
        $this->_pick_up_skin_file($dir,'strings');
        $skin = $this->_pick_up_skin_file($dir,'skin');
        $this->_push_path($skin,'map_stack'); // skin file will get taken off inside here
    }

    function _pick_up_skin_file($dir,$base)
    {
        $skintpl = $dir . '/' . $base . '.tpl';
        $skinphp = $dir . '/' . $base . '.php';
        $skin = file_exists($skintpl) ? $skintpl : (file_exists($skinphp) ? $skinphp : '');
        if( $skin )
            $this->_parse($skin);
        return $skin;
    }

    function _parse($file)
    {
        $file = str_replace( '\\', '/', $file );

        if( !in_array( $file, $this->files ) )
        {
            preg_match( '#([^/]+)\.([a-z]+)$#i', $file, $m );

            $bfunc = '_t_' . str_replace('_xml','',preg_replace('/[^a-z]+/i','_',$m[1])) . '_';

            // these will be visible to included/eval'd code

            $A =& $this->vars;
            $T = $this;

            if( $m[2] == 'tpl' )
            {
                require_once('cclib/cc-tpl-parser.php');
                //CCDebug::Log("parsing: $file");
                $parsed = cc_tpl_parse_file($file,$bfunc);
                //if( preg_match('/skin.tpl/',$file) ) CCDebug::PrintVar($parsed);
                $ret = eval( '?>' . $parsed);
                if( $ret != 'ok' )
                {
                    $lines = split("\n",$parsed);
                    array_unshift($lines,"-------- parsed template for $file --------");
                    CCDebug::Enable(true);
                    CCDebug::PrintVar($lines);
                }
            }
            else
            {
                require_once($file);
            }

            $init_func = $bfunc . 'init';

            if( function_exists($init_func) )
                $init_func($this,$this->vars);

            $this->files[] = $file;
        }
    }


    function _inner_include($path,$funcname='')
    {
        //CCDebug::Log("_inner call: $path / $funcname");

        $this->_push_path($path);
        $this->_parse($path);
        if( !empty($funcname) )
        {
            $funcname = trim($funcname); // fix this elsewhere!
            $funcname($this,$this->vars);
        }
        $this->_pop_path();
    }

    function _pop_path()
    {
        array_shift( $this->template_stack );
    }

    function _push_path($path,$index='template_stack')
    {
        if( is_array($path) )
        {
            unset($this->vars);
            CCDebug::StackTrace();
        }
        $path = dirname($path);
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
            $dir = str_replace( '\\', '/', str_replace( getcwd() . '\\', '', $path ) );
        else
            $dir = str_replace( getcwd() . '/', '', $path );
        array_unshift( $this->$index, $dir );
    }

    function CompatRequired()
    {
        if( !empty($this->vars['template_compat']) )
            return;

        $this->ImportSkin('ccskins/plain');

        global $CC_GLOBALS;

        $pi = pathinfo( $CC_GLOBALS['skin-file'] );
        $this->vars['skin'] = preg_replace('/\.[^\.]+$/', '', $pi['filename']);
        $sp = split( '\.', $pi['basename'] );
        $pi['filename'] = $sp[0];


        $this->vars['style_sheets'][] = $pi['filename'] . '.css';

        //if( empty($this->vars['head_links']) )
        {
            foreach( $this->vars['style_sheets'] as $css )
            {
                $this->vars['head_links'][] = array(
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                        'href' => ccd($pi['dirname'] . '/' . $css),
                        'title' => 'Default Style'
                    );
            }
        }

        if( !empty($this->vars['records']) && empty($this->vars['file_records']) )
            $this->vars['file_records'] =& $this->vars['records'];

        if( !empty($this->vars['file_records']) )
        {
            $k = array_keys($this->vars['file_records']);
            $c = count($k);
            for( $i = 0; $i < $c; $i++ )
            {
                $R =& $this->vars['file_records'][$k[$i]];
                $R['local_menu'] = cc_get_upload_menu($R);
                cc_get_ratings_info($R);
            }
        }

        if( !empty($this->vars['user_record']) )
            $this->vars['user_record'] = array( $this->vars['user_record'] );

        $configs =& CCConfigs::GetTable();
        $tmacs = $configs->GetConfig('tmacs');

        $first_key = key($tmacs);

        // older installs don't have file/ prefix,
        // fix that right here...
        if( !preg_match( '#[./]#', $first_key ) )
        {
            $newmacs = array();
            foreach( $tmacs as $K => $V )
            {
                $parts = split('/',$K);
                if( count($parts) == 1 )
                    $K = 'custom.xml/' . $parts[0];
                else
                    $K = $parts[0] . '.xml/' . $parts[1];
                $newmacs[$K] = $V;
            }
            $configs->SaveConfig('tmacs',$newmacs,'',false);
            $tmacs = $newmacs;
        }

        reset($tmacs);
        $TM = array();
        foreach( $tmacs as $TMF => $on )
            if( $on )
                $TM[] = $TMF;

        $this->vars['custom_macros'] = $TM;

        $this->vars['template_compat'] = true;

        //$this->vars['print_page_title'] = 'ccskins/plain/page.tpl/print_page_title';
    }

    /**
    * @deprecated
    */
    function ClearCache() { return true; }

}

class CCSkinMacro extends CCSkin
{
    var $_skin_macro;

    function CCSkinMacro($macro,$mapfile='')
    {
        global $CC_GLOBALS;
        $this->CCSkin(empty($mapfile) ? $CC_GLOBALS['skin-file'] : $mapfile, true );
        $this->_skin_macro = $macro;
    }

    function SetAllAndPrint( $args )
    {
        $args['auto_execute'][] = $this->_skin_macro;
        $ret = parent::SetAllAndPrint($args);
        return $ret;
    }
}

/**
* @deprecated
*/
class CCTemplate extends CCSkin
{
    function CCTemplate($template, $html_mode = true)
    {
        $this->CCSkin($template,$html_mode);
    }
}

/**
* @deprecated
*/
class CCTemplateMacro extends CCSkinMacro
{
    function CCTemplateMacro($filename,$macro)
    {
        $fname = empty($filename) ? '' : $filename. '/';
        $macro = empty($macro) ? $filename : $fname . $macro ;
        $this->CCSkinMacro($macro);
    }

}

?>
