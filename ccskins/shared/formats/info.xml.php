<?

if( !empty($A['detail']) ) { $A['idetail'] = $A['detail']; } else {  $A['idetail'] = null; } 
$A['R'] = CC_get_details($A['records']['0']['upload_id'],$A['idetail']);
$A['upname'] = CC_strchop($A['R']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['R']['user_real_name'],$A['chop'],$A['dochop']);

?><div  class="cc_list" id="_info_<?= $A['R']['upload_id']?>">
<?

if ( !empty($A['R']['local_menu'])) {

?><div  class="upload_menu_outer" id="upload_menu_outer_<?= $A['R']['upload_id']?>">
<div  class="upload_menu_title" id="upload_menu_title_<?= $A['R']['upload_id']?>">menu</div>
<?

$carr101 = $A['R']['local_menu'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['grp'] = $carr101[ $ck101[ $ci101 ] ];
   
$carr102 = $A['grp'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $A['item'] = $carr102[ $ck102[ $ci102 ] ];
   
?><div ><a  id="<?= $A['item']['id']?>" href="<?= $A['item']['action']?>"><span ><?= $A['item']['menu_text']?></span></a></div>
<?
} // END: for loop
} // END: for loop

?></div><?
} // END: if

?><a  href="<?= $A['R']['license_url']?>" title="<?= $A['R']['license_name']?>" class="cc_liclogo">
<img  src="<?= $T->URL('images/lics/small-' . $A['R']['license_logo']); ?>" />
</a>
<?

if ( !empty($A['idetail'])) {

?><h3  class="dtitle"><a  href="<?= $A['R']['file_page_url']?>"><?= $A['upname']?></a> <?= $GLOBALS['str_by']?> 
              <a  href="<?= $A['R']['artist_page_url']?>"><?= $A['user']?></a>
</h3>
<?
} // END: if

if ( !empty($A['R']['upload_extra']['featuring'])) {

?><div >Featuring: <b ><?= $A['R']['upload_extra']['featuring']?></b></div><?
} // END: if

?><div  class="cc_upload_date">
    <?= $A['R']['upload_date_format']?>
  </div>
<?
$A['tag_array'] = $A['R']['upload_taglinks'];

?><div  class="cc_tags">
      Tags: <?
$T->Call('tags.xml/taglinks');

?></div>
<?

if ( !empty($A['R']['upload_description_html'])) {

?><div  class="gd_description" id="iddesc_<?= $A['R']['upload_id']?>">
<div  style="padding: 10px;">
<span ><?= CC_strchop($A['R']['upload_description_text'],200);?></span>
</div>
</div><?
} // END: if

?><table  class="files_table">
<tr >
<td  class="column files_column">
<span  class="title files_title"><?= $GLOBALS['str_files'] ;?></span>:<br  />
<?

$carr103 = $A['R']['files'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $A['F'] = $carr103[ $ck103[ $ci103 ] ];
   
?>
            <?= $A['F']['file_nicname']?>: <a  href="<?= $A['F']['download_url']?>">download</a> <?= $A['F']['file_filesize']?><br  />
<?
} // END: for loop

?></td>
<td >&nbsp;</td>
<?

if ( isset($A['R']['remix_parents']) ) {

?><td  class="column parents_column">
<div >
<table >
<tr >
<td >
<img  src="<?= $T->URL('images/downloadicon.gif'); ?>" />
</td>
<td >
<span  class="title parents_title"><?= $GLOBALS['str_uses_samples_from'] ;?></span>:<br  />
<?

$carr104 = $A['R']['remix_parents'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $A['P'] = $carr104[ $ck104[ $ci104 ] ];
   $A['upname'] = CC_strchop($A['P']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['P']['user_real_name'],$A['chop'],$A['dochop']);

?><a  href="<?= $A['P']['file_page_url']?>" class="cc_file_link"><?= $A['upname']?></a> <?= $GLOBALS['str_by']?>
                <a  href="<?= $A['P']['artist_page_url']?>" class="cc_user_link"><?= $A['user']?></a><?

if ( !($ci104 == ($cc104-1)) ) {

?><br  />
<?
} // END: if
} // END: for loop

?></td>
</tr>
</table>
</div>
</td><?
} // END: if

?><td >&nbsp</td>
<?

if ( isset($A['R']['remix_children']) ) {

?><td  class="column children_column">
<div >
<table >
<tr >
<td >
<img  src="<?= $T->URL('images/uploadicon.gif') ?>" />
</td>
<td >
<span  class="title children_title"><?= $GLOBALS['str_samples_from_here'] ;?></span>:<br  />
<?

$carr105 = $A['R']['remix_children'];
$cc105= count( $carr105);
$ck105= array_keys( $carr105);
for( $ci105= 0; $ci105< $cc105; ++$ci105)
{ 
   $A['P'] = $carr105[ $ck105[ $ci105 ] ];
   $A['upname'] = CC_strchop($A['P']['upload_name'],$A['chop'],$A['dochop']);
$A['user'] = CC_strchop($A['P']['user_real_name'],$A['chop'],$A['dochop']);

?><a  href="<?= $A['P']['file_page_url']?>" class="cc_file_link"><?= $A['upname']?></a> <?= $GLOBALS['str_by']?>
                <a  href="<?= $A['P']['artist_page_url']?>" class="cc_user_link"><?= $A['user']?></a>
<?

if ( !($ci105 == ($cc105-1)) ) {

?><br  />
<?
} // END: if
} // END: for loop

?></td>
</tr>
</table>
</div>
</td><?
} // END: if

?></tr>
</table>
</div>