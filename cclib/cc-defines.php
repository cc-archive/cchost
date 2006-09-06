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
* Core defines for the system
*
* @package cchost
* @subpackage core
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * The name of this software. This is to not be changed.
 */
define('CC_APP_NAME', 'cchost');
define('CC_APP_NAME_PRETTY', 'ccHost');

/**
 * This constant is for a generic PROJECT_NAME for the project. This is
 * specific to a project and is not generally the same for every project.
 * @see CCLanguage
 * @see CCLanguage::LoadLanguages()
 * @see CCLanguage::SetLocalePref()
 * @see CCLanguage::GetLocalePref()
 */
define('CC_PROJECT_NAME', 'CC_APP_NAME');


/**
* Current Version
*/
define('CC_HOST_VERSION', '3.0');

define( 'CC_GLOBAL_SCOPE', 'media' );
define( 'CC_LOCAL_SCOPE',  'local' );

define('CC_1MG', 1024 * 1024);

define('CC_USER_COOKIE', 'lepsog2');

// Access flags
define('CC_MUST_BE_LOGGED_IN',   1 );
define('CC_ONLY_NOT_LOGGED_IN',  2 );
define('CC_DONT_CARE_LOGGED_IN', 4 );
define('CC_ADMIN_ONLY',          8 );
define('CC_OWNER_ONLY',          0x10 );

define('CC_DISABLED_MENU_ITEM', 0x20 );
define('CC_DYNAMIC_MENU_ITEM',  0x40 );

/* LANGUAGE DEFINES */

/** 
 * Default language is nothing so that the default strings in the code are the 
 * default. This is en_US because the original author strings are written by 
 * english speakers
 * @see CCLanguage
 */
define('CC_LANG', 'en_US');

/**
 * This constant is the default locale folder to find i18n translations.
 * @see CCLanguage
 * @see CCLanguage::LoadLanguages()
 * @see CCLanguage::CCLanguage()
 */
define('CC_LANG_LOCALE', 'locale');

/**
 * This constant is the default locale preference folder to find different
 * locale sets for possible different translations depending on installation
 * and user preference that are larger than just per-language differences of
 * i18n translations.
 * @see CCLanguage
 * @see CCLanguage::CCLanguage()
 * @see CCLanguage::LoadLanguages()
 * @see CCLanguage::SetLocalePref()
 * @see CCLanguage::GetLocalePref()
 */
define('CC_LANG_LOCALE_PREF', 'default');

/**
 * This constant is the default full path relative to an installation / web
 * root for the locale preference directory.
 * @see CCLanguage
 * @see CCLanguage::CCLanguage()
 * @see CCLanguage::LoadLanguages()
 * @see CCLanguage::SetLocalePref()
 * @see CCLanguage::GetLocalePref()
 */
define('CC_LANG_LOCALE_PREF_DIR', CC_LANG_LOCALE . '/' . CC_LANG_LOCALE_PREF);

/**
 * This constant is the domain for messages and is usually the same short
 * name for the project or package to be installed.
 * @see CCLanguage
 * @see CCLanguage::CCLanguage()
 * @see CCLanguage::LoadLanguages()
 * @see CCLanguage::SetDomain()
 * @see CCLanguage::GetDomain()
 */
define('CC_LANG_LOCALE_DOMAIN', CC_APP_NAME);

/**
 * This constant is the default full po filename.
 * @see CCLanguage
 * @see CCLanguage::SetDomain()
 * @see CCLanguage::GetDomain()
 */
define('CC_LANG_PO_FN', CC_LANG_LOCALE_DOMAIN . '.po');


/**
* Notification Event: App session init
*
* Event triggered after app session has been initialized, all
* modules are loaded, user is logged in.
* 
* Call back (handler) prototype:
*<code>
* function OnAppInit()
* </code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_APP_INIT',            'init');

/**
* Notification Event: App session done
*
* Event triggered after app session has executed the
* incoming URL and page has been displayed 
* 
* Call back (handler) prototype:
*<code>
* function OnAppDone()
* </code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_APP_DONE',            'done');


/**
* Request for Data Event: Build main menu
*
* Event triggered when the system needs to build and cache the main menu.
* 
* Call back (handler) prototype:
*<code>
* function OnBuildMenu()
*</code>
* The callback needs to call {@link CCMenu::AddItems{}} in order place items into the menu.
* @see CCEvents::AddHandler()
* @see CCMenu::GetMenu()
*/
define('CC_EVENT_MAIN_MENU',           'mainmenu');


