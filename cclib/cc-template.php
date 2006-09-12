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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCTemplateAdmin', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCTemplateAdmin', 'OnMapUrls'));

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
*/
class CCTemplate
{
    var $_template_file;
    var $_html_mode;
    var $_template;

    function CCTemplate($template_file, $html_mode = true)
    {
        $this->_template_file = $template_file;
        $this->_html_mode     = $html_mode;
    }

    function _init_lib()
    {
        cc_init_template_lib();
        if( empty($this->_template) )
        {
            $this->_template = new PHPTAL($this->_template_file);
            $this->_template->setOutputMode($this->_html_mode ? PHPTAL_XHTML : PHPTAL_XML );
        }
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
            print("There is an error rendering this page.<br /><a href=\"http://wiki.creativecommons.org/CcHost#Troubleshooting\">Help troubleshooting ccHost</a><br />");
            $dir = $CC_GLOBALS['php-tal-cache-dir'];
            if( is_dir($dir) && !is_writable($dir) )
            {
                chmod($dir,0777);
                print("The $dir directory must be writable. We have tried to change it. Refresh this page to ".
                       "see if that worked. If not, you may have to ask your system's administrator for assitance to ".
                        "make that possible.");
            }

            if( $admin_dump )
            {
                print("<pre >");
                print("<b>Here is the information returned from the template engine:</b>\n\n");
                print_r($res);
                print("</pre>");
            }

            exit;
        }

        if( $doprint )
        {
            // --- BEGIN hack for ZA
            if( substr($res,0,5) == '<html' )
            {
                print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
            }
            // --- END hack for ZA
            print(trim($res));
        }

        return $res;
    }

}

