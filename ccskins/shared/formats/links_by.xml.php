<div  id="cc_list">
<?

$carr101 = $A['records'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><div >
<?
$A['upname'] = CC_strchop($A['R']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['R']['user_real_name'],$A['chop'],$A['dochop']);

?><a  href="<?= $A['R']['file_page_url']?>" class="cc_file_link">
    <?= $A['upname']?>
  </a> <?= $GLOBALS['str_by']?>
  <a  class="cc_user_link" href="<?= $A['R']['artist_page_url']?>">
    <?= $A['user']?>
  </a>
</div><?
} // END: for loop

?><i  class="cc_tagline"><span >
<?

if( !empty($A['format_sig']) ) { $A['format_signature'] = $A['format_sig']; } else {  $A['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($A['format_signature']);

?></span></i>
</div>