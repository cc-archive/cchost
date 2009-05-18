<?

$install_settings = array(
    array( 
        'config_type'  => 'config',
        'config_scope' => 'media',
        'config_data'  => array (

            'cc-host-version'    => CC_HOST_VERSION,

            'cookie-domain'       => $vars['cookiedom']['v'] , 

            'site-disabled'       => 0,
            'enable-key'          => 'jimi',
            'enable-password'     => '_change_me_' . time(),

            'user-upload-root'    => 'content' , 
            'contest-upload-root' => 'contests' , 
            'dataview-dir'        => $local_base_dir . '/dataviews/',
            'template-root'       => $local_base_dir . '/skins/' , 
            'image-upload-dir'    => $local_base_dir . '/images/',
            'files-root'          => $local_base_dir . '/pages/',
            'extra-lib'           => $local_base_dir . '/lib/',
            'error-txt'           => $local_base_dir . '/error-msg.txt',
            'disabled-msg'        => $local_base_dir . '/disabled-msg.txt',
            'install-user-root'   => $local_base_dir . '/',
            'avatar-dir'          => '',
            'temp-dir'            => $local_base_dir . '/temp',

            'logfile-dir'         => empty($vars['logfile_dir']['v']) ?
                                         './' :
                                         $vars['logfile_dir']['v'] ,

            'mail_sender'         => $vars['admin-email']['v'],
            'mail_anon'           => CC_MAIL_THROTTLED,
            'mail_uploaders'      => CC_MAIL_THROTTLED,
            'mail_registered'     => CC_MAIL_THROTTLED,
            'mail_throttle'       => 3,
            'mail_to_admin'       => CC_DONT_CARE_LOGGED_IN,

            'pretty-urls'         => $vars['pretty_urls']['v'],

            'getid3-path'         => $vars['getid3']['v'] , 
            'getid3-v1'           => '1' , 
            'getid3-fileverify-enabled' => '1' , 

            'ban-message'         => "str_ban_message",
            'ban-email-enable'    => 1,
            'ban-email'           => 'str_ban_email',
            'allow-pool-ui'       => true,
            'allow-pool-search'   => true,
            'allow-pool-register' => false,
            'file-perms'          => 0777, 
            'querylimit'          => 25,
            'lang'                => CC_LANG, // JON: This should be install option
            'lang_locale_pref'    => CC_LANG_LOCALE_PREF, // same here!

            'supers'              => $vars['admin']['v'], 
            'run_once'            => 'welcome',

            'pool-push-hub' => '',
            'pool-pull-hub' => '',
            'pool-remix-throttle' => 10,
            'no-cache' => '',
            'reg-type' => CC_REG_NO_CONFIRM,
            'tags-min-length' => 3,
            'tags-max-length' => 25,
            'feed-cache-flag' => '',

            'format' => 1,
            'adminformat' => 1,
            'reviews_enabled' => 1,
            'flagging' => 1,

            'flag_msg' => 'str_flag_msg' ,
            'notify' => 1,
            'tags-min-show' => 1,
            'do-bpm' => 0,
            'lang_per_user' => '',
            'site_promo_tag' => 'site_promo',
            'default_user_image' => 'ccskins/shared/images/person.png',
            'pubwiz' => CC_DONT_CARE_LOGGED_IN,
            'tags-inherit' => array ( 0 => 'digital_distortion', ),

            'enable_playlists' => 1,
            'playlist_promo_tag' => 'site_promo',
            'embedded_player' => 'ccskins/shared/players/player_native.php',
            

            'contests' => array (),
            'counter' => '',
            'license' => '',
            'checksum' => '',

            'search-user-path-first' => '1',
            'show_google_form' => '1',
            'collab_enabled' => '',

            'v_5_1_paging' => '1',
            'v_5_1_button' => '1',
            'v_5_oh1_bug'  => '1',
            'v_5_1_minilic' => '1',

            ),
     ),
    array( 
        'config_type'  => 'settings',
        'config_scope' => 'media',
        'config_data'  => array (
            'homepage' => 'docs/new_install',
            'admins' => $vars['admin']['v'],
            'upload-auto-pub' => 1,
            'ratings' => 1,
            'editors' => '',
            'default-feed-tags' => '',
            ),
     ),
    array( 
        'config_type'  => 'ttag',
        'config_scope' => 'media',
        'config_data'  => array (
            'site-title' =>  $vars['sitename']['v'], 
            'root-url' =>  $vars['rooturl']['v'], 
            'site-description' => $vars['site-description']['v'], 
            'footer' => 'str_footer',
            'site-license' => 'str_site_license',
            'site-meta-description' => 'This is a cchost based site.',
            'site-meta-description' => "This is the " . CC_APP_NAME . " site," . $vars['sitename']['v'] . ". " . $vars['site-description']['v'],
    	    'site-meta-keywords' => CC_APP_NAME . ', remix, sharing, media',
            'banner-message' => '',
            'beta_message' => '',
            ),
     ),
    array( 
        'config_type'  => 'format-allow',
        'config_scope' => 'media',
        'config_data'  => array (
            'audio-aiff-aiff' => '',
            'audio-au-au' => 1,
            'audio-flac-flac' => 1,
            'audio-mp3-mp3' => 1,
            'audio-ogg-vorbis' => 1,
            'audio-real-real' => 1,
            'audio-asf-wma' => 1,
            'archive-zip-' => 1,
            'image-gif-gif' => 1,
            'image-jpg-jpg' => 1,
            'image-png-png' => 1,
            'video-swf-swf' => 1,
            'audio-midi-midi' => 1,
            'audio-riff-wav' => '',
            'video-riff-avi' => '',
            'video-quicktime-quicktime' => 1,
            'video-real-real' => '',
            'video-asf-wmv' => '',
            'image-bmp-bmp' => '',
            ),
     ),
    array( 
        'config_type'  => 'name-masks',
        'config_scope' => 'media',
        'config_data'  => array (
            'song' => '%login% - %filename%',
            'remix' => '%login% - %filename%',
            'contest' => '%contest% - %login% - %filename%',
            'contest-source' => '%contest% - %filename%',
            'upload-replace-sp' => 1,
            ),
     ),
    array( 
        'config_type'  => 'id3-tag-masks',
        'config_scope' => 'media',
        'config_data'  => array (
            'title' => '%title% %feat%',
            'artist' => '%artist%',
            'copyright' => '%Y% %artist% Licensed to the public under %license_url% Verify at %song_page%',
            'original_artist' => '%source_artist%',
            'remixer' => '%artist%',
            'year' => '%Y%',
            'url_user' => '%artist_page%',
            'album' => '%site%',
            ),
     ),
    array( 
        'config_type'  => 'chart',
        'config_scope' => 'media',
        'config_data'  => array (
            'ratings' => 1,
            'ratings_ban' => '',
            'requires-review' => '',
            'thumbs_up' => 1,
            'rank_formula' => '((upload_num_scores*4) + (upload_num_playlists*2))',
            ),
     ),
    array( 
        'config_type'  => 'licenses',
        'config_scope' => 'media',
        'config_data'  => array (
            'attribution_3' => 'on',
            'noderives_3' => '',
            'noncommercial_3' => 'on',
            'by-nc-nd_3' => '',
            'by-nc-sa_3' => '',
            'share-alike_3' => '',
            'nc-sampling+' => '',
            'publicdomain' => 'on',
            'sampling' => '',
            'sampling+' => '',
            ),
     ),
    array( 
        'config_type'  => 'tab_pages',
        'config_scope' => 'media',
        'config_data'  => array (
            'media' => array (
                'home' => array (
                    'text' => 'str_home',
                    'help' => 'str_home_page',
                    'tags' => '/docs/home',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    ),
                'picks' => array (
                    'text' => 'str_picks',
                    'help' => 'str_see_picks',
                    'tags' => '/docs/picks',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    ),
                'remix' => array (
                    'text' => 'str_remixes',
                    'help' => 'str_see_remixes',
                    'tags' => 'remix',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'sub',
                    ),
                'samples' => array (
                    'text' => 'str_samples',
                    'help' => 'str_see_samples',
                    'tags' => 'tags=sample',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'qry',
                    ),
                'people' => array (
                    'text' => 'str_people',
                    'help' => 'str_see_people',
                    'tags' => '/people',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    ),
                ),
            'remix' => array (
                'browse' => array (
                    'text' => 'str_browse_remixes',
                    'help' => 'str_browse_remixes',
                    'tags' => '/browse',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    ),
                'list' => array (
                    'text' => 'str_upload_listing',
                    'help' => 'str_upload_listing',
                    'tags' => 'tags=remix',
                    'limit' => '',
                    'access' => 4,
                    'function' => 'qry',
                    ),
                ),
            ),
     ),
    array( 
        'config_type'  => 'throttle',
        'config_scope' => 'media',
        'config_data'  => array (
            'enabled' => 0,
            'user-exceptions' => '',
            'quota-msg' => 'str_quota_msg',
            ),
     ),
    array( 
        'config_type'  => 'throttle_rules',
        'config_scope' => 'media',
        'config_data'  => array (
            0 => array (
                'order' => 1,
                'num_uploads' => 3,
                'limit_by_type' => 'remix',
                'time_period' => '1 days ago',
                'allow' => 'forbid',
                'allow_type' => 'remix',
                'stop' => 'stop',
                ),
            1 => array (
                'order' => 2,
                'num_uploads' => 4,
                'limit_by_type' => 'remix',
                'time_period' => '1 weeks ago',
                'allow' => 'forbid',
                'allow_type' => 'all',
                'stop' => 'stop',
                ),
            2 => array (
                'order' => 3,
                'num_uploads' => 1,
                'limit_by_type' => 'remix',
                'time_period' => 'forever',
                'allow' => 'allow',
                'allow_type' => 'all',
                'stop' => 'stop',
                ),
	),
     ),
    array( 
        'config_type'  => 'logging',
        'config_scope' => 'media',
        'config_data'  => array (
            0 => 'uploaddone',
            1 => 'srcchange',
            2 => 'filedone',
            3 => 'delete',
            4 => 'deletefile',
            5 => 'userdel',
            6 => 'uploadmoderated',
            7 => 'useripbanned',
            8 => 'userreg',
            9 => 'userprof',
            10 => 'topicdelete',
            ),
     ),
    array( 
        'config_type'  => 'submit_forms',
        'config_scope' => 'media',
        'config_data'  => array (
            'remix' => array (
                'enabled' => 1,
                'submit_type' => 'str_submit_remix',
                'text' => 'str_submit_a_remix',
                'help' => 'str_submit_remix_help',
                'tags' => array (
                    0 => 'media',
                    1 => 'remix',
                    ),
                'suggested_tags' => '',
                'weight' => 1,
                'form_help' => 'str_submit_remix_line',
                'isremix' => 1,
                'media_types' => 'audio',
                'action' => '',
                'logo' => 'submit-remix.gif',
                'type_key' => 'remix',
                ),
            'samples' => array (
                'enabled' => 1,
                'submit_type' => 'str_submit_sample',
                'text' => 'str_submit_samples',
                'help' => 'str_submit_samples_help',
                'tags' => array (
                    0 => 'sample',
                    1 => 'media',
                    ),
                'suggested_tags' => '',
                'weight' => 15,
                'form_help' => 'str_submit_samples_help_line',
                'isremix' => '',
                'media_types' => 'audio,archive',
                'action' => '',
                'logo' => 'submit-sample.gif',
                'type_key' => 'samples',
                ),
            'fullmix' => array (
                'enabled' => '1',
                'submit_type' => 'str_submit_original',
                'text' => 'str_submit_an_original',
                'help' => 'str_submit_original_help',
                'tags' => array (
                    0 => 'media',
                    1 => 'original',
                    ),
                'suggested_tags' => '',
                'weight' => 50,
                'form_help' => 'str_submit_original_help_line',
                'isremix' => '',
                'media_types' => 'audio',
                'action' => '',
                'logo' => 'submit-original.gif',
                'type_key' => 'fullmix',
                ),
            ),
     ),
    array( 
        'config_type'  => 'groups',
        'config_scope' => 'media',
        'config_data'  => array (
            'visitor' => array (
                'group_name' => 'str_visitors',
                'weight' => 1,
                ),
            'artist' => array (
                'group_name' => 'str_artists',
                'weight' => 2,
                ),
            'configure' => array (
                'group_name' => 'str_admin',
                'weight' => 100,
                ),
            ),
     ),
    array( 
        'config_type'  => 'menu',
        'config_scope' => 'media',
        'config_data'  => array (
            'submitforms' => array (
                'menu_text' => 'str_submit_files',
                'menu_group' => 'artist',
                'weight' => 1,
                'access' => 1,
                'id' => 'mi_submitfiles',
                'action' => 'submit',
                ),
            'login' => array (
                'menu_text' => 'str_log_in',
                'menu_group' => 'artist',
                'weight' => 1,
                'access' => 2,
                'id' => 'mi_login',
                'action' => 'login',
                ),
            'str_manage_files' => array (
                'menu_text' => 'str_manage_files',
                'menu_group' => 'artist',
                'weight' => 2,
                'access' => 1,
                'id' => 'mi_manage_files',
                'action' => 'api/query?t=manage_files&amp;user=%login_name%',
                ),
            'editprofile' => array (
                'menu_text' => 'str_edit_profile',
                'menu_group' => 'artist',
                'weight' => 3,
                'access' => 1,
                'id' => 'mi_edityourprofile',
                'action' => 'people/profile',
                ),
            'str_preferences' => array (
                'menu_text' => 'str_preferences',
                'menu_group' => 'artist',
                'weight' => 4,
                'access' => 1,
                'id' => 'mi_preferences',
                'action' => 'preferences',
                ),
            'register' => array (
                'menu_text' => 'str_register',
                'menu_group' => 'artist',
                'weight' => 5,
                'access' => 2,
                'id' => 'mi_register',
                'action' => 'register',
                ),
            'str_notifications' => array (
                'menu_text' => 'str_notifications',
                'menu_group' => 'artist',
                'weight' => 5,
                'access' => 1,
                'id' => 'mi_notify',
                'action' => 'people/notify/edit',
                ),
            'artist' => array (
                'menu_text' => 'str_your_page',
                'menu_group' => 'artist',
                'weight' => 10,
                'access' => 1,
                'id' => 'mi_yourpage',
                'action' => 'people/%login_name%',
                ),
            'configpage' => array (
                'menu_text' => 'Manage Site',
                'menu_group' => 'configure',
                'weight' => 1,
                'access' => 8,
                'id' => 'mi_managesite',
                'action' => 'admin/site',
                ),
            'globalsettings' => array (
                'menu_text' => 'Global Settings',
                'menu_group' => 'configure',
                'weight' => 2,
                'access' => 8,
                'id' => 'mi_globalsettings',
                'action' => 'admin/site/global',
                ),
            'tags' => array (
                'menu_text' => 'str_browse_tags',
                'menu_group' => 'visitor',
                'weight' => 2,
                'access' => 4,
                'id' => 'mi_browsetags',
                'action' => 'tags',
                ),
            'forums' => array (
                'menu_text' => 'str_forums',
                'menu_group' => 'visitor',
                'weight' => 3,
                'access' => 4,
                'id' => 'mi_forums',
                'action' => 'forums',
                ),
            ),
     ),
    array( 
        'config_type'  => 'channels',
        'config_scope' => 'media',
        'config_data'  => array (
            0 => array (
                'tags' => 'remix,editorial_pick',
                'text' => 'editors\' picks',
                ),
            1 => array (
                'tags' => 'remix,female_vocals',
                'text' => 'female vocals',
                ),
            2 => array (
                'tags' => 'remix,hip_hop',
                'text' => 'hip hop',
                ),
            3 => array (
                'tags' => 'remix,chill',
                'text' => 'chill',
                ),
            4 => array (
                'tags' => 'remix,downtempo',
                'text' => 'downtempo',
                ),
            5 => array (
                'tags' => 'remix,experimental',
                'text' => 'experimental',
                ),
            6 => array (
                'tags' => 'remix,trip_hop',
                'text' => 'trip hop',
                ),
            7 => array (
                'tags' => 'remix,funky',
                'text' => 'funky',
                ),
            8 => array (
                'tags' => 'remix,rap',
                'text' => 'rap',
                ),
            9 => array (
                'tags' => 'remix,dnb',
                'text' => 'drums n bass',
                ),
            10 => array (
                'tags' => 'remix,electronic',
                'text' => 'electronic',
                ),
            11 => array (
                'tags' => 'remix,acoustic',
                'text' => 'acoustic',
                ),
            ),
     ),


    array( 
        'config_type'  => 'skin-settings',
        'config_scope' => 'media',
        'config_data'  => array (
            'skin-file' => 'ccskins/cc5/skin.tpl',
            'string_profile' => 'ccskins/shared/strings/all_media.php',
            'list_file' => 'ccskins/shared/formats/upload_page_wide.php',
            'list_files' => 'ccskins/shared/formats/upload_list_wide.tpl',
            'form_fields' => 'form_fields.tpl/form_fields',
            'grid_form_fields' => 'form_fields.tpl/grid_form_fields',
            'tab_pos' => 'ccskins/shared/layouts/tab_pos_header.php',
            'box_shape' => 'ccskins/shared/layouts/box_round_native.php',
            'page_layout' => 'ccskins/shared/layouts/layout024.php',
            'font_scheme' => 'ccskins/shared/colors/font_arial.php',
            'font_size' => 'ccskins/shared/colors/fontsize_px12.php',
            'color_scheme' => 'ccskins/shared/colors/color_mono.php',
            'paging_style' => 'ccskins/shared/layouts/paging_google_ul.php',
            'formfields_layout' => 'ccskins/shared/layouts/form_fields_sets.php',
            'gridform_layout' => 'ccskins/shared/layouts/gridform_matrix.php',
            'button_style' => 'ccskins/shared/layouts/button_rounded.php',
            'max-listing' => 12,
            'head-type' => 'ccskins/shared/head.tpl',
            'skin_profile' => 'ccskins/shared/profiles/profile_cc5.php',
            ),
     ),
    array( 
        'config_type'  => 'extras',
        'config_scope' => 'media',
        'config_data'  => array (
            'macros' => array (
                1 => 'ccskins/shared/extras/extras_blurb.tpl',
                15 => 'ccskins/shared/extras/extras_user_pref.tpl',
                8 => 'ccskins/shared/extras/extras_news.tpl',
                3 => 'ccskins/shared/extras/extras_feeds.tpl',
                4 => 'ccskins/shared/extras/extras_support_cc.php',
                16 => $local_base_dir . '/skins/extras/extras_links.tpl',
                ),
            ),
     ),

    array( 
        'config_type'  => 'site-logo',
        'config_scope' => 'media',
        'config_data'  => array (
            'src' => '',
            'h' => 0,
            'w' => 0,
            ),
     ),
    array( 
        'config_type'  => 'pseudo-verify',
        'config_scope' => 'media',
        'config_data'  => array (
            'ac3' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'ac3',
                'description' => 'Dolby AC-3 / Dolby Digital',
                'tags' => 'audio,ac3',
                'pattern' => '^\x0B\x77',
                'isgetid3' => 1,
                ),
            'adif' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'adif',
                'description' => 'AAC - ADIF format',
                'tags' => 'audio,adif',
                'pattern' => '^ADIF',
                'isgetid3' => 1,
                ),
            'adts' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'adts',
                'description' => 'AAC - ADTS format',
                'tags' => 'audio,adts',
                'pattern' => '^\xFF[\xF0-\xF1\xF8-\xF9]',
                'isgetid3' => 1,
                ),
            'avr' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'avr',
                'description' => 'Audio Visual Research',
                'tags' => 'audio,avr',
                'pattern' => '^2BIT',
                'isgetid3' => 1,
                ),
            'bonk' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'bonk',
                'description' => 'Bonk v0.9+',
                'tags' => 'audio,bonk',
                'pattern' => '^\x00(BONK|INFO|META| ID3)',
                'isgetid3' => 1,
                ),
            'la' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'la',
                'description' => 'Lossless Audio',
                'tags' => 'audio,la',
                'pattern' => '^LA0[2-4]',
                'isgetid3' => 1,
                ),
            'lpac' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'lpac',
                'description' => 'Lossless Predictive Audio Compression',
                'tags' => 'audio,lpac',
                'pattern' => '^LPAC',
                'isgetid3' => 1,
                ),
            'mac' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'mac',
                'description' => 'Monkey\'s Audio Compressor',
                'tags' => 'audio,mac',
                'pattern' => '^MAC',
                'isgetid3' => 1,
                ),
            'mod' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'mod',
                'description' => 'MODule (assorted sub-formats)',
                'tags' => 'audio,mod',
                'pattern' => '^.{1080}(M.K.|[5-9]CHN|[1-3][0-9]CH)',
                'isgetid3' => 1,
                ),
            'it' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'it',
                'description' => 'MODule (Impulse Tracker)',
                'tags' => 'audio,it',
                'pattern' => '^IMPM',
                'isgetid3' => 1,
                ),
            'xm' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'xm',
                'description' => 'MODule (eXtended Module)',
                'tags' => 'audio,xm',
                'pattern' => '^Extended Module',
                'isgetid3' => 1,
                ),
            's3m' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 's3m',
                'description' => 'MODule (ScreamTracker)',
                'tags' => 'audio,s3m',
                'pattern' => '^.{44}SCRM',
                'isgetid3' => 1,
                ),
            'mpc' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'mpc',
                'description' => 'Musepack / MPEGplus',
                'tags' => 'audio,mpc',
                'pattern' => '^(MP\+|[\x00\x01\x10\x11\x40\x41\x50\x51\x80\x81\x90\x91\xC0\xC1\xD0\xD1][\x20-37][\x00\x20\x40\x60\x80\xA0\xC0\xE0])',
                'isgetid3' => 1,
                ),
            'ofr' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'ofr',
                'description' => 'OptimFROG',
                'tags' => 'audio,ofr',
                'pattern' => '^(\*RIFF|OFR)',
                'isgetid3' => 1,
                ),
            'rkau' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'rkau',
                'description' => 'RKive AUdio compressor',
                'tags' => 'audio,rkau',
                'pattern' => '^RKA',
                'isgetid3' => 1,
                ),
            'shn' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'shn',
                'description' => 'MKW Shorten',
                'tags' => 'audio,shn',
                'pattern' => '^ajkg',
                'isgetid3' => 1,
                ),
            'tta' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'tta',
                'description' => 'TTA Lossless Audio Compressor',
                'tags' => 'audio,tta',
                'pattern' => '^TTA',
                'isgetid3' => 1,
                ),
            'voc' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'voc',
                'description' => 'Creative Voice',
                'tags' => 'audio,voc',
                'pattern' => '^Creative Voice File',
                'isgetid3' => 1,
                ),
            'vqf' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'vqf',
                'description' => 'Vector Quantization Format',
                'tags' => 'audio,vqf',
                'pattern' => '^TWIN',
                'isgetid3' => 1,
                ),
            'wv' => array (
                'action' => 'da',
                'media-type' => 'audio',
                'default-ext' => 'wv',
                'description' => 'WavPack (v4.0+)',
                'tags' => 'audio,wv',
                'pattern' => '^wvpk',
                'isgetid3' => 1,
                ),
            'bink' => array (
                'action' => 'da',
                'media-type' => 'video',
                'default-ext' => 'bink',
                'description' => 'Bink / Smacker',
                'tags' => 'audio,video,bink',
                'pattern' => '^(BIK|SMK)',
                'isgetid3' => 1,
                ),
            'matroska' => array (
                'action' => 'da',
                'media-type' => 'video',
                'default-ext' => 'matroska',
                'description' => 'Mastroka (audio/video)',
                'tags' => 'audio,video,matroska',
                'pattern' => '^\x1A\x45\xDF\xA3',
                'isgetid3' => 1,
                ),
            'mpeg' => array (
                'action' => 'da',
                'media-type' => 'video',
                'default-ext' => 'mpeg',
                'description' => 'Moving Pictures Experts Group (audio/video)',
                'tags' => 'audio,video,mpeg',
                'pattern' => '^\x00\x00\x01(\xBA|\xB3)',
                'isgetid3' => 1,
                ),
            'nsv' => array (
                'action' => 'da',
                'media-type' => 'video',
                'default-ext' => 'nsv',
                'description' => 'Nullsoft Streaming Video',
                'tags' => 'audio,video,nsv',
                'pattern' => '^NSV[sf]',
                'isgetid3' => 1,
                ),
            'pcd' => array (
                'action' => 'da',
                'media-type' => 'graphic',
                'default-ext' => 'pcd',
                'description' => 'Kodak Photo CD',
                'tags' => 'graphic,pcd',
                'pattern' => '^.{2048}PCD_IPI\x00',
                'isgetid3' => 1,
                ),
            'tiff' => array (
                'action' => 'da',
                'media-type' => 'graphic',
                'default-ext' => 'tiff',
                'description' => 'Tagged Information File Format',
                'tags' => 'graphic,tiff',
                'pattern' => '^(II\x2A\x00|MM\x00\x2A)',
                'isgetid3' => 1,
                ),
            'iso' => array (
                'action' => 'da',
                'media-type' => 'document',
                'default-ext' => 'iso',
                'description' => '(ISO) CD-ROM Image',
                'tags' => 'document,iso',
                'pattern' => '^.{32769}CD001',
                'isgetid3' => 1,
                ),
            'rar' => array (
                'action' => 'da',
                'media-type' => 'archive',
                'default-ext' => 'rar',
                'description' => 'RAR compressed data',
                'tags' => 'archive,rar',
                'pattern' => '^Rar\!',
                'isgetid3' => 1,
                ),
            'szip' => array (
                'action' => 'da',
                'media-type' => 'archive',
                'default-ext' => 'szip',
                'description' => 'SZIP compressed data',
                'tags' => 'archive,szip',
                'pattern' => '^SZ\x0A\x04',
                'isgetid3' => 1,
                ),
            'txt' => array (
                'action' => 'da',
                'media-type' => 'document',
                'default-ext' => 'txt',
                'description' => 'Plain Text (ASCII)',
                'tags' => 'document,txt',
                'pattern' => '^[\s\x21-\x7F]+$',
                'isgetid3' => '',
                ),
            'flv' => array (
                'action' => 'da',
                'media-type' => 'video',
                'default-ext' => 'flv',
                'description' => 'Flash Video',
                'tags' => 'audio,video,flv',
                'pattern' => '^FLV\x01',
                'isgetid3' => 1,
                ),
            'tar' => array (
                'action' => 'da',
                'media-type' => 'archive',
                'default-ext' => 'tar',
                'description' => 'TAR compressed data',
                'tags' => 'archive,tar',
                'pattern' => '^.{100}[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20\x00]{12}[0-9\x20\x00]{12}',
                'isgetid3' => 1,
                ),
            'gz' => array (
                'action' => 'da',
                'media-type' => 'archive',
                'default-ext' => 'gz',
                'description' => 'GZIP compressed data',
                'tags' => 'archive,gz',
                'pattern' => '^\x1F\x8B\x08',
                'isgetid3' => 1,
                ),
            'pdf' => array (
                'action' => 'da',
                'media-type' => 'document',
                'default-ext' => 'pdf',
                'description' => 'Adobe PDF',
                'tags' => 'document,pdf',
                'pattern' => '^\x25PDF',
                'isgetid3' => 1,
                ),
            'msoffice' => array (
                'action' => 'da',
                'media-type' => 'document',
                'default-ext' => 'msoffice',
                'description' => 'Office (Word, Excel, Powerpoint, etc.)',
                'tags' => 'document,msoffice',
                'pattern' => '^\xD0\xCF\x11\xE0',
                'isgetid3' => 1,
                ),
            ),
     ),
);


?>
