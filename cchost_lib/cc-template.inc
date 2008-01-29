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

require_once('cchost_lib/cc-admin.php');


/**
* @package cchost
* @subpackage admin
*/
class CCExtrasForm extends CCEditConfigForm
{
    function CCExtrasForm()
    {
        $this->CCEditConfigForm('extras','',false);

        require_once('cchost_lib/cc-template.inc');

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

        $help = _('Drag items from here to sidebar (and back) to add (and remove) items. Drag items within sidebar to reorder.');
        $this->SetFormHelp($help);
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
            if( array_key_exists($mac,$opts) )
            {
                $text = $opts[$mac];
                $macs .= "\n<li id=\"targetmacros_{$i}\">{$opts[$mac]}</li>";
            }
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
<style type="text/css">
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
class CCBannerLogoForm extends CCUploadForm
{
    function CCBannerLogoForm($avatar_dir,$file)
    {
        $this->CCUploadForm();


        $fields['src'] =
           array(  'label'       => _('Banner Logo File'),
                   'form_tip'    => '',
                   'upload_dir'  => $avatar_dir,
                   'formatter'   => 'avatar',
                   'value'       => $file,
                   'flags'       => CCFF_POPULATE );

        $this->AddFormFields($fields);
        $this->SetSubmitText("Submit Banner Logo");
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
    function GetFormats($type)
    {
        $fp = new CCFileProps(); return $fp->GetProps('formats',$type);
    }

    function GetProfiles()
    {
        $fp = new CCFileProps(); return $fp->GetProps('profiles','profile',true);
    }

    function GetStringProfiles()
    {
        $fp = new CCFileProps(); return $fp->GetProps('strings','string_profile');
    }

    function GetLayouts($type)
    {
        $fp = new CCFileProps(); return $fp->GetProps('layouts',$type,false);
    }

    function GetFonts()
    {
        $fp = new CCFileProps(); return $fp->GetProps('colors','font',false);
    }

    function GetFontSizes()
    {
        $fp = new CCFileProps(); return $fp->GetProps('colors','fontsize',false);
    }

    function GetColors()
    {
        $fp = new CCFileProps(); return $fp->GetProps('colors','color',false);
    }

    function GetExtras($user_only=false)
    {
        $fp = new CCFileProps(); return $fp->GetProps('extras','extras',true,'',$user_only?'allow_user':'');
    }

    function GetSkins($skip_shared=false)
    {
        $fp = new CCFileProps(); return $fp->GetProps('','skin');
    }

    function GetContentTemplates()
    {
        $fp = new CCFileProps(); return $fp->GetProps('pages','content_page');
    }

    function GetContentPages()
    {
        global $CC_GLOBALS;
        $fp = new CCFileProps(); return $fp->GetProps('','dynamic_content_page',true,$CC_GLOBALS['files-root']);
    }

    function OnAdminExtras()
    {
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle(_("Sidebar Extras Content"));
        $form = new CCExtrasForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnPeopleCustomize($username)
    {
        require_once('cchost_lib/cc-page.php');
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

    function OnAdminTags($menu='')
    {
        require_once('cchost_lib/cc-page.php');
        if( !empty($menu) )
        {
            if($menu == 'menu')
                return $this->OnAdminTemplateMenu();
            if($menu == 'bannerlogo')
                return $this->OnBannerLogo();
        }

        CCPage::SetTitle(_("Edit Template Tags"));
        $form = new CCAdminTemplateTagsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnBannerLogo()
    {
        global $CC_GLOBALS;

        $configs =& CCConfigs::GetTable();
        $upload_dir = $CC_GLOBALS['image-upload-dir'];
        $ttags = $configs->GetConfig('site-logo');
        $filename = empty($ttags['src']) ? '' : basename($ttags['src']);
        $form = new CCBannerLogoForm($upload_dir,$filename);

        if( !empty($_POST['bannerlogo']) && $form->ValidateFields() )
        {
            $form->FinalizeAvatarUpload('src', $upload_dir);
            $form->GetFormValues($fields);
            if( $fields['src'] )
            {
                $args['src'] = ccp($upload_dir,$fields['src']);
                list( $args['w'], $args['h'] ) = @getimagesize($args['src']);
            }
            else
            {
                $args['src'] = 0;
            }
            $configs->SaveConfig('site-logo',$args);
            CCUtil::SendBrowserTo(ccl('admin','templatetags','menu'));
        }
        else
        {
            require_once('cchost_lib/cc-page.php');
            CCPage::SetTitle(_('Set Banner Image'));
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function OnAdminTemplateMenu()
    {
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle(_('Configure Skins'));

        $args[] = array( 'action'    => ccl('admin','templatetags','profiles'),
                         'menu_text' => _('Banner Text, Footers, etc.'),
                         'help'      => _('Edit the text of the banner, footer and other elements on every page') );

        $args[] = array( 'action'    => ccl('admin','templatetags','bannerlogo'),
                         'menu_text' => _('Banner Logo'),
                         'help'      => _('Upload a logo graphic for the banner') );

        $args[] = array( 'action'    => ccl('admin','templatetags','new'),
                         'menu_text' => _('New Page Element'),
                         'help'      => _('Web developers: Create a new element for page template skin') );

        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }

    function OnNewTags()
    {
        require_once('cchost_lib/cc-page.php');
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

    function GetFirstUserPath()
    {
        global $CC_GLOBALS;

        $use_paths = CCUtil::SplitPaths( $CC_GLOBALS['template-root']);
        foreach( $use_paths as $P )
        {
            if( strstr($P,'ccskins') )
                continue;
            return $P;
        }
        return '';
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
            'ttag'   => array('menu_text'   => _('Banner and Footers'),
                              'menu_group'  => 'configure',
                              'help'        => _('Edit what the banner and footer says on each page.'),
                              'weight'      => 50,
                              'action'      =>  ccl('admin','templatetags','menu'),
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
            'extras'  => array( 'menu_text'  => _('Sidebar Extras'),
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
            _("Displays 'Header/Footer' menus and option forms"), CC_AG_CONFIG );

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
