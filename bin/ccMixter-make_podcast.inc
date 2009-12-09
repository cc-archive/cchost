<?

error_reporting(E_ALL);

define( 'CCM_HOME_DIR', '/var/www/ccmixter') ;
define( 'PODCAST_WORK_DIR',  '/var/tmp/podcasts');
define( 'CROSSFADE_LEN', 4 );
define( 'MIN_LENGTH_FOR_CLIP', 76.0 );
define( 'TARGET_DIR', CCM_HOME_DIR . '/podcasts');

define( 'IN_CC_HOST', 1 );

chdir( CCM_HOME_DIR );

if( !function_exists('gettext') )
   require_once('cchost_lib/ccextras/cc-no-gettext.inc');
require_once('cchost_lib/cc-includes.php');
$cc_extras_dirs = 'cchost_lib/ccextras';
include('cchost_lib/cc-inc-extras.php');
require_once('cchost_lib/cc-custom.php');
require_once('cchost_lib/cc-template-api.php');
require_once('cchost_lib/cc-query.php');
require_once('cchost_lib/cc-custom.php');
require_once('cchost_lib/cc-template-api.php');

CCConfigs::Init();


$records               = q("f=php&playlist={$playlist_id}",false);
$playlist_head         = q("f=html&t=playlist_2_info&ids={$playlist_id}&nomime=1&noexit=1");
$playlist_attr_links   = q("f=html&chop=0&t=links_by_lic&tags=-site_promo+-bumper&playlist={$playlist_id}&nomime=1&noexit=1");
$playlist_bumper_links = q("f=html&chop=0&t=links_by_lic&tags=bumper+site_promo&type=any&playlist={$playlist_id}&nomime=1&noexit=1");
$playlist_info         = q("f=php&dataview=playlist_detail&ids={$playlist_id}&nomime=1",false);

$playlist_info = $playlist_info[0];

$poddir = PODCAST_WORK_DIR . '/podcast_' . $playlist_id;
$mp3dir = $poddir . '/mp3s';
$wavdir = $poddir . '/wavs';
$tempdir = $poddir . '/temp';
$targetdir = TARGET_DIR;

$author = $playlist_info['user_real_name'];
$podcast_name = $playlist_info['cart_name'];

$str_date   = str_replace('@','',$playlist_info['cart_date_format']);
$time       = strtotime($str_date);
$year       = date('Y', $time );
$pod_date   = date('Y_m_d', $time );
$target_wav = $tempdir . '/workfile.wav';
$mp3_name   = preg_replace( '/[^a-z_0-9-]+/i', '_', trim($podcast_name)) . '.mp3';
$work_mp3   = $poddir . '/' . $mp3_name;
$target_mp3 = TARGET_DIR . '/' . $mp3_name;
$mp3_url    = ccd( 'podcasts', $mp3_name );

$num_recs = count($records);

function post_podcast_topic()
{
    global $target_mp3,
        $mp3_url;
        
    $mp3size    = filesize($target_mp3);
    $duration   = get_wav_len($target_mp3,'playtime_string');
    
    _post_podcast_topic($mp3size/(1024*1024),$mp3_url,$duration);
}

