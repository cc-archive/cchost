<?
global $_TV;

if( !empty($_TV['detail']) ) { $_TV['idetail'] = $_TV['detail']; } else {  $_TV['idetail'] = null; } $_TV['by'] = _('by');
$_TV['R'] = CC_get_details($_TV['records']['0']['upload_id'],$_TV['idetail']);
$_TV['upname'] = CC_strchop($_TV['R']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['R']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><div  class="cc_list" id="_info_<?= $_TV['R']['upload_id']?>">
<?

if ( !empty($_TV['R']['local_menu'])) {

?><div  class="upload_menu_outer" id="upload_menu_outer_<?= $_TV['R']['upload_id']?>">
<div  class="upload_menu_title" id="upload_menu_title_<?= $_TV['R']['upload_id']?>">menu</div>
<?

$carr101 = $_TV['R']['local_menu'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['grp'] = $carr101[ $ck101[ $ci101 ] ];
   
$carr102 = $_TV['grp'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $_TV['item'] = $carr102[ $ck102[ $ci102 ] ];
   
?><div ><a  id="<?= $_TV['item']['id']?>" href="<?= $_TV['item']['action']?>"><span ><?= $_TV['item']['menu_text']?></span></a></div>
<?
} // END: for loop
} // END: for loop

?></div><?
} // END: if

?><a  href="<?= $_TV['R']['license_url']?>" title="<?= $_TV['R']['license_name']?>" class="cc_liclogo">
<img  src="<?= $_TV['root-url']?>ccimages/lics/small-<?= $_TV['R']['license_logo']?>" />
</a>
<?

if ( !empty($_TV['idetail'])) {

?><h3  class="dtitle"><a  href="<?= $_TV['R']['file_page_url']?>"><?= $_TV['upname']?></a> <?= $_TV['by']?> 
              <a  href="<?= $_TV['R']['artist_page_url']?>"><?= $_TV['user']?></a>
</h3>
<?
} // END: if

if ( !empty($_TV['R']['upload_extra']['featuring'])) {

?><div >Featuring: <b ><?= $_TV['R']['upload_extra']['featuring']?></b></div><?
} // END: if

?><div  class="cc_upload_date">
    <?= $_TV['R']['upload_date_format']?>
  </div>
<?
$_TV['tag_array'] = $_TV['R']['upload_taglinks'];

?><div  class="cc_tags">
      Tags: <?
_template_call_template('tags.xml/taglinks');

?></div>
<?

if ( !empty($_TV['R']['upload_description_html'])) {

?><div  class="gd_description" id="iddesc_<?= $_TV['R']['upload_id']?>">
<div  style="padding: 10px;">
<span ><?= CC_strchop($_TV['R']['upload_description_text'],200);?></span>
</div>
</div><?
} // END: if

?><table  class="files_table">
<tr >
<td  class="column files_column">
<span  class="title files_title"><?= _('Files');?></span>:<br  />
<?

$carr103 = $_TV['R']['files'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $_TV['F'] = $carr103[ $ck103[ $ci103 ] ];
   
?>
            <?= $_TV['F']['file_nicname']?>: <a  href="<?= $_TV['F']['download_url']?>">download</a> <?= $_TV['F']['file_filesize']?><br  />
<?
} // END: for loop

?></td>
<td >&nbsp;</td>
<?

if ( isset($_TV['R']['remix_parents']) ) {

?><td  class="column parents_column">
<div >
<table >
<tr >
<td >
<img  src="<?= $_TV['root-url']?>cctemplates/ccmixter/downloadicon.gif" />
</td>
<td >
<span  class="title parents_title"><?= _('Uses samples from');?></span>:<br  />
<?

$carr104 = $_TV['R']['remix_parents'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $_TV['P'] = $carr104[ $ck104[ $ci104 ] ];
   $_TV['upname'] = CC_strchop($_TV['P']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['P']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['P']['file_page_url']?>" class="cc_file_link"><?= $_TV['upname']?></a> <?= $_TV['by']?>
                <a  href="<?= $_TV['P']['artist_page_url']?>" class="cc_user_link"><?= $_TV['user']?></a><?

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

if ( isset($_TV['R']['remix_children']) ) {

?><td  class="column children_column">
<div >
<table >
<tr >
<td >
<img  src="<?= $_TV['root-url']?>cctemplates/ccmixter/uploadicon.gif" />
</td>
<td >
<span  class="title children_title"><?= _('Samples from here are used in');?></span>:<br  />
<?

$carr105 = $_TV['R']['remix_children'];
$cc105= count( $carr105);
$ck105= array_keys( $carr105);
for( $ci105= 0; $ci105< $cc105; ++$ci105)
{ 
   $_TV['P'] = $carr105[ $ck105[ $ci105 ] ];
   $_TV['upname'] = CC_strchop($_TV['P']['upload_name'],$_TV['chop'],$_TV['dochop']);
$_TV['user'] = CC_strchop($_TV['P']['user_real_name'],$_TV['chop'],$_TV['dochop']);

?><a  href="<?= $_TV['P']['file_page_url']?>" class="cc_file_link"><?= $_TV['upname']?></a> <?= $_TV['by']?>
                <a  href="<?= $_TV['P']['artist_page_url']?>" class="cc_user_link"><?= $_TV['user']?></a>
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