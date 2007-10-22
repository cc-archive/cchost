<div  id="cc_list">
<table >
<?

$carr101 = $A['records'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><tr >
<td  class="cc_list_fileinfo">
<?

if ( isset($A['R']['remix_parents']) ) {

?><div  class="cc_list_upstream">
<span  class="cc_list_upstream_title"><?= $GLOBALS['str_samples_from'] ?></span>:<br  />
<?

$carr102 = $A['R']['remix_parents'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $A['P'] = $carr102[ $ck102[ $ci102 ] ];
   $A['upname'] = CC_strchop($A['P']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['P']['user_real_name'],$A['chop'],$A['dochop']);

?><a  href="<?= $A['P']['file_page_url']?>" class="cc_file_link"><?= $A['upname']?></a> <?= $GLOBALS['str_by']?>
          <a  href="<?= $A['P']['artist_page_url']?>" class="cc_user_link"><?= $A['user']?></a><?

if ( !($ci102 == ($cc102-1)) ) {

?>, <?
} // END: if
} // END: for loop

?></div><?
} // END: if

?><div >
<?
$A['upname'] = CC_strchop($A['R']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['R']['user_real_name'],$A['chop'],$A['dochop']);

?><a  href="<?= $A['R']['license_url']?>" title="<?= $A['R']['license_name']?>" class="cc_liclogo">
<img  src="<?= $T->URL('images/lics/small-' . $A['R']['license_logo']); ?>" />
</a>
<a  class="cc_file_link" href="<?= $A['R']['file_page_url']?>">
          <?= $A['upname']?>
        </a> <?= $GLOBALS['str_by']?>
        <a  class="cc_user_link" href="<?= $A['R']['artist_page_url']?>">
          <?= $A['user']?>
        </a>
<span  class="cc_upload_date">
          <?= $A['R']['upload_date_format']?>
        </span>
<?

if ( !empty($A['R']['files']['0']['download_url'])) {

?><a  class="cc_download_url" href="<?= $A['R']['files']['0']['download_url']?>">
            <?= $A['R']['files']['0']['file_nicname']?>
        </a><?
} // END: if

?></div>
<?

if ( isset($A['R']['usertag_links']) ) {
$A['tag_array'] = $A['R']['usertag_links'];

if ( isset($A['tag_array']) ) {

?><div  class="cc_tags">
<?
_template_call_template('tags.xml/taglinks');

?></div><?
} // END: if
} // END: if

if ( !empty($A['R']['upload_description_html'])) {

?><div  class="cc_description">
       <?= $A['R']['upload_description_html']?>
     </div><?
} // END: if

?></td>
</tr>
<tr >
<td >
</td>
</tr>
<?
} // END: for loop

?></table>
<i  class="cc_tagline">
<?

if( !empty($A['format_sig']) ) { $A['format_signature'] = $A['format_sig']; } else {  $A['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($A['format_signature']);

?></i>
</div>