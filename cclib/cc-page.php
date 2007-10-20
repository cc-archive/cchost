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
* Main page display module
*
* @package cchost
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-template.php');
require_once('cclib/cc-menu.php');
require_once('cclib/cc-navigator.php');

/**
* Page template administration API
*
* Handles events and basic event routing for the page template used throughout the site
* @package cchost
* @subpackage admin
*
*/
class CCPageAdmin
{
    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrl()
    {
        CCEvents::MapUrl( 'viewfile', array( 'CCPageAdmin', 'ViewFile' ),  
                          CC_DONT_CARE_LOGGED_IN, '', '{docfilename}', _('Displays XHTML template'), CC_AG_VIEWFILE  );
        CCEvents::MapUrl( 'docs',     array( 'CCPageAdmin', 'ViewFile' ),  
                          CC_DONT_CARE_LOGGED_IN, '', '{docfilename}', _('Displays XHTML template (alias for viewfile)'), CC_AG_VIEWFILE );
        CCEvents::MapUrl( 'homepage', array( 'CCPageAdmin', 'Homepage' ),  
                          CC_DONT_CARE_LOGGED_IN, '', '', _('Displays home page assigned in config'), CC_AG_VIEWFILE );
    }

    /**
    * Event handler for {@link CC_EVENT_APP_INIT}
    * 
    * Maps an alias based on user preferences for 'homepage'
    */
    function OnAppInit()
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $homepage = $settings['homepage'];

        if( !empty($homepage) )
        {
            CCEvents::AddAlias('homepage',$homepage);
        }
    }

    /**
    * Displays the the default home page
    */
    function Homepage()
    {
        CCPage::ViewFile('home.xml');
    }

    /**
    * Display a file in the client area of the page (wrapper)
    *
    * @see CCPage::ViewFile()
    */
    function ViewFile($template='')
    {
        CCPage::ViewFile($template);
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
        require_once('cclib/cc-template.inc');

        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['homepage'] =
                array(  'label'      => _('Homepage'),
                        'form_tip'   => sprintf(_('example: contest/mycontest %s or files: viewfile/home.xml'), '<br />'),
                       'value'       => '',
                       'formatter'   => 'textedit',
                       'flags'       => CCFF_POPULATE);

            $fields['skin-file'] =
                array( 'label'       => _('Skin'),
                       'form_tip'    => _('Default skin for this view'),
                       'formatter'   => 'select',
                       'options'     => CCTemplateAdmin::GetTemplates('skin','(tpl|php)'),
                       'flags'       => CCFF_POPULATE );
            $fields['skin'] =
                array( 'label'       => '',
                       'formatter'   => 'skin_grabber',
                       'flags'       => CCFF_POPULATE);
            $fields['max-listing'] =
                array( 'label'       => _('Max Items Per Page'),
                       'form_tip'    => _('Maximum number of uploads, users in a listing'),
                       'class'       => 'cc_form_input_short',
                       'formatter'   => 'textedit',
                       'flags'       => CCFF_POPULATE | CCFF_REQUIRED);
            $fields['default-feed-tags'] =
                array( 'label'       => _('Default Feed Tags'),
                       'form_tip'    => _('Comma separated list of tags to use when no other feed is specificed (e.g. audio,remix).') . ' ' . _('Leave blank for no default feed.'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE);
        }

    }
}

function generator_skin_grabber($form,$varname,$value='',$class='')
{
    return( "<input type='hidden' id=\"$varname\" name=\"$varname\" value=\"$value\" />" );
}

function validator_skin_grabber($form,$fieldname)
{
    preg_match( '/skin-(.+)\.[^\.]{3,4}$/', $_POST['skin-file'], $m );
    $form->SetFormValue( $fieldname, $m[1] );
    return true;
}

