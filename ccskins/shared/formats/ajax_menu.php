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

[meta]
    type = template_component
    dataview = upload_menu
[/meta]

This is used for an ajax callback for just a menu on a record

no download/play/show stuff, just actions (review, edit, share, etc.)

----------------------------------
    Menu layout ?
------------------------------------

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

if( !empty($A['records']) )
    $R =& $A['records'][0];
else
    if( !empty($A['record']) )
        $R =& $A['record'];
    else
        return;

$menu =& $R['local_menu'];

print '<div id="ajax_menu"><ul>';

/** OWNER stuff *****/

if( !empty($menu['owner']) )
{
    foreach( $menu['owner'] as $mi )
        helper_ajax_menu_item($mi,$T);
}

/** REVIEW/RATE/SHARE menu ******/

if( !empty($menu['comment']['comments']) )
    helper_ajax_menu_item($menu['comment']['comments'],$T);

if( !empty($menu['share']['share_link']) )
    helper_ajax_menu_item($menu['share']['share_link'],$T);

/** ADMIN menu *****/

if( !empty($menu['admin']) )
{
    foreach( $menu['admin'] as $mi )
        helper_ajax_menu_item($mi,$T);
}

print '</ul>';

/** TRACKBACK menu *****/

$str = sprintf($T->String('str_list_i_saw_this'), '"' . $R['upload_name'] . '"');

?><div id="trackbackbox"><div class="box">
  <h2><?= $T->String('str_list_trackback') ?></h2>
  <a name="trackback"></a>
  <p id="trackback_caption"><?= $str ?></p><ul><?

$mi = array();
$mi['action'] = 'javascript:// noted';
$saws = array( array( 'remix',    $T->String('str_trackback_type_remix')),
               array( 'podcast',  $T->String('str_podcast')),
               array( 'video',    $T->String('str_trackback_type_video')),
               array( 'web',      $T->String('str_trackback_type_web')),
               array( 'album',    $T->String('str_trackback_type_album'), ) );
$url = "upload_trackback('{$R['upload_id']}', '";
foreach( $saws as $saw )
{
    $mi['menu_text'] = $saw[1];
    $mi['onclick'] = $url . $saw[0] . "');";
    helper_ajax_menu_item($mi,$T);
}

print "</ul></div></div>";

/** PLAYLIST menu *****/

if( !empty($menu['playlist']['playlist_menu']) )
{
    // actually we're going to embed the thing right here...
    // helper_ajax_menu_item($menu['playlist']['playlist_menu'],$T);
    print '<style type="text/css">.plblock a { display: block; }</style>';
print '<div class="box plblock" style="float:left"><h2>' . $T->String('str_playlists') . '</h2>';
    $A['args'] =& cc_get_playlist_with($R);
    $T->Call('playlist.tpl/playlist_popup');
print '</div>';
$script =<<<EOF
<script type="text/javascript">
function pl_item_cb(resp,json)
{
    this.parentNode.innerHTML = json.message ? eval(json.message) : eval(json);
}
function pl_item_action(event,url)
{
    new Ajax.Request( url, { method: 'get', onComplete: pl_item_cb.bind(this) } );
    Event.stop(event);
    return false;
}
$$('.cc_playlist_menu_item').each( function(e) {
    var url = e.href;
    e.href = 'javascript:// playlist goo';
    Event.observe( e, 'click', pl_item_action.bindAsEventListener(e,url) );
});
</script>
EOF;
    print $script;
}



function helper_ajax_menu_item(&$item,&$T) 
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
        print $T->String($item['menu_text']);
    
    print "</a></li>\n";
}


?>
