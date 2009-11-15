#!/usr/bin/php
<?php

/*
    SCRIPT FOR IMPORTING MAGNATUNE SONG INFO TABLE

*/

error_reporting(E_ALL);

chdir( dirname( dirname(__FILE__) )  );
    
define('IN_CC_HOST',1);
require_once( 'cc-host-db.php' );

define( 'SONG_INFO_FILENAME', '/var/tmp/song_info.csv');
define( 'SPLIT_PATTERN', '/"(.*[^\\\\])"[,\n\r]?/U' );
define( 'PREVIEW_STR', 'buy it at www.mag' );

function get_fields($buffer)
{
    preg_match_all(SPLIT_PATTERN, $buffer, $fields ); 
    return($fields[1]);
}

function insert_helper($fields,$table,$columns='')
{
    if( empty($columns) )
        $columns  = array_keys($fields);
    $data     = array_values($fields);
    $cols     = implode( ',', $columns );
    $count    = count($data);
    $values   = '';
    for( $i = 0; $i < $count; $i++ )
        $values .= " '" . addslashes($data[$i]) . "', ";
    $values   = substr($values,0,-2);
    $sql = "INSERT INTO $table ($cols) VALUES ( $values )";
    mysql_query($sql) or die( 'Insert failed: ' .  mysql_error() );
}

function next_id_helper($table)
{
    $sql = "SHOW TABLE STATUS LIKE '$table'" ;
    $qr = mysql_query($sql) or die( mysql_error() );
    $row = mysql_fetch_assoc($qr);
    if( empty($row['Auto_increment']) )
    {
        print_r($row);
        print("FOR TABLE: $table");
        exit;
    }
    return( $row['Auto_increment'] );
}

function fetch_magnatune_file()
{
    require_once('cchost_lib/snoopy/Snoopy.class.php');
    $snoopy = new Snoopy();
    $url = 'http://magnatune.com/info/song_info.csv';
    $ok = $snoopy->fetch($url);
    if( !$ok )
        die( $snoopy->error );
    $f = fopen(SONG_INFO_FILENAME,'w');
    fwrite($f,$snoopy->results);
    flush($f);
    fclose($f);
    $snoopy->results = '';
    $snoopy = null;
}

function p($msg)
{
    print "[IMPORT MAGNATUNE] {$msg}\n";
}

