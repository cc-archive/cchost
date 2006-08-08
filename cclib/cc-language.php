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
* @subpackage lang
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_APP_INIT,   array( 'CCLanguage', 'OnInitApp'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,   array( 'CCLanguage', 'OnMapUrls'));

CCEvents::AddHandler(CC_EVENT_MAP_URLS,
                     array( 'CCLanguageAdmin',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU, 
                     array( 'CCLanguageAdmin' , 'OnAdminMenu') );

/**
 * This is a class for dealing with standard gettext-based localization
 * (i18n) in php. It should be studied and used for sites that
 * want this type of support for their sites.
 *
 */
class CCLanguage
{
    /**
     * @access private
     * @var array
     * This holds an array that points to all locale prefs (folders) and the
     * languages within these folders for access later.
     */
    var $_all_languages;
   
    /**
     * @access private
     * @var string
     * This is the currently selected language.
     */
    var $_language;
    
    /**
     * @access private
     * @var string
     * The current locale preference folder selected (default is default)
     */
    var $_locale_pref;
    
    /**
     * @access private
     * @var string
     * The current domain to access strings in inside the locale .po files.
     */
    var $_domain;
    
    /**
     * Constructor
     * 
     * This method sets up the default language, preferences, etc for dealing
     * with languages for the entire app.
     *
     * TODO: Note that the defaults are in the file cc-defines.php at present
     * and will be moved to defaults that can be set where applicable in a 
     * user's interface.
     * 
     * @param string $language The default language
     * @param string $locale_dir The default locale master folder
     * @param string $locale The default locale preference folder
     * @param string $domain The domain to access strings with from .po files
     */
    function CCLanguage ( $language   = CC_LANG,
                          $locale_dir = CC_LANG_LOCALE, 
                          $locale     = CC_LANG_LOCALE_PREF,
                          $domain     = CC_LANG_LOCALE_DOMAIN )
    {
        global $CC_GLOBALS;
        // CCDebug::PrintVar($CC_GLOBALS);
    
        $this->_all_languages = array();
    
        $this->_domain = $domain;
    
        $this->LoadLanguages( $locale_dir );
   
        if ( isset($CC_GLOBALS['lang_locale_pref']) )
            $this->SetLocalePref( $CC_GLOBALS['lang_locale_pref'] );
        else
    	    $this->SetLocalePref( $locale );

        if ( !empty($CC_GLOBALS['user_language']) )
            $this->SetLanguage( $CC_GLOBALS['user_language'] );
        else if ( !empty($CC_GLOBALS['lang']) )
            $this->SetLanguage( $CC_GLOBALS['lang'] );
        else
    	    $this->SetLanguage( $language );
    }
    
    /**
     * Loads all locale preference folders and languages into the 
     * $_all_languages array for use during runtime.
     *
     * @param string $locale_dir The master locale directory.
     * @param string $po_fn The master name of catalogs.
     * @return bool <code>true</code> if loads, <code>false</code> otherwise
     */
    function LoadLanguages ($locale_dir = CC_LANG_LOCALE, 
                            $po_fn = CC_LANG_PO_FN) 
    {
    // try to head off any type of malicious search
    if ( empty($locale_dir) || $locale_dir == '/' )
        return false;

        // read in each locale preference folder
        $locale_dirs = glob( $locale_dir . '/*', GLOB_ONLYDIR ); 
    
        if ( count($locale_dirs) == 0 )
            return false;
    
        foreach ( $locale_dirs as $dir ) {
            // Read in each folder (language) for consideration
            $lang_dirs = glob( "$dir/*", GLOB_ONLYDIR );
            // if the locale pref. folder has no languages, then don't load it
            if ( count($lang_dirs) == 0 )
                continue;
            $locale_pref = basename($dir);
            $this->_all_languages['locale'][$locale_pref] = 
            array('path' => $dir);
    
            foreach ( $lang_dirs as $lang_dir ) {
                $lang_name = basename($lang_dir);
                // if there is no readable mo file, then get the hell out
                if ( is_readable( "$lang_dir/LC_MESSAGES/$po_fn" ) ) {
                    $this->
            _all_languages['locale'][$locale_pref]['language'][$lang_name] =
            array('path' => $lang_dir); 
                }
            }
        }
        // TODO: Need one more check here of the array and if there is nothing
        // usable, then should return a false here and remove that bad shit from
        // the global array.
    
        return true;
    }
    
    /* MUTATORS */
    
    /**
     * This is where the default locale is set upon startup
     * of the app and where one can set the locale pref at anytime.
     * 
     * @param string $locale_pref The default locale preference directory
     * @return bool <code>true</code> if sets, <code>false</code> otherwise
     */
    function SetLocalePref ($locale_pref = CC_LANG_LOCALE_PREF)
    {
        // conditions for not attempting anything
        if ( $locale_pref == $this->_locale_pref )
            return true;
    
    // these are the various possible settings ranked in order for
    // setting this directory.
        $locale_tests = array(&$locale_pref, 
                              &$_SERVER['HTTP_HOST'], 
                              CC_PROJECT_NAME);
    
        // test to see if we can set to some default in order of the array
        foreach ( $locale_tests as $test )
        {
            if ( isset($this->_all_languages['locale'][$test]) ) {
                $this->_locale_pref = $test;
                return true;
            }
        }
    
    // NOTE: I have gone back and forth on whether or not to set this
    // I think it is wisest to set as last precaution to the default
    // and ideally also make some note in the error log stating what is up
    $this->_locale_pref = CC_LANG_LOCALE_PREF;
        return false;
    }
   
    /**
     * This method sets the current language and also the default if no
     * parameter is provided.
     *
     * @param string $lang_pref This is the language pref as 2 or 4 length code.
     * @return bool <code>true</code> if sets, <code>false</code> otherwise
     */
    function SetLanguage ($lang_pref = CC_LANG)
    {
        $lang_possible = 
            &$this->_all_languages['locale'][$this->_locale_pref]['language'];
    
        if ( $lang_pref == $this->_language )
            return true;
   
    // Yet again, the conditions to test for default language
    // in order
        $lang_tests = array(&$lang_pref, 
                            $lang_pref . "_" . strtoupper($lang_pref));
    
        // test to see if we can set to some default in order of the array
        foreach ( $lang_tests as $test )
        {
            if ( isset($lang_possible[$test]) ) {
                $this->_language = $test;
                return true;
            }
        } 
        // if all else fails set it to the default
        $this->_language = CC_LANG;
        return false;
    }
   
    /**
     * Sets the domain for the .po files.
     * @param string $domain The domain for strings in .po files.
     */
    function SetDomain ($domain = DEFAULT_DOMAIN)
    {
        $this->_domain = $domain;
    }

    /* ACCESSORS */

    /** 
     * Gets all languages and locale prefs as an array.
     * @return array An array that looks like the one constructed by 
     * LoadLanguages()
     */
    function GetAllLanguages ()
    {
        return $this->_all_languages;
    }

    /**
     * Get the current locale preference (directory).
     * @return string The current locale preference directory
     */
    function GetLocalePref ()
    {
        return $this->_locale_pref;
    }

    /**
     * Get the current language.
     * @return string The current language
     */
    function GetLanguage()
    {
        return $this->_language;
    }

    /**
     * Get possible language as an array.
     * @param bool $inherits_parent <code>true</code> or <code>false</code>
     * @return array This is an array of possible language within the current
     * locale preference directory.
     */
    function GetPossibleLanguages()
    {
        // HACK FIX: vs
        // return( array( 'en' ) );

        // return array_keys(
        //    $this->_all_languages['locale'][$this->_locale_pref]['language']);

        $lang_list = 
	    array_keys(
	    $this->_all_languages['locale'][$this->_locale_pref]['language']);

        $possible_langs = array();

        foreach ( $lang_list as $item ) {
	    $possible_langs[$item] = $item;
        }
	
	// This is dumb in that if it is selected for user preferences, it
	// inherits the master default for an installation.
	// If default is selected for the master setting, then this is 
	// set to the constant, CC_LANG, and if that setting is not available
	// or set to nothing, then the default is to use the strings in the
	// code
        $possible_langs['default'] = _('default');
	
	return $possible_langs;
    }

    /**
     * Get possible locale prefs as an array.
     * @return array possible locale preferences
     */
    function GetPossibleLocalePrefs()
    {
        $locale_prefs_list = array_keys($this->_all_languages['locale']);
	// had to add this hack because array_combine() is only in php5
	$locale_prefs_list_combined = array();
	foreach ( $locale_prefs_list as $pref )
	    $locale_prefs_list_combined[$pref] = $pref;
        return $locale_prefs_list_combined;
	
    }

    /**
     * Get the current domain for strings.
     * @return string The current domain
     */
    function GetDomain()
    {
        return $this->_domain;
    }

    /**
     * This is where the main guts of this code takes place. I'm actually
     * splitting this out from the constructor because I think that it is
     * a better option to call this after any objects are pulled from a 
     * session variable and/or after doing some checking on the current
     * run-time setup.
     */
    function Init ()
    {
        putenv("LANG=" . $this->_language ); 
        setlocale(LC_ALL, $this->_language );
        bindtextdomain($this->_domain, 
                   CC_LANG_LOCALE . "/" . $this->_locale_pref );
        textdomain($this->_domain);
    }
    
    /**
     * This method is for generically testing what is happening inside
     * of this object.
     */
    function DebugLanguages ()
    {
	global $CC_GLOBALS;
        echo "<pre>";
        // print_r( $this->_all_languages );
        // print_r( $this );
	    print_r( $CC_GLOBALS );
        echo "</pre>";
    }


    /**
    * Event handler for {@link CC_EVENT_APP_INIT}
    *
    * @see CCEvents::AppInit()
    */
    function OnInitApp()
    {
        global $CC_GLOBALS;
        // Basically need to init all the language stuff here...
        $this->Init();
    	// The CC_GLOBALS array are the active values and not hard settings
    	// in the database
    	$CC_GLOBALS['lang'] = &$this->_language;
    	$CC_GLOBALS['lang_locale_pref'] = &$this->_locale_pref;
    	$CC_GLOBALS['language'] = &$this;
        // $this->DebugLanguages();
    }
    
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
/*
        CCEvents::MapUrl( ccp('admin','language'),  array( 'CCLanguage', 'Language'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','edit'),  array( 'CCLanguage', 'EditString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','getstring'),  array( 'CCLanguage', 'GetString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','translate'),  array( 'CCLanguage', 'Translate'), CC_ADMIN_ONLY );
*/
    }

}


