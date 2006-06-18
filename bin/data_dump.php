<?php

/**
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
 * Copyright 2005-2006, Creative Commons, www.creativecommons.org.
 * Copyright 2006, Jon Phillips, jon@rejon.org.
 *
 * data_dump.php
 *
 * This script generates a large dump of all the audio submitted to this
 * project using different feed formats (rss, atom, etc). It can output
 * individual files with specific feed formats or all files. Check the usage
 * options for more information.
 *
 * Add to do this script with crontab -e to do this

 * 15 4 * * 3,6 /web/ccmixter/www/bin/data_dump.sh 2>&1 >/dev/null
 *
 * TODO: Should probably have more error output.
 */

// The current feed types available.
$feed_types = array('datadump', 'atom', 'rss');

/**
 * Prints usage help options.
 */
function print_help ()
{
    global $feed_types;
    foreach ($feed_types as $type) {
        $feed_types_str .= "$type ";
    }

    echo         "\nThis app dumps listings of tagged content to files in \n",
                 "different feed formats (rss, atom, etc).\n\n",
         sprintf("Usage: \n\tphp %s [OPTION]...\n\n", $_SERVER['argv'][0]),
                 "Possible Arguments:\n\n",
                 "  -h\t\t\tGet help for this commandline program.\n",
                 "  -a\t\t\tDump all content in all feed types.\n",
                 "  -o [FILENAME]\t\tThe file to dump xml out to for a type.\n",
                 "  -f [FEEDTYPE]\t\tThe feed format to dump all content ",
                                     "from an \n\t\t\tinstallation. ", 
                                     "Default is atom.\n",
                 "  -t [TAGS]\t\tTags (ex: audio,media,experimental).\n",
                 "\nPossible Feed Types: $feed_types_str\n\n",
                 "Example 1: This outputs to dump.xml all files with tags \n",
                 "audio and media using the RSS feed format. \n\n",
         sprintf("\tphp %s -t audio,media -o dump.xml -f rss\n\n",
                 $_SERVER['argv'][0]),
                 "Example 2: This outputs a dump with tags audio and sample \n",
                 "of the same content in each format to different files.\n\n",
         sprintf("\tphp %s -t audio,sample -a\n",
                 $_SERVER['argv'][0]),
                 "\n";
    exit(1);
}


/**
 * Dumps individual feeds based on some type.
 */
function dump_feed ($feed_type, $dump_file_name, $tag_str)
{
    switch ($feed_type)
    {
        case 'atom':
            $dataDump = new CCFeedsAtom();
        break;
        case 'rss':
            $dataDump = new CCFeeds();
        break;
        case 'datadump':
        default: // default is FEED_CUSTOM
            $dataDump = new CCDataDump();
        break;
    }

    $dataDump->SetIsDump(true);
    $dataDump->SetDumpFileName($dump_file_name);
    $dataDump->GenerateFeed($tag_str);
}

/**
 * Dumps all feeds to the DUMP_DIR.
 */
function dump_feeds_all($tag_str = '')
{
    // these are all the current feed types
    $dumpAtom     = new CCFeedsAtom();
    $dumpRSS      = new CCFeeds();
    $dumpDataDump = new CCDataDump();

    // this way can iterate through any future settings we need
    foreach (array(&$dumpAtom, &$dumpRSS, &$dumpDataDump) as $dump)
    {
        $dump->SetIsDump(true);
        $dump->GenerateFeed($tag_str);
    }
}



// The following is necessary to cycle through startup of the sites
// engine.


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


// END: of startup of site engine



// parse command line options
$opt = getopt('hao:f:t:');

// if there are no arguments passed or -h option, then print help
if ( count($opt) == 0 || isset($opt['h']) )
    print_help();

// printing all feeds with -a is given preference
if ( isset($opt['a']) && !empty($opt['t']) ) {
    dump_feeds_all($opt['t']);
}
else if ( !empty($opt['o']) && !empty($opt['f']) && !empty($opt['t']) ) 
{
    // make sure the proper feed type is input
    foreach ($feed_types as $type) {
        if ($type == $opt['f'])
            $is_feed_type = true;
    }
    if ( ! $is_feed_type ) {
        echo "\nERROR:\n\t Not feed type: " . $opt['f']  . "\n\n";
        print_help();
    }
    dump_feed($type,$opt['o'],$opt['t']);
} else {
    print_help();
}


exit(0);
?>
