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
* $Header$
*
*/


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCMagnatune',  'OnMapUrls'));

class CCMagnatune
{
    function Loops()
    {
        $args['loop_preview_url']  = ccc('magnatune','view','contest','sources') . '#loops';
        $args['album_preview_url'] = ccc('magnatune','view','contest','sources') . '#catalog';
        $args['loop_stream_url']   = ccl('library','loopstream');
        $args['song_stream_url']   = ccl('library','songstream');

        $sql = 'SELECT DISTINCT loop_artist, artist, artistdesc FROM magnatune_loops, magnatune_song_info WHERE loop_artist = page '.
               'ORDER BY artist';
        $args['artists'] = CCDatabase::QueryRows($sql);


        $sql = 'SELECT DISTINCT artist, albumname, artistdesc, page FROM magnatune_song_info ORDER BY artist';
        $qr = CCDatabase::Query($sql);


        $args['albums_by_artist'] = array();
        $albums_by_artist =& $args['albums_by_artist'];
        while( $row = mysql_fetch_assoc($qr) )
        {
            if( empty($albums_by_artist[$row['artist']]) )
                $albums_by_artist[$row['artist']] = $row;
            $albums_by_artist[$row['artist']]['albums'][] = $row;
        }

        if( !empty($_POST['loop_artist']) )
        {
            $loop_artist = CCUtil::StripText($_POST['loop_artist']);
            if( !empty($loop_artist) )
            {
                $artist_name = CCDatabase::QueryItem("SELECT artist FROM magnatune_song_info WHERE page = '$loop_artist' LIMIT 1");
                $loops = CCDatabase::QueryRows("SELECT * FROM magnatune_loops WHERE loop_artist = '$loop_artist'");
                $args['loop_preview'] = array( 'loop_artist' => $loop_artist,
                                               'loop_artist_name' => $artist_name,
                                               'loops' => $loops );
            }
        }

        if( !empty($_POST['album_pick']) )
        {
            $album_pick = CCUtil::StripText($_POST['album_pick']);
            if( !empty($album_pick) )
            {
                $v = split('::',$album_pick);
                $artist = $v[0];
                $dbartist = addslashes($v[0]);
                $album  = $v[1];
                $dbalbum  = addslashes($v[1]);
                $sql = "SELECT download_mp3, trackname, songid FROM magnatune_song_info WHERE artist = '$dbartist' AND albumname = '$dbalbum'";
                $songs = CCDatabase::QueryRows($sql);
                $args['album_preview'] = array( 'album' => $album,
                                                'artist' => $artist,
                                                'songs' => $songs);
            }
        }
        
        CCPage::PageArg('mt', $args);
        CCPage::ViewFile('magnatune_loops.xml');
    }

    function LoopStream($loopid_wext)
    {
        preg_match('/(.*)\.m3u$/',$loopid_wext,$m);
        if( !empty($m[1]) )
        {
            $id = $m[1];
            $row = CCDatabase::QueryRow("SELECT loop_filename,loop_artist FROM magnatune_loops WHERE loop_id = '$id'");
            $name = urlencode($row['loop_filename']);
            $path = "http://magnatune.com/extra/remix/loops/{$row['loop_artist']}/$name.mp3";
            header("Content-type: audio/x-mpegurl");
            print "$path\n";
        }
        exit;
    }

    function SongStream($loopid_wext)
    {
        preg_match('/(.*)\.m3u$/',$loopid_wext,$m);
        if( !empty($m[1]) )
        {
            $id = $m[1];
            $path = CCDatabase::QueryItem("SELECT download_mp3 FROM magnatune_song_info WHERE songid = '$id'");
            header("Content-type: audio/x-mpegurl");
            print "$path\n";
        }
        exit;
    }

    function LocalSearch($pool_id,$query)
    {
        $query = CCUtil::StripText( urldecode($query) );
        if( empty($query) )
            return( array() );

        $fields = array( 'pool_item_description', 'pool_item_name', 'pool_item_artist' );
        $filter = CCSearch::BuildFilter($fields,$query,'any');
        $filter = "($filter) AND pool_item_pool = '$pool_id'";
        $pool_items =& CCPoolItems::GetTable();
        $items = $pool_items->QueryRows($filter);
        return($items);
    }

