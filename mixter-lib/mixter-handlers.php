<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,   array( 'CCRemote', 'OnUploadDone'),  'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD, array( 'CCRemote', 'OnDeleteUpload'),  'mixter-lib/cc-remote.inc'  );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,    array( 'CCRemote', 'OnAdminMenu'),  'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRemote', 'OnMapUrls'),  'mixter-lib/cc-remote.inc' );

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMixter',  'OnBuildMenu'), 'mixter-lib/mixter.inc' );

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCMixterAdmin', 'OnAdminMenu'), 'mixter-lib/mixter-admin.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMixterAdmin', 'OnMapUrls'), 'mixter-lib/mixter-admin.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCMagnatune',  'OnMapUrls'), 'mixter-lib/mixter-magnatune.inc' );

CCevents::AddHandler( CC_EVENT_MAP_URLS, array( 'MixterMailList', 'OnMapUrls' ), 'mixter-lib/mixter-maillist.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMixterRedir', 'OnMapUrls'), 'mixter-lib/mixter-redir.inc' );

//CCEvents::AddHandler(CC_EVENT_APP_INIT,  array( 'CCRelicense',  'OnAppInit'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCRelicense',  'OnMapUrls'), 'mixter-lib/mixter-relicense.inc' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCSampleBrowser' , 'OnMapUrls'), 'mixter-lib/mixter-sample-browser.inc' );

CCevents::AddHandler(CC_EVENT_MAP_URLS, array( 'MixterContest', 'OnMapUrls' ), 'mixter-lib/mixter-contest.inc' );

?>