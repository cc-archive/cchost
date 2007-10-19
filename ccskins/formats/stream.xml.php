<?
global $_TV;

?><div  id="cc_list">
<?
$_TV['by'] = _('by');

$carr101 = $_TV['records'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><div >
<?

if ( !empty($_TV['R']['stream_link'])) {

?><span >
<a  href="<?= $_TV['R']['stream_link']['url']?>" class="cc_streamlink">&nbsp;</a>
</span><?
} // END: if
$_TV['upname'] = CC_strchop($_TV['R']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['R']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['R']['file_page_url']?>" class="cc_file_link">
    <?= $_TV['upname']?>
  </a> <?= $_TV['by']?> 
  <a  class="cc_user_link" href="<?= $_TV['R']['artist_page_url']?>">
    <?= $_TV['user']?>
  </a>
</div><?
} // END: for loop

?><i  class="cc_tagline">
<?

if( !empty($_TV['format_sig']) ) { $_TV['format_signature'] = $_TV['format_sig']; } else {  $_TV['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($_TV['format_signature']);

?></i>
</div>