<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

global $_TV;

_template_compat_required();
?><style >
.cvalue {
  display:none;
}

.cbutton {
  margin: 2px;
  padding: 2px;
}

.cbutton_selected {
  background-color: #EEF;
  color: #006;
}

#channel_form td {
  vertical-align: top;
}
</style>
<h1  id="channel_title"><?= _('Remix Radio');?></h1>
<form  id="channel_form">
<table  cellpadding="0" cellspacing="0" id="outerframe">
<tr ><td  style="width:510px">
<div  id="djback">
<table  cellpadding="0" cellspacing="0">
<tr ><td  id="cell1">
<div  id="channel_intro">
<h3 ><?= _('Create Your Own Remix Radio Station');?></h3>
<p ><?= sprintf(_('Create your own stream or podcast from a random pool of remixes in %s by selecting a style and other choices.'),$_TV['site-title']) ;?></p>
</div>
</td></tr>
<tr ><td  id="cell2">
<?$_TV['channels'] = CC_get_config('channels');?><table  id="channels" cellspacing="0" cellpadding="0">
<?$_TV['channel_rows'] = array_chunk($_TV['channels'],5);$carr101 = $_TV['channel_rows'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $_TV['R'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr >
<?$carr102 = $_TV['R'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $_TV['C'] = $carr102[ $ck102[ $ci102 ] ];   $_TV['T'] = str_replace(',','+',$_TV['C']['tags']);$_TV['K'] = 'tags' . $ck102[$ci102] . $ck102[$ci102];?><td ><div  id="<?= $_TV['K']?>" class="cbutton"><?= $_TV['C']['text']?><span  id="cvalue_<?= $_TV['K']?>" class="cvalue"><?= $_TV['T']?></span></div></td>
<?}?></tr>
<?}?></table>
</td></tr>
<tr ><td  id="cell3">
<table  cellspacing="0" cellpadding="0" id="options">
<tr >
<td >
<span  class="opt_label"><?= _('Ratings:')?></span>
<select  id="score" name="score">
<option  value="500">5</option>
<option  value="450"><?= _('4.5 or above');?></option>
<option  value="400" selected="selected"><?= _('4 or above');?></option>
<option  value="350"><?= _('3.5 or above');?></option>
<option  value="300"><?= _('3.0 or above');?></option>
<option  value="0"><?= _('all');?></option>
</select>
</td>
<td >
<span  class="opt_label"><?= _('Since:')?></span>
<?$_TV['oneday'] = strtotime('1 day ago');$_TV['oneweek'] = strtotime('1 week ago');$_TV['twoweeks'] = strtotime('2 weeks ago');$_TV['onemonth'] = strtotime('1 month ago');$_TV['threemonths'] = strtotime('3 months ago');$_TV['oneyear'] = strtotime('1 year ago');?><select  id="sinceu" name="sinceu">
<option  value="<?= $_TV['oneday']?>"><?= _('Yesterday');?></option>
<option  value="<?= $_TV['oneweek']?>"><?= _('Last week');?></option>
<option  value="<?= $_TV['twoweeks']?>"><?= _('2 weeks ago');?></option>
<option  value="<?= $_TV['onemonth']?>"><?= _('Last month');?></option>
<option  value="<?= $_TV['threemonths']?>" selected="selected"><?= _('3 months ago');?></option>
<option  value="<?= $_TV['oneyear']?>"><?= _('Last year');?></option>
<option  value="0"><?= _('all time');?></option>
</select>
</td>
<td >
<span  class="opt_label"><?= _('This many:')?></span>
<select  id="limit" name="limit">
<option  value="10">10</option>
<option  value="25" selected="selected">25</option>
<option  value="50">50</option>
<option  value="100">100</option>
<option  value="200">200</option>
</select>
</td>
<td  style="width:40%">
<div  id="gobuttons" style="">
<div  class="cc_stream_page_link"><a  href="" id="streamlink"><span ><?= _('Stream Now');?></span></a></div>
<div  class="cc_podcast_link"><a  href="" id="podlink"><span ><?= _('Podcast');?></span></a></div>
</div>
<div  id="countresults"></div>
</td>
</tr>
</table>
</td></tr>
</table>
</div>
</td>
<td >
<div  id="playlist_target">
<div  id="playlist">
</div>
</div>
</td></tr>
</table>
</form>
<script >
baseCmd = '<?= $_TV['home-url']?>api/query';
sitePromoTag = '<?if ( isset($_TV['site_promo_tag']) ) {?><?= $_TV['site_promo_tag']?><?}?>';
</script>
<script  src="<?= $_TV['root-url']?>cctemplates/js/radio.js"></script>