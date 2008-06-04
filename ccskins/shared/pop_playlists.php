<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

/*
[meta]
    type = template_component
    desc = _('Most popular playlist uploads')
    dataview = popular_tracks
    embedded = 1
[/meta]
[dataview]
function popular_tracks_dataview()
{
    $ccf = ccl('files') . '/';
    $ccp = ccl('people') . '/';
    $ccb = url_args( ccl('playlist','browse'), 'id=' );
    $user_sql = cc_fancy_user_sql('user_real_name');

    $sql =<<<EOF
        SELECT upload_num_playlists, upload_id, upload_name, $user_sql,
        CONCAT( '$ccf', user_name, '/', upload_id ) as file_page_url, 
        CONCAT( '$ccp', user_name ) as artist_page_url,
        CONCAT( '$ccb', upload_id ) as playlist_browse_url
        %columns%
        FROM cc_tbl_uploads
        JOIN cc_tbl_user ON upload_user=user_id
        %joins%
        %where%
        %order%
        %limit%
EOF;
    $sql_count = 'SELECT count(*) FROM cc_tbl_uploads';

    return array( 'e' => array(),
                  'sql' => $sql,
                  'sql_count' => $sql_count,
                  );
}
[/dataview]
*/?>

<!-- template pop_playlists -->
<div  class="cc_pl_div" id="_cart_1" style="margin-top: 12px;">
<?

$recs =& $A['records'];
if( !empty($recs) )
{
    $ids = array();
    foreach( $recs as $AIR )
    { 
        $iun  = CC_strchop($AIR['upload_name'],30,true);
        $iurn = CC_strchop($AIR['user_real_name'],30,true);
        $ids[] = $AIR['upload_id'];
?>
    <div class="trr">
        <div  class="tdc cc_playlist_item" id="_pli_<?= $AIR['upload_id'] ?>">
            <span ><a class="cc_playlist_pagelink cc_file_link" id="_plk_<?= $AIR['upload_id'] ?>" target="_parent" href="<?= $AIR['file_page_url'] ?>"><?=$iun?></a></span>
            <?= $T->String('str_by') ?> <a  class="cc_user_link" href="<?= $AIR['artist_page_url'] ?>"><?= $iurn ?></a>
        </div>
        <div class="tdc" style="padding-left:15px"><?= $T->String('str_pl_found_in') ?> 
            <a href="<?= $AIR['playlist_browse_url'] ?>"><?= $AIR['upload_num_playlists'] ?> 
            <?= $T->String('str_pl_playlists') ?></a>
        </div>
        <div class="tdc"><a class="info_button" id="_plinfo_<?= $AIR['upload_id'] ?>"></a></div>
<?

    if ( !empty($A['is_logged_in'])) 
    {
?>
        <div  id="playlist_menu_<?= $AIR['upload_id']?>" class="cc_playlist_action tdc light_bg dark_border">
            <a class="cc_playlist_button" href="javascript://playlist_menu_<?= $AIR['upload_id']?>"><span ><?= $T->String('str_pl_add_to') ?></span></a>
        </div>
<?
    }
    if ( !empty($AIR['fplay_url'])) 
    {
?>
        <div  class="tdc cc_playlist_pcontainer">
            <a class="cc_player_button cc_player_hear" id="_ep_<?= $AIR['upload_id']?>" href="<?= $AIR['fplay_url']?>"></a>
        </div>
<?  
     }  

?>
    <div class="hrc"></div>
    </div> <!-- trr -->
<?
    }
}
?>

</div><!-- cc_pl_div -->

<br  clear="right" />
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css') ?>" title="Default Style"></link>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js') ?>"></script>
<?
    $T->Call('playerembed.xml/eplayer');
?>
<script type="text/javascript">
    var playlistMenu = new ccPlaylistMenu();
    playlistMenu.hookElements('cc_pl_div');
</script>
<?
    $T->Call('prev_next_links');
?>
