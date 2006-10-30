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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Module for handling admin UI for navigation tabs
*
* This module is <i>not</i> automatically included
*
* @package cchost
* @subpackage admin
*/

/**
* Form for renaming tab set
*/
class CCRenameNavTabForm extends CCForm
{
    function CCRenameNavTabForm($name)
    {
        $this->CCForm();
        $fields = array(
            'navtab_name' => 
                        array( 'label'      => _('Name'),
                               'value'      => $name,
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => _('This name will be used in the url to show the tabs.'),
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED)
                );
                    
        $this->AddFormFields($fields);

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

        $url = ccl('viewfile','adminhelp.xml') . '#navhelp';
        $addtab = ccl('admin','tabs',$page_name,'addtab');

        $help =
'<p>' . _('%sClick here%s for help with editing navigator tab sets.') .
"</p>\n<p>" . _('%sClick here%s to add another tab.') . "</p>\n<p><i>" . 
_('Clicking on these links will not save your form.') . '</i></p>';

        $help = sprintf($help, "<a href=\"$url\"><b>", "</b></a>", 
                               "<a href=\"$addtab\"><b>", "</b></a>");

        $this->SetHelpText($help);
        $heads = array(_('Delete'), _('Order'), _('Name'), _('Text'), _('Help Hint'), _('Function'), /*'Limit', */_('Data'), _('Access') );

        $this->SetColumnHeader($heads);

        $tab_names = array_keys($page);
        $count = count($tab_names);
        $seloptions = array();
        for( $i = 0; $i < $count; $i++ )
        {
            $t = $i + 1;
            $seloptions[$t] = "$t";
        }

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
                    'element_name'  => "mi[$name][order]",
                    'value'      => $i + 1,
                    'class'      => 'cc_form_input_short',
                    'options'    => $seloptions,
                    'formatter'  => 'select',
                    'flags'      => CCFF_NONE),
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
                    'options'    => array( 'any' => _('Match Any Tags'),
                                           'all' => _('Match All Tags'),
                                           'url' => _('Execute URL'),
                                           'sub' => _('SubTabs'),
                                           'qry' => _('Query')
                                            ),
                    'flags'      => CCFF_NONE ),
/*
                array(
                    'element_name'  => "mi[$name][limit]",
                    'value'      => empty($page[$name]['limit']) ? '' : 1,
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
*/
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
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => _('Logged in users only'),
                                           CC_DONT_CARE_LOGGED_IN => _("Everyone"),
                                           CC_ADMIN_ONLY          => _("Administrators only") ),
                    'flags'      => CCFF_NONE ),
                );

            $this->AddGridRow($name,$a);
        }
    }
}


