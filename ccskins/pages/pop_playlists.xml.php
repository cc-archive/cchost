<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

global $_TV;

_template_compat_required();
?><div >

<?$_TV['info'] = CC_popular_playlist_tracks();?><script >
//<!--
function play_all()
{
    var url = home_url + 'playlist/popup/' + q + 'ids=' + '<?= $_TV['info']['ids']?>&nosort=1';
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
<b ><span ><?= _('Play All in Window')?></span></b></a>
</li>
</ul>
</div>
<h1  style="width:50%;"><?= _('Popular Playlist Adds');?></h1>
<br  sytle="clear:right" />
<div  class="cc_pl_div" id="_cart_1" style="margin-top: 12px;">
<?$carr101 = $_TV['info']['recs'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $_TV['item'] = $carr101[ $ck101[ $ci101 ] ];   ?><div  class="trr">
<div  class="tdc cc_playlist_item" id="_pli_<?= $_TV['item']['upload_id']?>">
<?$_TV['iun'] = CC_strchop($_TV['item']['upload_name'],30,true);?><span >
<a  class="cc_playlist_pagelink" id="_plk_<?= $_TV['item']['upload_id']?>" target="_parent" href="<?= $_TV['item']['file_page_url']?>"><?= $_TV['iun']?></a>
</span> by 
    <?$_TV['iurn'] = CC_strchop($_TV['item']['user_real_name'],30,true);?><a  href="<?= $_TV['item']['artist_page_url']?>"><?= $_TV['iurn']?></a>
</div>
<div  class="tdc" style="padding-left:15px"> Found in <a  href="<?= $_TV['home-url']?>playlist/browse<?= $_TV['q']?>id=<?= $_TV['item']['upload_id']?>"><?= $_TV['item']['track_count']?> playlists</a></div>
<div  class="tdc"><a  class="cc_playlist_i" id="_plinfo_<?= $_TV['item']['upload_id']?>">
</a></div>
<?if ( !empty($_TV['is_logged_in'])) {?><div  id="playlist_menu_<?= $_TV['item']['upload_id']?>" class="cc_playlist_action tdc">
<a  class="cc_playlist_button" href="javascript://playlist_menu_<?= $_TV['item']['upload_id']?>"><span >Add To...</span></a>
</div><?}if ( !empty($_TV['item']['fplay_url'])) {?><div  class="tdc cc_playlist_pcontainer">
<a  class="cc_player_button cc_player_hear" id="_ep_<?= $_TV['item']['upload_id']?>" href="<?= $_TV['item']['fplay_url']?>">
</a>
</div><?}?><div  class="hrc">
</div>
</div><?}?></div>
<br  clear="right" />
<link  rel="stylesheet" type="text/css" href="<?= $_TV['root-url']?>cctemplates/playlist.css" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $_TV['root-url']?>cctemplates/detail.css" title="Default Style"></link>
<script  src="<?= $_TV['root-url']?>cctemplates/js/playlist.js"></script>
<?_template_call_template('playerembed.xml/eplayer');
?><script >
  new ccPlaylistMenu();
</script>
</div>