/**
*/
require_once('ccextras/cc-lang.inc');

/**
* @package cchost
* @subpackage admin
*/
class CCLanguageAdminForm extends CCForm
{
    function CCLanguageAdminForm()
    {
        global $CC_GLOBALS;

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('config');

        $this->CCForm();
/*
        $fields['lang_enabled'] = array(
                                'label'      => _("Enabled language support:"),
                                'formatter'  => 'checkbox',
                                'value'      => $CC_GLOBALS['lang_enabled'],
                                'flags'      => CCFF_POPULATE );
*/
        $fields['lang_locale_pref'] = array(
                                'label'      => _("Default Locale Preference:"),
                                'formatter'  => 'select',
                                'value'      => $settings['lang_locale_pref'],
                                'options'    => $CC_GLOBALS['language']->GetPossibleLocalePrefs(),
                                'flags'      => CCFF_POPULATE );

        $fields['lang'] = array(
                                'label'      => _("Default Language:"),
                                'formatter'  => 'select',
                                'value'      => empty($settings['lang']) ? 'default' : $settings['lang'],
                                'options'    => $CC_GLOBALS['language']->GetPossibleLanguages(),
                                'flags'      => CCFF_POPULATE );

        $help = "<p>" . _("Select the default locale preference and a default language and the system will try to translate menus, navigation tabs and anything else you might have customized. If the translation system does recognize the terms used, those menu items and navigation tabs will not change.") . "</p><p>" . 
	                _("The Locale Preference is a specific folder that contains specific language translations. The default is a folder of the same name. Inside these folders are the specific language translations.") . "</p><p>" . 
		        _("To create a specific translation, please copy the default folder with a unique name, and then select it as your Default Locale Preference. Then, edit the .po files in specific language folders for customization.") . "</p>";
        $this->SetFormHelp($help);
        $this->AddFormFields($fields);
    }
}

