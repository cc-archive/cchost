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

require_once('cclib/cc-admin.php');

/**
* The function is designed for ajax callbacks, get in, get out
*
*/
function cc_show_template($template_name='',$macro='')
{
    global $CC_GLOBALS;

    if( empty($template_name) )
    {
        print(_('must specify a template'));
        CCUtil::Send404();
        exit;
    }

    if( strpos('.xml',$template_name) === false )
        $template_name .= '.xml';

    if( !CCTemplate::GetTemplate($template_name) )
    {
        print(_("can't file template: $template_name"));
        CCUtil::Send404();
        exit;
    }

    if( empty($macro) )
    {
        $t = new CCTemplate($template_name);
        $configs =& CCConfigs::GetTable();
        $args = array_merge($configs->GetConfig('ttag'),$CC_GLOBALS);
        $args['q'] = $CC_GLOBALS['pretty-urls'] ? '?' : '&';
        $args['get'] = $_GET;
        $t->SetAllAndPrint($args);
        exit;
    }

    CCPage::ShowHeaderFooter(false,false);
    $template_name .= "/$macro";
    CCPage::PageArg('exec_this',$template_name);
    CCPage::PageArg('is_ajax',true,'exec_this');
}

/**
* @package cchost
* @subpackage admin
*/
class CCAdminTemplateMacrosForm extends CCEditConfigForm
{
    function CCAdminTemplateMacrosForm()
    {
        $this->CCEditConfigForm('tmacs');

        $fields = array();
        $this->_get_macros_from_file('sidebar', $fields);
        $this->_get_macros_from_file('custom',$fields);
        $this->AddFormFields($fields);
        $fname = '<b>' . CCTemplate::GetTemplate('sidebar.xml') .  '</b>';
        $this->SetHelpText( sprintf(_('Pick which UI elements should appear on every page. Edit the file %s to add modules here.'),$fname) );
        $this->SetModule( ccs(__FILE__) );
    }

    function _get_macros_from_file($filebase,&$fields)
    {
        if( !$filebase )
            return;

        $fname = CCTemplate::GetTemplate( $filebase . '.xml' );

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
        $this->SetHelpText(
            sprintf(_('These values are used on each page for %s. If you have customized the templates you can create new tags by %sclicking here%s.'), $CC_CFG_ROOT, "<a href=\"$newtaglink\">", "</a>"));
        $this->SetModule( ccs(__FILE__) );
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
                   array(  'label'       => _('Tag Name'),
                           'formatter'   => 'textedit',
                           'flags'       => CCFF_REQUIRED | CCFF_NOUPDATE) ;

        $this->AddFormFields($fields);
        $this->SetModule( ccs(__FILE__) );
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
        CCPage::SetTitle(_("Sidebar Content"));
        $form = new CCAdminTemplateMacrosForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnPeopleCustomize($username)
    {
        if( empty($_POST) )
        {
            CCPage::SetTitle(_("Custom Skin"));
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
        CCPage::SetTitle(_("Edit Template Tags"));
        $form = new CCAdminTemplateTagsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnNewTags()
    {
        CCPage::SetTitle(_("Add a New Template Tag"));
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
        
        $files = array();
        $tdirs = split(';',$CC_GLOBALS['template-root']);
        if( !in_array( 'cctemplates/', $tdirs ) && !in_array( 'cctemplates', $tdirs ) )
            $tdirs[] = 'cctemplates';
        foreach( $tdirs as $tdir )
        {
            $tdir = CCUtil::CheckTrailingSlash($tdir,false);
            CCTemplateAdmin::_scour_dir($files, $tdir, $prefix, $ext );
        }

        return $files;
    }

    function _scour_dir(&$files, $dir, $prefix, $ext)
    {
        if ($dh = opendir($dir)) 
        {
            while (($file = readdir($dh)) !== false) 
            {
                if( preg_match( "/^$prefix-([^-]+)\.$ext/", $file, $m ) )
                {
                    $files[ $dir . '/' . $file ] = $dir . '/' . $m[1];
                }
            }
            closedir($dh);
        }

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
            'ttag'   => array('menu_text'   => _('Titles and Footers'),
                              'menu_group'  => 'configure',
                              'help'        => _('Edit what the banner and footer says on each page.'),
                              'weight'      => 50,
                              'action'      =>  ccl('admin','templatetags'),
                              'access'      => CC_ADMIN_ONLY
                             ),
            /*
            'usercss'   => array( 'menu_text'  => 'Your Skin',
                             'menu_group' => 'configure',
                             'weight' => 50,
                             'action' =>  ccl('people','customize',CCUser::CurrentUserName()),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
            */
            'tmacs'  => array( 'menu_text'  => _('Sidebar Content'),
                             'menu_group'   => 'configure',
                             'help'         => _('Pick what features are available on the side bar.'),
                             'weight'       => 53,
                             'action'       =>  ccl('admin','content'),
                             'access'       => CC_ADMIN_ONLY
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
        CCEvents::MapUrl( 'admin/templatetags',     array('CCTemplateAdmin','OnAdminTags'),         
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _("Displays 'Header/Footer' form"), CC_AG_CONFIG );

        CCEvents::MapUrl( 'admin/content',          array('CCTemplateAdmin','OnAdminContent'),      
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _("Displays 'Sidebar' form, let's the admin select modules to display on every page."), 
            CC_AG_CONFIG );

        CCEvents::MapUrl( 'admin/templatetags/new', array('CCTemplateAdmin','OnNewTags'),           
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _("Display 'Create a new template tag' form"), CC_AG_CONFIG );

        CCEvents::MapUrl( 'people/customize',array('CCTemplateAdmin','OnPeopleCustomize'),   
            CC_ADMIN_ONLY, ccs(__FILE__) );

        CCEvents::MapUrl( 'viewtemplate',     'cc_show_template',         
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
    }

}

?>
