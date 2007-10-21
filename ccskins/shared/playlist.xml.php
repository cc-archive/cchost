<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');


function _t_playlist_playlist_create_dyn($T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<div  id="dyn_filter_editor" class="cc_round_box" >
<div  id="filter_form" >
</div>
</div>
<div  id="debug"></div>
<script  src="<?= $T->URL('js/query_filter.js')?>" ></script>
<script  src="<?= $T->URL('js/autocomp.js')?>" ></script>
<script  src="<?= $T->URL('js/autopick.js')?>" ></script>
<script >
    var params = <?= $A['plargs']['edit_query']?>;
    var filters = new ccQueryBrowserFilters( params );
    var formatter = new ccFormatter();
    var formInfo = filters.makeForm( 'ff', formatter );
    $('filter_form').innerHTML = formInfo.html;
    formatter.setup_watches();
    $(formInfo.innerId).style.display = 'block';
    Event.observe(formInfo.submitId,'click',onFilterSubmit.bindAsEventListener());
    $(formInfo.submitId).innerHTML = '<span ><?= $A['plargs']['submit_text']?></span>';

    function onFilterSubmit(event) {
      var qstring = filters.queryString() + '&f=playlist';
      var promo_tag = '<?= $A['plargs']['promo_tag']?>';
      if( promo_tag.length > 0 )
        qstring += '&promo_tag=' + promo_tag;
      document.location.href = '<?= $A['plargs']['submit_url']?>' + q + qstring;
    }
  </script>
<?}
function _t_playlist_playlist_popup($T,&$A) {
  $carr101 = $A['args']['with'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['R'] = $carr101[ $ck101[ $ci101 ] ];   ?><a  href="<?= $A['home-url']?>api/playlist/remove/<?= $A['args']['upload_id']?>/<?= $A['R']['cart_id']?>" class="cc_playlist_menu_item"><span >Remove from <span  class="cc_playlist_name"><?= $A['R']['cart_name']?></span></span></a>
<?}$carr102 = $A['args']['without'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['R'] = $carr102[ $ck102[ $ci102 ] ];   ?><a  href="<?= $A['home-url']?>api/playlist/add/<?= $A['args']['upload_id']?>/<?= $A['R']['cart_id']?>" class="cc_playlist_menu_item"><span >Add to <span  class="cc_playlist_name"><?= $A['R']['cart_name']?></span></span></a>
<?}?><a  href="<?= $A['home-url']?>api/playlist/new/<?= $A['args']['upload_id']?>" class="cc_playlist_menu_item cc_playlist_add_mi"><span >Add to new playlist</span></a>
<?}
function _t_playlist_playlist_browse($T,&$A) {
  $carr103 = $A['records'];$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $A['PL'] = $carr103[ $ck103[ $ci103 ] ];   ?><div  class="cc_playlist_line" id="_pl_<?= $A['PL']['cart_id']?>"><?= $A['PL']['cart_name']?> <span  class="cc_playlist_dyn_user">created by
      <?= $A['PL']['user_real_name']?></span>
<?if ( !empty($A['PL']['cart_dynamic'])) {?><span  class="cc_playlist_dyn_label"><?= _('(dynamic)')?></span><?}?><span ><?= CC_strchop(str_replace(',',' ',$A['PL']['cart_tags']),110)?></span>
<?if ( !($A['PL']['cart_dynamic']) ) {?><span > items: <?= $A['PL']['cart_num_items']?></span><?}?></div>
<?}$T->Call($A['prev_next_links']);
}
function _t_playlist_playlist_list($T,&$A) {
  ?><table  class="cc_pl_table"><tr ><td >
<?if ( !empty($A['args']['menu'])) {?><ul  class="cc_playlist_owner_menu">
<?$carr104 = $A['args']['menu'];$cc104= count( $carr104);$ck104= array_keys( $carr104);for( $ci104= 0; $ci104< $cc104; ++$ci104){    $A['MI'] = $carr104[ $ck104[ $ci104 ] ];   ?><li >
<a  target="_parent" href="<?= $A['MI']['url']?>" id="<?= $A['MI']['id']?>" class="<?= $A['MI']['class']?>"><span ><?= $A['MI']['text']?></span></a>
</li><?}?></ul>
<?}?></td><td >
<?if ( !empty($A['args']['feed_q'])) {?><div  class="cc_playlist_feed"><a  target="_parent" href="<?= $A['args']['feed_q']?>">
<img  src="<?= $A['root-url']?>ccimages/feed-icon16x16.png" /></a></div>
<?}$A['R'] = $A['args']['playlist'];?><a  target="_parent" href="<?= $A['home-url']?>playlist/browse/<?= $A['R']['cart_id']?>"><div  class="cc_playlist_title"><?= $A['R']['cart_name']?></div>
</a>
<span  class="cc_playlist_date">
        Created by <a  target="_parent" href="<?= $A['R']['artist_page_url']?>"><?= $A['R']['user_real_name']?></a> on <?= $A['R']['cart_date_format']?>
      </span>
<?if ( !empty($A['R']['cart_desc'])) {?><div  class="gd_description" id="pldesc_<?= $A['R']['cart_id']?>">
<div  style="padding: 10px;">
<?$A['cdesc'] = cc_format_text($A['R']['cart_desc']);?>
          <?= $A['cdesc']?>
        </div>
</div><?}?></td></tr></table>
<div  class="cc_pl_div" id="_cart_<?= $A['R']['cart_id']?>">
<?$A['reguser'] = $A['is_logged_in'];$A['records'] = $A['args']['records'];$T->Call('playlist.xml/playlist_list_lines');
?></div>
<?if ( !empty($A['is_logged_in'])) {?><span  id="pl_user_<?= $A['R']['cart_id']?>"></span><?}if ( !empty($A['args']['is_owner'])) {?><span  id="pl_owner_<?= $A['R']['cart_id']?>"></span><?}?><br  clear="right" />
<?}
function _t_playlist_playlist_list_lines($T,&$A) {
  $carr105 = $A['records'];$cc105= count( $carr105);$ck105= array_keys( $carr105);for( $ci105= 0; $ci105< $cc105; ++$ci105){    $A['item'] = $carr105[ $ck105[ $ci105 ] ];   ?><div  class="trr">
<div  class="tdc cc_playlist_item" id="_pli_<?= $A['item']['upload_id']?>">
<?$A['iun'] = CC_strchop($A['item']['upload_name'],30,true);?><span >
<a  class="cc_playlist_pagelink" id="_plk_<?= $A['item']['upload_id']?>" target="_parent" href="<?= $A['item']['file_page_url']?>"><?= $A['iun']?></a>
</span> by 
      <?$A['iurn'] = CC_strchop($A['item']['user_real_name'],30,true);?><a  target="_parent" href="<?= $A['item']['artist_page_url']?>"><?= $A['iurn']?></a>
</div>
<div  class="tdc"><a  class="cc_playlist_i" id="_plinfo_<?= $A['item']['upload_id']?>">
</a></div>
<?if ( !empty($A['reguser'])) {?><div  id="playlist_menu_<?= $A['item']['upload_id']?>" class="cc_playlist_action tdc">
<a  class="cc_playlist_button" href="javascript://playlist_menu_<?= $A['item']['upload_id']?>"><span >Add To...</span></a>
</div><?}if ( !empty($A['item']['fplay_url'])) {?><div  class="tdc cc_playlist_pcontainer">
<a  class="cc_player_button cc_player_hear" id="_ep_<?= $A['R']['cart_id']?>_<?= $A['item']['upload_id']?>" href="<?= $A['item']['fplay_url']?>">
</a>
</div><?}?><div  class="hrc">
</div>
</div><?}}
function _t_playlist_playlist_list_window_cart($T,&$A) {
  ?><div  class="cc_playlist_popup_window">
<table  class="cc_pl_table" cellpadding="0" cellspacing="0"><tr ><td >
<?if ( !empty($A['args']['menu'])) {?><ul  class="cc_playlist_owner_menu">
<?$carr106 = $A['args']['menu'];$cc106= count( $carr106);$ck106= array_keys( $carr106);for( $ci106= 0; $ci106< $cc106; ++$ci106){    $A['MI'] = $carr106[ $ck106[ $ci106 ] ];   ?><li >
<a  target="_parent" href="<?= $A['MI']['url']?>" id="<?= $A['MI']['id']?>" class="<?= $A['MI']['class']?>"><span ><?= $A['MI']['text']?></span></a>
</li><?}?></ul>
<?}?></td><td >
<?$A['R'] = $A['args']['playlist'];?><a  target="_parent" href="<?= $A['home-url']?>playlist/browse/<?= $A['R']['cart_id']?>">
<div  class="cc_playlist_title"><?= $A['R']['cart_name']?></div>
</a>
<span  class="cc_playlist_date">
      Created by <a  target="_parent" href="<?= $A['R']['artist_page_url']?>"><?= $A['R']['user_real_name']?></a>
<br  /><?= $A['R']['cart_date_format']?>
    </span>
</td></tr>
<tr ><td ></td><td  style="height:22px;">
<div  class="cc_playlist_pcontainer" id="plc_id"></div>
</td></tr>
</table>
<table  class="cc_pl_table">
<?$carr107 = $A['args']['records'];$cc107= count( $carr107);$ck107= array_keys( $carr107);for( $ci107= 0; $ci107< $cc107; ++$ci107){    $A['item'] = $carr107[ $ck107[ $ci107 ] ];   ?><tr >
<td  class="cc_playlist_item" id="_pli_<?= $A['item']['upload_id']?>">
<?$A['iname'] = CC_strchop($A['item']['upload_name'],45);$A['uname'] = CC_strchop($A['item']['user_real_name'],22);?><span >
<a  target="_parent" href="<?= $A['item']['file_page_url']?>"><?= $A['iname']?></a>
</span> by 
      <a  target="_parent" href="<?= $A['item']['artist_page_url']?>"><?= $A['uname']?></a>
</td>
<?if ( !empty($A['item']['fplay_url'])) {?><td  class="cc_playlist_pcontainer">
<a  class="cc_player_button cc_player_hear" id="_ep_<?= $A['R']['cart_id']?>_<?= $A['item']['upload_id']?>" href="<?= $A['item']['fplay_url']?>">
</a>
</td><?}?></tr><?}?></table>
<?if ( !empty($A['is_logged_in'])) {?><span  id="pl_user_<?= $A['R']['cart_id']?>"></span><?}if ( !empty($A['args']['is_owner'])) {?><span  id="pl_owner_<?= $A['R']['cart_id']?>"></span><?}?></div>
<?}
function _t_playlist_playlist_popup_window($T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<?$A['player_options'] = 'autoHook: false,showVolume: false,showProgress: false,plcc_id: \'plc_id\'';?>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<?$T->Call('playlist.xml/playlist_list_window_cart');
$T->Call('playerembed.xml/eplayer');
?><script >
    new ccPlaylistMenu();
    new ccPagePlayer(<?= $A['args']['playlist']['cart_id']?>);
    new ccParentRedirector(); 
  </script>
<style >
  div.cc_playlist_popup_window,
  #cc_wrapper1, #cc_wrapper2
  #cc_content, #cc_centercontent {
     width: auto;
  }
  #plc_id {
    width: 250px;
  }
  body {
    margin: 5px;
  }
  h1 { display: none; } /* don't ask */
  </style>
<?}
function _t_playlist_playlist_show_one($T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/detail.css') ?>"  title="Default Style"></link>
<?$A['player_options'] = 'autoHook: false';?><script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<?$T->Call('playlist.xml/playlist_list');
$T->Call('playerembed.xml/eplayer');
?><script >
    new ccPlaylistMenu();
    new ccPagePlayer(<?= $A['args']['playlist']['cart_id']?>);
  </script>
<?}
function _t_playlist_playlist_show_browser($T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>"  title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/detail.css') ?>"  title="Default Style"></link>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<div  id="playlist_browser" class="grid"><span >getting playlists...</span></div>
<?$A['player_options'] = 'autoHook: false';$T->Call('playerembed.xml/eplayer');
?><script >
    var plb = new ccPlaylistBrowser( 'playlist_browser', <?= $A['args']?> );
  </script>
<?}
function _t_playlist_playlist_menu($T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<script >
    new ccPlaylistMenu();
  </script>
<?}
function _t_playlist_playlist_edit_order($T,&$A) {
  ?>(not implemented)
<?}?>