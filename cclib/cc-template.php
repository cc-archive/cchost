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
        $this->vars = $configs->GetConfig('ttag');
        $this->vars['q'] = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $this->vars['get'] = $_GET;
        $this->vars['site-root'] = preg_replace('#http://[^/]+/?#','/',ccd());
        $this->vars['install_done'] = false;
        $this->vars['noproto'] = false;
        $this->vars['ajax'] = !empty($_REQUEST['ajax']);

        $this->template_stack = array();
        $this->map_stack = array();
        $this->files = array();
    }

    function SetArg($name,$value='',$macroname='')
    {
        $this->vars[$name] = $value;

        if( !empty($macroname) )
            $this->vars['macro_names'][] = $macroname;
    }

    function AddMacro($macro)
    {
        $this->vars['macro_names'][] = $macro;
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
        global $CC_GLOBALS;

        $this->vars = array_merge($CC_GLOBALS,$this->vars,$args);

        if( empty($this->vars['skin']) && !empty($CC_GLOBALS['skin']) )
            $this->vars['skin'] = $CC_GLOBALS['skin'];

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
    }

    function Call($file)
    {
        global $CC_GLOBALS;

        // forms of calling:
        //
        // [path_to][file][macroname]
        //
        // 'macroname' (alone)   - look this up in _TV
        // 'file.ext/macroname'  - load file, call macro
        // 'file.ext'            - load file
        //

        $funcname = '';

        if( !preg_match('#[\./]#',$file) && !empty($this->vars[$file]) )
            $file = $this->vars[$file];

        if( preg_match( '/\.(xml|htm|html|php|inc|tpl)$/', $file, $m ) )
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
                    $funcname($this,$this->vars);
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
            $path = $this->GetTemplate($filename,true);

        if( empty($path) )
        {
            print( "<h3>Can't find template: <span style='color:red'>$file</span></h3>");
            CCDebug::StackTrace();
        }

        $this->_inner_include($path,$funcname);
    }

    function GetTemplate($filename,$real_path=true)
    {
        $files = array( $filename . '.tpl',
                        $filename . '.php',
                        $filename,
                        $filename . '.xml.tpl',
                        $filename . '.xml.php',
                        $filename . '.htm',
                        $filename . '.html' );

        if( empty($this) || ((strtolower(get_class($this)) != 'cctemplate') && 
                                  !is_subclass_of($this,'CCTemplate') ) )
        {
            global $CC_GLOBALS;
            return CCUtil::SearchPath( $files, $CC_GLOBALS['template-root'], 'ccskins', $real_path);
        }

        return $this->Search($files,$real_path);
    }

    function GetTemplatePath()
    {
        global $CC_GLOBALS;
        return array_unique(array_merge( $this->template_stack,
                                         $this->map_stack, 
                                         CCUtil::SplitPaths( $CC_GLOBALS['template-root'], 'ccskins' ) ));
    }

    function URL($partial)
    {
        return ccd( $this->Search($partial,false) );
    }

    function Search($file, $real_path = false )
    {
        $dirs = $this->GetTemplatePath();
        return CCUtil::SearchPath( $file, $dirs, '', $real_path);
    }


    function ImportMap($map)
    {
        $map = $this->Search($map);
        $this->_push_path($map,'map_stack');
        $this->_parse($map);
    }

    function _parse($file)
    {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
            $file = str_replace( '\\', '/', $file );

        if( !in_array( $file, $this->files ) )
        {
            preg_match( '#([^/]+)\.([a-z]+)$#i', $file, $m );

            $bfunc = '_t_' . preg_replace('/[^a-z]+/i','_',$m[1]) . '_';

            // these will be visible to included/eval'd code

            $A =& $this->vars;
            $T = $this;

            if( $m[2] == 'tpl' )
            {
                require_once('cclib/cc-tpl-parser.php');
                $parsed = cc_tpl_parse_file($file,$bfunc);
                eval( '?>' . $parsed);
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


    function ClearCache() { return true; }

    function _inner_include($path,$funcname='')
    {
        $this->_push_path($path);
        $this->_parse($path);
        if( !empty($funcname) )
            $funcname($this,$this->vars);
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

        global $CC_GLOBALS;

        $pi = pathinfo( $CC_GLOBALS['skin-file'] );
        $sp = split( '\.', $pi['basename'] );
        $pi['filename'] = $sp[0];

        $this->vars['style_sheets'][] = $pi['filename'] . '.css';

        if( empty($this->vars['head_links']) )
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

        $this->vars['template_compat'] = true;
    }
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
        $args['auto_execute'] = empty($this->_mmacro) ? array($this->_mtemplate) : array( $fname . $this->_mmacro );
        $ret = parent::SetAllAndPrint($args,$doprint,$admin_dump);
        return $ret;
    }
}

?>