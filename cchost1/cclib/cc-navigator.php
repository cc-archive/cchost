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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCNavigator',  'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCNavigator',  'OnMapUrls'));

/**
* Form for editing the tab pages available in the current config root
*
*/
class CCEditTabPagesForm extends CCGridForm
{
    /**
    * Constructor
    *
    */
    function CCEditTabPagesForm(&$pages)
    {
        $this->CCGridForm();

        $heads = array('Delete','Name', '', '', '' );

        $this->SetColumnHeader($heads);
        $this->SetSubmitText("Save Changes");

        $page_names = array_keys($pages);
        $count = count($page_names);
        for( $i = 0; $i < $count; $i++ )
        {
            $name = $page_names[$i];
            $n = 1;
            $urlbase = ccl('admin','tabs', $name);
            if( $i > 0 )
            {
                $url = url_args($urlbase,'cmd=def');
                $deflink = "<a href=\"$url\">Make Default</a>";
                $delcheck = 'checkbox';
                $defclass = 'cc_file_command';
            }
            else
            {
                $deflink = $defclass = '';
                $delcheck = 'statictext';
            }

            $url = url_args($urlbase,'cmd=edit');
            $editlink = "<a href=\"$url\">Edit Tabs</a>";

            $a = array(  
                array(
                    'element_name'  => "mi[$name][delete]",
                    'value'      => '',
                    'formatter'  => $delcheck,
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => "mi[$name][name]",
                    'value'      => $name,
                    'class'      => 'cc_form_input_short',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                array(
                    'element_name'  => "mi[$name][" . $n++ . "]",
                    'value'      => $editlink,
                    'formatter'  => 'statictext',
                    'class'      => 'cc_file_command',
                    'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                array(
                    'element_name'  => "mi[$name][" . $n++ . "]",
                    'value'      => $deflink,
                    'class'      => $defclass,
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                );

            $this->AddGridRow($name,$a);
        }

        $url = url_args( ccl('admin','tabs'), 'cmd=addpage' );
        $link = "<a href=\"$url\">Add Page</a>";
        $this->SetHelpText("To add a new page click here: $link");
    }
}

/**
* Form for editing the tabs on a specific navigation page
*
*/
class CCEditTabsForm extends CCGridForm
{
    /**
    * Constructor
    *
    */
    function CCEditTabsForm(&$page,$page_name)
    {
        $this->CCGridForm();

        $url = ccl('view',$page_name);
        $vroot_help = ccl('viewfile','howtovirtual.xml');

        $help =<<<END
<p>Some hints:</p>
<ul class="cc_tab_edit_help"><li><b>Name</b><br /> This is used in the url. For example: if the tab name is 'funky' you can browse to the
            page and highlight that tab by using $url/<b>funky</b>. <br /><br /></li>
<li><b>Text</b><br /> This is the text that is used in the tab itself.<br /><br /></li>
<li><b>Help Hint</b><br /> The text that displays when the user hovers over an unselected tab.<br /><br /></li>
<li><b>Function</b><br /> Use this to determine what happens when the user clicks on the tab. You can chose to <ul><li>list out upload
            based on tags</li><li>specify a URL to fetch a form or file</li></ul> <br /></li>
<li><b>Limit</b><br /> If you chose an upload listing check this box to limit the searches to the local <a href="$vroot_help">virtual root</a>. If you chose
            to execute a URL then this has no effect.<br /><br /> </li>
<li><b>Data</b><br /> This fields doubles as <b>either</b>
            <ul><li>A <b>comma separated list</b> of tags to search the uploads database when the use selects the tab.</li>
            <li>An internal ccHost URL (e.g. 'viewfile/myhelp.xml' or 'files/submit')</li></ul>
       The interpretation depends the 'Function' field.
<br /><br />
</li>
</ul>

<input type="submit" name="addtab" id="addtab" value="Add a tab" />.
END;
        $this->SetHelpText($help);
        $heads = array('Delete', 'Name', 'Text', 'Help Hint', 'Function', 'Limit', 'Data', 'Access' );

        $this->SetColumnHeader($heads);

        $tab_names = array_keys($page);
        $count = count($tab_names);
        for( $i = 0; $i < $count; $i++ )
        {
            $delcheck = $i == 0 ? 'statictext' : 'checkbox';

            $name = $tab_names[$i];
            if( $name == 'handler' )
                continue;

            $a = array(  
                array(
                    'element_name'  => "mi[$name][delete]",
                    'value'      => '',
                    'formatter'  => $delcheck,
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => "mi[$name][name]",
                    'value'      => $name,
                    'class'      => 'cc_form_input_short',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                array(
                    'element_name'  => "mi[$name][text]",
                    'value'      => htmlentities($page[$name]['text']),
                    'class'      => 'cc_form_input_short',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                array(
                    'element_name'  => "mi[$name][help]",
                    'value'      => htmlentities($page[$name]['help']),
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                array(
                    'element_name'  => "mi[$name][function]",
                    'value'      => $page[$name]['function'],
                    'formatter'  => 'select',
                    'options'    => array( 'any' => 'Match Any Tags',
                                           'all' => 'Match All Tags',
                                           'url' => 'Execute URL'
                                            ),
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => "mi[$name][limit]",
                    'value'      => empty($page[$name]['limit']) ? '' : 1,
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => "mi[$name][tags]",
                    'value'      => $page[$name]['tags'],
                  //  'class'      => 'cc_form_input_short',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$name][access]",
                    'value'      => $page[$name]['access'],
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only" ),
                    'flags'      => CCFF_NONE ),
                );

            $this->AddGridRow($name,$a);
        }
    }
}


/**
* Tab navigator API
*
*/
class CCNavigator
{
    /**
    * Catch all function for handling all the variations of admin/tabs URLs.
    *
    * Shows and processes various forms related administering tab navigation including
    * the creation, editing and deleteing of tab pages, and the same functions for 
    * individual tabs on every page.
    *
    * @param string $page_name Specific page to edit or if null, manages all pages
    */
    function AdminTabs($page_name='')
    {
        $pages =& $this->_get_pages();

        if( empty($page_name) )
        {
            if( empty($_REQUEST['cmd']) )
            {
                $cmd = 'editpages';
            }
            else
            {
                $cmd = $_REQUEST['cmd'];
            }
        }
        else
        {
            if( !empty($_REQUEST['edittabs']) && !empty($_REQUEST['addtab']) )
            {
                $cmd = 'add';
                unset($_REQUEST['edittabs']);
            }
            elseif( empty($_REQUEST['cmd']) )
            {
                $cmd = 'edit';
            }
            else
            {
                $cmd = $_REQUEST['cmd'];
            }
        }

        switch($cmd)
        {
            case 'addpage':
            {
                $i = 0;
                while( array_key_exists( 'page' . $i , $pages ) )
                    $i++;
                $newpage['page' . $i] = $this->_get_seed_page();
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('tab_pages',$newpage);
                $link = ccl('admin','tabs');
                CCPage::SetTitle("Added New Page");
                CCPage::Prompt("A new page has been added click <a href=\"$link\">here</a> to edit it");
                break;
            }

            case 'editpages':
            {
                CCPage::SetTitle('Edit Tab Pages');
                $form = new CCEditTabPagesForm($pages);
                if( empty($_REQUEST['edittabpages']) || !$form->ValidateFields() )
                {
                    CCPage::AddForm($form->GenerateForm());
                }
                else
                {
                    $mi = $_POST['mi'];
                    CCUtil::StripSlash($mi);
                    $copy = '';
                    foreach( $mi as $oldname => $newdata )
                    {
                        if( array_key_exists('delete',$newdata) )
                            continue;

                        $copy[$newdata['name']] = $pages[$oldname];
                    }
                    $configs =& CCConfigs::GetTable();
                    $configs->SaveConfig('tab_pages',$copy,'',false);
                    CCPage::Prompt("Changes have been saved");
                }
                break;
            }

            case 'add':
            {
                $page = $_POST['mi'];
                CCUtil::StripSlash($page);
                $i = 0;
                while( array_key_exists( 'newtab' . $i , $page ) )
                    $i++;

                $page['newtab' . $i] = array(  'text'   => 'TabText',
                                               'help'   => 'TabHelperText',
                                               'tags'   => 'media',
                                               'limit'  => 1,
                                               'access' => CC_DONT_CARE_LOGGED_IN,
                                               'function' => 'any' );

                // FALL THRU ....
            }
            case 'edit':
            {
                CCPage::SetTitle("Edit Tabs for '$page_name'");
                if( empty($page) )
                    $page = $pages[$page_name];
                $form = new CCEditTabsForm($page,$page_name);
                if( empty($_REQUEST['edittabs']) || !$form->ValidateFields() )
                {
                    CCPage::AddForm($form->GenerateForm());
                }
                else
                {
                    $mi = $_POST['mi'];
                    CCUtil::StripSlash($mi);
                    $keys = array_keys($mi);
                    $count = count($keys);
                    $tabs = array();
                    for( $i = 0; $i < $count; $i++ )
                    {
                        $tab =& $mi[$keys[$i]];
                        if( array_key_exists('delete',$tab ) )
                        {
                            continue;
                        }
                        if( $tab['function'] == 'url' )
                        {
                            if( strtolower(substr($tab['tags'],0,7)) != 'http://' )
                                $data = preg_replace('%^/?(.*)%', '/\1', $tab['tags']  );
                            else
                                $data = $tab['tags'];
                        }
                        else
                        {
                            $data = CCTags::Normalize($tab['tags']);
                        }
                        $tabs[ $tab['name'] ] = 
                            array(  'text'   => $tab['text'],
                                    'help'   => $tab['help'],
                                    'tags'   => $data,
                                    'limit'  => array_key_exists('limit',$tab),
                                    'access' => $tab['access'],
                                    'function' => $tab['function'] );
                    }
                    $configs =& CCConfigs::GetTable();
                    if( !empty($pages[$page_name]['handler']) )
                    {
                        $tabs['handler'] = $pages[$page_name]['handler'];
                    }
                    $edited_page[$page_name] = $tabs;
                    $configs->SaveConfig('tab_pages',$edited_page);
                    $page_url = ccl('view',$page_name);
                    $p = "Changes saved. Click <a href=\"$page_url\">here</a> to see your changes.";
                    CCPage::Prompt($p);
                }
                break;
            }

            case 'def':
            {
                $copy_of_page[$page_name] = $pages[$page_name];
                unset($pages[$page_name]);
                $pages = array_merge($copy_of_page,$pages);
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('tab_pages',$pages,'',false);
                CCPage::SetTitle("Default Page Change");
                $url = ccl('admin','tabs');
                CCPage::Prompt("Changes have been saved. Click <a href=\"$url\">here</a> to continue editing pages");
                break;
            }
        }

    }

    /**
    * Internal: Returns pages current assigned to the current config root
    *
    */
    function & _get_pages()
    {
        $configs =& CCConfigs::GetTable();
        $pages = $configs->GetConfig('tab_pages');
        if( empty($pages) )
        {
            $pages['media'] = $this->_get_seed_page();
            $configs->SaveConfig('tab_pages',$pages);
        }
        return( $pages );
    }

    /**
    * Internal: returns default 'seed' page
    */
    function _get_seed_page()
    {
        $a = array(
             'home' =>  array(  'text'   => cct('Home'),
                                   'help'   => cct('Home page'),
                                   'tags'   => '/viewfile/home.xml',
                                   'limit'  => 0,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'url' ),
             'all'   =>    array(  'text'   => cct('All'),
                                   'help'   => cct('See all the latest uploads'),
                                   'tags'   => 'media',
                                   'limit'  => 0,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),
             'remix' =>    array(  'text'   => cct('Remix'),
                                   'help'   => cct('See the latest remixes'),
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'limit'  => 0,
                                   'tags'   => 'remix',
                                   'function' => 'any' ),
             'song' =>     array(  'text'   => cct('Original'),
                                   'help'   => cct('See recently uploaded originals'),
                                   'limit'  => 0,
                                   'tags'   => 'original',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),
             'picks'   =>    array(  'text'   => cct('Picks'),
                                   'help'   => cct('See picks by the Editorial Staff'),
                                   'limit'  => 0,
                                   'tags'   => 'editorial_pick',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),
             'funky'   =>  array(  'text'   => cct('Funky'),
                                   'help'   => cct('Only the funky stuff please!'),
                                   'limit'  => 0,
                                   'tags'   => 'funky,audio',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'all' ),
             );

        return( $a );
    }

    /**
    * Display a tabbed navigator and the selected page
    *
    * @param string $page_name Name of tab page to display, or if null, uses the default page for the config root
    * @param string $default_tab_name Name of the selected tab, or if null, uses the first tab on the page
    */
    function View($page_name='',$default_tab_name='')
    {
        $page =& CCPage::GetPage();
        $this->ShowTabs($page,true,$page_name,$default_tab_name);
    }

    function ShowTabs(&$page_out,$execute=false,$page_name='',$default_tab_name='')
    {
        global $CC_CFG_ROOT;

        // Step 1. Figure out what page we're loading
        if( !$this->_get_selected_page($page_name,$default_tab_name,$page) )
            return;
        
        // Step 2. Call any custom handler to allow for dynamic removal
        //         of tabs and other hacks
        if( !empty($page['handler']) )
        {
            $handler = $page['handler']['method'];
            if( is_array($handler) && is_string($handler[0]))
            {
                $class = $handler[0];
                $method = $handler[1];
                $obj = new $class;
                $handler = array( $obj, $method );
            }
            call_user_func_array($handler,array(&$page));
        }

        // Step 3. Remove tabs that we're not supposed to see
        $tab_keys = array_keys($page);
        $count = count($page);
        $mask = CCMenu::GetAccessMask();

        for( $i = 0; $i < $count; $i++ )
        {
            if( ($page[$tab_keys[$i]]['access'] & $mask) == 0 )
                unset($page[$tab_keys[$i]]);
        }
        
        // Step 3a. The keys and count might have changed
        $tab_keys = array_keys($page);
        $count = count($page);

        // Step 4. The 'default' tab might have removed above
        if( empty($page[$default_tab_name]) )
        {
            if( $count == 0 )
            {
                $this->_signal_error();
                return;
            }
            $default_tab_name = $tab_keys[0];
        }

        // Step 5. Highlight the selected tab
        for( $i = 0; $i < $count; $i++ )
        {
            $name = $tab_keys[$i];
            if( empty($page[$name]) )
                continue;
            $tab =& $page[$name];
            $tab['url'] = ccl( 'view', $page_name, $name );
            if( $execute && $default_tab_name == $name )
            {
                $tab['selected'] = true;
            }
            else
            {
                $tab['normal'] = true;
            }
        }

        // Step 6. Create a tab info structure the way the HTML 
        //         template wants it

        $tab_info['id']            = $page_name;
        $tab_info['num_tabs']      = $count;
        $tab_info['tab_width']     = intval(100/$count) . '%';

        $default_tab = $page[$default_tab_name];
        $tab_info['selected_text'] = $default_tab['help'];
        $tab_info['tags']          = $default_tab['tags'];
        $tab_info['function']      = $default_tab['function'];
        $tab_info['tabs']          = $page; // array_reverse($page);

        //CCDebug::PrintVar($tab_info,false);
        
        // Step 7. Add the supporting Javascript to the page
        //         for hover hints
        $page_out->AddScriptBlock('tab_script');

        // Step 8. This displays the tab on the page
        $page_out->AddTabNaviator( $tab_info, 'page_tabs' );

        if( $execute )
        {
            // Step 9. Execute the currently selected tab

            if( $default_tab['function'] == 'url' )
            {
                // 9a. The tab is an internal URL to execute, translate it into
                //     action (method + args psuedo-closure) and perform it.

                $url = $default_tab['tags'];
                if( strtolower(substr($url,0,7)) == 'http://' )
                    CCUtil::SendBrowserTo($url);

                $action = CCEvents::ResolveUrl( $url, true );
                //CCDebug::PrintVar($action);
                CCEvents::PerformAction($action);
                // huh?? $page_out->SetTitle(''); // action might have set the title
            }
            else
            {
                // 9b. Let folks know they can subscribe to this query
                //     TODO: although the query isn't limited to this vroot!!
                
                $page_out->SetTitle($default_tab['text']);

                $tagstr = $default_tab['tags'];
                $taghelp = strlen($tagstr) > 10 ? substr($tagstr,0,8) . '...' : $tagstr;
                CCFeeds::AddFeedLinks($tagstr,'','Tags: ' . $taghelp);

                // 9c. Limit queries to this vroot 
                if( $default_tab['limit'] )
                    $where['upload_config'] = $CC_CFG_ROOT;  // todo: this needs to be thought out a bit
                else
                    $where = '';

                // 9d. The tab is a tag-based query of the uploads
                //     database

                CCUpload::ListMultipleFiles($where,$default_tab['tags'],$default_tab['function']);
            }
        }
    }

    /**
    * Internal: returns the current selected page and tab
    */
    function _get_selected_page(&$page_name,&$default_tab_name,&$page)
    {
        $pages =& $this->_get_pages();

        $ok = !empty($pages);
        if( $ok )
        {
            if( empty($page_name) )
            {
                $names = array_keys($pages);
                $page_name = $names[0];
            }
            else
            {
                $ok = array_key_exists($page_name,$pages);
            }
        }
        if( $ok )
        {
            $page = $pages[$page_name];
            $ok = !empty($page);
        }
        if( $ok )
        {
            $tab_keys = array_keys($page);
            if( empty($default_tab_name) )
            {
                $default_tab_name = $tab_keys[0];
            }
            else
            {
                $ok = array_key_exists($default_tab_name,$page);
            }
        }
        if( !$ok )
        {
            $this->_signal_error();
        }

        return( $ok );
    }

    /**
    * Internal: fake out error
    */
    function _signal_error()
    {
        CCPage::SetTitle("System Error");
        CCPage::SystemError('Invalid Path');
    }

    /**
    * Event handler for building admin menus
    *
    */
    function OnAdminMenu(&$items, $scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'tab_pages'   => array(  'menu_text'  => 'Navigator',
                             'menu_group' => 'configure',
                             'help' => 'Create and edit navigator tabs',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 4,
                             'action' =>  ccl('admin','tabs') ),
             );

    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'view',             array('CCNavigator', 'View'),            CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'admin/tabs',       array('CCNavigator', 'AdminTabs'),       CC_ADMIN_ONLY );
    }

}





?>