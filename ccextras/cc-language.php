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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCLanguage',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCLanguage' , 'OnAdminMenu') );

require_once('ccextras/cc-lang.inc');

class CCLanguageAdminForm extends CCForm
{
    function CCLanguageAdminForm()
    {
        $this->CCForm();

        $options = array();
        $dirname = cca('cclib/lang');
        if ($topdir = opendir($dirname) ) 
        {
           while (($subdir= readdir($topdir)) !== false) 
           {
               if( strtolower($subdir) != 'cvs' && 
                   ($subdir != '.') && 
                   ($subdir != '..')  
                  )
               {
                   $options[$subdir] = $subdir;
               }
           }
           closedir($topdir);
        }
        
        global $CC_GLOBALS;

        $fields['lang_enabled'] = array(
                                'label'      => "Enabled language support:",
                                'formatter'  => 'checkbox',
                                'value'      => !empty($CC_GLOBALS['lang_enabled']),
                                'flags'      => CCFF_POPULATE );

        $fields['cclang'] = array(
                                'label'      => "Language:",
                                'formatter'  => 'select',
                                'value'      => empty($CC_GLOBALS['cclang']) ? 'en' : $CC_GLOBALS['cclang'],
                                'options'    => $options,
                                'flags'      => CCFF_POPULATE );

        $help =<<<END
When you select a language, the system will try to translate
            menus, navigation tabs and anything else you might
            have customized. If the translation system does
            recognize the terms used, those menu items and 
            navigation tabs will not change.
    
END;
        $this->SetFormHelp($help);
        $this->AddFormFields($fields);
    }
}

/**
*
*
*/
class CCLanguage
{
    function Admin()
    {
        global $CC_GLOBALS;

        $form = new CCLanguageAdminForm();
        CCPage::SetTitle("Language Support");
        if( empty($_POST['languageadmin']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            if( empty($values['lang_enabled']) )
            {
                $values['cclang'] = 'en';
                $values['lang_enabled'] = 0;
            }

            $config =& CCConfigs::GetTable();
            $config->SaveConfig('config',$values,CC_GLOBAL_SCOPE,true);
            CCUtil::SendBrowserTo(ccl('admin','language','translate'));
        }
    }

    function Translate()
    {
        CCPage::SetTitle("Performing Translation");
        global $CC_GLOBALS;
        $old_lang_enabled = $CC_GLOBALS['lang_enabled'];
        $CC_GLOBALS['lang_enabled'] = true;
        CCEvents::Invoke(CC_EVENT_TRANSLATE);
        $CC_GLOBALS['lang_enabled'] = $old_lang_enabled;
        CCPage::Prompt("Language support options saved.");
    }

    /**
    * Event handler for admin building
    *
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array(
                'language'   => array( 
                                 'menu_text'  => 'Language',
                                 'menu_group' => 'configure',
                                 'help' => 'Configure Language use',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 40,
                                 'action' =>  ccl('admin','language')
                                 ),
                );
        }
    }

    function Language($cclang='',$mode='u')
    {
        if( empty($cclang) )
        {
            $this->Admin();
            return;
        }

        global $CC_GLOBALS;

        $lang_file = "cclib/lang/$cclang/cc-translation-$mode.php";
        if( file_exists($lang_file) )
            include($lang_file);
        else
        {
        }
        $tablename = 'cc_translation_table_' . $mode;
        $args = $CC_GLOBALS;
        $tt =& $$tablename;
            CCDebug::LogVar('tt',$tt);
        natsort($tt);
        $args['string_table'] = $tt;
        $args['lang'] = $cclang;
        $args['mode'] = $mode;
        $template = new CCTemplate( 'ccextras/language.xml', true); // false would mean xml mode
        $text = $template->SetAllAndParse( $args );
        CCPage::AddScriptBlock('ajax_block');
        CCPage::SetTitle("Language String Editor");
        CCPage::AddPrompt('body_text',$text);
    }

    function GetString($cclang,$mode,$targetlang,$hash)
    {
        $en_file = "cclib/lang/$cclang/cc-translation-$mode.php";
        include($en_file);
        $tablename = "cc_translation_table_" . $mode;
        $tt = $$tablename;
        $encoded = htmlentities($tt[$hash]);
        $target_file = "cclib/lang/$targetlang/cc-translation-$mode.php";
        if( file_exists($target_file) )
        {
            $tt = null;
            include($target_file);
        }
        else
        {
            //$target_file = $en_file;
        }
        $tt = $$tablename;
        $string = $tt[$hash];
        $html =<<<END
     <div class="org_title">Original String</div>
     <div class="org_string">$encoded</div>
     <div class="org_title">Translates to:</div>
     <textarea id="translated" name="translated" rows="11" cols = "45">$string</textarea>
     <div><br /><a id="commentcommand" href="#" onclick='do_edit_string("$hash")'><span>Commit Changes</span></a> to: $target_file</div><br />
     <div><br /><a id="commentcommand" href="#" onclick='do_get_string("$hash")'><span>Revert</span></a></div>
END;

       print $html;
       exit;
    }

    function EditString($cclang,$mode,$hash)
    {
        if( empty($hash) ) {
            print("Invalid hash");
            exit;
        }

        $lang_dir = "cclib/lang/$cclang";
        $target_file = "$lang_dir/cc-translation-$mode.php";

        if( !file_exists($target_file) )
        {
            CCUtil::MakeSubdirs($lang_dir);
            $en_file = "cclib/lang/en/cc-translation-$mode.php";
            copy( $en_file, $target_file );
            chmod( $target_file, CC_DEFAULT_FILE_PERMS );
        }

        $tt[$hash] = CCUtil::StripSlash(urldecode($_REQUEST['string']));
        $recorder[$mode] = $tt;
        cc_lang_write_inner($lang_dir,$mode,$recorder);
        print("Changed saved");
        exit;
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','language'),  array( 'CCLanguage', 'Language'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','edit'),  array( 'CCLanguage', 'EditString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','getstring'),  array( 'CCLanguage', 'GetString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','translate'),  array( 'CCLanguage', 'Translate'), CC_ADMIN_ONLY );
    }

}


?>