/**
* Tab navigator Admin API
*
*/
class CCNavigatorAdmin
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
    function AdminTabs($nav_api, $page_name='',$cmd='')
    {
        $pages = $nav_api->_get_pages();

        if( empty($page_name) )
        {
            if( empty($_REQUEST['cmd']) )
                $cmd = 'editpages';
            else
                $cmd = $_REQUEST['cmd'];
        }
        else
        {
            if( empty($cmd) )
                $cmd = 'edit';
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
                CCUtil::SendBrowserTo($link);
                break;
            }

            case 'rename':
            {
                $form = new CCRenameNavTabForm($page_name);
                if( empty($_POST['renamenavtab']) || !$form->ValidateFields() )
                {
                    CCPage::SetTitle(_('Rename Navigator Tabs Set'));
                    CCPage::AddForm( $form->GenerateForm() );
                }
                else
                {
                    $form->GetFormValues($values);
                    $new_name = $values['navtab_name'];
                    $copy = array();
                    foreach( $pages as $name => $data)
                    {
                        if( $name == $page_name )
                            $copy[$new_name] = $data;
                        else
                            $copy[$name] = $data;
                    }
                    $configs =& CCConfigs::GetTable();
                    $configs->SaveConfig('tab_pages',$copy,'',false);
                    $url = ccl('admin','tabs');
                    CCUtil::SendBrowserTo($url);
                }
                break;
            }

            case 'editpages':
            {
                $keys = array_keys($pages);
                $count = count($keys);
                $html = '<table style="width: 60%;margin-top: 40px;">';
                for( $i = 0; $i < $count; $i++ )
                {
                    $pname = $keys[$i];
                    $html .= '<tr><td>Tab set: <b>' . $pname . '</b></td>';
                    $url = ccl('admin','tabs',$pname,'rename');
                    $html .= "<td><a href=\"$url\">[" . _('Rename') . 
                             "]</a></td>";
                    $url = ccl('admin','tabs',$pname,'edit');
                    $html .= "<td><a href=\"$url\">[" . _('Edit tabs') . 
                             "]</a></td>";
                    if( $i == 0 )
                    {
                        $html .= '<td></td><td></td>';
                    }
                    else
                    {
                        $url = ccl('admin','tabs',$pname,'deletepage');
                        $html .= "<td><a href=\"$url\">[" . _('Delete') . 
                                 "]</a></td><td></td>";
                        $url = ccl('admin','tabs',$pname,'makedefault');
                        $html .= "<td></td><td><a href=\"$url\">[" . 
                                 _('Make default') . "]</a></td>";
                    }
                    $html .= "</tr>";
                }
                $html .= "</table>";
                $url = url_args( ccl('admin','tabs'), 'cmd=addpage' );
                $html .= "<p><b><a href=\"$url\">[" . _('Add new tab set') . 
                         "]</a></p>";

                CCPage::SetTitle(_("Manage Navigator Tab Sets"));
                CCPage::AddPrompt('body_text',$html);

                break;
            }

            case 'addtab':
            {
                $page = $pages[$page_name];
                $i = 0;
                while( array_key_exists( 'newtab' . $i , $page ) )
                    $i++;

                $page['newtab' . $i] = array(  'text'   => _('Tab Text'),
                                               'help'   => _('Tab Helper Text'),
                                               'tags'   => 'media',
                                               'limit'  => 0,
                                               'access' => CC_DONT_CARE_LOGGED_IN,
                                               'function' => 'any' );

                $pages[$page_name] = $page;
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('tab_pages',$pages,'',false);
                $url = ccl('admin','tabs',$page_name,'edit');
                CCUtil::SendBrowserTo($url);
            }

            case 'edit':
            {
                CCPage::SetTitle(_("Edit Navigator Tabs:") . " '$page_name'");
                if( empty($page) )
                    $page = $pages[$page_name];
                $form = new CCEditTabsForm($page,$page_name);
                if( empty($_POST['edittabs']) || !$form->ValidateFields() )
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

                        // is tab deleted?
                        
                        if( array_key_exists('delete',$tab ) )
                        {
                            continue;
                        }

                        // massage data based on function
                        
                        if( $tab['function'] == 'url' )
                        {
                            if( strtolower(substr($tab['tags'],0,7)) != 'http://' )
                            {
                                // system seems to like it a whole lot better
                                // with a prepended '/'
                                $data = preg_replace('%^/?(.*)%', '/\1', $tab['tags']  );
                            }
                            else
                            {
                                // this is a web url
                                $data = $tab['tags'];
                            }
                        }
                        elseif( ($tab['function'] == 'any') ||
                                ($tab['function'] == 'all') )
                        {
                            // this is just tags...
                            $data = CCTags::Normalize($tab['tags']);
                        }
                        elseif( $tab['function'] == 'qry' )
                        {
                            $data = $tab['tags'];

                            // do we want to wash the query? default is 'page' so
                            // it works to just leave it out...
                            // $data = preg_replace('/format=[^;$]+/', '', $tab['tags'] );
                        }
                        else
                        {
                            // this is the name of sub-navigation tabs set
                            $data = $tab['tags'];
                        }

                        // handle duplicate sort order requests

                        $index = $tab['order'];
                        while( array_key_exists($index,$tabs) )
                        {
                            $index .= 'a';
                        }

                        // create the new tab...

                        $tabs[ $index ] = 
                            array(  'text'   => $tab['text'],
                                    'help'   => $tab['help'],
                                    'tabname' => $tab['name'],
                                    'tags'   => $data,
                                    'limit'  => 0, // array_key_exists('limit',$tab),
                                    'access' => $tab['access'],
                                    'function' => $tab['function'] );
                    }

                    // sort according to user preference
                    $keys = array_keys($tabs);
                    natsort($keys);
                    $sorted_tabs = array();

                    // put the tabs in order
                    // (don't use for() loop, number indexes
                    // have been shuffled around)

                    foreach( $keys as $key )
                    {
                        $tab =& $tabs[ $key ];
                        $name = $tab['tabname'];
                        unset($tab['tabname']);
                        $sorted_tabs[$name] = $tab;
                    }


                    $configs =& CCConfigs::GetTable();
                    if( !empty($pages[$page_name]['handler']) )
                    {
                        $sorted_tabs['handler'] = $pages[$page_name]['handler'];
                    }
                    $all_vroot_pages = $configs->GetConfig('tab_pages');
                    $all_vroot_pages[$page_name] = $sorted_tabs;
                    $configs->SaveConfig('tab_pages',$all_vroot_pages);
                    //$url = ccl('admin','tabs');
                    CCUtil::SendBrowserTo();
                }
                break;
            }

            case 'makedefault':
            {
                $copy_of_page[$page_name] = $pages[$page_name];
                unset($pages[$page_name]);
                $pages = array_merge($copy_of_page,$pages);
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('tab_pages',$pages,'',false);
                $url = ccl('admin','tabs');
                CCUtil::SendBrowserTo($url);
                break;
            }

            case 'deletepage':
            {
                unset($pages[$page_name]);
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('tab_pages',$pages,'',false);
                $url = ccl('admin','tabs');
                CCUtil::SendBrowserTo($url);
                break;
            }
        }

    }


    /**
    * Internal: returns default 'seed' page
    */
    function _get_seed_page()
    {
        $a = array(
             'home' =>     array(  'text'   => _('Home'),
                                   'help'   => _('Home page'),
                                   'tags'   => '/viewfile/home.xml',
                                   'limit'  => 0,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'url' ),
             'all'   =>    array(  'text'   => _('All'),
                                   'help'   => _('See all the latest uploads'),
                                   'tags'   => 'media',
                                   'limit'  => 0,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),
             'remix' =>    array(  'text'   => _('Remix'),
                                   'help'   => _('See the latest remixes'),
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'limit'  => 0,
                                   'tags'   => 'remix',
                                   'function' => 'any' ),
             'song' =>     array(  'text'   => _('Original'),
                                   'help'   => _('See recently uploaded originals'),
                                   'limit'  => 0,
                                   'tags'   => 'original',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function'=> 'any' ),
             'picks'   =>    array('text'   => _('Picks'),
                                   'help'   => _('See picks by the Editorial Staff'),
                                   'limit'  => 0,
                                   'tags'   => 'editorial_pick',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),
             'funky'   =>  array(  'text'   => _('Funky'),
                                   'help'   => _('Only the funky stuff please!'),
                                   'limit'  => 0,
                                   'tags'   => 'funky,audio',
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'all' ),
             );

        return( $a );
    }


}

?>
