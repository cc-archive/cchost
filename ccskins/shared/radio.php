<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/radio.css'); ?>" />
<h1  id="channel_title"><?= $GLOBALS['str_remix_radio'] ;?></h1>

<form  id="channel_form">
<table  cellpadding="0" cellspacing="0" id="outerframe">
<tr ><td  style="width:510px">
<div  id="djback" class="cc_round_box_bw">
<table  cellpadding="0" cellspacing="0">
<tr ><td  id="cell1">
<div  id="channel_intro">
<h3 ><?= $GLOBALS['str_create_your_own'] ;?></h3>
<p ><?= sprintf($GLOBALS['atr_create_your_own_stream'],$A['site-title']) ;?></p>
</div>
</td></tr>
<tr ><td  id="cell2">
<?
$A['channels'] = CC_get_config('channels');

?><table  id="channels" cellspacing="0" cellpadding="0">
<?
$A['channel_rows'] = array_chunk($A['channels'],5);

$carr101 = $A['channel_rows'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><tr >
<?

$carr102 = $A['R'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $A['C'] = $carr102[ $ck102[ $ci102 ] ];
   $A['T'] = str_replace(',','+',$A['C']['tags']);
$A['K'] = 'tags' . $ck102[$ci102] . $ck102[$ci102];

?><td ><div  id="<?= $A['K']?>" class="cbutton"><?= $A['C']['text']?><span  id="cvalue_<?= $A['K']?>" class="cvalue"><?= $A['T']?></span></div></td>
<?
} // END: for loop

?></tr>
<?
} // END: for loop

?></table>
</td></tr>
<tr ><td  id="cell3">
<table  cellspacing="0" cellpadding="0" id="options">
<tr >
<? $chart = cc_get_config('chart');
if( !empty($chart['ratings']) )
{
?> <td > <?

    if( empty($chart['thumbs_up']) )
    {?>
        <span  class="opt_label"><?= $GLOBALS['str_ratings']?>:</span>
        <select  id="score" name="score">
        <option  value="500">5</option>
        <option  value="450"><?= sprintf( $GLOBALS['str_or_above'], '4.5' ) ?></option>
        <option  value="400" selected="selected"><?= sprintf( $GLOBALS['str_or_above'], '4' ) ;?></option>
        <option  value="350"><?= sprintf( $GLOBALS['str_or_above'], '3.5' ) ?></option>
        <option  value="300"><?= sprintf( $GLOBALS['str_or_above'], '3' ) ;?></option>
        <option  value="0"><?= $GLOBALS['all'] ;?></option>
        </select>
    <? } else { ?>
        <span  class="opt_label"><?= $GLOBALS['str_recommends']?>:</span>
        <select  id="num_scorea" name="num_scores">
        <option  value="20"><?= sprintf( $GLOBALS['str_or_above'], '20' ) ?></option>
        <option  value="10"><?= sprintf( $GLOBALS['str_or_above'], '10' ) ?></option>
        <option  value="5" selected="selected"><?= sprintf( $GLOBALS['str_or_above'], '5' ) ;?></option>
        <option  value="0"><?= $GLOBALS['all'] ;?></option>
        </select>
    <? } ?>
    </td>
<? } // ratings enabled ?>
<td >
<span  class="opt_label"><?= $GLOBALS['str_since'] ?>:</span>
<?
$A['oneday'] = strtotime('1 day ago');
$A['oneweek'] = strtotime('1 week ago');
$A['twoweeks'] = strtotime('2 weeks ago');
$A['onemonth'] = strtotime('1 month ago');
$A['threemonths'] = strtotime('3 months ago');
$A['oneyear'] = strtotime('1 year ago');

?><select  id="sinceu" name="sinceu">
<option  value="<?= $A['oneday']?>"><?= $GLOBALS['str_yesterday']?></option>
<option  value="<?= $A['oneweek']?>"><?= $GLOBALS['str_last_week']?></option>
<option  value="<?= $A['twoweeks']?>"><?= $GLOBALS['str_2_weeks_ago']?></option>
<option  value="<?= $A['onemonth']?>"><?= $GLOBALS['str_last_month']?></option>
<option  value="<?= $A['threemonths']?>" selected="selected"><?= $GLOBALS['str_3_months_ago']?></option>
<option  value="<?= $A['oneyear']?>"><?= $GLOBALS['str_last_year']?></option>
<option  value="0"><?= $GLOBALS['str_all_time']?></option>
</select>
</td>
<td >
<span  class="opt_label"><?= $GLOBALS['str_this_many']?>:</span>
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
<div  class="cc_placy_page_link"><a  href="" id="playlink"><span ><?= $GLOBALS['str_play']?></span></a></div>
<div  class="cc_stream_page_link"><a  href="" id="streamlink"><span ><?= $GLOBALS['str_stream']?></span></a></div>
<div  class="cc_podcast_link"><a  href="" id="podlink"><span ><?= $GLOBALS['str_podcast']?></span></a></div>
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
</div>
<script >
baseCmd = '<?= $A['home-url']?>api/query';
sitePromoTag = '<?

if ( isset($A['site_promo_tag']) ) {

?><?= $A['site_promo_tag']?><?
} // END: if

?>';
</script>
<script  src="<?= $T->URL('js/radio.js'); ?>" ></script>