/**
* Request for Data Event: Display and patch menu
*
* Event triggered when the system is about to display the main menu giving modules
* an opportunity to dynamically alter the menu based on context (i.e. who
* is logged in.)
* 
* Call back (handler) prototype:
*<code>
* // The callback edits the $menu structure directly
* function OnPatchMenu(&$menu )
*</code>
* @see CCEvents::AddHandler()
* @see CCMenu::GetMenu()
*/
define('CC_EVENT_PATCH_MENU',          'patchmenu');


/**
* Request for Data Event: Display and patch upload local menu
*
* Event triggered when the system is about to display the local upload
* menu for a given upload record. This gives modules 
* an opportunity to dynamically alter the menu based on context (i.e. who
* is logged in.)
* 
* Call back (handler) prototype:
*<code>
* // The callback edits the $menu structure directly
* // $record is the upload record this menu is for
* function OnUploadMenu(&$menu, &$record )
*</code>
* @see CCEvents::AddHandler()
* @see CCMenu::GetLocalMenu()
*/
define('CC_EVENT_UPLOAD_MENU',         'uploadmenu');

/**
* Request for Data Event: Build upload local menu
*
* Event triggered when the system is requesting to build the
* local menu for a given upload.
* 
* Call back (handler) prototype:
*<code>
* // The callback edits the $menu structure directly
* function OnBuildUploadMenu(&$menu )
*</code>
* @see CCEvents::AddHandler()
* @see CCMenu::GetLocalMenu()
*/
define('CC_EVENT_BUILD_UPLOAD_MENU',   'builduploadmenu');


/**
* Request for Data Event: Build and display the admin's menu
*
* Triggered when the system is requesting to build the
* one of either the global admin functions or the admin functions
* for the current virtual root.
* 
* Call back (handler) prototype:
*<code>
* // The callback edits the $menu structure directly
* // $type is one of either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
* function OnAdminMenu(&$menu, $type )
*</code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_ADMIN_MENU',          'buildadminmenu');

/**
* Request for Data Event: Massage a raw upload row into a record
*
* Event trigged when <i>a single upload row</i> is pulled out of
* the database and is about to be displayed. Gives
* modules an opportunity to massage the row and
* populate it with data required by templates for display.
* The ccHost system uses this event to populate the 
* row with information about physical files, menus 
* commands, etc.
*
* Call back (handler) prototype:
*<code>
* // &$record is the upload data being massaged
* function OnUploadRow( &$record )
*</code>
* @see CCEvents::AddHandler()
* @see CCTable::GetRecordFromRow()
* @see CC_EVENT_UPLOAD_LISTING
* @see CC_EVENT_LISTING_RECORDS
*/
define('CC_EVENT_UPLOAD_ROW',          'uploadrow' );

/*
* Request for Data Event: Preparing an upload record for display
*
* Triggered when a <i>single upload row</i> is about
* to be displayed. This is the last event for individual
* records before display. The ccHost system uses this event 
* to populate the row with information about remixes and remix 
* sources.
*
* Call back (handler) prototype:
*<code>
* // &$record is the upload data being massaged
* function OnUploadListing( &$record )
*</code>
* @see CCEvents::AddHandler()
* @see CCTable::GetRecordFromRow()
* @see CC_EVENT_UPLOAD_ROW
* @see CC_EVENT_LISTING_RECORDS
*/
define('CC_EVENT_UPLOAD_LISTING',      'uploadlisting' );

/*
* Notification Event: A list of records is being displayed
*
* Triggered when a <i>multiple upload records</i> are about
* to be displayed. The ccHost rendering system uses this event 
* to populate the template with 'Podcast this page' and
* 'Stream this page' user interface elements.
*
* Call back (handler) prototype:
*<code>
* // &$records is an array of upload records about to be displayed
* function OnListingRecords( &$records )
*</code>
* @see CCEvents::AddHandler()
* @see CC_EVENT_UPLOAD_ROW
* @see CC_EVENT_UPLOAD_LISTING
*/
define('CC_EVENT_LISTING_RECORDS',     'listingrecs' );