/**
* Page template for the entire site
*
* This class is designed as a singleton instance. Calling GetPage() will 
* return the one instance for this session or (even better) simply call 
* any method using the CCPage::<i>method</i> static syntax.
*
* For less specialized use, you should use the base class instead.
*
* @see GetPage
* @see CCTemplate::CCTemplate()
*/
class CCPage extends CCTemplate
{
    var $_body_template;

    /**
    * Constructor
    *
    * Do not call this for the main page's output. Use GetPage function instead to get 
    * the global singleton instance.
    *
    * @see GetPage
    */
    function CCPage()
    {
        global $CC_GLOBALS;

        $this->CCTemplate( $CC_GLOBALS['skin-file'] );

        $this->vars['show_body_header'] = true;
        $this->vars['show_body_footer'] = true;
        $this->vars['chop'] = true;
        $this->vars['bread_crumbs'] = array();
        $this->vars['crumb_seperator'] = ' &raquo; ';
    }


    /**
    * Returns the a singleton instance of the page that will be displayed
    * 
    */
    function & GetPage($force = false)
    {
        static $_page;
        if( empty($_page) || $force )
            $_page = new CCPage();
        return($_page);
    }

    /**
    * Displays the contents of an XHTML file in the main client area of the page
    *
    * The file to be displayed must be in the ccfiles directory. It will be
    * parsed through the template engine so it has to be valid XML. All page
    * variables are available to the file so, for example, macro substitions
    * will work.
    * 
    * @param string $template Name of file (in the 'viewfile' directory) to parse and display
    */
    function ViewFile($template='')
    {
        if( empty($template) )
            CCUtil::SendBrowserTo(ccl());

        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        if( !($file = $page->GetViewFile($template)) && !preg_match('/\.xml$/',$template) )
        {
            $file = $page->GetViewFile($template . '.xml');
        }

        if( empty($file) )
        {
            $page->Prompt( sprintf(_("Can't find %s template"),$template) );
            CCUtil::Send404();
        }
        else
        {
            $page->_body_template = $file;
        }

    }

    function _check_for_title(&$page,$contents)
    {
        // um, bit of a hack but I can't figure out another
        // to have the <h1> tag in the file end up in the title 
        // of the browser (?),
        $r1 = '<h1[^>]*>(.*)</h1'; // normal h1
        $r2 = '<h1[^>]+((_|CC_Lang)\([\'"]([^\)]+)[\'"]\))[^<]+/>'; // lang'ized
        if( preg_match("#(($r1)|($r2))#Uis",$contents,$m) )
        {

            // inner most capture will be used for title bar
            $page->vars['page-caption'] = stripslashes($m[ count($m) - 1 ]);

            // disable the tempalte's H1 code
            $page->vars['page-title'] = '';
            $page->_reject_title = true;
        }
    }

