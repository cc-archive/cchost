<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

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
<p ><?= sprintf(_('Create your own stream or podcast from a random pool of remixes in %s by selecting a style and other choices.'),$A['site-title']) ;?></p>
</div>
</td></tr>
<tr ><td  id="cell2">
<?$A['channels'] = CC_get_config('channels');?><table  id="channels" cellspacing="0" cellpadding="0">
<?$A['channel_rows'] = array_chunk($A['channels'],5);$carr101 = $A['channel_rows'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['R'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr >
<?$carr102 = $A['R'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['C'] = $carr102[ $ck102[ $ci102 ] ];   $A['T'] = str_replace(',','+',$A['C']['tags']);$A['K'] = 'tags' . $ck102[$ci102] . $ck102[$ci102];?><td ><div  id="<?= $A['K']?>" class="cbutton"><?= $A['C']['text']?><span  id="cvalue_<?= $A['K']?>" class="cvalue"><?= $A['T']?></span></div></td>
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
<?$A['oneday'] = strtotime('1 day ago');$A['oneweek'] = strtotime('1 week ago');$A['twoweeks'] = strtotime('2 weeks ago');$A['onemonth'] = strtotime('1 month ago');$A['threemonths'] = strtotime('3 months ago');$A['oneyear'] = strtotime('1 year ago');?><select  id="sinceu" name="sinceu">
<option  value="<?= $A['oneday']?>"><?= _('Yesterday');?></option>
<option  value="<?= $A['oneweek']?>"><?= _('Last week');?></option>
<option  value="<?= $A['twoweeks']?>"><?= _('2 weeks ago');?></option>
<option  value="<?= $A['onemonth']?>"><?= _('Last month');?></option>
<option  value="<?= $A['threemonths']?>" selected="selected"><?= _('3 months ago');?></option>
<option  value="<?= $A['oneyear']?>"><?= _('Last year');?></option>
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
baseCmd = '<?= $A['home-url']?>api/query';
sitePromoTag = '<?if ( isset($A['site_promo_tag']) ) {?><?= $A['site_promo_tag']?><?}?>';
</script>
<script  src="<?= $T->URL('js/radio.js') ?>" ></script>