/**
* Notification Event: File info has been attached to record
*
* Triggered by {@link CCFiles::FilesForUpload} after the during
* {@link CC_EVENT_UPLOAD_ROW} event processing after the information
* for physical files has been put into the record preparing for
* display.
*
* Call back (handler) prototype:
*<code>
* // &$row is the upload row being displayed
* function OnUploadFile( &$row)
*</code>
* @see CCEvents::AddHandler()
* @see CC_EVENT_UPLOAD_ROW
* @see CC_EVENT_UPLOAD_LISTING
*/
define('CC_EVENT_UPLOAD_FILES',        'uploadfiles' );


/**
* Notification Event: New or changed upload
*
* Triggered at the end of new upload processing, properties
* edit, a file has been added or replaced in an upload
* record, etc.
*
* The $op value can be one of the following:
* <ul>
* <li><b>CC_UF_NEW_UPLOAD</b> - This is a new upload record</li>
* <li><b>CC_UF_PROPERTIES_EDIT</b> - User has change properties that might affect
* things like the physical filename or the remix sources, etc.</li>
* <li><b>CC_UF_FILE_ADD</b> - User has added a new file (through 'Manage Files')
* to the upload record.</li>
* <li><b>CC_UF_FILE_REPLACE</b> - User has replaced one of the physical files</li>
* </ul>
*
* If the $parents parameter is present it is an array of remix sources for the upload.
*
* Call back (handler) prototype:
*<code>
* function OnUploadDone( $upload_id, $op, &$parents = array() )
*</code>
* @see CCEvents::AddHandler()
* @see CC_EVENT_FILE_DONE
*/
define('CC_EVENT_UPLOAD_DONE',         'uploaddone' );

/**
* Notification Event: A new physical file has been uploaded or changed.
*
* Triggered when a physical file has been added, replaced or edited. 
*
* Call back (handler) prototype:
*<code>
*function OnFileDone(&$file)
*</code>
* @see CCEvents::AddHandler()
* @see CC_EVENT_UPLOAD_DONE
*/
define('CC_EVENT_FILE_DONE',           'filedone' );

/**
* private
*/
define('CC_EVENT_ED_PICK',             'edpick' );

/**
* Notification Event: Upload has been rated
*
* Call back (handler) prototype:
*<code>
*function OnRated( $ratings_record, $score, &$upload_record )
*</code>
* @see CCEvents::AddHandler()
*/ 
define('CC_EVENT_RATED',               'rated' );

/**
* Request for Data Event: Data request that an upload has been rated
*
* Triggered when calculating system tags for an upload. Different
* modules will produce different tags depending on the record in
* question. This event is called for <i>both</i> upload and file
* records since they both have their own tags that are then combined.
*
* Either the upload <i>or</i> file record paramater will be set,
* but never both.
*
* The event callback is to put the tags into the $tags argument.
*
* Call back (handler) prototype:
*<code>
*function OnGetSysTags( &$upload_record, &$file_record, &$tags)
*</code>
* @see CCEvents::AddHandler()
*/ 
define('CC_EVENT_GET_SYSTAGS',         'getsystags' );

/**
* Request for Data Event: Massage a raw user row into a record
*
* Event trigged when a user row is pulled out of
* the database and is about to be displayed. Gives
* modules an opportunity to massage the row and
* populate it with data required by templates for display.
* The ccHost system uses this event to populate the 
* row with information like the user's ratings status,
* number of reviews, etc.
*
* Call back (handler) prototype:
*<code>
* // &$record is the user data being massaged
* function OnUploadUser( &$record )
*</code>
* @see CCEvents::AddHandler()
* @see CCUser::GetRecordFromRow()
*/
define('CC_EVENT_USER_ROW',            'userrow' );

/**
* Request for Data Event: Map URLs to methods and functions
*
* Triggered when the system to build up the map of URL-to-functions.
* Call back is expected to call {@link CCEvents::MapUrl()} in order
* to populate the map.
*
* Call back (handler) prototype:
*<code>
* function OnMapUrls()
*</code>
* @see CCEvents::AddHandler()
* @see CCEvents::MapUrl()
*/
define('CC_EVENT_MAP_URLS',            'mapurls');

/**
* Request for Data Event: Can the user upload this type?
*
* Triggered when the system needs to know if the requested
* submit type is allowed for the current user.
*
* Call back (handler) prototype:
*<code>
* function OnUploadAllowed( &$submit_types )
*</code>
* @see CCSubmit::Submit()
* @see CCThrottle::OnUploadAllowed()
*/
define('CC_EVENT_UPLOAD_ALLOWED',      'throttle');

