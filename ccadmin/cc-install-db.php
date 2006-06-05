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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

function cc_install_tables(&$vars,&$msg)
{
    // 
    // USERS
    //
    $drops [] = 'cc_tbl_user';

    $sql[] = <<<END

CREATE TABLE cc_tbl_user 
    (
      user_id          int(11) unsigned NOT NULL auto_increment,
      user_name        varchar(25)     NOT NULL default '',
      user_real_name   varchar(255)    NOT NULL default '',
      user_password    tinyblob        NOT NULL default '',
      user_email       tinytext        NOT NULL default '',
      user_image       varchar(255)    NOT NULL default '',
      user_description mediumtext      NOT NULL default '',
      user_homepage    mediumtext      NOT NULL default '',
      user_registered  datetime        NOT NULL,
      user_favorites   mediumtext      NOT NULL default '',
      user_whatilike   mediumtext      NOT NULL default '',
      user_whatido     mediumtext      NOT NULL default '',
      user_lookinfor   mediumtext      NOT NULL default '',
      user_extra       mediumtext      NOT NULL default '',
      user_last_known_ip varchar(25)     NOT NULL default '',
      
      user_num_remixes INT(7) unsigned,
      user_num_remixed INT(7) unsigned,
      user_num_uploads INT(7) unsigned,
      
      user_score       INT(11) unsigned,
      user_num_scores  INT(11) unsigned,
      user_rank        INT(11) unsigned,

      user_num_reviews  INT(7) unsigned,
      user_num_reviewed INT(7) unsigned,

      user_num_posts    INT(11) unsigned,

      PRIMARY KEY user_id (user_id)
    )
END;

    // 
    // UPLOADS
    //
    $drops [] = 'cc_tbl_uploads';

    $sql[] = <<<END

CREATE TABLE cc_tbl_uploads 
    (
      upload_id         int(11) unsigned  NOT NULL auto_increment,
      upload_user       int(11) unsigned  NOT NULL,
      upload_contest    int(11) unsigned  NOT NULL,

      upload_name         varchar(255)     NOT NULL default '',
      upload_license      varchar(255)     NOT NULL default '',
      upload_config       varchar(255)     NOT NULL default '',
      upload_extra        mediumtext       NOT NULL default '',  
      upload_tags         mediumtext       NOT NULL default '',
      upload_date         datetime         NOT NULL,
      upload_description  mediumtext       NOT NULL default '',
      upload_published    int(1) unsigned  NOT NULL,
      upload_banned       int(1) unsigned  NOT NULL default 0,
      upload_topic_id     int(11) unsigned  NOT NULL default 0,

      upload_num_remixes       INT(7) unsigned,
      upload_num_pool_remixes  INT(7) unsigned,
      upload_num_sources       INT(7) unsigned,
      upload_num_pool_sources  INT(7) unsigned,

      upload_score        INT(11) unsigned,
      upload_num_scores   INT(11) unsigned,
      upload_rank         INT(11) unsigned,

      PRIMARY KEY upload_id (upload_id)
    )

END;

        // 
    // UPLOADS
    //
    $drops [] = 'cc_tbl_files';

    $sql[] = <<<END

CREATE TABLE cc_tbl_files
    (
      file_id         int(11) unsigned  NOT NULL auto_increment,
      file_upload     int(11) unsigned  NOT NULL,

      file_name         mediumtext       NOT NULL default '',
      file_nicname      varchar(25)      NOT NULL default '',
      file_format_info  mediumtext       NOT NULL default '',
      file_extra        mediumtext       NOT NULL default '',
      file_filesize     int(20) unsigned NOT NULL,
      file_order        int(11)  unsigned NOT NULL default 0,
      file_is_remote    tinyint unsigned NOT NULL default 0,

      PRIMARY KEY file_id (file_id)
    )

END;


    // 
    // (REMIX) TREE
    //
    $drops [] = 'cc_tbl_tree';

    $sql[] = <<<END

CREATE TABLE cc_tbl_tree 
    (
      tree_id       int(11) unsigned NOT NULL auto_increment,
      tree_parent   int(11) unsigned NOT NULL default 0,
      tree_child    int(11) unsigned NOT NULL default 0,

      PRIMARY KEY tree_id (tree_id)
    )

END;

    // 
    // CONTESTS
    //
    $drops [] = 'cc_tbl_contests';
    $sql[] = <<<END

CREATE TABLE cc_tbl_contests
    (
      contest_id              int(11) unsigned  NOT NULL auto_increment,
      contest_user            int(11) unsigned  NOT NULL,
      contest_short_name      varchar(255)     NOT NULL default '',
      contest_friendly_name   varchar(255)     NOT NULL default '',
      contest_rules_file      varchar(255)     NOT NULL default '',
      contest_template        varchar(255)     NOT NULL default '',
      contest_bitmap          varchar(255)     NOT NULL default '',
      contest_description     text             NOT NULL default '',
      contest_open            datetime         NOT NULL,
      contest_deadline        datetime         NOT NULL,
      contest_created         datetime         NOT NULL,
      contest_auto_publish    int(1)           NOT NULL,
      contest_publish         int(1)           NOT NULL,
      contest_vote_online     int(1)           NOT NULL,
      contest_vote_deadline   datetime         NOT NULL,

      PRIMARY KEY contest_id (contest_id)
    )
END;

    // 
    // POLL
    //
    $drops [] = 'cc_tbl_polls';

    $sql[] = <<<END

CREATE TABLE cc_tbl_polls
    (
      poll_valueid  int(11) unsigned  NOT NULL auto_increment,
      poll_id       varchar(255),
      poll_value    varchar(255), 
      poll_numvotes int(11) unsigned NOT NULL default 0,

      PRIMARY KEY poll_valueid (poll_valueid)
    )
END;

    // 
    // Ratings
    //
    $drops [] = 'cc_tbl_ratings';

    $sql[] = <<<END

CREATE TABLE cc_tbl_ratings
    (
      ratings_id       int(11) unsigned  NOT NULL auto_increment,
      ratings_score    int(11) NOT NULL default 0,
      ratings_upload   int(11) NOT NULL default 0,
      ratings_user     int(11) NOT NULL default 0,
      ratings_ip       varchar(20),

      PRIMARY KEY rating_ids (ratings_id)

    )

END;

    // 
    // TAGS
    //
    $drops [] = 'cc_tbl_tags';
 
    $sql[] = <<<END

CREATE TABLE cc_tbl_tags
    (
      tags_tag   varchar(50),
      tags_count int(11) unsigned NOT NULL default 0,
      tags_type  int(2) unsigned NOT NULL default 0,

      PRIMARY KEY tags_tag (tags_tag)
    )
END;

    // 
    // TAG ALIAS
    //
    $drops [] = 'cc_tbl_tag_alias';

    $sql[] = <<<END

CREATE TABLE cc_tbl_tag_alias
    (
      tag_alias_tag    varchar(50),
      tag_alias_alias  varchar(50),

      PRIMARY KEY tag_alias_tag (tag_alias_tag)
    )
END;

    // 
    // LICENSES
    //
    $drops [] = 'cc_tbl_licenses';

    $sql[] = <<<END

CREATE TABLE cc_tbl_licenses
    (
        license_id              varchar(255) NOT NULL,
        license_url             mediumtext   NOT NULL,
        license_name            varchar(255) NOT NULL,
        license_jurisdiction    varchar(255) NOT NULL,
        license_permits         mediumtext   NOT NULL,
        license_required        mediumtext   NOT NULL,
        license_prohibits       mediumtext   NOT NULL,
        license_logo            varchar(255) NOT NULL,
        license_tag             varchar(255) NOT NULL,
        license_strict          int(4)       NOT NULL,
        license_text            mediumtext   NOT NULL,

       PRIMARY KEY license_id (license_id)
    )
END;


    // 
    // KEYS
    //
    // used for new registration
    //
    $drops [] = 'cc_tbl_keys';

    $sql[] = <<<END

CREATE TABLE cc_tbl_keys
    (
      keys_id    int(11) unsigned  NOT NULL auto_increment,
      keys_key   varchar(255),
      keys_ip    varchar(40),
      keys_time  datetime,

      PRIMARY KEY keys_id (keys_id)
    )
END;

    // 
    // Configs
    //
    // used for various config arrays
    //
    $drops [] = 'cc_tbl_config';

    $sql[] = <<<END

CREATE TABLE cc_tbl_config
    (
      config_id     int(11) unsigned  NOT NULL auto_increment,
      config_type   varchar(255),
      config_scope  varchar(40),
      config_data   mediumtext,

      PRIMARY KEY config_id (config_id)
    )
END;

    // 
    // Outbound feed cache
    //
    // used for caching feeds
    //
    $drops [] = 'cc_tbl_feedcache';

    $sql[] = <<<END

CREATE TABLE cc_tbl_feedcache
    (
      feedcache_id     int(11) unsigned  NOT NULL auto_increment,
      feedcache_tags   mediumtext       NOT NULL default '',
      feedcache_text   text             NOT NULL default '',
      feedcache_date   datetime,
      feedcache_type   varchar(20),

      PRIMARY KEY feedcache_id (feedcache_id)
    )
END;

    // 
    // Activity log
    //
    $drops [] = 'cc_tbl_activity_log';

    $sql[] = <<<END

CREATE TABLE cc_tbl_activity_log
    (
      activity_log_id        int(11) unsigned  NOT NULL auto_increment,
      activity_log_event     varchar(255) NOT NULL default '',
      activity_log_date      datetime,
      activity_log_user_name varchar(255) NOT NULL default '',
      activity_log_ip        varchar(255) NOT NULL default '',
      activity_log_param_1   varchar(255) NOT NULL default '',
      activity_log_param_2   varchar(255) NOT NULL default '',
      activity_log_param_3   varchar(255) NOT NULL default '',

      PRIMARY KEY activity_log_id (activity_log_id)
    )
END;

    // 
    // Topics
    //
    $drops [] = 'cc_tbl_topics';

    $sql[] =<<<END

CREATE TABLE cc_tbl_topics
    (
      topic_id         int(11) unsigned  NOT NULL auto_increment,
      topic_upload     int(11) unsigned  NOT NULL,
      topic_user       int(11) unsigned  NOT NULL,
      topic_views      int(11) unsigned  NOT NULL,
      topic_type       varchar(100) NOT NULL default '',
      topic_date       datetime        NOT NULL,
      topic_edited     datetime        NOT NULL,
      topic_deleted    int(2) unsigned  NOT NULL,

      topic_name         mediumtext       NOT NULL default '',
      topic_text         mediumtext       NOT NULL default '',
      topic_tags         mediumtext       NOT NULL default '',

      topic_forum      INT(6) unsigned NOT NULL,
      topic_thread     INT(11) unsigned NOT NULL,

      PRIMARY KEY topic_id (topic_id)
    )
END;

    // 
    // Topics Tree
    //
    $drops [] = 'cc_tbl_topic_tree';

    $sql[] =<<<END
CREATE TABLE cc_tbl_topic_tree
    (
      topic_tree_id       int(11) unsigned  NOT NULL auto_increment,
      topic_tree_parent   int(11) unsigned  NOT NULL,
      topic_tree_child    int(11) unsigned  NOT NULL,

      PRIMARY KEY topic_tree_id (topic_tree_id)
    )
END;

    //
    // email notifications
    //
    
    $drops[] = 'cc_tbl_notifications';

    $sql[] =<<<END

CREATE TABLE cc_tbl_notifications
    (
      notify_id         int(11) unsigned  NOT NULL auto_increment,
      notify_user       int(11) unsigned  NOT NULL,
      notify_other_user int(11) unsigned  NOT NULL,
      notify_mask       int(4)  unsigned  NOT NULL,

      PRIMARY KEY notify_id (notify_id)
    )
END;

    //
    // Main forums table
    //

    $drops[] = 'cc_tbl_forums';

    $sql[] =<<<END

CREATE TABLE cc_tbl_forums
    (
      forum_id              int(6) unsigned  NOT NULL auto_increment,
      forum_post_access     int(4) unsigned  NOT NULL,
      forum_read_access     int(4) unsigned  NOT NULL,
      forum_weight          int(4) unsigned  NOT NULL,
      forum_name            varchar(255) NOT NULL,
      forum_description     varchar(255) NOT NULL,
      forum_group           int(4) NOT NULL,

      PRIMARY KEY forum_id (forum_id)
    )
END;

    //
    // Forum groups
    //

    $drops[] = 'cc_tbl_forum_groups';

    $sql[] =<<<END

CREATE TABLE cc_tbl_forum_groups
    (
      forum_group_id         int(4) unsigned  NOT NULL auto_increment,
      forum_group_name       varchar(255) NOT NULL,
      forum_group_weight     int(4) unsigned  NOT NULL,

      PRIMARY KEY forum_group_id (forum_group_id)
    )
END;

    //
    // Forum threads
    //

    $drops[] = 'cc_tbl_forum_threads';

    $sql[] =<<<END

CREATE TABLE cc_tbl_forum_threads
    (
      forum_thread_id         int(11) unsigned  NOT NULL auto_increment,
      forum_thread_forum      int(6)  unsigned  NOT NULL,
      forum_thread_user       int(11) unsigned  NOT NULL,
      forum_thread_oldest     int(11) unsigned  NOT NULL,
      forum_thread_newest     int(11) unsigned  NOT NULL,
      forum_thread_date       datetime        NOT NULL,
      forum_thread_extra      mediumtext NOT NULL default '',

      forum_thread_sticky     int(2) unsigned  NOT NULL,
      forum_thread_closed     int(2) unsigned  NOT NULL,

      PRIMARY KEY forum_thread_id (forum_thread_id)
    )
END;

    /* DROP PREVIOUS TABLES */
    $tables = CCDatabase::ShowTables();

    foreach( $drops as $drop )
    {
        if( in_array($drop,$tables) )
        {
            mysql_query( "DROP TABLE $drop" );
            $msg = mysql_error();
            if( $msg )
               return(false);
        }
    }

    /* INSTALL TABLES */
    foreach( $sql as $s )
    {
       mysql_query($s);
       $msg = mysql_error();
       if( $msg )
           return(false);
    }


    $configs =& CCConfigs::GetTable();

    // -------------------- config -------------------------------

    $arr = array ( 
       'cookie-domain'       => $vars['cookiedom']['v'] , 
       'site-disabled'       => 0,
       'enable-key'          => 'jimi',
       'enable-password'     => '_change_me_' . time(),
       'php-tal-dir'         => 'cclib/phptal/libs' , 
       'php-tal-cache-dir'   => 'cclib/phptal/phptal_cache',
       'user-upload-root'    => 'people' , 
       'contest-upload-root' => 'contests' , 
       'template-root'       => 'cctemplates/' , 
       'files-root'          => 'ccfiles/',
       'pretty-urls'         => $vars['pretty_urls']['v'],
       'getid3-path'         => $vars['getid3']['v'] , 
       'getid3-v1'           => '1' , 
       'getid3-fileverify-enabled' => '1' , 
       'logfile-dir'        => $vars['logfile_dir']['v'],
       'ban-message'        => 'This upload is under review and is only visible to the owner and admins. Please contact the site administrator.',
       'cc-host-version'    => CC_HOST_VERSION,
       'allow-pool-ui'       => true,
       'allow-pool-search'   => true,
       'allow-pool-register' => false,

       'v_1_2h'              => true, // mark this installation as having 
                                      // ratings/remix count fields
       'v_1_2k'              => true, // reviews/topics
       'v_2_1a'              => true, // email notifications
       'v_3_0c'              => true, // forums

    );

    $configs->SaveConfig( 'config', $arr, CC_GLOBAL_SCOPE);

    // ------------------ settings ---------------------------------

    $arr = array(
           'homepage'          => 'viewfile/home.xml' , 
           'style-sheet'       => 'cctemplates/skin-simple.css' , 
           'admins'            => $vars['admin']['v'], 
           'thumbnail-x'       => '120px' , 
           'thumbnail-y'       => '120px' , 
           'upload-auto-pub'   => '1' , 
           'max-listing'       => 10,
           'ratings'           => true,
        );

    
    $configs->SaveConfig( 'settings', $arr, CC_GLOBAL_SCOPE);

    // ------------------- ttag --------------------------------

    $arr = array( 
            'site-title' =>  $vars['sitename']['v'], 
            'banner-html' =>  $vars['sitename']['v'], 
            'root-url' =>  $vars['rooturl']['v'], 
            'site-description' => $vars['site-description']['v'], 
            'footer' => <<<END
This site uses <a href="http://wiki.creativecommons.org/wiki/CcHost">ccHost</a>, licensed under <a href="http://creativecommons.org/licenses/GPL/2.0/">CC-GNU-GPL</a>, which is a product of the <a href="http://sourceforge.net/projects/cctools/">ccTools</a> project and uses <a href="http://getid3.sourceforge.net/">GetID3</a> and <a href="http://phptal.sourceforge.net/">PHPTal</a>.
END
 , 
            'site-license' => <<<END
<a rel="license" href="http://creativecommons.org/licenses/by-nc/2.5/"><img alt="Creative Commons License" src="http://creativecommons.org/images/public/somerights20.gif" id="cc_license_image"></a> The text of this site is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc/2.5/">Creative Commons Attribution-NonCommercial 2.5 License</a>.
END
        );

    $configs->SaveConfig( 'ttag', $arr, CC_GLOBAL_SCOPE);

    // -------------------- format-allow -------------------------------

    $arr = array( 
       'audio-aiff-aiff' => '1' , 
       'audio-au-au' => '1' , 
       'audio-flac-flac' => '1' , 
       'audio-mp3-mp3' => '1' , 
       'audio-ogg-vorbis' => '1' , 
       'audio-real-real' => '1' , 
       'audio-asf-wma' => '1' , 
       'archive-zip-' => '1' , 
       'image-gif-gif' => '1' , 
       'image-jpg-jpg' => '1' , 
       'image-png-png' => '1' , 
       'video-swf-swf' => '1'  );

    $configs->SaveConfig( 'format-allow', $arr, CC_GLOBAL_SCOPE);

    // -------------------- name-masks -------------------------------

    $arr = array( 
           'song'    => '%login% - %filename%' , 
           'remix'   => '%login% - %filename%' , 
           'contest' => '%contest% - %login% - %filename%' , 
           'contest-source' => '%contest% - %filename%' ,
           'upload-replace-sp' => '1' , 
            );

    $configs->SaveConfig( 'name-masks', $arr, CC_GLOBAL_SCOPE);

    // -------------------- id3-tag-masks -------------------------------

    $arr = array( 
           'title' => '%title%' , 
           'artist' => '%artist%' , 
           'copyright' => '%Y% %artist% Licensed to the public under %license_url% Verify at %song_page%' , 
           'original_artist' => '%source_artist%' , 
           'remixer' => '%artist%' , 
           'year' => '%Y%' , 
           'url_user' => '%artist_page%' , 
           'album' => '%site%' 
        );

    $configs->SaveConfig( 'id3-tag-masks', $arr, CC_GLOBAL_SCOPE);

    // -----------------  ratings chart defaults ----------------------------------

        $args['per-star']   =  2;
        $args['per-review'] =  2;
        $args['per-child']  =  0.1;
        $args['per-parent'] =  0.1;
        $args['cut-off']    = '2 weeks ago';
        $args['per-hour']    = 0.02;
        $args['dirty']      = true;

        $configs->SaveConfig('chart', $args, CC_GLOBAL_SCOPE);

    // ----------------- default forums and forum groups ----------------------------

        $sql = array();
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (1, 'The Site', 1)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (2, 'The Content', 2)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (3, 'Off Beats', 10)";
        
        $ad = CC_ADMIN_ONLY;
        $dc = CC_DONT_CARE_LOGGED_IN;
        $ru = CC_MUST_BE_LOGGED_IN;

        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (1, $ad, $dc, 1, 'Announcements', 'Messages from the admins', 1)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (2, $ru, $dc, 2, 'Help', 'get aid', 2)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (3, $ru, $dc, 3, 'The Big OT', 'off topic stuff', 3)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (4, $ru, $dc, 4, 'Bugs', 'Report bugs here', 1)";

        CCDatabase::Query($sql);

    return(true);
}


?>
