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
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_APP_INIT,   array( 'CCLanguage', 'OnInitApp'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,   array( 'CCLanguage', 'OnMapUrls'));
// CCEvents::AddHandler(CC_EVENT_ADMIN_MENU, array( 'CCLanguage', 'OnAdminMenu'));


/**
 * This is a class for dealing with standard gettext-based localization
 * (i18n) in php. It should be studied and used for sites that
 * want this type of support for their sites.
 *
 * @subpackage util
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
    $this->_all_languages = array();
    $this->_domain = $domain;
    
        $this->LoadLanguages( $locale_dir );
    $this->SetLocalePref( $locale );
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
        // $lang_setting = CC_LANG;
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
     * @return array This is an array of possible language within the current
     * locale preference directory.
     */
    function GetPossibleLanguages()
    {
    return $this->_all_languages['locale'][$this->_locale_pref]['language'];
    }

    /**
     * Get possible locale prefs as an array.
     * @return array possilble locale preferences
     */
    function GetPossibleLocalePrefs()
    {
    return $this->_all_languages['locale'];
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
        echo "<pre>";
        // print_r( $this->_all_languages );
        print_r( $this );
	echo("<p>1st</p>");
        echo "</pre>";
    }


    /**
    * Event handler for {@link CC_EVENT_APP_INIT}
    *
    * @see CCEvents::AppInit()
    */
    function OnInitApp()
    {
        // global $CC_GLOBALS;
        // Basically need to init all the language stuff here...
	    // print_r( $CC_GLOBALS );
	// $this->SetLanguage('pt_BR');
        $this->Init();
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


?>
