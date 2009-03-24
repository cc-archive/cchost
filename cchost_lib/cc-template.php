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

define('CC_DEFAULT_SKIN_SEARCH_PATHS', 'ccskins/shared/pages/;ccskins/;ccskins/shared/;ccskins/shared/formats/;' );

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

        $this->vars['q']            = $q = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $this->vars['query-url']    = ccl('api','query') . $q;
        $this->vars['get']          = $_GET;
        $this->vars['site-root']    = preg_replace('#http://[^/]+/?#','/',ccd());
        $this->vars['noproto']      = false;
        $this->vars['ajax']         = !empty($_REQUEST['ajax']);
        $this->vars['true']         = true;
        $this->vars['false']        = false;

        $site_logo['logo'] = $configs->GetConfig('site-logo');

        // this seems like (memory) overkill, need to optimize
        $this->vars = array_merge($CC_GLOBALS,$this->vars,$site_logo);

        // for compat with pre 5.0.beta.2 
        if( empty($this->vars['head-type']) )
            $this->vars['head-type'] = 'ccskins/shared/head.tpl';

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

        $this->template_stack = array();
        $this->map_stack = array();
        $this->files = array();
        $this->search_cache = array();

        $template = $this->GetTemplate($this->filename);
        $this->_pick_up_skin_file(dirname($template),'skin');

        $this->_strings_loaded = false; // rrrrg
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
        $this->SetAllAndPrint($args,false);
        $t = ob_get_contents();
        ob_end_clean();
        return $t;
    }

    /**
    * Print the current page/macro
    *
    * @param mixed $args Last minute arguments to pump into the rendering
    */
    function SetAllAndPrint( $args, $headers=true )
    {
        $snapshot = $this->vars; // make this instance reusable (this is not tested)
    
        if( !empty($args) )
            $this->vars = array_merge($this->vars,$args);

        if( $this->html_mode && $headers )
        {
            // Force UTF-8 necessary for some languages (chinese,japanese,etc)
            if( CCDebug::IsEnabled() )
            {
                if( headers_sent($file,$line) )
                {
                    print("Headers send $file $line<br />");
                    CCDebug::StackTrace();
                }
            }
            header('Content-type: text/html; charset=' . CC_ENCODING) ;
        }

        // we have to special case and pick up string files early 

        // first the skin/template specific string file then the global string profile
        $this->_pick_up_skin_file(dirname($this->filename),'strings');

        if( !empty($this->vars['string_profile']) && file_exists($this->vars['string_profile']) )
            require_once($this->vars['string_profile']);
        $this->_strings_loaded = true;

        // Load the main skin file here...
        // hmmm
        //$this->Call($this->filename);

        // execute specific macros...

        if( !empty($this->vars['auto_execute']) )
        {
            // if the skin profile has any overrides we have to load them here in
            // the event of ajax callbacks (otherwise we would load them in 
            // AddCustomizations but only full page displays gets those)

            if( !empty($this->vars['profile_extras']) && file_exists($this->vars['profile_extras']) ) // last minute overrides
            {
                $A =& $this->vars;
                $T =& $this;
                include($this->vars['profile_extras']);
            }

            $this->_push_path($this->filename);
            foreach( $this->vars['auto_execute'] as $exec )
            {
                $this->Call($exec);
            }
            $this->_pop_path();
        }

        $this->vars = $snapshot;
    }

    function AddCustomizations()
    {
        $T =& $this;
        $A =& $this->vars;
        foreach( array( 'page_layout', 'color_scheme', 'font_scheme', 'font_size', 'tab_pos', 'box_shape', ) as $inc )
        {
            if( !empty($_REQUEST[$inc]) )
                $A[$inc] = $_REQUEST[$inc];
            if( !empty($A[$inc]) && file_exists($A[$inc]))
            {
                require_once($A[$inc]);
            }
        }
    }

    function _d($t)
    {
        // print $t;
    }

    function _get_head_files($auto_create=1)
    {
        global $CC_GLOBALS,$CC_CFG_ROOT;

        $base = $CC_GLOBALS['user-upload-root'] . '/' . $CC_CFG_ROOT . '_skin_'; 
        $files = glob( $base . '*.*' );
        if( empty($files) && $auto_create )
        {
            $base .= date('YmdHis');
            return array( $base . '.css', $base . '.js' );
        }

        return $files;
    }

    /**
    * Create a cache of common head js and css files and blocks
    *
    * The idea here is to suck in all the stateless javascript and style
    * information common to all pages in this skin and write them out
    * to 2 cache files, one for the JS one for the CSS. We will then use
    * them for a <script tag and a <link tag in the <head> for each page
    *
    * The cache files are named for the current skin and the time they
    * were generated and put into the root of the user's uplaod directory
    * because that's the one place in the system guaranteed to be both
    * writable and accessable by browsers.
    *
    * Admin's can determin whether to use this method of head caching
    * from the Setting/Skins/Basic Settings options screen. 
    *
    * To clear the cache from the browser use ?update=1
    */
    function CachedHead()
    {
        // this returns valid file names even if they don't exist
        list( $css_file, $script_file ) = $this->_get_head_files();

        if( !file_exists($script_file) )
        {
            // there are no cached files so we create them

            $f_js  = fopen($script_file,'w');
            $f_css = fopen($css_file,   'w');

            // we're going to suck in all the CSS files 

            if( !empty($this->vars['style_sheets']) ) foreach( $this->vars['style_sheets'] as $css ) {
                    $note = $this->GetTemplate($css);
                    $text = file_get_contents($note);
                    if( preg_match_all("/url\(['\"]([^'\"]+)['\"]/",$text,$m) )
                    {
                        $dir = $this->_strip_root(dirname($note)) . '/';
                        foreach( $m[1] as $urlarg )
                        {
                            $path = ccd($this->_strip_root(realpath( $dir . $urlarg )));
                            $text = str_replace($urlarg,$path,$text);
                        }
                    }
                    $this->_write_css($f_css,$text);
            }

            // There is already a list of js files in this->var['script_links'] and they
            // will be written to the cache first, but the customizations are freewheeling
            // so we will parse those first and add any links we find links or blocks in
            // the generated HTML

            ob_start();
            $this->AddCustomizations();
            $text = ob_get_contents();
            ob_end_clean();


            require_once('cchost_lib/htmlparser/htmlparser.inc');
            $parser = new HtmlParser($text);
            $tag = null;
            $attrs = null;
            while ($parser->parse()) {

                switch( $parser->iNodeType )
                {
                    case NODE_TYPE_ELEMENT:
                        $attrs =& $parser->iNodeAttributes;
                        $tag = strtolower($parser->iNodeName);
                        $this->_d( "START: $tag<br />\n");
                        if( $tag == 'script' and !empty($attrs['src']) )
                        {
                            // this is a script link, add it to our 'to-be-written' list

                            $this->_d( "SCRIPT LINK: {$attrs['src']}<br />\n");
                            $this->vars['script_links'][] = str_replace(ccl(),'',$attrs['src']);
                        }
                        break;
                    case NODE_TYPE_ENDELEMENT:
                        //$tag = strtolower($parser->iNodeName);
                        $this->_d("END: $tag<br />\n");
                        switch($tag) 
                        {
                            case 'link':
                                if( !empty($attrs['rel']) )
                                {
                                    if( $attrs['rel'] == 'stylesheet' )
                                    {
                                        // this is a link to a stylesheet, write it now
                                        // ...
                                        // tbh I'm not sure this preserves the order 
                                        // properly but we'll see...

                                        $file = str_replace(ccl(),'',$attrs['href']);
                                        $text = file_get_contents($file);
                                        $this->_write_css($f_css,$text);
                                    }
                                }
                                break;
                        }
                        $tag = null;
                        $attrs = null;
                        $val = null;
                        break;
                    case NODE_TYPE_COMMENT:
                        // todo: verify that all the IF[IE] comments are handled
                        // correctly...
                        $this->_d("COMMENT: $tag<br />\n");
                        continue;
                    case NODE_TYPE_TEXT:
                        $val = trim($parser->iNodeValue);
                        $this->_d("TEXT: $tag<br />\n$val<br />\n");
                        switch( $tag )
                        {
                            case 'style':
                                $this->_write_css($f_css,$val);
                                break;
                            case 'script':
                                if( empty($attrs['src']) )
                                {
                                    // this is an actual block of JS, add it to the
                                    // 'to-be-written' list
                                    $this->vars['script_blocks'][] = $val;
                                }
                                break;
                        }
                        break;
                }

            }

            // Some customizations write 'end_script_text' blocks. We store
            // those up here and put them into a js function into the cache
            // Later we will add a call to this function to the bottom of
            // every page

            if( !empty($this->vars['end_script_text']) ) {
                $scr = 'function _cc_post_page_load() { ';
                foreach( $this->vars['end_script_text'] as $FL )
                {
                    $scr .= $FL;
                }
                $scr .= '} ';

                // now put the function into the 'to-be-written' queue

                $this->vars['script_blocks'][] = $scr;

                // clear this now so we don't write the blocks
                // twice while creating this cache file...
                
                $this->vars['end_script_text'] = array();
            }
            

            // OK, we are finally ready to start writing out some javascript, start by sucking
            // in all the js files 

            if( !empty($this->vars['script_links']) ) foreach( $this->vars['script_links'] as $FL )
            {
                if( strstr($FL, 'strings_js.php?ajax=1') !== false )
                {
                    // we have to special case the JS strings files because they need to be
                    // 'eval'd right here to do int'l and other string substitutions

                    $FL = 'strings_js.php';
                    $note = $this->GetTemplate($FL,true);
                    $text = file_get_contents($note);
                    $text = preg_replace( '/header\(.*;/', '', $text );
                    $text = str_replace( 'exit;', '', $text );
                    $T = $this;
                    ob_start();
                    eval( '?>' . $text);
                    $text = ob_get_contents();
                    ob_end_clean();
                }
                else
                {
                    // otherwise we can read the file 'as is'

                    $note = $this->GetTemplate($FL,true);
                    $text = file_get_contents($note);
                }

                $this->_write_js($f_js,$text);
            }

            if( !empty($this->vars['script_blocks']) ) foreach( $this->vars['script_blocks'] as $FL ) {
                    $this->_write_js($f_js,$FL);
            }

            fclose($f_js);
            fclose($f_css);
        }

        print "\n" . '<script type="text/javascript" src="' . ccd($script_file) . '"></script>';
        print "\n" . '<link rel="stylesheet"  type="text/css" title="Default Style" href="' . ccd($css_file) . '" />';

        $this->vars['end_script_text'][] = "if( typeof(_cc_post_page_load) == 'function' ) { _cc_post_page_load(); }";

    }

    function _strip_root($f)
    {
        return str_replace(str_replace('\\','/',getcwd()) . '/','',str_replace('\\','/',$f));
    }

    function _write_css($f,$text)
    {
        fwrite( $f, preg_replace('/\s+/',' ',trim(preg_replace('#/\*.+\*/#Us','',$text))));
    }

    function _write_js($f,$text)
    {
        /*
        $text = preg_replace( array('#/\*.+\*' . '/#Us','%//.*$%m'),'',$text);
        */
        $text = preg_replace(array('/^\s+/m',"/([;,{])\n/",'#^//.*\n#m'),
                             array('',       '\1',         ''),
                             trim($text));
        fwrite( $f, $text . "\n" );
    }

    function String($args)
    {
        global $CC_GLOBALS;

        if( !$this->_strings_loaded )
        {
            // rrrg, this is hacked for now... see CCConfig::Init for why this kinda works
            if( !empty($CC_GLOBALS['string_profile']) && file_exists($CC_GLOBALS['string_profile']) )
                require_once($CC_GLOBALS['string_profile']);
        }

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
                if( (substr($fmt,0,4) == 'str_') && !empty($GLOBALS[$fmt]) )
                {
                    $id = $fmt;
                    $fmt =  $GLOBALS[$fmt];
                }
                if( isset($args[0]) && is_array($args[0]) )
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

        /*
            turn on the code below for 'onscreen' editing of strings 

        if( CCUser::IsAdmin() && (!empty($var) || !empty($id)) )
        {
            $var = empty($var) ? '' : "var: $var";
            $id  = empty($id)  ? '' : "str: $id";
            $text = "<!-- $id $var -->" . $text;
        }
        */

        return CCUtil::StripSlash($text);
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
    function Call($macropath,$forceParse=false)
    {
        list( $filename, $funcname ) = $this->LookupMacro($macropath);
        if( function_exists($funcname) )
        {
            // the file is already in memory, just call the function
            $funcname($this,$this->vars);
            return;
        }
        if( empty($filename) )
        {
            //CCUtil::Send404();
            return;
        }
        $this->_inner_include($filename,$funcname,$forceParse);
    }


    function GetProps($macropath)
    {
        if( file_exists($macropath) )
        {
            $file = $macropath;
        }
        else
        {
            list( $file, $macro ) = $this->_inner_lookup_macro($macropath);
            if( empty($file) )
                return null;
        }
        require_once('cchost_lib/cc-file-props.php');
        $fp = new CCFileProps();
        return $fp->GetFileProps($file);
    }


    function LookupMacro($macropath)
    {
        return $this->_inner_lookup_macro($macropath);
    }

    function _inner_lookup_macro($macropath)
    {
        global $CC_GLOBALS;

        //CCDebug::Log("Lookup: $macropath");

        $funcname = '';

        if( !preg_match('#[\./]#',$macropath) && !empty($this->vars[$macropath]) )
            $macropath = $this->vars[$macropath];

        if( preg_match( '/\.(xml|html?|php|inc|tpl)$/', $macropath, $m ) )
        {
            // this is no macro on the end

            if( is_file($macropath) )
            {
                // a full path was passed in, we're done
                return array( $macropath, '' );
            }

            $filename = $macropath;
        }
        else
        {
            $macro  = basename($macropath);
            $filename = dirname($macropath); // call dirname to strip off the macro this is the filepart 

            if( !empty($filename) && ($filename{0} != '.') )
            {
                $funcname = '_t_' . preg_replace( '/((?:\.)[^\.]+$|[^a-zA-Z0-9\.]+)/', '_', basename($filename)) . $macro;
                if( function_exists($funcname) )
                    return array( $filename, $funcname );
            }
            else
            {
                $filename = $macropath;
            }
        }

        if( !empty($filename) )
            $path = $this->GetTemplate($filename,true);

        //CCDebug::LogVar('tpath',$path);

        if( empty($path) )
        {
            print( "<h3>Can't find template: <span style='color:red'>$macropath</span></h3>");
            if( !CCDebug::IsEnabled() )
                CCUtil::Send404();
            CCDebug::PrintVar($this);
        }

        return array( $path, $funcname );
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
    *    priority (e.g. 'ccskins/plain/page.tpl')
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
        
        $user_paths = CCUtil::SplitPaths( $CC_GLOBALS['template-root'], CC_DEFAULT_SKIN_SEARCH_PATHS );
        
        if( empty($CC_GLOBALS['search-user-path-first']) )
        {
            $paths = array_merge( array('./'), $this->template_stack, $this->map_stack, $user_paths  );
        }
        else
        {
            $paths = array_merge( array('./'), $user_paths, $this->template_stack, $this->map_stack );
        }
        
        $arr = array_filter( array_unique( $paths ) );
        
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
            die("\"/>Can't find: $partial");
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
        
        $sfile = is_array($file) ? md5(join('',$file)) : md5($file);
        if( !isset($this->search_cache[$sfile]) )
        {
            $dirs = $this->GetTemplatePath();
            $this->_latest_search_path = $dirs; // for debugging
            $found = CCUtil::SearchPath( $file, $dirs, '', $real_path, CC_SEARCH_RECURSE_DEFAULT);
            $this->search_cache[$sfile] = $found;
            return $found;
        }

        return $this->search_cache[$sfile];
    }

    function UserGraphic($partial)
    {
        global $CC_GLOBALS;
        $path = $CC_GLOBALS['image-upload-dir'] . $partial;
        if( file_exists($path) )
            return ccd($path);
        die("\"/>Can't find graphic $partial");
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
        if( file_exists($skintpl) )
        {
            $this->_parse($skintpl);
            return $skintpl;
        }

        if( file_exists($skinphp) )
        {
            $php = file_get_contents($skinphp);
            $A =& $this->vars;
            $T =& $this;
            eval( '?>' . $php);
        }

        return $skinphp;
    }

    function _parse($file,$forceParse=false)
    {
        $file = str_replace( '\\', '/', $file );

        if( $forceParse || !in_array( $file, $this->files ) )
        {
            preg_match( '#([^/]+)\.([a-z]+)$#i', $file, $m );

            $bfunc = '_t_' . str_replace('_xml','',preg_replace('/[^a-z]+/i','_',$m[1])) . '_';

            // these will be visible to included/eval'd code

            $A =& $this->vars;
            $T =& $this;

            //CCDebug::Log("Loading/parsing: $file");

            if( $m[2] == 'tpl' )
            {
                require_once('cchost_lib/cc-tpl-parser.php');
                //CCDebug::Log("parsing: $file");
                $parsed = cc_tpl_parse_file($file,$bfunc);
                //if( preg_match('/skin.tpl/',$file) ) CCDebug::PrintVar($parsed);
                $ret = eval( '?>' . $parsed);

                if( $ret != 'ok' && CCUser::IsAdmin() )
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


    function _inner_include($path,$funcname='',$forceParse=false)
    {
        //CCDebug::Log("_inner call: $path / $funcname");

        $this->_push_path($path);
        $this->_parse($path,$forceParse);
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
    }

    function ClearCache() 
    { 
        if( function_exists('_catch_log') )
            _catch_log( array(), 'Clearing template head cache' );
        $files = CCTemplate::_get_head_files(false);
        foreach( $files as $F )
            if( file_exists($F) )
                unlink($F);
        return true; 
    }

}

function skin_OnConfigChange( &$spec, &$old, &$new_value )
{
    if( count($new_value) != 1 || !array_key_exists('mod-stamp',$new_value) )
    {
        CCSkin::ClearCache();
    }
}


class CCSkinMacro extends CCSkin
{
    var $_macro;
    var $_macro_file;
    var $_macro_macro;
    var $_macro_loaded;
    var $_macro_props;

    function CCSkinMacro($macro)
    {
        global $CC_GLOBALS;
        $this->CCSkin($CC_GLOBALS['skin-file']);
        $this->_macro = $macro;
        $this->_macro_loaded = false;
        list( $this->_macro_file, $this->_macro_macro ) = $this->_inner_lookup_macro($macro);
        $this->_macro_props = parent::GetProps($this->_macro_file);
    }

    function Call($with)
    {
        $this->_hello = true;
        return parent::Call($with);
    }

    function LookupMacro($macropath='')
    {
        if( empty($macropath) )
            return array( $this->_macro_file, $this->_macro_macro );
        return parent::LookupMacro($macropath);
    }
    
    function GetSkinFile($macropath='')
    {
        return $this->_macro_file;
    }

    function GetProps($macropath='')
    {
        return $this->_macro_props;
    }

    function SetAllAndPrint( $args, $headers=false )
    {
        // there's some magic here... because SetAll&Print will call this->Call
        // which will call LookupMacro with a null macro
        $args['auto_execute'][] = '';
        $ret = parent::SetAllAndPrint($args,$headers,!$this->_macro_loaded);
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
