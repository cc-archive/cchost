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

CCEvents::AddHandler(CC_EVENT_APP_INIT, 'cc_mixter_install');

function cc_mixter_install()
{
    global $CC_GLOBALS;

    $do_install = empty($CC_GLOBALS['cc_mixter_installed']) ||
                 (CCUser::IsAdmin() && !empty($_REQUEST['mixterinstall']));

    if( !$do_install ) return;

    $configs =& CCConfigs::GetTable();
    $arr = $configs->GetConfig('config');
    $arr ['cc_mixter_installed'] = true;
    $arr ['site-disabled']       = 0;
    $arr ['enable-key']          = 'jimi';
    $arr ['enable-password']     = 'terurocks';
    $arr ['files-root']          = 'mixter-files/';
    $arr ['avatar-dir']          = 'forum/images/avatars';
    $arr ['logfile-dir']         = '../offline/';
    $arr ['allow-pool-ui']       = true;
    $arr ['allow-pool-search']   = true;
    $arr ['allow-pool-register'] = false;
    $arr ['mail_sender']         = 'webmaster-ccmixter@creativecommons.org';
    $arr ['no-cache']            = false;
    $arr ['reg-type']            = CC_REG_USER_EMAIL;
    $configs->SaveConfig( 'config', $arr, CC_GLOBAL_SCOPE);
    $CC_GLOBALS = array_merge($CC_GLOBALS, $arr);

// ------------------ settings ---------------------------------
    $arr = $configs->GetConfig('settings');
    $arr['admins'] = 'admin, victor, mlinksva';
    $arr['editors'] = 'lucas_gonze,djlang59,mhite,teru,ASHWAN,ditri23,beatgorilla';
    $configs->SaveConfig( 'settings', $arr, CC_GLOBAL_SCOPE );

// ------------------ SANDBOX settings ---------------------------------
    $arr['style-sheet'] = 'cctemplates/skin-ccmixter.css';
    $configs->SaveConfig( 'settings', $arr, 'sandbox' );


// ------------------- ttag --------------------------------
    $arr = $configs->GetConfig('ttag');
    $arr['banner-html'] = 'ccMixter <span style="font-size:12px;color:#ccc;">BETA</span>';
    $arr['site-title'] = 'ccMixter BETA';
    $arr['footer'] =<<<END
This site is a product of the <a href="http://sourceforge.net/projects/cctools/">ccTools</a> project and uses <a 
href="http://getid3.sourceforge.net/">GetID3</a> and <a href="http://phptal.sourceforge.net/">PHPTal</a>. Before using this site, please
read our <a href="/terms">terms of use</a> and <a href="/privacy">privacy policy</a>. Contact: <a href="/media/people/contact/admin">site 
administrator</a>.
END;
    $configs->SaveConfig( 'ttag', $arr, CC_GLOBAL_SCOPE );

// ------------------- SANDBOX ttag --------------------------------
    $arr['banner-html'] = 'ccMixter <span style="font-size:12px;color:#ccc;"><a href="/sandbox">sandbox</a></span>';
    $arr['site-title'] = 'ccMixter SANDBOX';
    $configs->SaveConfig( 'ttag', $arr, 'sandbox' );


// ------------------- tmacs (sidebar) ------------------------
        $tmacs
         = array (
        'UBeenRemixed_admin_only' => 0, 
        'Podcast_and_Stream_Links' => 1, 
        'List_Contests' => 0, 
        'Virtual_Roots' => 0, 
        'Search_Box' => 0, 
        'Ratings_Chart' => 0, 
        'Editorial_Picks' => 1, 
        'Latest_Uploads' => 0, 
        'Latest_Remixes' => 1, 
         )
        ;
    $configs->SaveConfig( 'tmacs', $tmacs, CC_GLOBAL_SCOPE, false );

// ------------------- nav tabs --------------------------------
    $mtabs = array (

     'home' => array
            (
                'text' => 'Home'
                ,'help' => 'Home page'
                ,'tags' => '/viewfile/home.xml'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'url'
            )

        ,'picks' => array
            (
                'text' => 'Picks'
                ,'help' => 'See picks by the Editorial Staff'
                ,'tags' => '/viewfile/picks.xml'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'url'
            )

        ,'remix' => array
            (
                'text' => 'Remix'
                ,'help' => 'See the latest remixes'
                ,'tags' => 'remix'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'any'
            )

        ,'samples' => array
            (
                'text' => 'Samples'
                ,'help' => 'See recently uploaded samples'
                ,'tags' => '/samples'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'url'
            )

        ,'pells' => array
            (
                'text' => '\'Pells'
                ,'help' => 'See recently uploaded a cappellas'
                ,'tags' => 'acappella'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'any'
            )

        ,'people' => array
            (
                'text' => 'People'
                ,'help' => 'See the newest users who uploaded here'
                ,'tags' => '/people'
                ,'limit' => 0
                ,'access' => 4
                ,'function' => 'url'
            )
    );

    $pages['media'] = $mtabs;
    $configs->SaveConfig( 'tab_pages',$pages, CC_GLOBAL_SCOPE, false );

// ------------------- tag aliases --------------------------------
    $aliases = array(
        array( 'hiphop', 'hip_hop'), 
        array( 'accapella', 'acappella'), 
        array( 'acapella', 'acappella'), 
        array( 'a_capella', 'acappella'), 
        array( 'a_cappella', 'acappella'), 
        array( 'drum_bass', 'dnb'), 
        array( 'drum_n_bass', 'dnb'), 
        array( 'd_b', 'dnb'), 
        array( 'drums_and_bass', 'dnb'), 
        array( 'glitchy', 'glitch'), 
        array( 'triphop', 'trip_hop'), 
        array( 'scratch', 'scratching'), 
        array( 'mix', 'remix'), 
        array( 'mashup', 'mash_up'), 
        array( 'newage', 'new_age'), 
        array( 'cutup', 'cut_up'), 
        );

    $tagalias =& CCTagAliases::GetTable();
    foreach( $aliases as $alias )
    {
        $w['tag_alias_tag'] = $alias[0];
        if( !$tagalias->QueryRow($w) )
        {
            $a['tag_alias_tag']   = $alias[0];
            $a['tag_alias_alias'] = $alias[1];
            $tagalias->Insert($a);
        }
    }

// ------------------- throttle --------------------------------
    $throttle  = array (
        'enabled' => 1, 
        'user-exceptions' => 'djlang59, curious, weirdpolymer', 
        'quota-msg' => <<<END
You are not authorized to submit this type of file at 
this time, most likely because you have met the quota 
for this type of submission.
END
         );

    $configs->SaveConfig( 'throttle', $throttle, CC_GLOBAL_SCOPE, false );

// ------------------- throttle rules --------------------------------
    $throttle_rules      = array (
        '0' =>  array (
            'num_uploads' => '1', 
            'limit_by_type' => 'remix', 
            'time_period' => 'forever', 
            'allow' => 'allow', 
            'allow_type' => 'all', 
            'stop' => 'stop', 
             ),
        '1' => array (
            'num_uploads' => '1', 
            'limit_by_type' => 'pella', 
            'time_period' => 'forever', 
            'allow' => 'allow', 
            'allow_type' => 'all', 
            'stop' => 'stop', 
             ),
        '2' => array (
            'num_uploads' => '2', 
            'limit_by_type' => 'fullmix', 
            'time_period' => '1 days ago', 
            'allow' => 'forbid', 
            'allow_type' => 'fullmix', 
            'stop' => 'continue', 
             ),
        '3' => array (
            'num_uploads' => '3', 
            'limit_by_type' => 'fullmix', 
            'time_period' => 'forever', 
            'allow' => 'forbid', 
            'allow_type' => 'fullmix', 
            'stop' => 'stop', 
             ),
         );

    $configs->SaveConfig( 'throttle_rules', $throttle_rules, CC_GLOBAL_SCOPE, false );

// ------------------- ratings callibrations --------------------------------
        $chart = array (
            'per-star' => 2, 
            'per-review' => 1, 
            'per-child' => 0.1, 
            'per-parent' => 0.1, 
            'cut-off' => '2 weeks ago', 
            'per-hour' => 0.05, 
            'dirty' => 1, 
             );

    $configs->SaveConfig( 'chart', $chart, CC_GLOBAL_SCOPE, false );

// ------------------- licenses --------------------------------

        $licenses  = array (
            'attribution', 
            'noncommercial', 
         );

    $configs->SaveConfig( 'licenses', $licenses, CC_GLOBAL_SCOPE, false );

// ------------------- activity logging --------------------------------

        $logging = array (
            CC_EVENT_UPLOAD_DONE,
            CC_EVENT_FILE_DONE,
            CC_EVENT_DELETE_UPLOAD,
            CC_EVENT_DELETE_FILE,
            CC_EVENT_USER_REGISTERED,
         );

    $configs->SaveConfig( 'logging', $logging, CC_GLOBAL_SCOPE, false );

// ------------------- file formats --------------------------------

        $format_allow = array (
            'audio-aiff-aiff' => 0, 
            'audio-au-au' => 1, 
            'audio-flac-flac' => 1, 
            'audio-mp3-mp3' => 1, 
            'audio-ogg-vorbis' => 1, 
            'audio-real-real' => 1, 
            'audio-asf-wma' => 1, 
            'archive-zip-' => 1, 
            'image-gif-gif' => 0, 
            'image-jpg-jpg' => 0, 
            'image-png-png' => 0, 
            'video-swf-swf' => 0, 
            'audio-midi-midi' => 1, 
            'audio-riff-wav' => 0, 
            'video-riff-avi' => 0, 
            'video-quicktime-quicktime' => 0, 
            'video-real-real' => 0, 
            'video-asf-wmv' => 0, 
            'image-bmp-bmp' => 0, 
             );

    $configs->SaveConfig( 'format-allow', $format_allow, CC_GLOBAL_SCOPE, false );
    
    // ------------------- submit forms --------------------------------

    $formhelp =<<<END
There is 10Mg limit on uploads.<br />
Please do not submit until you have read <a href="/terms">our terms of use</a>.<br />
Having trouble? <a href="/media/viewfile/isitlegal.xml#upload_problems">click here</a>.
END;

    $subforms = array (
    'remix' =>   array (
        'enabled' => 1, 
        'submit_type' => 'Remix', 
        'text' => 'Submit a Remix', 
        'help' => 'A remix using samples downloaded from this site. When submitting a remix make sure to properly attribute the artist you sampled to comply with the Attribution part the Creative Commons license. The next screen will have a search function that allows you do just that.', 
        'tags' =>   array (
            '0' => 'media', 
            '1' => 'remix', 
             ),
        'weight' => '1', 
        'form_help' => $formhelp,
        'isremix' => 1, 
        'media_types' => 'audio', 
        'logo' => 'mixter-remix.gif', 
        'type_key' => 'remix', 
         ),
    'pella' =>  array (
        'text' => 'Submit an A Cappella', 
        'submit_type' => 'A Cappella', 
        'help' => 'Stand alone vocal parts, either spoken word or sung. Mono recording with no effects  (reverb, delay, etc.) on them are best because they are the most flexible to work with. Many singers think they sound "better" with a lot of effects but it is always better to leave those choices to a producer/remixer to allow them to use their creative skills to the fullest potential.', 
        'tags' =>  array (
            '0' => 'acappella', 
            '1' => 'media', 
             ),
        'weight' => 10, 
        'isremix' => 0, 
        'form_help' => $formhelp,
        'enabled' => 1, 
        'media_types' =>   array (
            '0' => 'audio', 
             ),
        'logo' => 'mixter-pella.gif', 
        'type_key' => 'pella', 
         ),
    'samples' =>  array (
        'text' => 'Submit Samples', 
        'submit_type' => 'Sample', 
        'help' => 'Samples can be a loop, a one-shot note or drum hit or any other snippet of sound that might be useful to a producer or remixer. You are encouraged to make a collection of samples and upload them together in archive format (ZIP), however sound files are accepted as well. By far the most flexible samples to work with are mono and have no effects for acoustic instruments and minimal effects for synthesized sounds.', 
        'tags' =>  array (
            '0' => 'sample', 
            '1' => 'media', 
             ),
        'weight' => 15, 
        'form_help' => $formhelp,
        'enabled' => 1, 
        'isremix' => 0, 
        'media_types' => array (
            '0' => 'audio', 
            '1' => 'archive', 
             ),
        'logo' => 'mixter-loop.gif', 
        'type_key' => 'samples', 
         ),
    'fullmix' =>  array (
        'text' => 'Submit a Fully Mixed Track', 
        'submit_type' => 'Original', 
        'help' => 'An original track that is fully mixed is <i>extremely unlikely</i> to be remixed because of the extra work the producer or remixer has to do the extract the parts they actually wish to use. Before uploading your track here, consider uploading to one of several free hosting sites sponsored by Creative Commons such <a href="http://archive.org/audio">Internet Archive</a> or <a href="http://ourmedia.org">Our Media</a> both of which might be more appropriate places to post completely mixed tracks.', 
        'tags' =>  array (
            '0' => 'media', 
            '1' => 'original', 
             ),
        'weight' => 50, 
        'enabled' => 1, 
        'form_help' => $formhelp,
        'isremix' => 0, 
        'media_types' => array (
            '0' => 'audio', 
             ),
        'logo' => 'mixter-mixed.gif', 
        'type_key' => 'fullmix', 
         ),
     );

    $configs->SaveConfig( 'submit_forms', $subforms, CC_GLOBAL_SCOPE, false );

    CCPage::Prompt("ccMixter settings installed");
}
