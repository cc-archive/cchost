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

global $CC_GLOBALS;

$CC_GLOBALS['template-stack'] = array();
$CC_GLOBALS['map-stack'] = array();

/**
*/
class CCTemplate
{
    function CCTemplate($template, $html_mode = true)
    {
        global $CC_GLOBALS;

        $this->filename  = $template;
        $this->html_mode = $html_mode;

        $configs =& CCConfigs::GetTable();
        $this->_t_args = $configs->GetConfig('ttag');
        $this->_t_args['q'] = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $this->_t_args['get'] = $_GET;
        $this->_t_args['site-root'] = preg_replace('#http://[^/]+/?#','/',ccd());
        $this->_t_args['install_done'] = false;
        $this->_t_args['noproto'] = false;
        $this->_t_args['ajax'] = !empty($_REQUEST['ajax']);
    }

    function SetArg($name,$value='',$macroname='')
    {
        $this->_t_args[$name] = $value;

        if( !empty($macroname) )
            $this->_t_args['macro_names'][] = $macroname;
    }

    function & SetAllAndParse($args, $doprint = false, $admin_dump = false)
    {
        ob_start();
        $this->SetAllAndPrint($args,$doprint,$admin_dump);
        $t = ob_get_contents();
        ob_end_clean();
        return($t);
    }

    function SetAllAndPrint($args, $admin_dump = false)
    {
        global $CC_GLOBALS, $_TV;

        if( empty($args['skin']) && !empty($CC_GLOBALS['skin']) )
            $args['skin'] = $CC_GLOBALS['skin'];

        if( CCUser::IsLoggedIn() )
        {
            $args['logged_in_as'] = CCUser::CurrentUserName();
            $args['logout_url'] = ccl('logout');
            $args['is_logged_in'] = 1;
        } else {
            $args['is_logged_in'] = 0;
        }
        $args['is_admin']  = CCUser::IsAdmin();
        $args['not_admin'] = !$args['is_admin'];

        $_TV = array_merge( $_TV, $args );
        if( $this->html_mode )
        {
            // Force UTF-8 necessary for some languages (chinese,japanese,etc)
            header('Content-type: text/html; charset=' . CC_ENCODING) ;
        }

        _template_call_template($this->filename);
        if( !empty($args['auto_execute']) )
        {
            _template_push_path($this->filename);
            foreach( $args['auto_execute'] as $exec )
                _template_call_template($exec);
            _template_pop_path();
        }
    }

    function GetTemplate($filename,$real_path=true)
    {
        global $CC_GLOBALS;

        $files = array( $filename . '.php',
                        $filename,
                        $filename . '.xml.php',
                        $filename . '.htm',
                        $filename . '.html' );

        return _template_search($files,$real_path);
    }


    function GetTemplatePath()
    {
        global $CC_GLOBALS;
        return array_unique(array_merge( $CC_GLOBALS['template-stack'], 
                                         $CC_GLOBALS['map-stack'], 
                                         CCUtil::SplitPaths( $CC_GLOBALS['template-root'], 'ccskins' ) ));
    }

    function ClearCache() { return true; }
}

function _template_search($file, $real_path = false )
{
    $dirs = CCTemplate::GetTemplatePath();
    return CCUtil::SearchPath( $file, $dirs, '', $real_path);
}

function _template_compat_required()
{
    global $_TV,$CC_GLOBALS;

    if( !empty($_TV['template_compat']) )
        return;

    $pi = pathinfo( $CC_GLOBALS['skin-file'] );
    $sp = split( '\.', $pi['basename'] );
    $pi['filename'] = $sp[0];

    $_TV['style_sheets'][] = $pi['filename'] . '.css';

    if( empty($_TV['head_links']) )
    {
        foreach( $_TV['style_sheets'] as $css )
        {
            $_TV['head_links'][] = array(
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => ccd($pi['dirname'] . '/' . $css),
                    'title' => 'Default Style'
                );
        }
    }

    if( !empty($_TV['file_records']) )
    {
        $k = array_keys($_TV['file_records']);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $_TV['file_records'][$k[$i]];
            $R['local_menu'] = cc_get_upload_menu($R);
            cc_get_ratings_info($R);
        }
    }

    $_TV['template_compat'] = true;
}

function _template_inner_include($path,$funcname='')
{
    _template_push_path($path);
    require_once($path);
    if( !empty($funcname) )
        $funcname();
    _template_pop_path();
}

function _template_import_map($map)
{
    _template_push_path($map,'map-stack');
    require_once($map);
}

function _template_pop_path()
{
    global $CC_GLOBALS;
    array_shift( $CC_GLOBALS['template-stack'] );
}

function _template_push_path($path,$index='template-stack')
{
    global $CC_GLOBALS;

    $path = dirname($path);
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
        $dir = str_replace( '\\', '/', str_replace( getcwd() . '\\', '', $path ) );
    else
        $dir = str_replace( getcwd() . '/', '', $path );
    array_unshift( $CC_GLOBALS[$index], $dir );
}

function _template_call_template($file)
{
    global $_TV, $CC_GLOBALS;

    // forms of calling:
    //
    // [path_to][file][macroname]
    //
    // 'macroname' (alone)   - look this up in _TV
    // 'file.ext/macroname'  - load file, call macro
    // 'file.ext'            - load file
    //

    $funcname = '';

    if( !preg_match('#[\./]#',$file) && !empty($_TV[$file]) )
        $file = $_TV[$file];

    if( preg_match( '/\.(xml|htm|html|php|inc)$/', $file, $m ) )
    {
        // this is no macro on the end

        if( is_file($file) )
        {
            // a full path was passed in, we're done
            _template_inner_include($file);
            return;
        }

        $filename = $file;
    }
    else
    {
        $macro  = basename($file);
        // call dirname to strip off the macro this is the filepart 
        $file_path = dirname($file);
        if( !empty($file_path) && ($file_path != '.') )
        {
            $file_name = basename($file_path); // this is the actual file
            $ext = array_pop( explode('.', $file_name ) );
            $basename = preg_replace( '/[^a-zA-Z0-9]+/', '_', basename($file_name, '.' . $ext) );
            $funcname = '_t_' . $basename . '_' . $macro;
            if( function_exists($funcname) )
            {
                // the file is already in memory, just call the function
                $funcname();
                return;
            }
            $filename = $file_path;
        }
        else
        {
            $filename = $file;
        }
    }

    if( !empty($filename) )
        $path = CCTemplate::GetTemplate($filename,true);

    if( empty($path) )
    {
        print( "<h3>Can't find template: <span style='color:red'>$file</span></h3>");
        CCDebug::StackTrace();
    }

    _template_inner_include($path,$funcname);
}

class CCTemplateMacro extends CCTemplate
{
    var $_mtemplate;
    var $_mmacro;

    function CCTemplateMacro($template,$macro)
    {
        global $CC_GLOBALS;
        $this->CCTemplate($CC_GLOBALS['skin-map'], true );
        $this->_mtemplate = $template;
        $this->_mmacro = $macro;
    }

    function SetAllAndPrint( $args, $doprint = false, $admin_dump = false )
    {
        $fname = empty($this->_mtemplate) ? '' : $this->_mtemplate . '/';
        $args['auto_execute'] = array( $fname . $this->_mmacro );
        $ret = parent::SetAllAndPrint($args,$doprint,$admin_dump);
        return $ret;
    }
}

?>