    // sigh.
    // this has to be run after updating the song_info table
    // so that remix searches work too...
    //
    function SyncPool()
    {
        $sql =<<<END
            SELECT *
            FROM `new_magnatune_song_info`
            GROUP BY albumname, artist
            ORDER BY artist, albumname
END;

        $qr = CCDatabase::Query($sql);

        $pool_items = new CCTable('cc_tbl_pool_item', 'pool_item_id');
        $song_infos = new CCTable('magnatune_song_info', 'songid');

        $m_fields = array( 	 'songid',
                             'artist',
                             'page',
                             'trackname',
                             'albumname',
                             'tracknum',
                             'year',
                             'mp3genre',
                             'magnatunegenres',
                             'seconds',
                             'buy',
                             'home',
                             'artistdesc',
                             'bandphoto',
                             'launchdate',
                             'albumsku',
                             'upc',
                             'city_state',
                             'country',
                             'download_mp3',
                             'download_mp3lofi' );

        while( $song_info = mysql_fetch_array($qr) )
        {
            // add this song to the magnatune song info

            $A = array();

            $A['albumsku'] = $song_info['albumsku'];

            if( !$song_infos->QueryKey($A) )
            {
                $R = array();

                foreach( $m_fields as $m_field )
                    $R[$m_field] = $song_info[$m_field];

                $song_infos->Insert($R);
            }

            // add this album to the magnatune pool

            if( !$pool_items->QueryKey($A) ) // pool item for this item is already there
            {
                $id = $pool_items->NextID();

                $extra = array( 'guid' => 'http://ccmixter.org/media/magnatune/file/' . $id );

                $R = array();

                $R['pool_item_url']          = 'http://magnatune.com/artists/' . $song_info['page'];
                $R['pool_item_name']         = $song_info['albumname'];
                $R['pool_item_artist']       = $song_info['artist'];
                $R['pool_item_id']           = $id;
                $R['pool_item_pool']         = 1;
                $R['pool_item_url']          = 'http://magnatune.com/artists/' . $song_info['page'];
                $R['pool_item_download_url'] = ''; // hmmm
                $R['pool_item_description']  = $song_info['artistdesc'];
                $R['pool_item_extra']        = serialize($extra);
                $R['pool_item_license']      = 'by-nc-sa';
                $R['pool_item_approved']     = 1;
                $R['pool_item_timestamp']    = time();
                $R['albumsku']               = $song_info['albumsku'];
                
                $pool_items->Insert($R);
            }
        }

        CCPage::Prompt("Magnatune pool table sync'd");
    }


    // this was a one time function for digging out the 
    // magnatune contest entrants info
    function csv()
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter('magnatune');
        $uploads->SetOrder('user_name');
        $records = $uploads->GetRecords('');
        header('Content-type: text/plain');
        print( "USERNAME, UPLOAD_NAME, FILE_NAME, USER_REAL_NAME, DAYTIME_PHONE, COUNTRY, BIRTHDATE\n");
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $record =& $records[$i];
            if( is_string($record['user_extra']) )
                $uextra = unserialize($record['user_extra']);
            else
                $uextra = $record['user_extra'];
            
            $minfo =& $uextra['magnatune_contest_info'];

            print( $record['user_name'] . ', ' .
                   $record['upload_name'] . ', ' .
                   $record['files'][0]['file_name'] . ', ' .
                   $minfo['realname'] . ', ' .
                   $minfo['daytimephone'] . ', ' .
                   $minfo['country'] . ', ' .
                   $minfo['birthdate'] . "\n" );
        }

        exit;
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('library','loops'),      array('CCMagnatune','Loops'),      CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('library','loopstream'), array('CCMagnatune','LoopStream'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('library','songstream'), array('CCMagnatune','SongStream'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('mt','csv'),             array('CCMagnatune','csv'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('mt','syncpool'),        array('CCMagnatune','SyncPool'), CC_ADMIN_ONLY);
    }
}

?>