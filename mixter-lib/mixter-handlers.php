<?

/*
  $Id$
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('CC_EVENT_FILTER_PODCAST_INFO', 'filtpodinfo');

require_once('dig/config.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMagnatune',  'OnMapUrls'), 'mixter-lib/mixter-magnatune.inc','','','ccMixter' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'MixterContest', 'OnMapUrls' ), 'mixter-lib/mixter-contest.inc','','','ccMixter' );

CCEvents::AddHandler(CC_EVENT_FILTER_PODCAST_INFO,  'cc_filter_podinfo', 'mixter-lib/mixter-filter-podinfo.inc','','','ccMixter' );

?>
