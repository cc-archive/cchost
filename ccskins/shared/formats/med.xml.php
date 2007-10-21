<?
global $_TV;

?><div  id="cc_list">
<table >
<?
$_TV['by'] = _('by');

$carr101 = $_TV['records'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['R'] = $carr101[ $ck101[ $ci101 ] ];
   
?><tr >
<td  class="cc_list_fileinfo">
<?

if ( isset($_TV['R']['remix_parents']) ) {

?><div  class="cc_list_upstream">
<span  class="cc_list_upstream_title"><?= _('Samples from');?></span>:<br  />
<?

$carr102 = $_TV['R']['remix_parents'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $_TV['P'] = $carr102[ $ck102[ $ci102 ] ];
   $_TV['upname'] = CC_strchop($_TV['P']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['P']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['P']['file_page_url']?>" class="cc_file_link"><?= $_TV['upname']?></a> <?= $_TV['by']?>
          <a  href="<?= $_TV['P']['artist_page_url']?>" class="cc_user_link"><?= $_TV['user']?></a><?

if ( !($ci102 == ($cc102-1)) ) {

?>, <?
} // END: if
} // END: for loop

?></div><?
} // END: if

?><div >
<?
$_TV['upname'] = CC_strchop($_TV['R']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['R']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['R']['license_url']?>" title="<?= $_TV['R']['license_name']?>" class="cc_liclogo">
<img  src="<?= $T->URL('images/lics/small-' . $_TV['R']['license_logo']); ?>" />
</a>
<a  class="cc_file_link" href="<?= $_TV['R']['file_page_url']?>">
          <?= $_TV['upname']?>
        </a> <?= $_TV['by']?>
        <a  class="cc_user_link" href="<?= $_TV['R']['artist_page_url']?>">
          <?= $_TV['user']?>
        </a>
<span  class="cc_upload_date">
          <?= $_TV['R']['upload_date_format']?>
        </span>
<?

if ( !empty($_TV['R']['files']['0']['download_url'])) {

?><a  class="cc_download_url" href="<?= $_TV['R']['files']['0']['download_url']?>">
            <?= $_TV['R']['files']['0']['file_nicname']?>
        </a><?
} // END: if

?></div>
<?

if ( isset($_TV['R']['usertag_links']) ) {
$_TV['tag_array'] = $_TV['R']['usertag_links'];

if ( isset($_TV['tag_array']) ) {

?><div  class="cc_tags">
<?
_template_call_template('tags.xml/taglinks');

?></div><?
} // END: if
} // END: if

if ( !empty($_TV['R']['upload_description_html'])) {

?><div  class="cc_description">
       <?= $_TV['R']['upload_description_html']?>
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

if( !empty($_TV['format_sig']) ) { $_TV['format_signature'] = $_TV['format_sig']; } else {  $_TV['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($_TV['format_signature']);

?></i>
</div>