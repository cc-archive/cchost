<?
$AInfo = CC_popular_playlist_tracks();
?>
<script >
//<!--
function play_all()
{
    var url = home_url + 'playlist/popup/' + q + 'ids=' + '<?= $AInfo['ids']?>&nosort=1';
    var dim = "height=300,width=550";
    // var url = home_url + 'api/query' + q + 't=mplayerbig&f=html&playlist=' + playlist_id + '&' + qs;
    // var dim = "height=170, width=420";
    var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                  "resizable=1,scrollbars=1," + dim );
}
// -->
</script>
<div  style="width:200px;float:right;text-align: center;">
    <ul  class="cc_playlist_owner_menu">
        <li >
            <a href="javascript://open in window" id="playall" class="cc_playlist_playwindow" onclick="play_all()">
            <b><span><?= $GLOBALS['str_play_all_in_window'] ?></span></b></a>
        </li>
    </ul>
</div>
<h1  style="width:50%;"><?= $GLOBALS['str_popular_playlist_adds'] ?></h1>
<br  style="clear:right" />
<div  class="cc_pl_div" id="_cart_1" style="margin-top: 12px;">
<?

foreach( $AInfo['recs'] as $AIR )
{ 
    $iun  = CC_strchop($AIR['upload_name'],30,true);
    $iurn = CC_strchop($AIR['user_real_name'],30,true);
    $html =<<<EOF
<div class="trr">
    <div  class="tdc cc_playlist_item" id="_pli_{$AIR['upload_id']}">
        <span ><a class="cc_playlist_pagelink" id="_plk_{$AIR['upload_id']}" target="_parent" href="{$AIR['file_page_url']}">{$iun}</a></span>
        {$GLOBALS['str_by']} <a  href="{$AIR['artist_page_url']}">{$iurn}</a>
    </div>
    <div class="tdc" style="padding-left:15px">{$GLOBALS['str_found_in']} 
        <a href="{$A['home-url']}playlist/browse{$A['q']}id={$AIR['upload_id']}">{$AIR['track_count']} {$GLOBALS['str_playlists']}</a>
    </div>
    <div class="tdc"><a class="cc_playlist_i" id="_plinfo_{$AIR['upload_id']}"></a></div>
EOF;
    print $html;

    if ( !empty($A['is_logged_in'])) 
    {
?><div  id="playlist_menu_<?= $AIR['upload_id']?>" class="cc_playlist_action tdc">
<a  class="cc_playlist_button" href="javascript://playlist_menu_<?= $AIR['upload_id']?>"><span ><?= $GLOBALS['str_add_to'] ?></span></a>
</div><?
    }
    if ( !empty($AIR['fplay_url'])) 
    {
?><div  class="tdc cc_playlist_pcontainer">
<a class="cc_player_button cc_player_hear" id="_ep_<?= $AIR['upload_id']?>" href="<?= $AIR['fplay_url']?>"></a>
</div>
<?  
     }  

?>
<div class="hrc"></div>
</div> <!-- trr --><?
}
?>

</div><!-- cc_pl_div -->

<br  clear="right" />
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/detail.css') ?>" title="Default Style"></link>
<script  src="<?= $T->URL('js/playlist.js') ?>"></script>
<?$T->Call('playerembed.xml/eplayer');?>
<script>new ccPlaylistMenu();</script>
</div>