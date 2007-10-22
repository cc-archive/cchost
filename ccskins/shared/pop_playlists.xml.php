<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_pop_playlists_init($T,&$targs) {
    $T->CompatRequired();
}?><div >

<?$A['info'] = CC_popular_playlist_tracks();?><script >
//<!--
function play_all()
{
    var url = home_url + 'playlist/popup/' + q + 'ids=' + '<?= $A['info']['ids']?>&nosort=1';
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
<a  href="javascript://open in window" id="playall" class="cc_playlist_playwindow" onclick="play_all()">
<b ><span ><?= ?></span></b></a>
</li>
</ul>
</div>
<h1  style="width:50%;"><?= ;?></h1>
<br  sytle="clear:right" />
<div  class="cc_pl_div" id="_cart_1" style="margin-top: 12px;">
<?$carr101 = $A['info']['recs'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['item'] = $carr101[ $ck101[ $ci101 ] ];   ?><div  class="trr">
<div  class="tdc cc_playlist_item" id="_pli_<?= $A['item']['upload_id']?>">
<?$A['iun'] = CC_strchop($A['item']['upload_name'],30,true);?><span >
<a  class="cc_playlist_pagelink" id="_plk_<?= $A['item']['upload_id']?>" target="_parent" href="<?= $A['item']['file_page_url']?>"><?= $A['iun']?></a>
</span> by 
    <?$A['iurn'] = CC_strchop($A['item']['user_real_name'],30,true);?><a  href="<?= $A['item']['artist_page_url']?>"><?= $A['iurn']?></a>
</div>
<div  class="tdc" style="padding-left:15px"> Found in <a  href="<?= $A['home-url']?>playlist/browse<?= $A['q']?>id=<?= $A['item']['upload_id']?>"><?= $A['item']['track_count']?> playlists</a></div>
<div  class="tdc"><a  class="cc_playlist_i" id="_plinfo_<?= $A['item']['upload_id']?>">
</a></div>
<?if ( !empty($A['is_logged_in'])) {?><div  id="playlist_menu_<?= $A['item']['upload_id']?>" class="cc_playlist_action tdc">
<a  class="cc_playlist_button" href="javascript://playlist_menu_<?= $A['item']['upload_id']?>"><span >Add To...</span></a>
</div><?}if ( !empty($A['item']['fplay_url'])) {?><div  class="tdc cc_playlist_pcontainer">
<a  class="cc_player_button cc_player_hear" id="_ep_<?= $A['item']['upload_id']?>" href="<?= $A['item']['fplay_url']?>">
</a>
</div><?}?><div  class="hrc">
</div>
</div><?}?></div>
<br  clear="right" />
<link  rel="stylesheet" type="text/css" href="<?= $A['root-url']?>cctemplates/playlist.css" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $A['root-url']?>cctemplates/detail.css" title="Default Style"></link>
<script  src="<?= $A['root-url']?>cctemplates/js/playlist.js"></script>
<?$T->Call('playerembed.xml/eplayer');
?><script >
  new ccPlaylistMenu();
</script>
</div>