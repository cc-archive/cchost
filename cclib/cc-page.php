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
                          CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{docfilename}', _('Displays XHTML template'), CC_AG_VIEWFILE  );
        CCEvents::MapUrl( 'docs',     array( 'CCPageAdmin', 'ViewFile' ),  
                          CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{docfilename}', _('Displays XHTML template (alias for viewfile)'), CC_AG_VIEWFILE );
    }

    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        //CCDebug::PrintVar($args);
        if( strtolower($args['format']) != 'page' )
            return;

        extract($args);

        if( !empty($title) )
            CCPage::SetTitle($title);

        if( !empty($template_args) )
        {
            foreach( $template_argas as $K => $V )
                CCPage::PageArg($K,$V);
        }

        $dochop = isset($chop) && $chop > 0;
        $chop   = isset($chop) ? $chop : 25;
        CCPage::PageArg('chop',$chop);
        CCPage::PageArg('dochop',$dochop);
        CCPage::PageArg( 'records', $records, $template );

        // this needs to be done through CCDataview:

        if( !empty($records) && (count($records) > 1) && (empty($paging) || ($paging == 'on') || ($paging='default')) )
        {
            CCPage::AddPagingLinks($queryObj->dataview);
        }

        if( !isset( $qstring ) )
            $qstring = $queryObj->SerializeArgs($args);

        CCPage::PageArg('qstring',$qstring );        

        if( empty($template) )
            $template = 'list_files';

        CCPage::PageArg( 'records', $records, $template);

        /* 

            I think this is supposed to happen through the templates 
            using 'qstring'???
        */
        /*
        if( !empty($feed) )
        {
            // Let folks know they can subscribe to this query

            $feed = strlen($feed) > 10 ? substr($feed,0,8) . '...' : $feed;
            $tags = empty($tags) ? '' : $tags;
            $qstring = empty($qstring) ? '' : $qstring;
            CCFeed::AddFeedLinks( $tags, $qstring, $feed);
        }

        */
        $result = true;
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
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['homepage'] =
                array(  'label'      => _('Homepage'),
                        'form_tip'   => sprintf(_('For example a file: docs/home %sor a navigation tab: view/media/home'), '<br />'),
                       'value'       => '',
                       'formatter'   => 'textedit',
                       'flags'       => CCFF_POPULATE);
            $fields['default-feed-tags'] =
                array( 'label'       => _('Default Feed Tags'),
                       'form_tip'    => _('Comma separated list of tags to use when no other feed is specificed (e.g. audio,remix).') . ' ' 
                                        . _('Leave blank for no default feed.'),
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
* @see CCSkin::CCSkin()
*/
class CCPage extends CCSkin
{
    var $_body_template;
    var $_have_forms;

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

        $this->CCSkin( $CC_GLOBALS['skin-file'] );
        $this->vars['auto_execute'][] = 'page.tpl';

        $this->vars['show_body_header'] = true;
        $this->vars['show_body_footer'] = true;
        $this->vars['chop'] = 20;
        $this->vars['dochop'] = true;
        $this->vars['bread_crumbs'] = array();
        $this->vars['crumb_seperator'] = ' &raquo; ';
        $this->_have_forms = false;
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
            //CCUtil::Send404();
        }
        else
        {
            // see notes in CCPage::Show for why this is important
            if( empty($page->vars['page-title']) )
            {
                $contents = file_get_contents($file);
                $page->_check_for_title($contents);
            }

            $page->_body_template = $file;
        }
    }

    /**
    * Make a variable available to the page when rendering
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

    function AddMacro($macroname)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->_add_macro($macroname);
    }

    function _add_macro($macroname)
    {
        parent::AddMacro($macroname);
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

        $arg = func_num_args() == 1 ? $title : func_get_args();
        $page->SetArg('page-title', $arg );
        $page->SetArg('page-caption', $arg );
    }

    /**
    * Force the display (HTML output to client) of the current page
    *
    * @param string $body Specific HTML for the client area of the page
    */
    function PrintPage( & $body )
    {
        CCPage::AddContent($body);
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

        if( !CCUtil::IsHTTP() )
            return;

        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
            $page =& CCPage::GetPage();
        else
            $page =& $this;

        $page->vars['menu_groups'] = CCMenu::GetMenu();

        if( !empty($page->_body_template) )
        {
            $page->AddMacro($page->_body_template);
        }

        if( empty($CC_GLOBALS['hide_sticky_tabs']) && 
            empty($page->vars['tab_info']) )
        {
            $naviator_api = new CCNavigator();
            $naviator_api->ShowTabs($page);
        }

        if( empty($CC_GLOBALS['hide_sticky_search']) )
        {
            $page->vars['sticky_search'] = true;
            $page->vars['advanced_search_url'] = ccl('search');
        }

        /*
            Google puts a lot of emphasis on <title> tag so yes, we
            go to great lengths to make sure there is something 
            relevant there.
        */
        if( empty($page->vars['page-caption']) )
        {
            if( empty($page->vars['page-title']) )
            {
                if( !empty($page->vars['html_content']) )
                {
                    foreach( $page->vars['html_content'] as $contents )
                    {
                        $page->_check_for_title($contents);
                        if( !empty($page->vars['page-caption']) )
                            break;
                    }
                }
            }
            else
            {
                $page->vars['page-caption'] = $page->vars['page-title'];
            }
        }

        CCEvents::Invoke(CC_EVENT_RENDER_PAGE, array( &$page ) );

        if( !empty($_REQUEST['dump_page']) ) // && $isadmin )
             CCDebug::PrintVar($page->vars,false);

        if( !empty($CC_GLOBALS['no-cache']) )
            CCEvents::_send_no_cache_headers();

        if( $print )
            $page->SetAllAndPrint(array());
        else
            return $page->SetAllAndParse(array());

    }

    function GetViewFile($filename,$real_path=true)
    {
        global $CC_GLOBALS;
        $files = CCSkin::GetFilenameGuesses($filename);
        return CCUtil::SearchPath( $files, $CC_GLOBALS['files-root'], 'ccskins/shared', $real_path, true );
    }

    function GetViewFilePath()
    {
        global $CC_GLOBALS;
        return CCUtil::SplitPaths( $CC_GLOBALS['files-root'], 'ccskins/shared/' );
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
    * @param string $prompt Contents of message 
    */
    function Prompt($prompt)
    {
        $prompt = func_num_args() == 1 ? $prompt : func_get_args();
        CCPage::AddPrompt('system_prompt', $prompt );
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

        $page->vars['forms'][] = array(
                                    $form->GetTemplateMacro(),
                                    $form->GetTemplateVars() );
        if( !$page->_have_forms )
        {
            $page->vars['macro_names'][] = 'print_forms';
            $page->_have_forms = true;
        }
    }

     /**
    * Add a html content into the body of the page
    *
    * @param string $html_text The text to add
    */
    function AddContent($html_text)
    {
        if( empty($this) || (strtolower(get_class($this)) != 'ccpage') )
           $page =& CCPage::GetPage();
         else
           $page =& $this;

        $page->vars['html_content'][] = $html_text;

        if( empty($page->vars['macro_names']) || !in_array( 'print_html_content', $page->vars['macro_names'] ) )
            $page->vars['macro_names'][] = 'print_html_content';
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

        //if( substr($script_url,0,7) != 'http://' )
        //    $script_url = ccd( CCSkin::Search($script_url) );

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
            $page->vars['macro_names'][] = 'print_prompts';
        elseif( !in_array( 'print_prompts', $page->vars['macro_names'] ) )
            array_unshift($page->vars['macro_names'],'print_prompts');
    }

    function _check_for_title($contents)
    {
        // um, bit of a hack but I can't figure out another
        // to have the <h1> tag in the file end up in the title 
        // of the browser (?),
        $r1 = '<h1[^>]+>%\(([^)]+)\)%'; // macro
        $r2 = "<h1[^>]*><\?=[^']+'([^']+)'"; // global string
        $r3 = '<h1[^>]*>(.*)</h1'; // normal h1
        if( preg_match("#(($r1)|($r2)|($r3))#Uis",$contents,$m) )
            $this->vars['page-caption'] = stripslashes($m[ count($m) - 1 ]);
    }
    /**
    * Calculate and add paging links ( next/prev ) for listings
    *
    * @param object $table A instance of the CCTable being queried
    * @param string $sql_where The SQL WHERE clause to limit queries
    * @param integer $limit Override system defaults for how many records in a page
    */
    function AddPagingLinks($table_or_dataview,$sql_where='',$limit ='')
    {
        global $CC_GLOBALS;

        $args = array();

        if( empty($limit) )
        {
            $limit    = empty($CC_GLOBALS['max-listing']) ? 12 : $CC_GLOBALS['max-listing'];
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

        if( empty($table_or_dataview->_key_field) )
        {
            $sql = $table_or_dataview->sql_count;
            $all_row_count = CCDatabase::QueryItem($sql);
        }
        else
        {
            $table_or_dataview->SetOffsetAndLimit(0,0);
            $all_row_count = $table_or_dataview->CountRows($sql_where);
            $table_or_dataview->SetOffsetAndLimit($offset,$limit);
        }
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

        }

        return $args;
    }
}



?>
