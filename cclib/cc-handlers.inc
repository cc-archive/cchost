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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCAccess' , 'OnMapUrls'),  'cclib/cc-access.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCAccess' , 'OnAdminMenu'), 'cclib/cc-access.php' );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCAdmin' , 'OnAdminMenu'), 'cclib/cc-admin.php' );
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCAdmin' , 'OnBuildMenu'), 'cclib/cc-admin.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCAdmin' , 'OnMapUrls'), 'cclib/cc-admin.php' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCAdmin' , 'OnGetConfigFields'), 'cclib/cc-admin.php' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS, array('CCRestAPI', 'OnMapUrls'), 'cclib/cc-api.php' );

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCBanHV',  'OnBuildUploadMenu'), 'cclib/cc-ban.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCBanHV',  'OnUploadMenu'), 'cclib/cc-ban.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCBanHV',  'OnUploadRow'), 'cclib/cc-ban.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCBan',  'OnMapUrls'), 'cclib/cc-ban.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCBan',  'OnAdminMenu'), 'cclib/cc-ban.inc'  );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCContest' , 'OnMapUrls'), 'cclib/cc-contest.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCContestHV',  'OnUploadRow'), 'cclib/cc-contest.php' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,    array( 'CCContest' , 'OnGetMacros'), 'cclib/cc-contest.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCContest', 'OnAdminMenu'), 'cclib/cc-contest.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,            array( 'CCDatabaseAdmin', 'OnMapUrls'), 'cclib/cc-db-admin.php' );

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCEditorials',  'OnBuildUploadMenu'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCEditorials',  'OnUploadMenu'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCEditorials',  'OnUploadRow'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCEditorials',  'OnMapUrls'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCEditorials' , 'OnGetConfigFields'), 'cclib/cc-editorials.php' );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,        array( 'CCEditorials',  'OnGetSysTags'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_FILTER_ED_PICK,     array( 'CCEditorials',  'OnFilterEdPick'), 'cclib/cc-editorials.php');
CCEvents::AddHandler(CC_EVENT_FILTER_ED_PICK_DETAIL, array( 'CCEditorials',  'OnFilterEdPickDetail'), 'cclib/cc-editorials.php');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCFeed', 'OnGetConfigFields'), 'cclib/cc-feed.php' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCFeed',  'OnUploadDelete'), 'cclib/cc-feed.php');
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,        array( 'CCFeed',  'OnFileDelete'), 'cclib/cc-feed.php');
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,        array( 'CCFeed',  'OnUploadDone'), 'cclib/cc-feed.php');
//CCEvents::AddHandler(CC_EVENT_RENDER_PAGE,        array( 'CCFeed',  'OnRenderPage'), 'cclib/cc-feed.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCFeedsAtom', 'OnMapUrls'), 'cclib/cc-feeds-atom.php');
CCEvents::AddHandler(CC_EVENT_ADD_FEED_LINKS,     array( 'CCFeedsAtom',  'OnAddFeedLinks'), 'cclib/cc-feeds-atom.php'); 

// this is in the base class
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCFeedsAtom', 'OnApiQueryFormat'),'cclib/cc-feeds-atom.php'); 

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCFeedsRSS',  'OnMapUrls'),'cclib/cc-feeds-rss.php');
CCEvents::AddHandler(CC_EVENT_ADD_FEED_LINKS,     array( 'CCFeedsRSS',  'OnAddFeedLinks'),'cclib/cc-feeds-rss.php'); 

// this is in the base class
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCFeedsRSS',  'OnApiQueryFormat'),'cclib/cc-feeds-rss.php'); 

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,       array( 'CCFileRename', 'OnAdminMenu'),     'cclib/cc-filerename.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,         array( 'CCFileRename', 'OnMapUrls'),       'cclib/cc-filerename.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_RENAMER,   array( 'CCFileRename', 'OnUploadRenamer'), 'cclib/cc-filerename.php' );

