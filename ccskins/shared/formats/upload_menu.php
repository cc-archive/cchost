<?
/*
[meta]
    dataview = upload_menu
[/meta]


/*----------------------------------
    Menu stuff
------------------------------------*/

/*
    [play] => Array
            [stream] => Array
                    [menu_text] => Stream
                    [weight] => -1
                    [group_name] => play
                    [id] => cc_streamfile
                    [access] => 4
                    [action] => http://cch5.org/media/files/stream/Transistor_Karma/11706.m3u
                    [type] => 

    [download] => Array
            [1] => Array
                    [action] => http://cch5.org/people/
                    [menu_text] => mp3  (3.44MB)
                    [group_name] => download
                    [type] => audio/mpeg
                    [weight] => 1
                    [tip] => Transistor_Karma_-_The_Waterpipe_Aria_from_Ariane_and_Barbecue_a_remix_opera.mp3
                    [access] => 4
                    [id] => cc_downloadbutton

    [remix] => Array
            [replyremix] => Array
    [share] => Array
            [share_link] => Array
    [comment] => Array
            [comments] (Write Review)
    [owner] => Array
            [editupload] => Array
            [managefiles] => Array
            [manageremixes] => Array
    [admin] => Array
            [publish] => Array  (could be under owner)
            [deleteupload] => Array
            [howididit] => Array
            [editorial] => Array
            [ban] => Array
            [uploadadmin] => Array
    [playlist] => Array
            [playlist_menu] => Array

*/

function _t_upload_menu_init($T,&$A)
{
    if( !empty($A['record']) )
        $R =& $A['record'];
    else
        if( !empty($A['records']) )
            $R =& $A['records'][0];
        else
            return;

    $menu = empty($R['local_menu']) ? cc_get_upload_menu($R) : $R['local_menu'];

    /** OWNER menu *****/

    if( !empty($menu['owner']) )
    {
        print "  <div class=\"box\" id=\"download_box\"><ul>\n";

        foreach( $menu['owner'] as $mi )
            helper_upload_menu_item($mi);

        print "     </ul></div>\n";
    }

    /** PLAY/DOWNLOAD menu *****/

    print "  <div class=\"box\" id=\"download_box\"><ul>\n";

    if( !empty($R['fplay_url']) ) {
        $mi = array();
        $mi['pre'] = $T->String('str_play');
        $mi['class'] = 'cc_player_button cc_player_hear';
        $mi['id'] = "_ep_{$R['upload_id']}";
        $mi['action'] = $R['fplay_url'];
        helper_upload_menu_item($mi);
    }

    if( !empty($menu['play']) )
        foreach( $menu['play'] as $mi )
            helper_upload_menu_item($mi);

    $mi = array();
    $mi['action'] = "javascript://download";
    $mi['id'] = "_ed_{$R['upload_id']}";
    $mi['menu_text'] = $T->String('str_list_download');
    $mi['class'] = 'download_hook';
    helper_upload_menu_item($mi);

    print "</ul></div>\n";

    /** REVIEW/RATE/SHARE menu ******/

    print "<div class=\"box\" id=\"download_box\"><ul>\n";

    if( !empty($menu['comment']['comments']) )
        helper_upload_menu_item($menu['comment']['comments']);

    if( !empty($menu['playlist']['playlist_menu']) )
        helper_upload_menu_item($menu['playlist']['playlist_menu']);

    if( !empty($menu['share']['share_link']) )
        helper_upload_menu_item($menu['share']['share_link']);

    print "</ul></div>\n";

    /** TRACKBACK menu *****/

    $str = sprintf($T->String('str_list_i_saw_this'), '"' . $R['upload_name'] . '"');
    print "<div class=\"box\" id=\"download_box\">\n" .
          "<h2>{$T->String('str_list_trackback')}</h2>\n<a name=\"trackback\"></a>" .
          "<p>{$str}</p><ul>\n";

    $mi = array();
    $mi['action'] = 'javascript:// noted';
    $saws = array( array( 'remix',    $T->String('str_remix')),
                   array( 'podcast',  $T->String('str_podcast')),
                   array( 'video',    $T->String('str_video')),
                   array( 'web',      $T->String('str_list_web_blog')),
                   array( 'album',    $T->String('str_list_album'), ) );
    $url = "upload_trackback('{$R['upload_id']}', '";
    foreach( $saws as $saw )
    {
        $mi['menu_text'] = $saw[1];
        $mi['onclick'] = $url . $saw[0] . "');";
        helper_upload_menu_item($mi);
    }

    print "</ul></div>";

    /** ADMIN menu *****/

    if( !empty($menu['admin']) )
    {
        print "  <div class=\"box\" id=\"download_box\"><ul>\n";

        foreach( $menu['admin'] as $mi )
            helper_upload_menu_item($mi);

        print "     </ul></div>\n";
    }
}


function helper_upload_menu_item(&$item) 
{
    if( empty($item['parent_id']) )
        print '<li>';
    else
        print "<li id=\"{$item['parent_id']}\">";

    if( !empty($item['pre']) )
        print $item['pre'];

    print '<a ';

    $attrs = array( 'action' => 'href', 
                    'tip'    => 'title',
                    'id'     => 'id',
                    'class'  => 'class',
                    'type'   => 'type',
                    'onclick'=> 'onclick' );

    foreach( $attrs as $K => $V )
        if( !empty($item[$K]) )
            print "$V=\"{$item[$K]}\" ";

    print '>';
    
    if( !empty($item['menu_text']) )
        print $item['menu_text'];
    
    print "</a></li>\n";
}
?>