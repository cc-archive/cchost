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
    HOW THE MAGNATUNE SONG INFO INTERACTS WITH ccMixter
    ----------------------------------------------------

    cc_tbl_pool_item is a ccHost native table that is used for Magnatune
    albums (not album cuts). 
    
    The native key is the pool_item_id field. This key is used in 
    cc_tbl_pool_tree to keep track of remixes done against
    the Magnatune catalog. 
    
    cc_tbl_pool_tree.pool_tree_pool_parent acts as a foreign key 
    when tracking remixes.

    cc_tbl_pool_item.albumsku is used as a foreign key to the table
    magnatune_song_info which is a copy of the 
    information published on the Magnatune site.

    magnatune_loops is a special table to represent the loop
    packs that were prepared for the Magnatune contest. These
    are hosted at magnatune.com and this table refers to those
    zip files.

    To import the Magnatune data see offline/magnatune_import/*

    Places the data is used:

    -- When posting a remix, the user can search the 'Magnatune
       sample pool' -- this is a search against cc_tbl_pool_item
       filtered on Magnatune's pool_id. 

    -- After the upload the pool_tree table is updated to reflect
       the related Magnatune sources and the new local remixes.

    -- When displaying a remix and it's sources, the pool_item 
       table is where the name of the album and artist comes from

    -- On the Magnatune samples page (mixter-files/magnatune_loops.xml)
       The magnatune_loops table is used to list out sample packs
       and the magnatune_song_info table is used to list out the
       entire Magnatune catalog.
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCMagnatune
{
    function Loops()
    {
        $args['loop_preview_url']  = ccc('magnatune','view','contest','sources') . '#loops';
        $args['album_preview_url'] = ccc('library', 'albumlist') . '#catalog';

        $sql = 'SELECT DISTINCT loop_artist, artist, artistdesc FROM magnatune_loops, magnatune_song_info WHERE loop_artist = page '.
               'ORDER BY artist';
        $args['artists'] = CCDatabase::QueryRows($sql);

        $filter = '';
        $args['genre_filter_on'] = false;
        if( !empty($_GET['genre']) )
        {
            $genre = CCUtil::StripText($_GET['genre']);
            if( !empty($genre) && ($genre != 'all') )
            {
                $filter = ' WHERE magnatunegenres LIKE \'%' . $genre . '%\' ';
                $args['genre_filter_on'] = true;
                $args['current_genre'] = $genre;
            }
        }
        $sql = 'SELECT DISTINCT artist, albumname, artistdesc, page, albumsku ' .
                "FROM magnatune_song_info $filter ORDER BY artist" ;
        $qr = CCDatabase::Query($sql);

        $args['albums_by_artist'] = array();
        $albums_by_artist =& $args['albums_by_artist'];
        while( $row = mysql_fetch_assoc($qr) )
        {
            if( empty($albums_by_artist[$row['artist']]) )
                $albums_by_artist[$row['artist']] = $row;
            $albums_by_artist[$row['artist']]['albums'][] = $row;
        }

        $g = empty($args['current_genre']) ? 'all' : $args['current_genre'];
        $sql = "SELECT DISTINCT magnatunegenres FROM magnatune_song_info";
        $all_genres = CCDatabase::QueryItems($sql);
        $all_genres_str = implode(',', $all_genres);
        $all_genres = explode(',',$all_genres_str);
        $all_genres = array_unique($all_genres);
        sort($all_genres);
        $args['genres'] = $all_genres;

        //CCDebug::PrintVar($args['genres']);
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

    function LocalSearch($pool_id,$query,$type)
    {
        $query = CCUtil::StripText( urldecode($query) );
        if( empty($query) )
            return( array() );

        switch( $type )
        {
            case 'artist':
                $fields = array( 'pool_item_artist' );
                break;
            case 'title' :
                $fields = array( 'pool_item_name' );
                break;
            default:
                $fields = array( 'pool_item_description', 'pool_item_name', 'pool_item_artist' );
                break;
        }
        require_once('cclib/cc-search.php');
        $filter = CCSearch::BuildFilter($fields,$query,'any');
        $filter = "($filter) AND pool_item_pool = '$pool_id'";
        $pool_items =& CCPoolItems::GetTable();
        $items = $pool_items->QueryRows($filter);
        return($items);
    }

    function LoopList($loop_artist='')
    {
        if( empty($loop_artist) )
            CCUtil::Send404(true);

        $artist_name = CCDatabase::QueryItem("SELECT artist FROM magnatune_song_info WHERE page = '$loop_artist' LIMIT 1");
        $loops = CCDatabase::QueryRows("SELECT * FROM magnatune_loops WHERE loop_artist = '$loop_artist'");
        $args['preview_macro'] = 'loop_preview';
        $args['mt']['loop_stream_url']   = ccl('library','loopstream');
        $args['mt']['loop_preview'] = array( 'loop_artist' => $loop_artist,
                                       'loop_artist_name' => $artist_name,
                                       'loops' => $loops );
        $this->_show_preview($args);
    }

    function AlbumList($album_pick)
    {
        $sql = "SELECT download_mp3, trackname, songid, artist, albumname ".
               "FROM magnatune_song_info WHERE albumsku = '$album_pick'";
        $songs = CCDatabase::QueryRows($sql);
        $args['preview_macro'] = 'album_preview';
        $args['mt']['song_stream_url']   = ccl('library','songstream');
        $args['mt']['album_preview'] = array( 'album' => addslashes($songs[0]['albumname']),
                                        'artist' => addslashes($songs[0]['artist']),
                                        'songs' => $songs);
        $this->_show_preview($args);
    }

    function _show_preview(&$args)
    {
        global $CC_GLOBALS;

        $template = new CCTemplate( 'magnatune_preview.xml' );
        $html = $template->SetAllAndParse($args);
        print($html);
        exit;
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
        CCEvents::MapUrl( ccp('library','loops'),      array('CCMagnatune','Loops'),      CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('library','loopstream'), array('CCMagnatune','LoopStream'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('library','songstream'), array('CCMagnatune','SongStream'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('library','albumlist'),  array('CCMagnatune','AlbumList'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('library','looplist'),   array('CCMagnatune','LoopList'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('mt','csv'),             array('CCMagnatune','csv'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
    }
}

?>