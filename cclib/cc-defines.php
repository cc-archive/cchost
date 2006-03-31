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

define('CC_HOST_VERSION', '2.1-CVS');

define( 'CC_GLOBAL_SCOPE', 'media' );
define( 'CC_LOCAL_SCOPE',  'local' );

define('CC_1MG', 1024 * 1024);

define('CC_USER_COOKIE', 'lepsog2');

define('CC_DEFAULT_FILE_PERMS', 0664 );
define('CC_DEFAULT_DIR_PERM',   0775 );


// Access flags
define('CC_MUST_BE_LOGGED_IN',   1 );
define('CC_ONLY_NOT_LOGGED_IN',  2 );
define('CC_DONT_CARE_LOGGED_IN', 4 );
define('CC_ADMIN_ONLY',          8 );
define('CC_OWNER_ONLY',          0x10 );

define('CC_DISABLED_MENU_ITEM', 0x20 );
define('CC_DYNAMIC_MENU_ITEM',  0x40 );

// system events
define('CC_EVENT_APP_INIT',            'init');
define('CC_EVENT_APP_DONE',            'done');

define('CC_EVENT_MAIN_MENU',           'mainmenu');
define('CC_EVENT_PATCH_MENU',          'patchmenu');

define('CC_EVENT_UPLOAD_MENU',         'uploadmenu');
define('CC_EVENT_BUILD_UPLOAD_MENU',   'builduploadmenu');
define('CC_EVENT_ADMIN_MENU',          'buildadminmenu');
define('CC_EVENT_UPLOAD_ROW',          'uploadrow' );
define('CC_EVENT_UPLOAD_LISTING',      'uploadlisting' );
define('CC_EVENT_UPLOAD_FILES',        'uploadfiles' );
define('CC_EVENT_UPLOAD_DONE',         'uploaddone' );
define('CC_EVENT_FILE_DONE',           'filedone' );
define('CC_EVENT_LISTING_RECORDS',     'listingrecs' );
define('CC_EVENT_ED_PICK',             'edpick' );
define('CC_EVENT_RATED',               'rated' );
define('CC_EVENT_GET_SYSTAGS',         'getsystags' );
define('CC_EVENT_USER_ROW',            'userrow' );
define('CC_EVENT_MAP_URLS',            'mapurls');
define('CC_EVENT_UPLOAD_ALLOWED',      'throttle');
define('CC_EVENT_CONTEST_ROW',         'contestrow' );
define('CC_EVENT_GET_MACROS',          'getmacros' );
define('CC_EVENT_SUBMIT_FORM_TYPES',   'submitformtypes' );
define('CC_EVENT_RENDER_PAGE',         'renderpage');

define('CC_EVENT_FORM_INIT',           'forminit' );
define('CC_EVENT_FORM_FIELDS',         'formfields' );
define('CC_EVENT_FORM_POPULATE',       'formpopulate' );
define('CC_EVENT_FORM_VERIFY',         'formverify' );

define('CC_EVENT_DO_SEARCH',            'dosearch' );

define('CC_EVENT_GET_UPLOAD_FIELDS',   'getupflds' );
define('CC_EVENT_GET_USER_FIELDS',     'getuserflds' );
define('CC_EVENT_GET_CONFIG_FIELDS',   'getcfgflds' );

define('CC_EVENT_DELETE_UPLOAD',       'delete' );
define('CC_EVENT_DELETE_FILE',         'deletefile' );

define('CC_EVENT_USER_DELETED',         'userdel' );
define('CC_EVENT_USER_REGISTERED',      'userreg' );
define('CC_EVENT_USER_PROFILE_CHANGED', 'userprof' );
define('CC_EVENT_LOGIN_FORM',           'loginform' );
define('CC_EVENT_LOGOUT',               'logout' );

define('CC_EVENT_TRANSLATE',            'translate' );

//
// menu action flag
define('CC_MENU_DISPLAY', 1);
define('CC_MENU_EDIT',    2);

//
// form flags
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



// upload descriptors/system tags
define('CCUD_ORIGINAL',  'original');
define('CCUD_REMIX',     'remix');
define('CCUD_SAMPLE',    'sample');

define('CCUD_MEDIA_BLOG_UPLOAD',     'media');

define('CCUD_CONTEST_MAIN_SOURCE',   'contest_source');
define('CCUD_CONTEST_SAMPLE_SOURCE', 'contest_sample');
define('CCUD_CONTEST_ALL_SOURCES',   'contest_sample, contest_source');
define('CCUD_CONTEST_ENTRY',         'contest_entry');
define('CCUD_CONTEST_ALL',           'contest_entry,contest_sample,contest_source');

// tag types 
define('CCTT_SYSTEM', 1);
define('CCTT_ADMIN',  2);
define('CCTT_USER',   4);

// search criteria flags
define( 'CC_SEARCH_USERS', 1 );
define( 'CC_SEARCH_UPLOADS', 2 );
define( 'CC_SEARCH_ALL',  CC_SEARCH_USERS | CC_SEARCH_UPLOADS);

// upload event flags
define( 'CC_UF_NEW_UPLOAD', 1 );
define( 'CC_UF_FILE_REPLACE', 2 );
define( 'CC_UF_FILE_ADD', 3 );
define( 'CC_UF_PROPERTIES_EDIT', 4 );

// menu flags
define( 'CCMF_CUSTOM', 1 );

define('CC_ENABLE_KEY', 'jimi');

define('CC_MAX_SHORT_REMIX_DISPLAY', 3);

// registration types
define('CC_REG_USER_EMAIL', 3 );
define('CC_REG_ADMIN_EMAIL', 2 );
define('CC_REG_NO_CONFIRM', 0 );

?>
