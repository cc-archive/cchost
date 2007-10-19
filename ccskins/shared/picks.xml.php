<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_picks_init($T,&$targs) {
    $T->CompatRequired();
}?><div >

<?$A['_by'] = _('by');$A['pod_title'] = _('Podcast');$A['stream_title'] = _('Stream');$A['play_title'] = _('Play');?><script >
function pickwinplay(qstring)
{
  var url = home_url + 'playlist/popup' + q + qstring;
  var dim = "height=300,width=550";
  var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                "resizable=1,scrollbars=1," + dim );
}
</script>
<?
function _t_picks_picks($T,&$A) {
  ?><h3 ><?= $A['pick_title']?></h3>
<?$A['chart'] = cc_query_fmt($A['qstring']);if ( !empty($A['chart'])) {?><table  cellspacing="0" cellpadding="0" id="edpick_stream_links">
<tr >
<td >
<div  class="cc_podcast_link"><a  href="<?= $A['home-url']?>podcast/page?<?= $A['qstring']?>"><span ><?= $A['pod_title']?></span></a></div>
</td>
<td >
<div  class="cc_stream_page_link">
<a  href="<?= $A['home-url']?>stream/page/playlist.m3u?<?= $A['qstring']?>"><span ><?= $A['stream_title']?></span></a></div>
</td>
<?if ( !empty($A['enable_playlists'])) {?><td >
<div  class="cc_stream_page_link">
<a  href="javascript://play win" onclick="pickwinplay(<?= $A['qstring']?>);"><span ><?= $A['play_title']?></span></a></div>
</td><?}?></tr>
</table>
<?$carr101 = $A['chart'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['item'] = $carr101[ $ck101[ $ci101 ] ];   ?><div ><a  href="<?= $A['item']['file_page_url']?>" class="cc_file_link"><?= $A['item']['upload_name']?></a>
<br  />
<span ><?= $A['_by']?>
<a  href="<?= $A['item']['artist_page_url']?>"><?= $A['item']['user_real_name']?></a></span>
<?if ( !empty($A['ed_pick'])) {$carr102 = $A['item']['upload_extra']['edpicks'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['editorial'] = $carr102[ $ck102[ $ci102 ] ];   ?><p >
<i >
<?= CC_strchop($A['editorial']['review_text'],40)?>
<a  href="<?= $A['editorial']['review_url']?>">(more) </a></i>
</p><?}}?></div><?}}if ( !($A['chart']) ) {?><div >No chart</div><?}}?><h1 ><?= _('Editors\' Picks and Hot Tracks');?></h1>
<style >
.pickspage td
{
    vertical-align: top;
    padding-right: 12px;
}
.pickspage
{
    margin-left: 3%;
    width: 90%;
}
.pickspage td p
{
  margin: 1px;
}
.pickspage td a
{
  white-space: nowrap;
}
.pickspage td span, 
.pickspage td i
{
  white-space: nowrap;
  padding-left: 20px;
}
.pickspage td i
{
  color: green;
}
.pickspage td div
{
  margin-bottom: 7px;
}
.pickspage .cc_file_link
{
  font-weight: bold;
}
.pickspage h3 {
  border-bottom: 1px dotted #77A;
}

.pickspage .cc_podcast_link,
.pickspage .cc_stream_page_link {
   
}
.pickspage .cc_podcast_link, 
.pickspage .cc_stream_page_link, 
.pickspage .cc_podcast_link span, 
.pickspage .cc_stream_page_link span {
  margin:0px;
  padding: 3px 0px 0px 0px;
}

.pickspage .cc_podcast_link,
.pickspage .cc_stream_page_link {
  text-align: center;
  width: 91px;
}

.pickspage .cc_podcast_link {
   background: url('<?= $A['site-root']?>cctemplates/ccmixter/mixter-button-o-small.gif') no-repeat;
}
.pickspage .cc_stream_page_link {
   background: url('<?= $A['site-root']?>cctemplates/ccmixter/mixter-button-b-small.gif') no-repeat;
}

</style>
<table  class="pickspage">
<tr >
<td  rowspan="2" style="padding-right: 25px;">
<?$A['ed_pick'] = 1;$A['pick_title'] = _('Editors\' Picks');$A['qstring'] = 'tags=editorial_pick&sort=date&dir=DESC&limit=22';$T->Call('picks.xml/picks');
?></td>
<td >
<?$A['settings'] = CC_get_config('chart');$A['pick_title'] = _('What\'s Hot Right Now');$A['ed_pick'] = 0;$A['qstring'] = 'tags=remix,-digital_distortion&sort=num_scores&dir=DESC&sinced=' . $A['settings']['cut-off'] . '&limit=12';$T->Call('picks.xml/picks');
?></td>
</tr>
<tr >
<td >
<?$A['pick_title'] = _('All Time Hot List');$A['ed_pick'] = 0;$A['qstring'] = 'tags=remix&sort=num_scores&dir=DESC&limit=10';$T->Call('picks.xml/picks');
?></td>
</tr>
</table>
</div>