<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_license_license_enable(&$T,&$A) {
  ?><input  type="checkbox" name="<?= $A['field']['license']['license_id']?>" id="<?= $A['field']['license']['license_id']?>" checked="<?= empty($A['field']['value']) ? null : $A['field']['value']; ?>"></input>
<label  for="<?= $A['field']['license']['license_id']?>"><?= $A['field']['license']['license_text']?></label>
<?if ( !empty($A['field']['license']['license_url'])) {?><div  class="cc_file_license"><a  href="<?= $A['field']['license']['license_url']?>" target="_new">more info...</a></div><?}?><img  class="cc_license_image" src="<?= $T->URL('images/lics/' . $A['field']['license']['license_logo']) ?>" />
<br  />
<?}
function _t_license_license_choice(&$T,&$A) {
  ?><table >
<?$carr101 = $A['field']['license_choice'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['license'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr ><td ><img  class="cc_license_image" src="<?= $T->URL('images/lics/' .$A['license']['license_logo']) ?>" /></td>
<td ><input  type="radio" checked="<?= $A['license']['license_checked'] ?>" name="upload_license" value="<?= $A['license']['license_id']?>" id="<?= $A['license']['license_id']?>"></input>
<label  for="<?= $A['license']['license_id']?>"><?= $A['license']['license_text']?></label>
</td></tr>
<?}?></table>
<?}
function _t_license_license_rdf(&$T,&$A) {
  ?>
 <?= $A['record']['start_comm']?>
    <?$T->Call('license.xml/raw_license_rdf');
?>
 <?= $A['record']['end_comm']?>
<?}
function _t_license_raw_license_rdf(&$T,&$A) {
  ?><rdf:RDF  xmlns="http://web.resource.org/cc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<Work  rdf:about="">
<dc:title ><?= $A['record']['upload_name']?>
<?if ( !empty($A['record']['year'])) {?><dc:date ><?= $A['record']['year']?><?}?><dc:description ><?= $A['record']['upload_description_html']?>
<dc:creator ><Agent >
<dc:title ><?= $A['record']['user_real_name']?>
</Agent>
<dc:rights ><Agent >
<dc:title ><?= $A['record']['user_real_name']?>
</Agent>
<?if ( !empty($A['record']['dcmitype'])) {?><dc:type  rdf:resource="http://purl.org/dc/dcmitype/<?= $A['record']['dcmitype']?>">
<?}if ( !empty($A['record']['has_parents'])) {$carr102 = $A['record']['remix_parents'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['parent'] = $carr102[ $ck102[ $ci102 ] ];   ?><dc:source  resource="<?= $A['parent']['file_page_url']?>">
<?}}?><license  rdf:resource="<?= $A['record']['license_url']?>"></license>
</Work>
<?if ( !empty($A['record']['files']['0']['file_extra']['sha1'])) {?><Work  rdf:about="urn:sha1:<?= $A['record']['files']['0']['file_extra']['sha1']?>">
<license  rdf:resource="<?= $A['record']['license_url']?>"></license>
</Work>
<?}?><License  rdf:about="<?= $A['record']['license_url']?>">
<?if ( !empty($A['record']['license_permits'])) {$carr103 = CC_split_tags($A['record']['license_permits']);$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $A['pt'] = $carr103[ $ck103[ $ci103 ] ];   ?><permits  rdf:resource="http://web.resource.org/cc/<?= $A['pt']?>"></permits>
<?}}if ( !empty($A['record']['license_required'])) {$carr104 = CC_split_tags($A['record']['license_required']);$cc104= count( $carr104);$ck104= array_keys( $carr104);for( $ci104= 0; $ci104< $cc104; ++$ci104){    $A['rd'] = $carr104[ $ck104[ $ci104 ] ];   ?><permits  rdf:resource="http://web.resource.org/cc/<?= $A['rd']?>"></permits>
<?}}if ( !empty($A['record']['license_prohibits'])) {$carr105 = CC_split_tags($A['record']['license_prohibits']);$cc105= count( $carr105);$ck105= array_keys( $carr105);for( $ci105= 0; $ci105< $cc105; ++$ci105){    $A['ph'] = $carr105[ $ck105[ $ci105 ] ];   ?><prohibits  rdf:resource="http://web.resource.org/cc/<?= $A['ph']?>"></prohibits>
<?}}?></License>

<?}?>