/**
*
* @subpackage admin
*/
class CCLanguageAdmin
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
            if( !isset($values['lang']) )
                $values['lang'] = CC_LANG;
	    
            if ( !isset($values['lang_locale_pref']) )
                $values['lang_locale_pref'] = CC_LANG_LOCALE_PREF;

            $config =& CCConfigs::GetTable();    
	    // CCDebug::PrintVar($values);
	    $config->SaveConfig('config',$values,CC_GLOBAL_SCOPE,true);
            CCUtil::SendBrowserTo(ccl('admin','language', 'save'));
        }
    }

    function SavePage ()
    {
        CCPage::SetTitle("Language Support");
        CCPage::Prompt("Language support options saved.");
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
        // if( empty($cclang) )
        // {
            $this->Admin();
            return;
        // }

        // JON: thre rest is for the string editor which isn't connected
	// up yet...

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

/*
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
            chmod( $target_file, cc_default_file_perms() );
        }

        $tt[$hash] = CCUtil::StripSlash(urldecode($_REQUEST['string']));
        $recorder[$mode] = $tt;
        cc_lang_write_inner($lang_dir,$mode,$recorder);
        print("Changed saved");
        exit;
    }
*/
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','language'),  array( 'CCLanguageAdmin', 'Language'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','save'),  array( 'CCLanguageAdmin', 'SavePage'), CC_ADMIN_ONLY );
	/*
        CCEvents::MapUrl( ccp('admin','language','edit'),  array( 'CCLanguageAdmin', 'EditString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','getstring'),  array( 'CCLanguageAdmin', 'GetString'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','language','translate'),  array( 'CCLanguageAdmin', 'Translate'), CC_ADMIN_ONLY ); */
    }

}


?>
