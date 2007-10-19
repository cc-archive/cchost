<?
global $_TV;

?><div  id="cc_list">
<?
$_TV['by'] = _('by');

?><ul >
<?

$carr101 = $_TV['records'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><li >
<?
$_TV['upname'] = CC_strchop($_TV['R']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['R']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['R']['file_page_url']?>" class="cc_file_link">
    <?= $_TV['upname']?>
  </a>
<a  class="cc_user_link" href="<?= $_TV['R']['artist_page_url']?>">
    <?= $_TV['user']?>
  </a>
</li><?
} // END: for loop

?></ul>
<i  class="cc_tagline"><span >
<?

if( !empty($_TV['format_sig']) ) { $_TV['format_signature'] = $_TV['format_sig']; } else {  $_TV['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($_TV['format_signature']);

?></span></i>
</div>