    /**
    * Make a variable available to the template parser
    *
    * @param string $name The name of the variable as will be seen in the template
    * @param mixed  $value The value that will be substituted for the 'name'
    * @param string $macroname The name of a specific macro to invoke during template generation
    */
    function PageArg($name, $value='', $macroname='')
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->SetArg($name,$value,$macroname);
    }

    function PageMacro($macroname)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->AddMacro($macroname);
    }


    /**
    * Get a variable available to the template parser
    *
    * @param string $name The name of the variable as will be seen in the template
    * @returns mixed $value Value of template variable
    */
    function GetPageArg($name)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        if( isset($page->vars[$name]) )
            return $page->vars[$name];

        return '';
    }

    /**
    * Sets the the title for the page
    *
    * @param string $title The title.
    */
    function SetTitle( $title )
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        if( empty($page->_reject_title) )
        {
            CCPage::PageArg('page-title',$title);
        }
        else
        {
            CCPage::PageArg('page-title',false);
        }
    }

    /**
    * Force the display (HTML output to client) of the current page
    *
    * @param string $body Specific HTML for the client area of the page
    */
    function PrintPage( & $body )
    {
        CCPage::AddPrompt('body_text',$body);
        CCPage::Show();
    }

    /**
    * Add a stylesheet link to the header of the page
    *
    * @param string $css Name of the css file (inlcuding relative path)
    * @param string $title Title of link
    */
    function SetStyleSheet( $css, $title = '' )
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars['style_sheets'][] = $css;
    }

    /**
    * Show or hide the banner, menus and footers on the page
    *
    * @param bool $show_header true means show banner and menus (this is default)
    * @param bool $show_footer true means show footer (this is default)
    */
    function ShowHeaderFooter( $show_header, $show_footer )
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars['show_body_header'] = $show_header;
        $page->vars['show_body_footer'] = $show_footer;
    }

    /**
    * Output the page to the client
    *
    
    */
    function Show($print=true)
    {
        global $CC_GLOBALS;

        /////////////////
        // Step -2
        //
        // Don't do this method from command line
        //
        if( !CCUtil::IsHTTP() )
            return;

        /////////////////
        // Step -1
        //
        // Allow static call
        //
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        /////////////////
        // Step 1
        //
        // Merge config into already existing page args
        //
        $page->vars = array_merge($page->vars,$CC_GLOBALS); // is this right?

        /////////////////
        // Step 1a
        //
        // Trigger custom macros
        //
        // 
        $configs =& CCConfigs::GetTable();
        $tmacs = $configs->GetConfig('tmacs');

        $first_key = key($tmacs);

        // older installs don't have file/ prefix,
        // fix that right here...
        if( strpos( $first_key, '/' ) === false )
        {
            $newmacs = array();
            foreach( $tmacs as $K => $V )
            {
                if( strpos( $K, '/' ) === false )
                    $K = "custom/$K";
                $newmacs[$K] = $V;
            }
            $configs->SaveConfig('tmacs',$newmacs,'',false);
            $tmacs = $newmacs;
        }

        reset($tmacs);

        foreach( $tmacs as $K => $V )
        {
            if( $V )
            {
                $page->vars['custom_macros'][] = str_replace( '/', '.xml/', $K );
            }
        }

        /////////////////
        // Step 2
        //
        // Pick style....
        //
        $style_set = false;
        if( CCUser::IsLoggedIn() )
        {
            $cookiename = 'style-sheet-' . CCUser::CurrentUserName();
            if( !empty($_COOKIE[$cookiename]) )
            {
                $page->SetStyleSheet($_COOKIE[$cookiename],_("User Styles"));
                $style_set = true;
            }
        }

        $isadmin = CCUser::IsAdmin();

        /////////////////
        // Step 3
        //
        // Populate current user's name
        //
        // (do this at ctor now)

        /////////////////
        // Step 4
        //
        // Populate menu
        //
        $page->vars['menu_groups'] = CCMenu::GetMenu();

        /////////////////
        // Step 5
        //
        // Populate a custom body template
        // This code assumes GetViewFile has already been called on _body_template
        // 
        if( !empty($page->_body_template) )
        {
            $page->AddMacro($page->_body_template);
        }

        // wow...
        if( !empty($page->vars['prompts']) )
        {
            $prompts = $page->vars['prompts'];
            foreach( $prompts as $P )
                if( $P['name'] = 'body_text' )
                    $page->_check_for_title($page,$P['value']);
        }
        if( !empty($page->vars['body_html']) )
            $page->_check_for_title($page,$page->vars['body_html']);

        /////////////////
        // Step 6
        //
        // Show the current set of tabs at the top of the screen
        //
        // 
        if( empty($CC_GLOBALS['hide_sticky_tabs']) && empty($page->vars['tab_info']) )
        {
            $naviator_api = new CCNavigator();
            $naviator_api->ShowTabs($page);
        }

        /////////////////
        // Step 7
        //
        // Show a search box in the banner
        //
        // 
        if( empty($CC_GLOBALS['hide_sticky_search']) )
        {
            $page->vars['sticky_search'] = true;
            $page->vars['advanced_search_url'] = ccl('search');
        }

        CCEvents::Invoke(CC_EVENT_RENDER_PAGE, array( &$page ) );

        if( !empty($_REQUEST['dump_page']) ) // && $isadmin )
             CCDebug::PrintVar($page->vars,false);

        // CCDebug::LogVar('page environment',$page->vars);
    
        if( !empty($CC_GLOBALS['no-cache']) )
            CCEvents::_send_no_cache_headers();

        //CCDebug::Chronometer($page_print);

        if( $print )
            $page->SetAllAndPrint(array(), CCUser::IsAdmin() );
        else
            return( $page->SetAllAndParse(array(), false, CCUser::IsAdmin()) );
        //CCDebug::Log( "Page print: " . CCDebug::Chronometer($page_print) );
    }

    function GetViewFile($filename,$real_path=true)
    {
        global $CC_GLOBALS;
        $files = array( $filename . '.php',
                        $filename . '.tpl',
                        $filename );
        return CCUtil::SearchPath( $files, $CC_GLOBALS['files-root'], 'ccskins/pages', $real_path );
    }

    function GetViewFilePath()
    {
        global $CC_GLOBALS;
        return CCUtil::SplitPaths( $CC_GLOBALS['files-root'], 'ccskins/pages' );
    }
    
    /**
    * Output a div with the class 'php_error_message'
    *
    * @param string $err_msg Contents of message
    */
    function PhpError($err_msg)
    {
        CCPage::AddPrompt('php_error_message',$err_msg);
    }

    /**
    * Output a div with the class 'system_error_message'
    *
    * @param string $err_msg Contents of message
    */
    function SystemError($err_msg)
    {
        if( !CCUtil::IsHTTP() )
        {
            print($err_msg);
        }
        else
        {
            CCPage::AddPrompt('system_error_message',$err_msg);
        }
    }

    /**
    * Output a div with the class 'system_prompt'
    *
    * @param string $prompt_text Contents of message
    */
    function Prompt($prompt_text)
    {
        CCPage::AddPrompt('system_prompt',$prompt_text);
    }

    /**
    * Add a form to the page's template variables
    *
    * Use this method to add a form to the page.
    * @see CCForm::GenerateForm()
    * @param object $form The CCForm object to add.
    */
    function AddForm($form)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars = array_merge($page->vars, $form->GetTemplateVars());
        $page->vars['macro_names'][] = $form->GetTemplateMacro();
    }
 
    /**
    * Generates a call out to a client script in the template
    * 
    * How to use this method: 
    <ol><li>Create some client script in a template</li>
    <li>Put it into a named metal-macro block , say 'hover_script'</li>
    <li>Back in PHP call this method with a reference to the script (e.g. 'hover_script')</li></ol>
    * 
    * @param string $script_macro_name The macro with file reference
    * @param bool $place_at_end Set to true if script block requires to be at the end of the page
    */
    function AddScriptBlock($script_macro_name,$place_at_end = false)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $group = $place_at_end ? 'end_script_blocks' : 'script_blocks';

        if( empty($page->vars[$group]) || !in_array($script_macro_name,$page->vars[$group]) )
            $page->vars[$group][] = $script_macro_name;
    }

    /**
    * Include a script link in the head of the page
    * 
    * 
    * @param string $script_url Path to .js file
    */
    function AddScriptLink($script_url,$top=true)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $arr = array();
        $arr_name = $top ? 'script_links' : 'end_script_links';
        if( !empty($page->vars[$arr_name]) )
            $arr = $page->vars[$arr_name];
        $arr[] = $script_url;
        $page->vars[$arr_name] = array_unique($arr);
    }

    /**
    * Add a navigation tab set to the top of the page
    *
    * @param array &$tab_info Array of meta data for tabs
    * @param string $macro Name of macro to invoke
    */
    function AddTabNaviator(&$tab_info,$macro)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $page->vars['tab_info'] = $tab_info;
        $page->vars['page_tabs'] = $macro;
    }


    /**
    * Include a trail of bread crumb urls at the top of the page
    * 
    * $trail arg is an array:
    * <code>
    *   $trail = array( 
    *      array( 'url' => '/',      'text' => 'home' ),
    *      array( 'url' => '/people' 'text' => 'people' ),
    *      array( 'url' => '/people/' . $user, 'text' => $user )
    *    );
    * </code>         
    * @param array $trail Links to display at top of page
    */
    function AddBreadCrumbs($trail)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $page->vars['bread_crumbs'] = $trail;
    }

    /**
    * Add a LINK tag into the output
    *
    * @param string $placement Either 'head_links' or 'feed_links' 
    * @param string $rel The value for REL attribute
    * @param string $type MIME type (e.g. text/css)
    * @param string $href Value for the HREF attribute
    * @param string $title Value for TITLE attribute
    * @param string $link_text Text for footer links (e.g. 'RSS 1.0')
    * @param string $link_help Text beside footer links (e.g. 'Remixes of pathchilla')
    */
    function AddLink($placement, $rel, $type, $href, $title, $link_text = '', $link_help = '')
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars[$placement][] = array(   'rel'       => $rel,
                                                   'type'      => $type,
                                                   'href'      => $href,
                                                   'title'     => $title,
                                                   'link_text' => $link_text,
                                                   'link_help' => $link_help);
    }

    /**
    * Add a prompt div to the page
    *
    * @param string $name Class name of prompt
    * @param string $value Content of prompt message
    */
    function AddPrompt($name,$value)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars['prompts'][] = array(  'name' => $name,
                                                 'value' => $value );

        if( empty($page->vars['macro_names']) )
            $page->vars['macro_names'][] = 'show_prompts';
        elseif( !in_array( 'show_prompts', $page->vars['macro_names'] ) )
            array_unshift($page->vars['macro_names'],'show_prompts');
    }

    /**
    * Calculate and add paging links ( next/prev ) for listings
    *
    * @param object $table A instance of the CCTable being queried
    * @param string $sql_where The SQL WHERE clause to limit queries
    * @param integer $limit Override system defaults for how many records in a page
    */
    function AddPagingLinks(&$table,$sql_where,$limit ='')
    {
        $args = array();

        if( empty($limit) )
        {
            $configs  =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $limit    = $settings['max-listing'];
            if( !$limit )
                $limit = 10;
        }

        if( isset($_REQUEST['offset']) && (intval($_REQUEST['offset']) > 0) )
        {
            $got_offset = true;
            $offset = $_REQUEST['offset'];
        }
        else
        {
            $got_offset = false;
            $offset = 0;
        }

        $table->SetOffsetAndLimit(0,0);
        $all_row_count = $table->CountRows($sql_where);
        if( $limit < $all_row_count )
        {
            $current_url = ccl(CCEvents::_current_action());
            if( count($_GET) > 1 )
            {
                foreach( $_GET as $key => $val )
                {
                    if( $key == 'offset' || $key == 'ccm' )
                        continue;
                    $current_url = url_args( $current_url, "$key=$val" );
                }
            }

            if( $offset )
            {
               $prev_offs = 'offset=' . ($offset - $limit);
               $url = url_args( $current_url, $prev_offs );
               $back_text = '<<< ' . _('Back');
               CCPage::PageArg('back_text',$back_text);
               CCPage::PageArg('prev_link',$url);
               $args['prev_link'] = $url;
               $args['back_text'] = $back_text;
               $args['prev_offs'] = $prev_offs;

            }
            if( $offset + $limit < $all_row_count )
            {
               $next_offs = 'offset=' . ($offset + $limit);
               $url = url_args( $current_url, $next_offs );
               $more_text = _('More') . ' >>>';
               CCPage::PageArg('more_text',$more_text);
               CCPage::PageArg('next_link',$url);
               $args['next_link'] = $url;
               $args['more_text'] = $more_text;
               $args['next_offs'] = $next_offs;
            }

            $table->SetOffsetAndLimit($offset,$limit);
        }

        return $args;
    }
}



?>