function _post_podcast_topic($mp3size,$mp3_url_param,$duration='')
{
    global $target_mp3, $playlist_info, $time,
        $playlist_head         ,
        $playlist_attr_links   ,
        $playlist_bumper_links ,
        $playlist_info         ;
        
    $site       = 'ccmixter.org';    
    $fs         = '(' . number_format($mp3size,2) . 'MB)';
        
    $topic_text =<<<EOF
{$playlist_head}
<h3><a rel="enclosure" href="{$mp3_url_param}">Download podcast - {$duration} {$fs}</a></h3>
<p><b>Songs...</b></p>
{$playlist_attr_links}


<p><b>Bumpers...</b></p>
{$playlist_bumper_links}
    <!--
         enclosure_url%{$mp3_url_param}%
         enclosure_size%{$mp3size}%
         enclosure_duration%{$duration}%
         enclosure_type%audio/mp3%
    -->
<br /><br />Subscribe to podcast <a href="http://{$site}/view/media/playlists/podcast">here</a>.    
EOF;

    $post_data  = array(
        'topic_type' => 'podcast',
        'topic_date' => date( 'Y-m-d H:i'),
        'topic_format' => 1, // 1 is CC_TOPIC_FORMAT_HTML
        'topic_name' => str_replace('&','and',$playlist_info['cart_name']),
        'topic_text' => $topic_text,
        'topic_user' => $playlist_info['cart_user'],
        'topic_nsfw' => $playlist_info['cart_nsfw'],
        'topic_upload' => 0,
        'topic_thread' => 0,
        );
    
    require_once('cchost_lib/ccextras/cc-topics.inc');
    $topics =& CCTopics::GetTable();
    $topics->Insert($post_data,0);    
}

function make_podcast_dirs()
{
    global $poddir,$mp3dir,$wavdir,$tempdir,$targetdir;
    
    foreach( array(PODCAST_WORK_DIR, $poddir, $tempdir, $mp3dir,$wavdir,$targetdir) as $dir )
    {
        print("Trying to make: $dir\n");
        if( !file_exists($dir) ) {
            mkdir($dir,0777,true);
        }
        if( !file_exists($dir) ) {
            die("Failed to make: $dir\n");
        }
    }
}    

function publish_podcast()
{
    global $work_mp3, $target_mp3;
    
    rename($work_mp3,$target_mp3);
    
}

function encode_mp3()
{
    global $playlist_info,$podcast_name,$author,$year,$poddir,$target_wav,$work_mp3,$target_mp3;
    
    $sql = "SELECT CONCAT('content/',user_name,'/',user_image) FROM cc_tbl_user WHERE user_name='{$playlist_info['user_name']}'";
    $imgfile = CCDatabase::QueryItem($sql);
    $icon_file = cca($imgfile);
    
    $cmd ="lame -S --tt \"${podcast_name}\" --ta \"${author}\" --tl \"ccMixter.org\" --ty \"${year}\" " .
                "--ti \"${icon_file}\" -v -V 3 \"${target_wav}\" \"${work_mp3}\"";
                
    sq($poddir,$cmd);
    exec($cmd);

}

function build_wav_file()
{
    global $target_wav,$wavdir,$num_recs;
    
    $prevlen = 0.0;
    copy($wavdir . '/000.wav', $target_wav);
    
    for( $i = 1; $i < $num_recs; $i++ )
    {
        $wavname = sprintf("{$wavdir}/%03d.wav",$i);
        $len1 = get_wav_len($wavname);
        $len2 = get_wav_len($target_wav);
        cross_fade( $target_wav, CROSSFADE_LEN, $target_wav, $len2, $wavname, $len1, $prevlen );
        $prevlen = $len1;
    }
}


