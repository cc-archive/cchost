<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

global $_TV;

_template_compat_required();
?><div >

<?$_TV['_by'] = _('by');$_TV['pod_title'] = _('Podcast');$_TV['stream_title'] = _('Stream');$_TV['play_title'] = _('Play');?><script >
function pickwinplay(qstring)
{
  var url = home_url + 'playlist/popup' + q + qstring;
  var dim = "height=300,width=550";
  var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                "resizable=1,scrollbars=1," + dim );
}
</script>
<?
function _t_picks_picks() {
   global $_TV;
?><h3 ><?= $_TV['pick_title']?></h3>
<?$_TV['chart'] = cc_query_fmt($_TV['qstring']);if ( !empty($_TV['chart'])) {?><table  cellspacing="0" cellpadding="0" id="edpick_stream_links">
<tr >
<td >
<div  class="cc_podcast_link"><a  href="<?= $_TV['home-url']?>podcast/page?<?= $_TV['qstring']?>"><span ><?= $_TV['pod_title']?></span></a></div>
</td>
<td >
<div  class="cc_stream_page_link">
<a  href="<?= $_TV['home-url']?>stream/page/playlist.m3u?<?= $_TV['qstring']?>"><span ><?= $_TV['stream_title']?></span></a></div>
</td>
<?if ( !empty($_TV['enable_playlists'])) {?><td >
<div  class="cc_stream_page_link">
<a  href="javascript://play win" onclick="pickwinplay(<?= $_TV['qstring']?>);"><span ><?= $_TV['play_title']?></span></a></div>
</td><?}?></tr>
</table>
<?$carr101 = $_TV['chart'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $_TV['item'] = $carr101[ $ck101[ $ci101 ] ];   ?><div ><a  href="<?= $_TV['item']['file_page_url']?>" class="cc_file_link"><?= $_TV['item']['upload_name']?></a>
<br  />
<span ><?= $_TV['_by']?>
<a  href="<?= $_TV['item']['artist_page_url']?>"><?= $_TV['item']['user_real_name']?></a></span>
<?if ( !empty($_TV['ed_pick'])) {$carr102 = $_TV['item']['upload_extra']['edpicks'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $_TV['editorial'] = $carr102[ $ck102[ $ci102 ] ];   ?><p >
<i >
<?= CC_strchop($_TV['editorial']['review_text'],40)?>
<a  href="<?= $_TV['editorial']['review_url']?>">(more) </a></i>
</p><?}}?></div><?}}if ( !($_TV['chart']) ) {?><div >No chart</div><?}}?><h1 ><?= _('Editors\' Picks and Hot Tracks');?></h1>
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
   background: url('<?= $_TV['site-root']?>cctemplates/ccmixter/mixter-button-o-small.gif') no-repeat;
}
.pickspage .cc_stream_page_link {
   background: url('<?= $_TV['site-root']?>cctemplates/ccmixter/mixter-button-b-small.gif') no-repeat;
}

</style>
<table  class="pickspage">
<tr >
<td  rowspan="2" style="padding-right: 25px;">
<?$_TV['ed_pick'] = 1;$_TV['pick_title'] = _('Editors\' Picks');$_TV['qstring'] = 'tags=editorial_pick&sort=date&dir=DESC&limit=22';_template_call_template('picks.xml/picks');
?></td>
<td >
<?$_TV['settings'] = CC_get_config('chart');$_TV['pick_title'] = _('What\'s Hot Right Now');$_TV['ed_pick'] = 0;$_TV['qstring'] = 'tags=remix,-digital_distortion&sort=num_scores&dir=DESC&sinced=' . $_TV['settings']['cut-off'] . '&limit=12';_template_call_template('picks.xml/picks');
?></td>
</tr>
<tr >
<td >
<?$_TV['pick_title'] = _('All Time Hot List');$_TV['ed_pick'] = 0;$_TV['qstring'] = 'tags=remix&sort=num_scores&dir=DESC&limit=10';_template_call_template('picks.xml/picks');
?></td>
</tr>
</table>
</div>