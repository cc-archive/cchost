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
        $fname = '<b>' . CCTemplate::GetTemplate('sidebar') .  '</b>';
        $this->SetHelpText( sprintf(_('Pick which UI elements should appear on every page. Edit the file %s to add modules here.'),$fname) );
        $this->SetModule( ccs(__FILE__) );
    }

    function _get_macros_from_file($filebase,&$fields)
    {
        if( !$filebase )
            return;

        $fname = CCTemplate::GetTemplate( $filebase );

        if( !file_exists($fname) || !is_file($fname) )
            return;

        $text = @file_get_contents( $fname );
        if( empty($text) )
            return;

        if( strstr($fname,'.php') )
            $regex = "/function\s+_t_{$filebase}_([^_][a-z_]+)\(/i";
        else // tpl
            $regex = '/%macro\((?:\s+)?([^_][^)\s]+)\)/i';

        preg_match_all( $regex, $text, $m );

        foreach($m[1] as $T)
        {
            $N = $filebase . '/' . $T;
            $fields[$N] = array( 'label'     => str_replace('_',' ',$T),
                                 'form_tip'  => $filebase != 'custom' ? "(in {$filebase})" : '',
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
                       array(  'label'       => '%(' . $K . ')%',
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

    function GetFormats($type)
    {
        return CCTemplateAdmin::_get_meta_type('formats',$type);
    }

    function GetProfiles()
    {
        return CCTemplateAdmin::_get_meta_type('profiles','profile',true);
    }

    function GetStringProfiles()
    {
        return CCTemplateAdmin::_get_meta_type('strings','string_profile');
    }

    function GetLayouts($type)
    {
        return CCTemplateAdmin::_get_meta_type('layouts',$type,false);
    }

    function GetFonts()
    {
        return CCTemplateAdmin::_get_meta_type('colors','font',false);
    }

    function GetFontSizes()
    {
        return CCTemplateAdmin::_get_meta_type('colors','fontsize',false);
    }

    function GetColors()
    {
        return CCTemplateAdmin::_get_meta_type('colors','color',false);
    }

    function _get_meta_type($sub_dir,$type,$ret_files=true)
    {
        global $CC_GLOBALS;
        
        $files = array();
        $tdirs = CCUtil::SplitPaths($CC_GLOBALS['template-root'], 'ccskins/shared');

        foreach( $tdirs as $tdir )
        {
            $tdir = CCUtil::CheckTrailingSlash($tdir,false);
            CCTemplateAdmin::_scour_format_dir($files, $tdir, '/' . $sub_dir . '/*.*' );
        }
        
        $match_files = array();
        foreach($files as $ffile)
        {
            $props = CCTemplateAdmin::_get_format_props($ffile);
            if( !$props || ($props['type'] != $type) )
                continue;

            if( $ret_files )
            {
                if( empty($props['desc']) )
                {
                    $match_files[$ffile] = $ffile;
                }
                else
                {
                    $match_files[$ffile] = $props['desc'];
                }
            }
            else
            {
                $props['id'] = $ffile;
                $match_files[] = $props;
            }
        }

        return $match_files;
    }

    function _get_format_props($filename)
    {
        $text = file_get_contents($filename);
        if( !preg_match('#.*\[meta\](.*)\[/meta\].*#ms',$text,$m) )
            return null;
        $lines = split("\n",$m[1]);
        $props = array();
        foreach( $lines as $line )
        {
            $line = trim($line);
            if( empty($line) )
                continue;
            $parts = split('=',$line);
            $props[ trim($parts[0]) ] = trim($parts[1]);
        }
        if( !empty($props['desc']) )
        {
            if( preg_match("/^_\(['\"](.+)['\"]\)$/",$props['desc'],$m) )
                $props['desc'] = _($m[1]);
        }
        return $props;
    }

    function _scour_format_dir(&$files, $dir, $mask)
    {
        $hits = glob( $dir . $mask );
        if( !empty($hits) )
        {
            $files += $hits;
         }
        $dirs = glob( $dir . '/*', GLOB_ONLYDIR );
        foreach( $dirs as $subdir )
        {
            if( $subdir{0} != '.' )
                CCTemplateAdmin::_scour_dir($files,$subdir,$mask);
        }
    }

    function GetUserPaths($append='')
    {
        global $CC_GLOBALS;

        if( $append )
            $append  = '/' . $append;

        $use_paths = CCUtil::SplitPaths( $CC_GLOBALS['template-root']);
        $paths = array();
        foreach( $use_paths as $P )
        {
            if( strstr($P,'ccskins') )
                continue;
            $paths[$P . $append] = $P . $append;
        }
        return $paths;
    }

    function GetSkins($skip_shared=false)
    {
        global $CC_GLOBALS;
        
        $files = array();
        $tdirs = CCUtil::SplitPaths($CC_GLOBALS['template-root'], 'ccskins');
        foreach( $tdirs as $tdir )
        {
            $tdir = CCUtil::CheckTrailingSlash($tdir,false);
            CCTemplateAdmin::_scour_dir($files, $tdir, '/skin.*' );
        }

        if( $skip_shared )
            $files = array_diff( $files, array( 'ccskins/shared' ) );

        return $files;
    }

    function _scour_dir(&$files, $dir, $mask)
    {
        $hits = glob( $dir . $mask );
        if( !empty($hits) )
            $files[ $hits[0] ] = $dir;
        $dirs = glob( $dir . '/*', GLOB_ONLYDIR );
        foreach( $dirs as $subdir )
        {
            if( $subdir{0} != '.' )
                CCTemplateAdmin::_scour_dir($files,$subdir,$mask);
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

    }

}

?>