/**
* Request for Data Event: Massage a raw contest row into a record
*
* Event trigged when a contest row is pulled out of
* the database and is about to be displayed. Gives
* modules an opportunity to massage the row and
* populate it with data required by templates for display.
* The ccHost system uses this event to populate the 
* row with detailed information about the creator of 
* the contest.
*
* Call back (handler) prototype:
*<code>
* // &$record is the contest data being massaged
* function OnUploadContest( &$record )
*</code>
* @see CCEvents::AddHandler()
* @see CCContest::GetRecordFromRow()
*/
define('CC_EVENT_CONTEST_ROW',         'contestrow' );

/**
* Request for Data Event: Get macro translations
* 
* A 'macro' in this context is a token that can be
* used for renaming or ID3 tagging a file. Macros
* are defined by modules that respond to this event
* and fill in the macro array with tokens (and 
* values if requested). 
*
* For example, if your module has a set of values 
* that might be useful in file renaming (like the
* frame rate of a video) then you could register
* for this event and expose a '%fps%' macro that
* allows admins to use that value whenever someone
* uploads a file.
*
* See the implementation of {@link CCFileRename::Rename()} for an example of 
* how to invoke this event.
*
* See the implemention of {@link CCContest::OnGetMacros()} for an example
* of how to handle the event and return the right data.
*
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_GET_MACROS',          'getmacros' );

/**
* Request for Data Event: Submit form types 
*
* Triggered when the system needs to initialize the
* submit form types. This event is triggered approximately
* once in the lifetime of ccHost installation and then
* roughly never again.
*
* For example of an event handler see the implementation
* of {@link CCMusicForms::OnSubmitFormTypes()}
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_SUBMIT_FORM_TYPES',   'submitformtypes' );

/**
* Notification Event: Page is about to be rendered
*
* Triggered just before the page object does a merge
* with the environment variables and the current
* skin's page template. This allows modules to do
* any last moment tweaks to the page before display.
*
* Event handler prototype:
*<code>
* // $page is an instance of {@link CCPage}
*function OnRenderPage( &$page );
*</code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_RENDER_PAGE',         'renderpage');

/**#@+
* Notification Event: Phase of form processing.
*
* $form paramater is an insance of {@link CCForm}
* @see CCEvents::AddHandler()
*/
/** 
* Prototype:
*<code>
*function OnFormInit( &$form );
*</code>
*/
define('CC_EVENT_FORM_INIT',           'forminit' );

/** 
* Prototype:
*<code>
*function OnFormFields( &$form, &$form_fields );
*</code>
*/
define('CC_EVENT_FORM_FIELDS',         'formfields' );

/** 
* Prototype:
*<code>
*function OnFormExtraFields( &$form, &$form_extra_fields );
*</code>
*/
define('CC_EVENT_EXTRA_FORM_FIELDS',   'formfieldsex' );

/** 
* Prototype:
*<code>
*function OnFormPopulate( &$form, &$values);
*</code>
*/
define('CC_EVENT_FORM_POPULATE',       'formpopulate' );

/** 
* Prototype:
*<code>
*function OnFormVerify( &$form, &$is_verified );
*</code>
*/
define('CC_EVENT_FORM_VERIFY',         'formverify' );

/**#@-*/

/**
* Notification Event: Advanced search hook
*
* Triggered <i>before</i> the default search takes place. This
* gives a chance for modules to hook the search request and
* process it completely on their own.
*
* See the implementation of {@link CCReview::OnDoSearch()} for
* example of a search hook and replace.
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_DO_SEARCH',            'dosearch' );

/**
* Request for Data Event: Fields for Admin Settings Forms
*
* Triggered by {@link CCAdminConfigForm::CCAdminConfigForm()} 
* and {@link CCAdminSettingsForm::CCAdminSettingsForm()}
* when the system is populating the fields for either the
* Global Settings admin form or the Settings admin form for
* the current virtual root.
*
* This allows modules to store variables in $CC_GLOBALS 
* across sessions as well as allow admins to edit those
* values. See the implementation of {@link CCEditorials::OnGetConfigFields()}
* for an example of how this is done.
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_GET_CONFIG_FIELDS',   'getcfgflds' );

/**
* Notification Event: Upload is about to be deleted
*
* N.B. Record could be in an unstable place as modules
* that respond to this event are deleting resources
* associated with the record along the way.
*
* Event call back (handler) prototype:
*<code>
* function OnUploadDelete( &$record );
*</code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_DELETE_UPLOAD',       'delete' );

/**
* Notification Event: Physical file is about to be deleted
*
* Event call back (handler) prototype:
*<code>
* function OnUploadFile( &$file_id );
*</code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_DELETE_FILE',         'deletefile' );

/**
* Notification Event: User record is about to be deleted
*
* N.B. Record could be in an unstable place as modules
* that respond to this event are deleting resources
* associated with the record along the way.
*
* Event call back (handler) prototype:
*<code>
* function OnUserDelete( $user_id );
*</code>
* @see CCEvents::AddHandler()
*/
define('CC_EVENT_USER_DELETED',         'userdel' );

