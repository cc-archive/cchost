<?
/* $Id$ */


if( empty($argv[1]) )
{
    die( "Missing playlist arg");
}

$playlist_id = $argv[1];

print( "Playlist id: {$playlist_id}\n" );

require_once( dirname(__FILE__) . '/ccMixter-make_podcast.inc');


make_podcast();

function make_podcast()
{
    make_podcast_dirs();   
    convert_to_wavs();
    build_wav_file();
    encode_mp3();
    publish_podcast();
    post_podcast_topic();
}


?>