function main()
{
    p("Calling magnatune.com...");
    fetch_magnatune_file();
    p("...done");

    global $CC_DB_CONFIG;

    ///////////////
    //
    // Step 1. Open the CSV file
    //
    $f = fopen(SONG_INFO_FILENAME,'r');

    if ($f) 
    {
        p('Getting columns');
        
        ///////////////
        //
        // Step 2. Get column names and try to match them to 
        //         known columns
        //
        $buffer = fgets($f, 4096);
        $columns = get_fields($buffer);

        // these are the colums we expect...
        $exp = array
            (
                'artist',
                'trackname',
                'albumname',
                'tracknum',
                'year',
                'mp3genre',
                'magnatunegenres',
                'seconds',
                'download_mp3', // 'url',
                'buy',
                'home',
                'artistdesc',
                'bandphoto',
                'launchdate',
                'albumsku'
            );

        $num_columns= count($exp);
        $meta = array();
        for( $i = 0; $i < $num_columns; $i++ )
        {
            if( $columns[$i] == 'url' )
                $columns[$i] = 'download_mp3';

            if( $exp[$i] == $columns[$i] )
            {
                $n = $i;
            }
            else
            {
                // this code exists in case Magnatune
                // changes the format the of CSV file
                // (this is more likely than you might
                // think)
                //
                // This code has NOT been tested...
                //
                for( $n = 0; $n < count($columns); $n++ )
                {
                    if( $exp[$i] == $columns[$n] )
                    {
                        $n = $i;
                        break;
                    }
                }
            }

            $meta[$exp[$i]] = $n;
        }


        p('Opening database');
        
        ///////////////
        //
        // Step 3. Open database
        //
        $config = $CC_DB_CONFIG;

        $link = @mysql_connect( $config['db-server'], 
                                $config['db-user'], 
                                $config['db-password']) or die( mysql_error() );
        
        @mysql_select_db( $config['db-name'], $link ) or die( mysql_error() );


        ///////////////
        //
        // Step 4. Drop existing table and redo the structure here
        //         This is done do clear out songids (don't depend on
        //         them, Magnatune doesn't)
        //
        p('drop old table');
        
        $sql = "DROP TABLE IF EXISTS magnatune_song_info";
        mysql_query($sql) or die("couldn't drop table\n" . mysql_error() );
        
        $sql =<<<END
CREATE TABLE magnatune_song_info (
  songid int(11) NOT NULL auto_increment,
  artist varchar(160) NOT NULL default '',
  page varchar(150) NOT NULL default '',
  trackname text,
  albumname varchar(255) NOT NULL default '',
  tracknum int(11) NOT NULL default '0',
  year int(11) NOT NULL default '0',
  mp3genre varchar(120) NOT NULL default '',
  magnatunegenres varchar(100) NOT NULL default '',
  seconds int(11) NOT NULL default '0',
  buy varchar(255) NOT NULL default '',
  home varchar(200) NOT NULL default '',
  artistdesc varchar(255) NOT NULL default '',
  bandphoto varchar(255) NOT NULL default '',
  launchdate date NOT NULL default '0000-00-00',
  albumsku varchar(140) NOT NULL default '',
  upc varchar(212) NOT NULL default '',
  city_state varchar(140) NOT NULL default '',
  country varchar(140) NOT NULL default '',
  download_mp3 varchar(255) NOT NULL default '',
  download_mp3lofi varchar(255) NOT NULL default '',
  PRIMARY KEY  (songid)
) TYPE=MyISAM;
END;
          
        mysql_query($sql) or die("couldn't create table\n" . mysql_error() );

        mysql_query('LOCK TABLES magnatune_song_info WRITE, cc_tbl_pool_item WRITE') or die("couldn't lock table\n" . mysql_error() );

        p("Reading records...");
        flush();

        /////////////////////////////
        //
        // Step 5. Process a record
        //
        while( !feof($f) ) 
        {
            $buffer = fgets($f, 4096);

            // this step won't be needed after john cleans
            // up the csv...
            if( strstr($buffer, PREVIEW_STR) !== false )
                continue;

            if( empty($buffer) )
                continue;

            // bad comma data for Seth Carlin
            $buffer = str_replace('"Grand Duo"',"Grand Duo",$buffer);

            /////////////////////////////
            //
            // Step 5a. Process fields in a record
            //
            $fields = get_fields($buffer);

            if( count($fields) != $num_columns )
                die("bad column count in data: \n$buffer\n\n");

            $sku = $fields[ $meta['albumsku'] ];
            $sql = "SELECT COUNT(*) FROM cc_tbl_pool_item WHERE albumsku = '$sku'";
            $qr = mysql_query($sql) or die( mysql_error() );
            $row = mysql_fetch_row($qr);
            if( $row[0] == 0 )
            {
                /////////////////////////////
                //
                // Step 5b. 
                //
                // we've never seen this album before, put it into the 
                // pool_item table...
                //
                $id = next_id_helper('cc_tbl_pool_item');

                $extra = array( 'guid' => 'http://ccmixter.org/media/magnatune/file/' . $id );

                $R = array();

                $R['pool_item_id']           = $id;
                $R['pool_item_url']          = $fields[ $meta['home'] ];
                $R['pool_item_name']         = $fields[ $meta['albumname'] ];
                $R['pool_item_artist']       = $fields[ $meta['artist'] ];
                $R['pool_item_pool']         = 1;
                $R['pool_item_url']          = $fields[ $meta['home'] ];
                $R['pool_item_download_url'] = ''; // hmmm
                $R['pool_item_description']  = $fields[ $meta['artistdesc'] ];
                $R['pool_item_extra']        = serialize($extra);
                $R['pool_item_license']      = 'by-nc-sa';
                $R['pool_item_approved']     = 1;
                $R['pool_item_timestamp']    = time();
                $R['albumsku']               = $fields[ $meta['albumsku'] ];
                
                insert_helper($R,'cc_tbl_pool_item');
                p("New album: '" . $R['pool_item_name'] . "' by " . $R['pool_item_artist']);
                flush();
            }

            ///////////////////////////////////////////
            //
            // Step 5c. Copy the data into our database
            //
            if( !in_array('page',$columns) )
            {
                // ugh, csv is missing 'page'
                $cols = $columns;
                $cols[] = 'page';
                $fields[] = preg_replace('#.*/([^/]+)$#','\1',$fields[ $meta['home'] ]);
                insert_helper($fields,'magnatune_song_info',$cols);
            }
            else
            {
                insert_helper($fields,'magnatune_song_info',$columns);
            }
        }

        p("Done");
        mysql_query('UNLOCK TABLES');
        flush();
        fclose($f);
        
        unlink(SONG_INFO_FILENAME);
    }
    else
    {
        $sfile = SONG_INFO_FILENAME;
        die("Couldn't open {$sfile} file");
    }
}


main();


?>
