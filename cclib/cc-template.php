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
function cc_init_template_lib()
{
    static $_init = 0;
    if( !empty($_init) )
        return;
    
    $_init = true;

    global $CC_GLOBALS;

    set_include_path(get_include_path() . PATH_SEPARATOR . $CC_GLOBALS['php-tal-dir'] );

    define('PHPTAL_CACHE_DIR', $CC_GLOBALS['php-tal-cache-dir'] . '/') ;
    //define('PHPTAL_NO_CACHE', true) ;
    require_once($CC_GLOBALS['php-tal-dir'] . "/PHPTAL.php"); 

}

/**
* See class PHPTAL_SourceResolver
*/
class CCTemplateSourceResolver
{
    /**
     * Resolve a template source path.
     *
     * This method is invoked each time a template source has to be
     * located.
     *
     * This method must returns a PHPTAL_SourceLocator object which
     * 'point' to the template source and is able to retrieve it.
     *
     * If the resolver does not handle this kind of path, it must return
     * 'false' so PHPTAL will ask other resolvers.
     *
     * @param string $path       -- path to resolve
     *
     * @param string $repository -- templates repository if specified on
     *                              on template creation. 
     *
     * @param string $callerPath -- caller realpath when a template look
     *                              for an external template or macro,
     *                              this should be usefull for relative urls
     *
     * @return PHPTAL_SourceLocator | false 
     */
    function resolve($path, $repository=false, $callerPath=false)
    {
        // the default PHPTAL resolver will look in the
        // calling template's dir and also in any
        // repository (whatever that is)

        $hit = CCTemplate::GetTemplate($path);
        if( $hit )
        {
            $locator  = new PHPTAL_SourceLocator($hit);
            return $locator;
        }
        return false;
    }
}
/**
*/
class CCTemplate
{
    var $_template_file;
    var $_html_mode;
    var $_template;
    var $_raw_src;
    var $_raw_src_id;
    
    function CCTemplate($template, $html_mode = true, $is_raw_source=false,$raw_src_id='')
    {
        $this->_template_file = $is_raw_source ? '' : $template;
        $this->_html_mode     = $html_mode;
        $this->_raw_src       = $is_raw_source ? $template : '';
        $this->_raw_src_id    = $raw_src_id;
        $this->_encoding      = CC_ENCODING;
        $this->_quoteStyle    = CC_QUOTE_STYLE;
    }

    function _init_lib()
    {
        cc_init_template_lib();
        if( empty($this->_template) )
        {
            $this->_template = new PHPTAL($this->_template_file);
            if( !empty($this->_raw_source) )
                $this->_template->SetSource($this->_raw_source,$this->_raw_source_id);
            $this->_template->setOutputMode($this->_html_mode ? PHPTAL_XHTML : PHPTAL_XML );
            $resolver = new CCTemplateSourceResolver();
            $this->_template->addSourceResolver($resolver);
        }
    }

    function GetTemplate($filename,$real_path=true)
    {
        global $CC_GLOBALS;

        return CCUtil::SearchPath($filename,$CC_GLOBALS['template-root'],'cctemplates/',$real_path);
    }

    function GetTemplatePath()
    {
        global $CC_GLOBALS;

        return CCUtil::SplitPaths( $CC_GLOBALS['template-root'], 'cctemplates' );
    }

    function SetAllAndPrint( $args, $admin_dump = false )
    {
        $this->SetAllAndParse( $args, true, $admin_dump );
    }

    function & SetAllAndParse( $args, $doprint = false, $admin_dump = false )
    {
        global $CC_GLOBALS;
        $admin_dump = $admin_dump || CCUser::IsAdmin();
        $this->_init_lib();
        $this->_template->setAll($args);
        $res = $this->_template->execute();
        if( PEAR::isError($res) )
        {
            print(_("There is an error rendering this page.") . "<br /><a href=\"http://wiki.creativecommons.org/CcHost#Troubleshooting\">" . _('Help troubleshooting ccHost') . "</a><br />");
            $dir = $CC_GLOBALS['php-tal-cache-dir'];
            if( is_dir($dir) && !is_writable($dir) )
            {
                chmod($dir,0777);
                print(sprintf(_("The %s directory must be writable. We have tried to change it. Refresh this page to see if that worked. If not, you may have to ask your system's administrator for assitance to make that possible."), $dir));
            }

            if( $admin_dump )
            {
                print("<pre >");
                print("<b>" . _('Here is the information returned from the template engine:') . "</b>\n\n");
                print_r($res);
                print("</pre>");
            }

            exit;
        }

        if( $doprint )
        {
            if( $this->_html_mode )
            {
                // Force UTF-8 necessary for some languages (chinese,japanese,etc)
                header('Content-type: text/html; charset=' . CC_ENCODING) ;
                // --- BEGIN hack for ZA
                if( substr($res,0,5) == '<html' )
                {
                    print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
                }
                // --- END hack for ZA
            }

            print(trim($res));
        }

        return $res;
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

    function & SetAllAndParse( $args, $doprint = false, $admin_dump = false )
    {
        $args['auto_macro'] = $this->_mtemplate . '/' . $this->_mmacro;
        $args['auto_execute'] = array( 'auto_macro' );
        $ret = parent::SetAllAndParse($args,$doprint,$admin_dump);
        return $ret;
    }
}

?>