/**
* @package cchost
* @subpackage admin
*/
class CCAdminTemplateMacrosForm extends CCEditConfigForm
{
    function CCAdminTemplateMacrosForm()
    {
        global $CC_GLOBALS;

        $troot = $CC_GLOBALS['template-root'];

        $this->CCEditConfigForm('tmacs');

        $fields = array();
        $this->_get_macros_from_file('sidebar', $fields);
        $this->_get_macros_from_file('custom',$fields);
        $this->AddFormFields($fields);
        $fname = "<b>{$troot}sidebar.xml</b>";
        $this->SetHelpText( sprintf(_(
                                'Pick which UI elements should appear on every page.
                               Edit the file %s to add modules here.
                               '),$fname) );
    }

    function _get_macros_from_file($filebase,&$fields)
    {
        global $CC_GLOBALS;

        if( !$filebase )
            return;

        $fname = $CC_GLOBALS['template-root'] . '/' . $filebase . '.xml';
        if( !file_exists($fname) || !is_file($fname) )
            return;

        $text = @file_get_contents( $fname );
        if( empty($text) )
            return;

        $regex = '/define-macro="([^_][^"]*)"/s';
        preg_match_all( $regex, $text, $m );

        foreach($m[1] as $T)
        {
            $N = $filebase . '/' . $T;
            $fields[$N] = array( 'label'     => str_replace('_',' ',$T),
                                 'form_tip'  => $filebase != 'custom' ? "(in {$filebase}.xml)" : '',
                                 'formatter' => 'checkbox',
                                 'flags'     => CCFF_POPULATE );
        }
    }
}

/**
* @package cchost
* @subpackage admin
*/
class CCAdminTemplateTagsForm extends CCEditConfigForm
{
    function CCAdminTemplateTagsForm()
    {
        $this->CCEditConfigForm('ttag');

        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');

        $fields = array();
        foreach( $ttags as $K => $V )
        {
            $fields[$K] =
                       array(  'label'       => $K,
                               'form_tip'    => '',
                               'formatter'   => strlen($V) > 80 ? 'textarea' : 'textedit',
                               'value'       => htmlspecialchars($V),
                               'flags'       => CCFF_NOSTRIP );
        }
        $this->AddFormFields($fields);
        $this->SetSubmitText("Submit Changes");
        $newtaglink = ccl('admin', 'templatetags', 'new' );
        global $CC_CFG_ROOT;
        $this->SetHelpText("These values are used on each page for '$CC_CFG_ROOT'. " .
                            "If you have customized the ".
                           "templates you can create new tags by <a href=\"$newtaglink\">clicking here</a>.");
    }
}

/**
* @package cchost
* @subpackage admin
*/
class CCNewTemplateTagForm extends CCForm
{
    function CCNewTemplateTagForm()
    {
        $this->CCForm();

        $fields['newtag'] =
                   array(  'label'       => 'Tag Name',
                           'formatter'   => 'textedit',
                           'flags'       => CCFF_REQUIRED | CCFF_NOUPDATE) ;

        $this->AddFormFields($fields);
    }
}

/**
* @package cchost
* @subpackage admin
*/
class CCTemplateAdmin
{
    function OnAdminContent()
    {
        CCPage::SetTitle("Sidebar Content");
        $form = new CCAdminTemplateMacrosForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnPeopleCustomize($username)
    {
        if( empty($_POST) )
        {
            CCPage::SetTitle("Custom Skin");
            $form = new CCPickStyleSheetForm();
            $form->SetHandler( ccl('people','customize',$username) ); // otherwise it's global
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $css = CCUtil::StripText($_POST['style-sheet']);
            
            cc_setcookie('style-sheet-' . CCUser::CurrentUserName(),$css,time() + (60*60*24*30) );

            if( empty($_POST['http_referer']) )
                CCPage::SetStyleSheet($css);
            else
                CCUtil::SendBrowserTo();
        }
    }

    function OnAdminTags()
    {
        CCPage::SetTitle("Edit Template Tags");
        $form = new CCAdminTemplateTagsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnNewTags()
    {
        CCPage::SetTitle("Addd a New Template Tag");
        $form = new CCNewTemplateTagForm();
        if( empty($_POST['newtemplatetag']) )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $newtagname = $_REQUEST['newtag'];
            $newtag[$newtagname] = '';
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig('ttag',$newtag);
            CCUtil::SendBrowserTo(ccl('admin','templatetags'));
        }
    }

    function GetTemplates($prefix,$ext)
    {
        global $CC_GLOBALS;
    
        $dir = $CC_GLOBALS['template-root'];
        $files = array();
        if ($dh = opendir($dir)) 
        {
            while (($file = readdir($dh)) !== false) 
            {
                if( preg_match( "/^$prefix-([^-]+)\.$ext/", $file, $m ) )
                {
                    $files[ $dir . $file ] = $m[1];
                }
            }
            closedir($dh);
        }

        return( $files );
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'ttag'   => array( 'menu_text'  => 'Titles and Footers',
                             'menu_group' => 'configure',
                             'help' => 'Edit what the banner and footer on each page says',
                             'weight' => 50,
                             'action' =>  ccl('admin','templatetags'),
                             'access' => CC_ADMIN_ONLY
                             ),
            /*
            'usercss'   => array( 'menu_text'  => 'Your Skin',
                             'menu_group' => 'configure',
                             'weight' => 50,
                             'action' =>  ccl('people','customize',CCUser::CurrentUserName()),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
            */
            'tmacs'  => array( 'menu_text'  => 'Sidebar Content',
                             'menu_group' => 'configure',
                             'help' => 'Pick what features are available on the side bar',
                             'weight' => 53,
                             'action' =>  ccl('admin','content'),
                             'access' => CC_ADMIN_ONLY
                             ),
                );

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/templatetags',     array('CCTemplateAdmin','OnAdminTags'),         CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/content',          array('CCTemplateAdmin','OnAdminContent'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/templatetags/new', array('CCTemplateAdmin','OnNewTags'),           CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'people/customize',       array('CCTemplateAdmin','OnPeopleCustomize'),   CC_MUST_BE_LOGGED_IN);
    }

}

?>
