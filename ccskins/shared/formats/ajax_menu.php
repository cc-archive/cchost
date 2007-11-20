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
    dataview = ajax_menu
    embedded = 1
[/meta]
[dataview]
function ajax_menu_dataview() 
{
    $sql =<<<EOF
SELECT upload_id, upload_banned, upload_tags, upload_published, upload_contest,
       user_id, user_name, file_name, upload_user, upload_name
    FROM cc_tbl_uploads
    JOIN cc_tbl_user ON upload_user = user_id
    JOIN cc_tbl_files ON upload_id = file_upload
%joins%
WHERE %where% (file_order = 0)
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                  'name' => 'ajax_menu',
                   'e'  => array(CC_EVENT_FILTER_FILES,
                                 CC_EVENT_FILTER_DOWNLOAD_URL)
                );
}
[/dataview]
This is used for an ajax callback for just a menu on a record

no download/play/show stuff, just actions (review, edit, share, etc.)


*/

if( empty($A['records']) )
    return;

$R = $A['records'][0];

$menu = empty($R['local_menu']) ? cc_get_upload_menu($R) : $R['local_menu'];

print '<div id="ajax_menu"><ul>';

/** OWNER stuff *****/

if( !empty($menu['owner']) )
{
    foreach( $menu['owner'] as $mi )
        helper_ajax_menu_item($mi);
}

/** REVIEW/RATE/SHARE menu ******/

if( !empty($menu['comment']['comments']) )
    helper_ajax_menu_item($menu['comment']['comments']);

if( !empty($menu['share']['share_link']) )
    helper_ajax_menu_item($menu['share']['share_link']);

/** ADMIN menu *****/

if( !empty($menu['admin']) )
{
    foreach( $menu['admin'] as $mi )
        helper_ajax_menu_item($mi);
}

print '</ul>';

/** TRACKBACK menu *****/

$str = sprintf($T->String('str_list_i_saw_this'), '"' . $R['upload_name'] . '"');
print "<div class=\"box\" style=\"float:left\">\n" .
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
    helper_ajax_menu_item($mi);
}

print "</ul></div>";

/** PLAYLIST menu *****/

if( !empty($menu['playlist']['playlist_menu']) )
{
    // actually we're going to embed the thing right here...
    // helper_ajax_menu_item($menu['playlist']['playlist_menu']);
    print '<style>.plblock a { display: block; }</style>';
print '<div class="box plblock" style="float:left"><h2>' . $T->String('str_playlists') . '</h2>';
    $A['args'] =& cc_get_playlist_with($R);
    $T->Call('playlist.tpl/playlist_popup');
print '</div>';
$script =<<<EOF
<script>
function pl_item_cb(resp,json)
{
    this.parentNode.innerHTML = json.message ? json.message : json;
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



function helper_ajax_menu_item(&$item) 
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