CCEvents::AddHandler(CC_EVENT_UPLOAD_ID3TAGGER,   array( 'CCID3Tagger', 'OnUploadID3Tagger'), 'cclib/cc-filetagger.php' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCPhysicalFile', 'OnMapUrls'), 'cclib/cc-files.php');

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCID3Tagger', 'OnAdminMenu'), 'cclib/cc-filetagger.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCID3Tagger', 'OnMapUrls'), 'cclib/cc-filetagger.php'  );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,          array( 'CCFileVerify', 'OnAdminMenu'),  'cclib/cc-fileverify.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,            array( 'CCFileVerify', 'OnMapUrls'),    'cclib/cc-fileverify.php'  );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,         array( 'CCFileVerify', 'OnGetSysTags'), 'cclib/cc-fileverify.php' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,          array( 'CCFileVerify', 'OnGetMacros'),  'cclib/cc-fileverify.php' );
CCEvents::AddHandler(CC_EVENT_INIT_VALIDATOR,      array( 'CCFileVerify', 'Install' ),     'cclib/cc-fileverify.php' );

CCEvents::AddHandler(CC_EVENT_APP_INIT,   '_verify_getid3_install', 'cclib/cc-getid3.php' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCGetID3' , 'OnGetConfigFields' ), 'cclib/cc-getid3.php' );
CCEvents::AddHandler(CC_EVENT_SYSPATHS, array( 'CCGetID3' , 'OnSysPaths' ), 'cclib/cc-getid3.php' );

CCEvents::AddHandler(CC_EVENT_APP_INIT, array( 'CCLanguage', 'OnInitApp'), 'cclib/cc-language.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS, array( 'CCLanguageAdmin',  'OnMapUrls'), 'cclib/cc-language.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU, array( 'CCLanguageAdmin' , 'OnAdminMenu'), 'cclib/cc-language.inc'  );

CCEvents::AddHandler(CC_EVENT_FILE_DONE,    array( 'CCLicense',  'OnFileDone'), 'cclib/cc-license.inc' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCLicense' , 'OnGetMacros'), 'cclib/cc-license.inc' );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,  array( 'CCLicense',  'OnGetSysTags'), 'cclib/cc-license.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCLicense',  'OnAdminMenu'), 'cclib/cc-license.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCLicense',  'OnMapUrls'), 'cclib/cc-license.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,   array( 'CCLicenseHV',  'OnUploadRow'), 'cclib/cc-license.php' );

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCLogin' , 'OnGetConfigFields'), 'cclib/cc-login.php' );
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,  array( 'CCLogin',  'OnBuildMenu'), 'cclib/cc-login.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,   array( 'CCLogin',  'OnMapUrls'), 'cclib/cc-login.php' );

CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCMacro' , 'OnGetMacros'), 'cclib/cc-macro.php' );

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCMediaHost',  'OnBuildUploadMenu'), 'cclib/cc-mediahost.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCMediaHost',  'OnUploadMenu'), 'cclib/cc-mediahost.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCMediaHost',  'OnUploadRow'), 'cclib/cc-mediahost.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCMediaHost',  'OnMapUrls'), 'cclib/cc-mediahost.php' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,         array( 'CCMediaHost',  'OnGetMacros'), 'cclib/cc-mediahost.php' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCMediaHost' , 'OnGetConfigFields'), 'cclib/cc-mediahost.php'  );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCMenu', 'OnAdminMenu'), 'cclib/cc-menu.php' );
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMenu', 'OnBuildMenu'), 'cclib/cc-menu.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMenu', 'OnMapUrls'), 'cclib/cc-menu.php' );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCNavigatorAdmin',  'OnAdminMenu'), 'cclib/cc-navigator-admin.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCNavigatorAdmin',  'OnMapUrls'), 'cclib/cc-navigator-admin.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPageAdmin', 'OnMapUrl'), 'cclib/cc-page.php' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPageAdmin', 'OnGetConfigFields'), 'cclib/cc-page.php'  );
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCPageAdmin',  'OnApiQueryFormat'), 'cclib/cc-page.php' ); 

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCPoolUI',  'OnMapUrls'), 'cclib/cc-pools-ui.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,     array( 'CCPoolUI',  'OnAdminMenu'), 'cclib/cc-pools-ui.php'  );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCPoolUI',  'OnUploadDelete'), 'cclib/cc-pools-ui.php' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCQuery',  'OnMapUrls'), 'cclib/cc-query.php' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCQuery' , 'OnGetConfigFields'), 'cclib/cc-query.php'  );

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCRating' , 'OnGetConfigFields' ), 'cclib/cc-ratings.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCRating',  'OnMapUrls'), 'cclib/cc-ratings.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCRating' , 'OnAdminMenu'), 'cclib/cc-ratings.php'  );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCRatingsHV' , 'OnUserRow'), 'cclib/cc-ratings.inc'  );
CCEvents::AddHandler(CC_EVENT_USER_DELETED,       array( 'CCRating' , 'OnUserDelete'), 'cclib/cc-ratings.php'  );

CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCRemix', 'OnUploadDelete'), 'cclib/cc-remix.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCRemix' ,'OnMapUrls'), 'cclib/cc-remix.php'  );

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,     array( 'CCRenderAudio', 'OnUploadMenu'), 'cclib/cc-renderaudio.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,      array( 'CCRenderAudio', 'OnUploadRow'), 'cclib/cc-renderaudio.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,        array( 'CCRenderAudio', 'OnMapUrls'), 'cclib/cc-renderaudio.php' );
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT, array( 'CCRenderAudio', 'OnApiQueryFormat'), 'cclib/cc-renderaudio.php' ); 

CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCRenderFlash', 'OnUploadRow'), 'cclib/cc-renderflash.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRenderFlash', 'OnMapUrls'), 'cclib/cc-renderflash.php' );

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,       array( 'CCRenderImage', 'OnUploadMenu'), 'cclib/cc-renderimage.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCRenderImage', 'OnMapUrls'), 'cclib/cc-renderimage.php' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,        array( 'CCRenderImage', 'OnUploadRow'), 'cclib/cc-renderimage.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,        array( 'CCRenderImage' , 'OnAdminMenu' ), 'cclib/cc-renderimage.php' );

CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,        array( 'CCRenderZip', 'OnUploadRow'), 'cclib/cc-renderzip.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_MACROS,      array( 'CCRenderZip',  'OnFilterMacros'), 'cclib/cc-renderzip.php'  );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCSearch', 'OnMapUrls'), 'cclib/cc-search.php'  );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCSkinAdmin' , 'OnMapUrls'),  'cclib/cc-skin-admin.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCSkinAdmin' , 'OnAdminMenu'), 'cclib/cc-skin-admin.php' );


CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCSubmit',  'OnBuildMenu'), 'cclib/cc-submit.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCSubmit',  'OnMapUrls'), 'cclib/cc-submit.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCSubmit', 'OnAdminMenu'), 'cclib/cc-submit.php' );

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,        array( 'CCTagEvents', 'OnBuildMenu'), 'cclib/cc-tags.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,         array( 'CCTagEvents', 'OnMapUrls'), 'cclib/cc-tags.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,       array( 'CCTagEvents', 'OnAdminMenu'), 'cclib/cc-tags.inc' );
CCEvents::AddHandler(CC_EVENT_SOURCES_CHANGED,  array( 'CCTag', 'OnSourcesChanged'), 'cclib/cc-tags.php'  );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCTemplateAdmin', 'OnAdminMenu'), 'cclib/cc-template.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCTemplateAdmin', 'OnMapUrls'), 'cclib/cc-template.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCUserAdmin', 'OnMapUrls'), 'cclib/cc-user-admin.php' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCUserAdmin', 'OnAdminMenu'), 'cclib/cc-user-admin.php' );

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCUserAPI', 'OnBuildMenu'), 'cclib/cc-user.inc' );
CCEvents::AddHandler(CC_EVENT_PATCH_MENU,         array( 'CCUser', 'OnPatchMenu'), 'cclib/cc-user.php' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCUserAPI', 'OnMapUrls'), 'cclib/cc-user.inc' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,         array( 'CCUserAPI', 'OnGetMacros'), 'cclib/cc-user.inc' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCUserAPI' , 'OnGetConfigFields'), 'cclib/cc-user.inc'  );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCUserHook', 'OnMapUrls'), 'cclib/cc-user-hook.php' );

// yes, the next two were meant to map to the same method...
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,   array( 'CCUser', 'OnUploadRow'), 'cclib/cc-user.php' );
CCEvents::AddHandler(CC_EVENT_CONTEST_ROW,  array( 'CCUser', 'OnUploadRow'), 'cclib/cc-user.inc' );

CCEvents::AddHandler(CC_EVENT_FILTER_DOWNLOAD_URL, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_UPLOAD_USER_TAGS, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_REMIXES_SHORT, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_REMIXES_FULL, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_RATINGS_STARS, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_FILES, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_UPLOAD_TAGS, 'cc_filter_std', 'cclib/cc-filters.php' );
CCEvents::AddHandler(CC_EVENT_FILTER_EXTRA, 'cc_filter_std', 'cclib/cc-filters.php' );





?>