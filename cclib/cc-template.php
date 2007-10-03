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
        $this->filename  = $template;
        $this->html_mode = $html_mode;
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
                        $filename . '.htm',
                        $filename . '.html' );

        return CCUtil::SearchPath( $files, $CC_GLOBALS['template-root'], 'cchost_files/viewfile', $real_path );
     
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

    CCDebug::Log("{$_SERVER['REQUEST_URI']} Calling for: $file");

    if( !preg_match('#[\./]#',$file) && !empty($_TV[$file]) )
    {
        $file = $_TV[$file];
        CCDebug::Log("Alias for: $file");
    }
        
    if( preg_match( '/\.php$/', $file ) && file_exists($file) )
    {
        require_once($file);
        return;
    }

    if( preg_match( '/\.xml$/', $file ) )
    {
        $filename = $file;
    }
    else
    {
        $parts = split('/', $file);
        $filename = $parts[0];
        if( !empty($parts[1]) )
        {
            $basename = preg_replace( '/[^a-zA-Z0-9]+/', '_', basename($filename,'.xml') );
            $funcname = '_t_' . $basename . '_' . $parts[1];
            if( function_exists($funcname) )
            {
                $funcname();
                return;
            }
        }
    }
    
    $path = CCTemplate::GetTemplate($filename,true);
    if( empty($path) )
    {
        print( "<h3>Can't find $file</h3>");
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