<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_custom_Podcast_and_Stream_Links($T,&$A) {

    if( empty($A['artist_page']) ) 
    { 
        if( empty($A['qstring']) ) 
            return;

        $qstring = $A['qstring']; 
    }
    else 
    {
        $qstring = 'user=' . $A['artist_page'];
    }

    $qstring .= '&limit=15';
    $q = $A['q'];

    print "<p>{$GLOBALS['str_media']}</p>\n<ul>\n";

    if ( !empty($A['enable_playlists'])) 
    {
        $script = true;
        if( !empty($A['get']['offset']) ) { $A['offs'] = $A['get']['offset']; } else {  $A['offs'] = 0;} 
        if( !empty($A['fplay_title']) ) { $fplayt = $A['fplay_title']; } else {  $fplayt = $GLOBALS['str_play_this_page']; } 
        print "<li><a id=\"mi_play_page\" href=\"javascript://play page\" onclick=\"ppage()\">{$fplayt}</a></li>\n";
    }
    else
    {
        $script = false;
    }

    print "<li><a id=\"mi_stream_page\" href=\"{$A['home-url']}api/query/stream.m3u{$q}f=m3u&{$qstring}\">{$GLOBALS['str_stream_this_page']}</a></li>\n" .
          "<li><a id=\"mi_podcast_page\" title=\"{$GLOBALS['str_drag_this_link']}\" href=\"{$A['home-url']}api/query/{$q}f=rss&{$qstring}\">{$GLOBALS['str_podcast_this_page']}</a></li>\n" .
          "</ul>\n";

    if( $script )
    {
        ?>
<script>
function ppage() { 
    var url = home_url + 'playlist/popup' + q + 'offset=<?= $A['offs']?>&<?= $qstring ?>';
    var dim = "height=300,width=550";
    var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                  "resizable=1,scrollbars=1," + dim );
}
</script>
        <?
    }

}

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