/**#@+
* @access private
*/
define('CC_EVENT_USER_REGISTERED',      'userreg' );
define('CC_EVENT_USER_PROFILE_CHANGED', 'userprof' );
define('CC_EVENT_LOGIN_FORM',           'loginform' );
define('CC_EVENT_LOGOUT',               'logout' );

define('CC_EVENT_TRANSLATE',            'translate' );
/**#@-*/

/**#@+
* menu action flag
* @access private
*/
define('CC_MENU_DISPLAY', 1);
define('CC_MENU_EDIT',    2);
/**#@-*/

/**#@+
* Form field flag. See {@link CCForm::AddFormFields()} for details.
*/
define('CCFF_NONE',             0);
define('CCFF_SKIPIFNULL',     0x01); // insert/update - GetFormValues
define('CCFF_NOUPDATE',       0x02); // insert/update

define('CCFF_POPULATE',       0x04); // populate - PopulateValues

define('CCFF_HIDDEN',         0x08); // html form - GenerateForm

define('CCFF_REQUIRED',       0x20); // validate - ValidateFields
define('CCFF_NOSTRIP',        0x40); // validate
define('CCFF_NOADMINSTRIP',   0x80); // validate
define('CCFF_STATIC',        0x100); // validate
define('CCFF_HTML',          0x200); // populate/validate

define('CCFF_HIDDEN_DEFAULT',  CCFF_HIDDEN | CCFF_POPULATE);
/**#@-*/



/**#@+
* Resevered system tags (originally called upload descriptor, hence CCUD)
*/
define('CCUD_ORIGINAL',  'original');
define('CCUD_REMIX',     'remix');
define('CCUD_SAMPLE',    'sample');

define('CCUD_MEDIA_BLOG_UPLOAD',     'media');

define('CCUD_CONTEST_MAIN_SOURCE',   'contest_source');
define('CCUD_CONTEST_SAMPLE_SOURCE', 'contest_sample');
define('CCUD_CONTEST_ALL_SOURCES',   'contest_sample, contest_source');
define('CCUD_CONTEST_ENTRY',         'contest_entry');
define('CCUD_CONTEST_ALL',           'contest_entry,contest_sample,contest_source');
/**#@-*/

/**#@+
* Tag type, used by the {@link CCTags::CCTags() tagging system}
*/
define('CCTT_SYSTEM', 1);
define('CCTT_ADMIN',  2);
define('CCTT_USER',   4);
/**#@-*/

/**#@+
* Search criteria flag
*/
define( 'CC_SEARCH_USERS', 1 );
define( 'CC_SEARCH_UPLOADS', 2 );
define( 'CC_SEARCH_ALL',  CC_SEARCH_USERS | CC_SEARCH_UPLOADS);
/**#@-*/

/**#@+
* Upload event type flag (see {@link CC_EVENT_UPLOAD_DONE}
*/
define( 'CC_UF_NEW_UPLOAD', 1 );
define( 'CC_UF_FILE_REPLACE', 2 );
define( 'CC_UF_FILE_ADD', 3 );
define( 'CC_UF_PROPERTIES_EDIT', 4 );
/**#@-*/

/**
* not used?
* @access private
*/
define( 'CCMF_CUSTOM', 1 );

/**
* @access private
*/
define('CC_ENABLE_KEY', 'jimi');

/**
* When listing multiple records, how many links show in the 'Samples are used in' box
* before the 'More...' link shows up?
*/
define('CC_MAX_SHORT_REMIX_DISPLAY', 3);

/**#@+
* User registration type mode
*/
define('CC_REG_USER_EMAIL', 3 );
define('CC_REG_ADMIN_EMAIL', 2 );
define('CC_REG_NO_CONFIRM', 0 );
/**#@-*/

?>
