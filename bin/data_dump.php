<?php

/**
 * This script generates a large dump of xml of all the audio submitted to this
 * project.
 *
 * Add to a crontab -e to do this

 * // 15 4 * * 3,6 /web/ccmixter/www/bin/data_dump.sh 2>&1 >/dev/null
 * 
 * TODO: Add commandline options with standard getopts
 */

define('FEED_DUMP_FILE', 'dump.xml');

/* define('FEED_CUSTOM', 1);
define('FEED_ATOM', 2);
define('FEED_RSS', 3);
*/

if (!empty($_SERVER['argv'][1])) 
    $dump_file = $_SERVER['argv'][1];
else
    $dump_file = FEED_DUMP_FILE;

// chdir('/web/ccmixter/www');

if (!empty($_SERVER['argv'][2]))
    $feed_type = $_SERVER['argv'][2];
else
    $feed_type = 'custom';


error_reporting(E_ALL & ~E_NOTICE);

if( !file_exists('cc-config-db.php') )
    die('<html><body>ccHost has not been properly installed</body></html>');

if( file_exists('ccadmin') )
    die('<html><body>ccHost Installation is not complete.</body></html>');

define('IN_CC_HOST', true);

//if( file_exists('.cc-ban.txt') )        // this file is written by doing
//    require_once('.cc-ban.txt');        // per-user account management...

require_once('cclib/cc-debug.php');

CCDebug::Enable(false);                 // set this to 'true' if you are a
                                        // developer or otherwise customizing
                                        // the code. 

CCDebug::LogErrors( E_ALL & ~E_NOTICE );  // Log errors to a file during beta
                                          // this will help ccHost developers
                                          // when things go wrong on your site

CCDebug::InstallErrorHandler(true);     

require_once('cc-includes.php');
require_once('cc-custom.php');

CCConfigs::Init();                      // config settings established here
CCLogin::InitCurrentUser();             // user logged in 
CCEvents::Invoke(CC_EVENT_APP_INIT);    // Let all modules know it's safe to 
                                        // get in the waters

CCEvents::PerformAction();              // process incoming url 

// CCPage::Show();                         // show the resulting page

CCDebug::InstallErrorHandler(false); 

CCEvents::Invoke(CC_EVENT_APP_DONE);    




switch ($feed_type)
{
    case 'atom':
    	$dataDump = new CCFeedsAtom();
	break;
    case 'rss':
    	$dataDump = new CCFeeds();
	break;
    case 'custom':
    default: // default is FEED_CUSTOM
    	$dataDump = new CCDataDump();
	break;
}

$dataDump->SetIsDump(true);
$dataDump->SetDumpFile($dump_file); // default is all_audio.xml
$dataDump->GenerateFeed();

exit;
?>