function cross_fade( $out_file, $fade_length, $first_file, $first_length,
                        $second_file, $second_length, $prev_len )
{
    global $tempdir, $poddir;
    
    
    sq( $poddir, "Concat {$second_file}");
    
    if( $prev_len == 0.0 )
        $prev_len = $first_length;
    
    $trim_length = $first_length - $fade_length;
        
    if( $first_length < $fade_length ) // too small
    {
      sq($tempdir, "{$first_file} is shorter than fade, early out" );
      $cmd = "sox ${first_file} ${second_file} ${out_file}";
      sq($tempdir, $cmd);
      exec( $cmd );
      return;
    }
    
    if( $second_length < $fade_length ) {
      sq($tempdir, "{$second_file} is shorter than fade, early out");
      $cmd = "sox ${first_file} ${second_file} ${out_file}";
      sq($tempdir, $cmd);
      exec($cmd);
      return;
    }
    
    $fade_out_opts = '';

    if( $prev_len > 76.0 )
    {
        $sigh_len      = $fade_length - 1;
        $fade_out_opts = "fade t 0 0:0:{$sigh_len} 0:0:{$fade_length} vol -1.1 db";
    }
    else
    {
        sq($tempdir, "Will NOT fade out $first_file");
    }
    
    
    $fade_in_opts = '';
    
    if( $second_length > MIN_LENGTH_FOR_CLIP  ) 
        $fade_in_opts = "fade t 0:0:{$fade_length} vol -0.9 db";
    else
        sq($tempdir, "Will NOT fade in $second_file");
    
    # the operations are simple (if verbose)
    #
    # keep in mind that sox is non-destructive, the input file
    # is processed to the output file
    #
    # So: we chop off the last n secs of input1 and fade it down,
    # we chop the first n secs of input2 and fade it up.
    # We mix those n sec pieces on top of each other
    # We concat shortned versions of input1/2 with the new mixed
    # n seconds between them.

    $first_file  = '"' . $first_file . '"';
    $second_file = '"' . $second_file . '"';

    $cmds[] = "sox {$first_file} -s {$tempdir}/_t_fade_this_out.wav trim {$trim_length}";
    $cmds[] = "sox {$tempdir}/_t_fade_this_out.wav {$tempdir}/_t_faded_out_clip.wav {$fade_out_opts}";
    $cmds[] = "sox {$second_file} -s {$tempdir}/_t_fade_this_in.wav trim 0 {$fade_length}";
    $cmds[] = "sox {$tempdir}/_t_fade_this_in.wav {$tempdir}/_t_faded_in_clip.wav {$fade_in_opts}";
    $cmds[] = "sox -m -v 1.0 {$tempdir}/_t_faded_out_clip.wav -v 1.0 {$tempdir}/_t_faded_in_clip.wav {$tempdir}/_t_crossfade.wav";
    $cmds[] = "sox {$first_file} -s {$tempdir}/_t_trimmed_1_song.wav trim 0 {$trim_length}";
    $cmds[] = "sox {$second_file} -s {$tempdir}/_t_clipped_2_song.wav trim {$fade_length}";
    $cmds[] = "sox {$tempdir}/_t_trimmed_1_song.wav {$tempdir}/_t_crossfade.wav {$tempdir}/_t_clipped_2_song.wav ${out_file}";
    
    foreach( $cmds as $CMD )
    {
        //sq($tempdir, $CMD);
        exec($CMD);
    }
    $temps = glob($tempdir . '/_t_*.wav');
    foreach( $temps as $T )
        unlink($T);
    
}

function sq($mx,$str)
{
    $str = str_replace($mx,'...',$str);
    print $str . "\n";
}

function q($str,$capture=true)
{
    if( !$capture )
        return q_inner($str);
    ob_start();
    q_inner($str);
    $text = ob_get_contents();
    ob_end_clean();
    return $text;
}

function q_inner($str)
{
    $query = new CCQuery();
    $args = $query->ProcessAdminArgs($str);
    list( $results ) = $query->Query($args);
    return $results;
}


function convert_to_wavs()
{
    global $records, $wavdir;
    
    $keys = array_keys($records);
    $i = 0;
    foreach( $keys as $K )
    {
        $R =& $records[$K];
        
        $temp_wavname = sprintf("{$wavdir}/temp.wav");
        $cmd = "mpg123 -q -w {$temp_wavname} \"{$R['files'][0]['local_path']}\"";
        sq( CCM_HOME_DIR, $cmd );
        exec($cmd);
        
        $wavname = sprintf("{$wavdir}/%03d.wav",$i++);
        $cmd = "sox \"{$temp_wavname}\" -r 44100 -c 2 {$wavname}";
        sq( $wavdir, $cmd );
        exec($cmd);
        
        unlink($temp_wavname);
    }
}


function get_wav_len($path,$tag='playtime_seconds')
{
    require_once('cchost_lib/cc-getid3.php');
    $id3 =& CCGetID3::InitID3Obj();
    $tags = $id3->analyze($path);
    return $tags[$tag];
}

function d($obj)
{
    var_dump($obj);
    exit;
}

?>