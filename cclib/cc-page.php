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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPageAdmin', 'OnMapUrl') );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPageAdmin', 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_APP_INIT,           array( 'CCPageAdmin', 'OnAppInit') );

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
        CCEvents::MapUrl( 'viewfile', array( 'CCPageAdmin', 'ViewFile' ),  CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'homepage', array( 'CCPageAdmin', 'Homepage' ),  CC_DONT_CARE_LOGGED_IN );
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
        CCPage::SetTitle(_("Welcome to ccHost"));
        CCPage::ViewFile('home.xml');
    }

    /**
    * Display a file in hte client area of the page (wrapper)
    *
    * @see CCPage::ViewFile()
    */
    function ViewFile($template)
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
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['homepage'] =
               array(  'label'      => 'Homepage',
                       'form_tip'   => 'example: contest/mycontest<br />or: files<br />or: viewfile/home.xml',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE);

            $fields['style-sheet'] =
                       array( 'label'       => 'Skin Style',
                               'form_tip'   => 'Default style sheet for this view',
                               'formatter'  => 'select',
                               'options'    => CCTemplateAdmin::GetTemplates('skin','css'),
                               'flags'      => CCFF_POPULATE );
/*
            $fields['page-template'] =
                       array( 'label'       => 'Page Template',
                               'form_tip'   => 'Default page template for this view',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED);
*/
            $fields['max-listing'] =
                       array( 'label'       => 'Max Items Per Page',
                               'form_tip'   => 'Maximum number of uploads, users in a listing',
                               'class'      => 'cc_form_input_short',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED);
            $fields['default-feed-tags'] =
                       array( 'label'       => 'Default Feed Tags',
                               'form_tip'   => 'Comma separated list of tags to use when no other feed is specificed.'
                                               . ' (e.g. audio,remix) Leave blank for no default feed.',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE);
        }

    }
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
    var $_page_args;
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

        $this->CCTemplate( $CC_GLOBALS['skin-page'] );

        $configs =& CCConfigs::GetTable();
        $this->_page_args = $configs->GetConfig('ttag');
        $this->_page_args['show_body_header'] = true;
        $this->_page_args['show_body_footer'] = true;
        $this->_page_args['chop'] = true;
        $this->_page_args['bread_crumbs'] = array();
        $this->_page_args['crumb_seperator'] = ' &raquo; ';
    }

    /**
    * Returns the a singleton instance of the page that will be displayed
    * 
    */
    function & GetPage()
    {
        static $_page;
        if( empty($_page) )
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
    * @param string $template Name of file (in the 'ccfiles' directory) to parse and display
    */
    function ViewFile($template)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;
        
        $page->_body_template = $template;
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

        $page->_page_args[$name] = $value;

        if( !empty($macroname) )
            $page->_page_args['macro_names'][] = $macroname;
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

        if( isset($page->_page_args[$name]) )
            return $page->_page_args[$name];

        return '';
    }

    /**
    * Sets the the title for the page
    *
    * @param string $title The title.
    */
    function SetTitle( $title )
    {
        CCPage::PageArg('page-title',$title);
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
    function SetStyleSheet( $css, $title = 'Style Sheet' )
    {
        CCPage::AddLink( 'head_links', 'stylesheet', 'text/css', ccd($css), $title );
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

        $page->_page_args['show_body_header'] = $show_header;
        $page->_page_args['show_body_footer'] = $show_footer;
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
        $page->_page_args = array_merge($page->_page_args,$CC_GLOBALS); // is this right?

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
                $page->_page_args['custom_macros'][] = str_replace( '/', '.xml/', $K );
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
                $page->SetStyleSheet($_COOKIE[$cookiename],"User Styles");
                $style_set = true;
            }
        }

        $settings = $configs->GetConfig('settings');

        if( !$style_set )
        {
            $page->SetStyleSheet($settings['style-sheet'],'Default Style');
        }

        $isadmin = CCUser::IsAdmin();

        /////////////////
        // Step 3
        //
        // Populate current user's name
        //
        if( CCUser::IsLoggedIn() )
        {
            $page->_page_args['logged_in_as'] = CCUser::CurrentUserName();
            $page->_page_args['logout_url'] = ccl('logout');
            $page->_page_args['is_logged_in'] = 1;
        } else {
            $page->_page_args['is_logged_in'] = 0;
        }
        $page->_page_args['is_admin']  = $isadmin;
        $page->_page_args['not_admin'] = !$isadmin;

        /////////////////
        // Step 4
        //
        // Populate menu
        //
        $page->_page_args['menu_groups'] = CCMenu::GetMenu();

        /////////////////
        // Step 5
        //
        // Populate a custom body template
        //
        // 
        if( !empty($page->_body_template) )
        {
            $vfile = $page->GetViewFile($page->_body_template);
            $template = new CCTemplate( $vfile, true );
            $body =& $template->SetAllAndParse($page->_page_args, false, $isadmin);
            $page->AddPrompt('body_text',$body);
        }

        /////////////////
        // Step 6
        //
        // Show the current set of tabs at the top of the screen
        //
        // 
        if( empty($CC_GLOBALS['hide_sticky_tabs']) && empty($page->_page_args['tab_info']) )
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
            $page->_page_args['sticky_search'] = true;
            $page->_page_args['advanced_search_url'] = ccl('search');
        }

        CCEvents::Invoke(CC_EVENT_RENDER_PAGE, array( &$page ) );

        if( !empty($_REQUEST['dump_page']) && $isadmin )
             CCDebug::PrintVar($page->_page_args,false);

        //CCDebug::LogVar('page environment',$page->_page_args);
    
        if( !empty($CC_GLOBALS['no-cache']) )
            CCEvents::_send_no_cache_headers();

        if( $print )
            $page->SetAllAndPrint($page->_page_args, CCUser::IsAdmin() );
        else
            return( $page->SetAllAndParse($page->_page_args, false, CCUser::IsAdmin()) );
    }

    function GetViewFile($filename)
    {
        global $CC_GLOBALS;

        return CCUtil::SearchPath( $filename, $CC_GLOBALS['files-root'], 'ccfiles' );
    }

    function GetViewFilePath()
    {
        global $CC_GLOBALS;

        return CCUtil::SplitPaths( $CC_GLOBALS['files-root'], 'ccfiles' );
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

        $page->_page_args = array_merge($page->_page_args, $form->GetTemplateVars());
        $page->_page_args['macro_names'][] = $form->GetTemplateMacro();
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

        if( empty($page->_page_args[$group]) || !in_array($script_macro_name,$page->_page_args[$group]) )
            $page->_page_args[$group][] = $script_macro_name;
    }

    /**
    * Include a script link in the head of the page
    * 
    * 
    * @param string $script_url Path to .js file
    */
    function AddScriptLink($script_url)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $arr = array();
        if( !empty($page->_page_args['script_links']) )
            $arr = $page->_page_args['script_links'];
        $arr[] = $script_url;
        $page->_page_args['script_links'] = array_unique($arr);
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

        $page->_page_args['tab_info'] = $tab_info;
        $page->_page_args['page_tabs'] = $macro;
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

        $page->_page_args['bread_crumbs'] = $trail;
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

        $page->_page_args[$placement][] = array(   'rel'       => $rel,
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

        $page->_page_args['prompts'][] = array(  'name' => $name,
                                                 'value' => $value );

        if( empty($page->_page_args['macro_names']) )
            $page->_page_args['macro_names'][] = 'show_prompts';
        elseif( !in_array( 'show_prompts', $page->_page_args['macro_names'] ) )
            array_unshift($page->_page_args['macro_names'],'show_prompts');
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
               $url = url_args( $current_url, 'offset=' . ($offset - $limit) );
               $back_text = '<<< ' . _('Back');
               CCPage::PageArg('back_text',$back_text);
               CCPage::PageArg('prev_link',$url);
               $args['prev_link'] = $url;
               $args['back_text'] = $back_text;

            }
            if( $offset + $limit < $all_row_count )
            {
               $url = url_args( $current_url, 'offset=' . ($offset + $limit) );
               $more_text = _('More') . ' >>>';
               CCPage::PageArg('more_text',$more_text);
               CCPage::PageArg('next_link',$url);
               $args['next_link'] = $url;
               $args['more_text'] = $more_text;
            }

            $table->SetOffsetAndLimit($offset,$limit);
        }

        return $args;
    }
}



?>
