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


/*
* This module is designed to be called from either inside a browser session
* or from the command line (like from a cron job)
*
* For command line you must use -d IN_CC_HOST=1 define
*
*/
define('BDATE', 0 );
define('BELEM', 1 );
define('BUSER', 2 );
define('BIP',   3 );
define('BUPLOAD', 1 );


if( !defined('IN_CC_HOST') )
    die('Welcome to CC Host');

if( empty($CC_GLOBALS) )
{
    if( dirname( __FILE__ ) == getcwd() )
        chdir('..');
    require_once('cclib/cc-debug.php');
    if( !function_exists('gettext') )
       require_once('ccextras/cc-no-gettext.inc');
    require_once('cc-non-ui.php');
    require_once('cclib/cc-upload-table.php');
    require_once('cclib/cc-user.php');
    $configs = new CCConfigs();
    $CC_GLOBALS = $configs->GetConfig('config');
    calc_plays();
}

function update_playlist_counts()
{
    require_once('ccextras/cc-cart-table.inc');
    $items =& CCPlaylistItems::GetTable();
    $j1 = $items->AddJoin( new CCPlaylist(), 'cart_item_cart' );
    $uploads = new CCUploads();
    $j2 = $items->AddJoin( $uploads, 'cart_item_upload' );
    $items->GroupOn( 'cart_item_upload' );
    $where = 'cart_user != upload_user';
    $rows = $items->QueryRows($where,'upload_id, count(*) as upload_num_playlists');
    foreach($rows as $row)
        $uploads->Update($row);
}

function calc_plays()
{
    global $CC_GLOBALS;
    $fname = $CC_GLOBALS['logfile-dir'] . 'bump_count.txt';
    if( !file_exists($fname) )
        return "Could not find bump count file $fname";

    $lines = @file($fname);
    $farchive = $CC_GLOBALS['logfile-dir'] . 'bump_count_' . date('YmdHis') . '.txt';
    rename($fname,$farchive);

    $song_of_the_day = empty($CC_GLOBALS['playlist_feat']) ? '' : '_ep_1_' . $CC_GLOBALS['playlist_feat'];
    $min_seconds = empty($CC_GLOBALS['playlist_minsec']) ? 60 : $CC_GLOBALS['playlist_minsec'];

    $tracks = array();
    $plists = array();
    $counts = array();
    $len = count($lines);
    for( $i = 0; $i < $len; $i++ )
    {
        $F = split(',',$lines[$i]);
        if( !empty($song_of_the_day) && $F[BELEM] == $song_of_the_day )
            continue;
        $user = empty($F[BUSER]) ? trim($F[BIP]) :$F[BUSER];
        unset($F[BUSER]);
        unset($F[BIP]);
        preg_match_all( '/[0-9]+/',$F[BELEM],$m);
        if( empty($m[0][1]) )
        {
            $cart_id   = 0;
            $upload_id = $m[0][0];
        }
        else
        {
            $cart_id = $m[0][0];
            $upload_id = $m[0][1];
        }
        $F[BUPLOAD] = $upload_id;
        if( !empty($counts[$user]) )
        {
            $C =& $counts[$user];
            $clen = count($C);
            if( (strtotime ($F[BDATE]) - strtotime ($C[$clen-1][BDATE])) < $min_seconds  )
                continue;
            $found = false;
            for( $n = 0; $n < $clen; $n++ )
            {
                if( $C[$n][BUPLOAD] == $upload_id )
                {
                    $found = true;
                    break;
                }
            }
            if( $found )
                continue;
        }
        $counts[$user][] = $F;

        if( empty($tracks[$upload_id]) )
            $tracks[$upload_id] = array( 1, $upload_id);
        else
            $tracks[$upload_id][0]++;

        if( $cart_id && ($cart_id != 1) )
        {
            if( empty($plists[$cart_id]) )
                $plists[$cart_id] = array( 1, $cart_id);
            else
                $plists[$cart_id][0]++;
        }
    }

    $counts = array(); // free up mem

    $sql = array();
    foreach( $tracks as $T )
        $sql[] = "UPDATE cc_tbl_uploads SET upload_num_plays = upload_num_plays + {$T[0]} WHERE upload_id = {$T[1]}";
    CCDatabase::Query($sql);
    $sql = array();
    foreach( $plists as $P )
        $sql[] = "UPDATE cc_tbl_cart SET cart_num_plays = cart_num_plays + ${P[0]} WHERE cart_id = {$P[1]}";
    CCDatabase::Query($sql);

    if( !empty($CC_GLOBALS['playlist_rank']) )
    {
        $wplay = empty($CC_GLOBALS['playlist_weigh_play']) ? 0 : $CC_GLOBALS['playlist_weigh_play'];
        $wadd  = empty($CC_GLOBALS['playlist_weigh_add'])  ? 0 : $CC_GLOBALS['playlist_weigh_add'];
        $wrate = empty($CC_GLOBALS['playlist_weigh_rate']) ? 0 : $CC_GLOBALS['playlist_weigh_rate'];

        $sql = "UPDATE cc_tbl_uploads SET upload_rank = 0";
        CCDatabase::Query($sql);

        if( empty($wrate) )
        {
            $ratesql = '';
        }
        else
        {
            require_once('cclib/cc-ratings-admin.inc');
            $api = new CCRatingsAdmin();
            $api->Recalc(false);
            $ratesql = "+((upload_rank/5) * $wrate)";
        }

        update_playlist_counts();

        $sql =<<<END
            UPDATE cc_tbl_uploads SET upload_rank = ROUND(
                        (upload_num_plays * $wplay) + 
                        (upload_num_playlists * $wadd) 
                        $ratesql );
END;

        CCDatabase::Query($sql);
    }
}


?>