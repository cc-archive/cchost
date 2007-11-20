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
class CCExtrasForm extends CCEditConfigForm
{
    function CCExtrasForm()
    {
        $this->CCEditConfigForm('extras','',false);

        require_once('cclib/cc-template.inc');

        $fields['macros'] = array(
                'label'     => _('Sidebar Extras'),
                'formatter' => 'extras_editor',
                'options'   => CCTemplateAdmin::GetExtras(),
                'flags'     => CCFF_POPULATE,
                );

        $fields['macros_order'] = array(
                'label'     => '',
                'formatter' => 'extras_hidden',
                'options'   => CCTemplateAdmin::GetExtras(),
                'flags'     => CCFF_POPULATE_WITH_DEFAULT,
                );

        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Save'));
        $this->SetModule( ccs(__FILE__) );
    }
}

function generator_extras_hidden($form,$id,$value='',$class='')
{
    return '<input type="hidden" value="*"  id="' . $id .'"  name="' . $id .'" />';
}

function validator_extras_hidden($form,$fieldname)
{
    return true;
}

function validator_extras_editor($form,$fieldname)
{
    $order = $_POST['macros_order'];
    preg_match_all('/targetmacros\[\]=([0-9]+)&?/',$order,$m);
    $macs = array();
    foreach( $m[1] as $i )
    {
        $macs[] = $_POST['F'][$i];
    }
    $form->SetFormValue('macros',$macs);
    return true;
}

function generator_extras_editor($form,$id,$value='',$class='')
{
    $opts = $form->GetFormFieldItem($id,'options');

    $macs = '';

    if( empty($value) )
    {
        $macs = '';
        $hgt = 'height:14px;';
    }
    else
    {
        $nums = array_keys($opts);
        foreach( $value as $mac )
        {
            $i = array_search($mac,$nums) + 1;
            $text = $opts[$mac];
            $macs .= "\n<li id=\"targetmacros_{$i}\">{$opts[$mac]}</li>";
        }
        $hgt = '';
    }

    $list = '';
    $hides = '';
    $i = 0;
    foreach( $opts as $F => $opt )
    {
        ++$i;
        $hides .= "\n<input type=\"hidden\" name=\"F[{$i}]\" value=\"{$F}\" />";
        if( !empty($value) && in_array( $F, $value ) )
            continue;
        $list .= "\n<li id=\"draglistmacros_{$i}\">{$opt}</li>";
    }

    $html =<<<EOF
<div style="width:50%;margin:0px auto;">
    <ul id="draglistmacros" class="ddex">
        {$list}
    </ul>
</div>
{$hides}
EOF;

    $msg = _('Drag items to here');
    $target =<<<EOF
    
    <div class="ddex"><p style="padding:3px;"><b>{$msg}</b></p><ul id="targetmacros" class="ddex" style="border:1px solid black;{$hgt}">{$macs}</ul></div>

<script type="text/javascript">
function on_macros_drop(a,b)
{
    if( \$('macros_order').value == '*' )
        \$('targetmacros').style.height = "";

    \$('macros_order').value = Sortable.serialize('targetmacros');
}

Sortable.create("draglistmacros",
 {dropOnEmpty:true,containment:['draglistmacros',"targetmacros"],constraint:false});
Sortable.create("targetmacros",
 {dropOnEmpty:true,containment:['draglistmacros',"targetmacros"],constraint:false,onUpdate: on_macros_drop});

</script>
<style>
.ddex { list-style: none; padding: 0px; }
.ddex li { cursor: move; padding: 4px; margin: 5px; border: 1px solid #999}
</style>
EOF;

    CCPage::PageArg('edit_extra',$target);


    return $html;
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
    function OnAdminExtras()
    {
        CCPage::SetTitle(_("Sidebar Extras Content"));
        $form = new CCExtrasForm();
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

    function GetExtras()
    {
        return CCTemplateAdmin::_get_meta_type('extras','extras');
    }

    function GetDataViewFromTemplate($template)
    {
        $skinmac = new CCSkinMacro($template);
        list( $file, $macro ) = $skinmac->LookupMacro();
        if( empty($file) )
            return null;
        $props = CCTemplateAdmin::_get_format_props($file);
        if( empty($props['dataview']) )
            return null;
        if( empty($props['embedded']) )
        {
            global $CC_GLOBALS;
            $props['file'] = CCUtil::SearchPath( $props['dataview'] . '.php', $CC_GLOBALS['dataview-dir'], 'ccdataviews', true );
        }
        else
        {
            $text = file_get_contents($file);
            if( !preg_match('#\[dataview\](.*)\[/dataview\]#s',$text,$m) )
                return null;
            $props['code'] = $m[1];
        }
        return $props;
    }

    function GetDataView($dataview)
    {
        global $CC_GLOBALS;
        $props['dataview'] = $dataview;
        $props['file'] = CCUtil::SearchPath( $dataview . '.php', $CC_GLOBALS['dataview-dir'], 'ccdataviews', true );
        return $props;
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
            'tmacs'  => array( 'menu_text'  => _('Sidebar Extras'),
                             'menu_group'   => 'configure',
                             'help'         => _('Pick what features are available on the side bar.'),
                             'weight'       => 53,
                             'action'       =>  ccl('admin','extras'),
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

        CCEvents::MapUrl( 'admin/extras',          array('CCTemplateAdmin','OnAdminExtras'),      
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
