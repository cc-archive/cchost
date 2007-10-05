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
        $this->_t_args = $configs->GetConfig('ttag');
        $this->_t_args['q'] = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $this->_t_args['get'] = $_GET;
        $this->_t_args['site-root'] = preg_replace('#http://[^/]+/?#','/',ccd());
        $this->_t_args['install_done'] = false;
        $this->_t_args['noproto'] = false;
        if( CCUser::IsLoggedIn() )
        {
            $this->_t_args['logged_in_as'] = CCUser::CurrentUserName();
            $this->_t_args['logout_url'] = ccl('logout');
            $this->_t_args['is_logged_in'] = 1;
        } else {
            $this->_t_args['is_logged_in'] = 0;
        }
        $this->_t_args['is_admin']  = CCUser::IsAdmin();
        $this->_t_args['not_admin'] = !$this->_t_args['is_admin'];
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
        $_TV = $args;
        if( $this->html_mode )
        {
            // Force UTF-8 necessary for some languages (chinese,japanese,etc)
            header('Content-type: text/html; charset=' . CC_ENCODING) ;
        }
        _template_call_template($this->filename);
    }

    function GetTemplate($filename,$real_path=true)
    {
        global $CC_GLOBALS;

        $files = array( $filename . '.php',
                        $filename,
                        $filename . '.xml.php',
                        $filename . '.htm',
                        $filename . '.html' );

        $f = CCUtil::SearchPath( $files, $CC_GLOBALS['template-root'], 'cchost_files/viewfile', $real_path );
        //CCDebug::PrintVar($f,false);
        return $f;
     
    }


    function GetTemplatePath()
    {
        global $CC_GLOBALS;

        return CCUtil::SplitPaths( $CC_GLOBALS['template-root'], 'cctemplates' );
    }
}

function _template_call_template($file)
{
    global $_TV;

    // forms of calling:
    //
    // [path_to][file][macroname]
    //
    // 'macroname' (alone)   - look this up in _TV
    // 'file.ext/macroname'  - load file, call macro
    // 'file.ext'            - load file
    //

    CCDebug::Log("{$_SERVER['REQUEST_URI']} Calling for: $file");

    if( !preg_match('#[\./]#',$file) && !empty($_TV[$file]) )
    {
        $file = $_TV[$file];
        CCDebug::Log("Alias for: $file");
    }
        
    if( preg_match( '/\.(xml|htm|html|php|inc)$/', $file, $m ) )
    {
        // this is no macro on the end

        if( is_file($file) )
        {
            // a full path was passed in, we're done
            require_once($file);
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
    }

    if( !empty($filename) )
        $path = CCTemplate::GetTemplate($filename,true);

    if( empty($path) )
    {
        print( "<h3>Can't find template: <span style='color:red'>$file</span></h3>");
        CCDebug::StackTrace();
    }

    //print("Performing: $path " . (empty($funcname) ? '' : $funcname) . "<br \>\n");
    require_once($path);
    if( !empty($funcname) )
        $funcname();
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
        //$args['auto_macro'] = 
        $args['auto_execute'] = array( $this->_mtemplate . '/' . $this->_mmacro );
        $ret = parent::SetAllAndPrint($args,$doprint,$admin_dump);
        return $ret;
    }
}

?>