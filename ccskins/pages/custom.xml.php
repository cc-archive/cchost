<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_custom_init($T,&$targs) {
    $T->CompatRequired();
}
function _t_custom__UBeenRemixed_admin_only($T,&$A) {
  $A['ubeenargs'] = CC_pending_pool_remix();if ( !empty($A['ubeenargs'])) {?><h2 ><a  href="<?= $A['ubeenargs']['url']?>"><?= $A['ubeenargs']['message']?></a></h2><?}}
function _t_custom_Play_this_page($T,&$A) {
  }
function _t_custom_Podcast_and_Stream_Links($T,&$A) {
  if ( !empty($A['upload_ids'])) {?><p >
<div  class="cc_podcast_link"><a  href="<?= $A['home-url']?>podcast/page?ids=<?= $A['upload_ids']?>"><span ><?= _('PODCAST this page')?></span></a></div>
<?if ( !empty($A['artist_page'])) {?><div  class="cc_podcast_link"><a  href="<?= $A['home-url']?>podcast/artist/<?= $A['artist_page']?>"><span >PODCAST <?= $A['artist_page']?></span></a></div><?}if ( !empty($A['can_stream_page'])) {?><div  class="cc_stream_page_link"><a  href="<?= $A['home-url']?>stream/page/playlist.m3u?ids=<?= $A['upload_ids']?>"><span ><?= _('STREAM this page')?></span></a></div><?}?></p><?}if ( !empty($A['enable_playlists'])) {if ( !empty($A['fplay_args'])) {$A['fqstring'] = join('&',$A['fplay_args']);if( !empty($A['get']['offset']) ) { $A['offs'] = $A['get']['offset']; } else {  $A['offs'] = 0;} if( !empty($A['fplay_title']) ) { $A['fplayt'] = $A['fplay_title']; } else {  $A['fplayt'] = _('PLAY this page'); } ?><script >
function ppage() { 
    var url = home_url + 'playlist/popup' + q + 'offset=<?= $A['offs']?>&<?= $A['fqstring']?>';
    var dim = "height=300,width=550";
    var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                  "resizable=1,scrollbars=1," + dim );
}
</script>
<?if ( !isset($A['upload_ids']) ) {?><br  /><?}?><div  class="cc_stream_page_link"><a  href="javascript://open player" onclick="ppage(); return false;"><span ><?= $A['fplayt']?></span></a></div><?}}}
function _t_custom_List_Contests($T,&$A) {
  ?><p ><?= CC_Lang('Open Contests')?></p>
<ul >
<?$carr101 =  CC_query('CCContests','GetOpenContests', 'cclib/cc-contest.inc');$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['item'] = $carr101[ $ck101[ $ci101 ] ];   ?><li >
<a  href="<?= $A['item']['contest_url']?>" class="cc_contest_open"><?= $A['item']['contest_friendly_name']?></a>
</li><?}?></ul>
<?}
function _t_custom_Virtual_Roots($T,&$A) {
  ?><p ><?= _('Mini Sites');?></p>
<ul >
<?$carr102 =  CC_get_config_roots();$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['item'] = $carr102[ $ck102[ $ci102 ] ];   ?><li >
<a  href="<?= $A['item']['url']?>"><?= $A['item']['scope_name']?></a>
</li><?}?></ul>
<?}
function _t_custom_Search_Box($T,&$A) {
  ?><form  action="<?= $A['home-url']?>search/results" method="get">
<div >
<input  class="cc_search_edit" name="search_text" value="search text"></input>
<input  type="hidden" name="search_type" value="any"></input>
<input  type="hidden" name="search_in" value="3"></input>
<input  type="submit" value="Search"></input>
<?if ( !empty($A['advanced_search_url'])) {?><a  href="<?= $A['advanced_search_url']?>"><?= _('Advanced')?></a><?}?></div>
</form>
<?}
function _t_custom_Ratings_Chart($T,&$A) {
  ?><p ><?= _('Highest Rated')?></p>
<?$A['chart'] = CC_ratings_chart(7);?><ul  condition="chart">
<?$carr103 = $A['chart'];$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $A['item'] = $carr103[ $ck103[ $ci103 ] ];   ?><li ><a  href="<?= $A['item']['file_page_url']?>"><?= $A['item']['upload_short_name']?></a></li><?}?></ul>
<?if ( !($A['chart']) ) {?><p ><?= _('No Chart')?></p><?}}
function _t_custom_Editorial_Picks($T,&$A) {
  $A['latest_title'] = _('Editors Picks');$A['latest_tag'] = 'editorial_pick';$T->Call('custom.xml/_query_latest');
?><a  href="<?= $A['home-url']?>editorial/picks" class="cc_more_menu_link"><?= CC_Lang('See all picks...')?></a>
<?}
function _t_custom_Latest_Uploads($T,&$A) {
  $A['latest_title'] = _('New Uploads');$A['latest_tag'] = '';$T->Call('custom.xml/_query_latest');
}
function _t_custom_Latest_Remixes($T,&$A) {
  $A['latest_title'] = _('New Remixes');$A['latest_tag'] = 'remix';$T->Call('custom.xml/_query_latest');
}
function _t_custom__query_latest($T,&$A) {
  ?><p ><?= $A['latest_title']?></p>
<?$A['qrecords'] = CC_cache_query($A['latest_tag'],'all','upload_date','DESC',5);if ( !empty($A['qrecords'])) {?><ul >
<?$carr104 = $A['qrecords'];$cc104= count( $carr104);$ck104= array_keys( $carr104);for( $ci104= 0; $ci104< $cc104; ++$ci104){    $A['qrecord'] = $carr104[ $ck104[ $ci104 ] ];   ?><li ><a  href="<?= $A['qrecord']['file_page_url']?>"><?= $A['qrecord']['upload_short_name']?></a></li><?}?></ul><?}}
function _t_custom_Recent_Reviews($T,&$A) {
  $A['reviews'] = CC_recent_reviews();?><p ><?= _('Recent Reviewers');?></p>
<ul  condition="reviews">
<?$carr105 = $A['reviews'];$cc105= count( $carr105);$ck105= array_keys( $carr105);for( $ci105= 0; $ci105< $cc105; ++$ci105){    $A['rev'] = $carr105[ $ck105[ $ci105 ] ];   ?><li ><a  href="<?= $A['rev']['topic_permalink']?>"><?= CC_strchop($A['rev']['user_real_name'],12);?></a></li><?}?></ul>
<a  href="<?= $A['home-url']?>reviews" class="cc_more_menu_link"><?= CC_Lang('More reviews...')?></a>
<?}
function _t_custom_Recent_Playlists($T,&$A) {
  $A['pl_lists'] = CC_recent_playlists();?><p ><?= _('Recent Playlists');?></p>
<ul  condition="pl_lists">
<?$carr106 = $A['pl_lists'];$cc106= count( $carr106);$ck106= array_keys( $carr106);for( $ci106= 0; $ci106< $cc106; ++$ci106){    $A['pl_list'] = $carr106[ $ck106[ $ci106 ] ];   ?><li ><a  href="<?= $A['home-url']?>playlist/browse/<?= $A['pl_list']['cart_id']?>"><?= CC_strchop($A['pl_list']['cart_name'],12);?></a></li><?}?></ul>
<a  href="<?= $A['home-url']?>playlist/browse" class="cc_more_menu_link"><?= CC_Lang('More playlists...')?></a>
<?}
function _t_custom_Support_CC($T,&$A) {
  ?><p >Support CC</p>
<ul >
<li >
<a  href="http://creativecommons.org/support/">
<img  src="http://creativecommons.org/images/support/2006/spread-3.gif" border="0" />
</a>
</li>
</ul>
<?}?>