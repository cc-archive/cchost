<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/*
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,   array( 'CCRemote', 'OnUploadDone'),  'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD, array( 'CCRemote', 'OnDeleteUpload'),  'mixter-lib/cc-remote.inc'  );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,    array( 'CCRemote', 'OnAdminMenu'),  'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRemote', 'OnMapUrls'),  'mixter-lib/cc-remote.inc' );
*/


CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMagnatune',  'OnMapUrls'), 'mixter-lib/mixter-magnatune.inc','','','ccMixter' );
CCevents::AddHandler(CC_EVENT_MAP_URLS,     array( 'MixterContest', 'OnMapUrls' ), 'mixter-lib/mixter-contest.inc','','','ccMixter' );

?>
