<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/radio.css'); ?>" />
<h1><?= $T->String('str_radio_remix') ?></h1>
<div id="radio_container">
<form  id="channel_form">
<table  cellpadding="0" cellspacing="0" id="outerframe">
<tr ><td  style="width:510px">
<div  id="djback" class="box">
<table  cellpadding="0" cellspacing="0">
<tr ><td  id="cell1">
<div  id="channel_intro">
<h3 ><?= $T->String('str_radio_station') ;?></h3>
<p ><?= sprintf($T->String('str_radio_create'),$A['site-title']) ;?></p>
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
<tr >
<td  id="cell3">
<table  cellspacing="0" cellpadding="0" id="options">
    <tr >
        <? $chart = cc_get_config('chart');
        if( !empty($chart['ratings']) )
        {
        ?> <td > <?

            if( empty($chart['thumbs_up']) )
            {?>
                <span  class="opt_label"><?= $T->String('str_ratings')?>:</span>
                <select  id="score" name="score">
                <option  value="500">5</option>
                <option  value="450"><?= sprintf( $T->String('str_filter_d_or_above'), '4.5' ) ?></option>
                <option  value="400" selected="selected"><?= sprintf( $T->String('str_d_or_above'), '4' ) ;?></option>
                <option  value="350"><?= sprintf( $T->String('str_filter_d_or_above'), '3.5' ) ?></option>
                <option  value="300"><?= sprintf( $T->String('str_filter_d_or_above'), '3' ) ;?></option>
                <option  value="0"><?= $T->String('str_filter_all') ;?></option>
                </select>
            <? } else { ?>
                <span  class="opt_label"><?= $T->String('str_recommends')?>:</span>
                <select  id="num_scores" name="num_scores">
                <option  value="20"><?= sprintf( $T->String('str_filter_d_or_above'), '20' ) ?></option>
                <option  value="10"><?= sprintf( $T->String('str_filter_d_or_above'), '10' ) ?></option>
                <option  value="5" selected="selected"><?= sprintf( $T->String('str_filter_d_or_above'), '5' ) ;?></option>
                <option  value="0"><?= $T->String('str_filter_all') ;?></option>
                </select>
            <? } ?>
            </td>
        <? } // ratings enabled ?>
        <td >
            <span  class="opt_label"><?= $T->String('str_filter_since') ?>:</span>
            <select  id="sinceu" name="sinceu">
            <option  value="<?= strtotime('1 day ago')?>"><?= $T->String('str_filter_yesterday')?></option>
            <option  value="<?= strtotime('1 week ago')?>"><?= $T->String('str_filter_last_week')?></option>
            <option  value="<?= strtotime('2 weeks ago')?>"><?= $T->String('str_filter_2_weeks_ago')?></option>
            <option  value="<?= strtotime('1 month ago')?>"><?= $T->String('str_filter_last_month')?></option>
            <option  value="<?= strtotime('3 months ago')?>" selected="selected"><?= $T->String('str_filter_3_months_ago')?></option>
            <option  value="<?= strtotime('1 year ago')?>"><?= $T->String('str_filter_last_year')?></option>
            <option  value="0"><?= $T->String('str_filter_all_time')?></option>
            </select>
        </td>
        <td >
            <span  class="opt_label"><?= $T->String('str_filter_this_many')?>:</span>
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
                <div  class="cc_placy_page_link"><a  href="" id="playlink"><span ><?= $T->String('str_play')?></span></a></div>
                <div  class="cc_stream_page_link"><a  href="" id="streamlink"><span ><?= $T->String('str_stream')?></span></a></div>
                <div  class="cc_podcast_link"><a  href="" id="podlink"><span ><?= $T->String('str_podcast')?></span></a></div>
            </div>
            <div  id="countresults"></div>
        </td>
    </tr>
    </table>
</td></tr>
</table>
</div>
</td>
</tr>
</table>
</form>
</div><!-- radio container -->
<script >
baseCmd = '<?= $A['home-url']?>api/query';
sitePromoTag = '<?= empty($A['site_promo_tag']) ? '' : $A['site_promo_tag'] ?>';
</script>
<script  src="<?= $T->URL('js/radio.js'); ?>" ></script>