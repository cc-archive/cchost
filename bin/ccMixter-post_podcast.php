<?
/*
    This script is used to post a podcast topic
    where the podcast file (mp3) is already built

    $Id$ 

*/

if( empty($argv[3]) )
{
    die( "usage: php -f ccMixter-post_podcast.php PLAYLIST_ID URL_TO_MP3 SIZE_IN_MB\n" ) ;
}

$playlist_id = $argv[1]; // must be before include

print( "Playlist id: {$playlist_id}\n" );

require_once( dirname(__FILE__) . '/ccMixter-make_podcast.inc');

$mp3_url     = $argv[2]; // must be after include
$size_in_mb  = $argv[3];

make_podcast();

function make_podcast()
{
    global $mp3_url, $size_in_mb;
    
    _post_podcast_topic($size_in_mb,$mp3_url,'');
}


?>