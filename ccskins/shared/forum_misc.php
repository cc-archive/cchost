<?

function _t_forum_misc_forum_admin($T,&$A) {
  
?><table  class="forumadmin">
<?

    $carr104 = $A['fadmin']['forum_groups'];
    $cc104= count( $carr104);
    $ck104= array_keys( $carr104);
    for( $ci104= 0; $ci104< $cc104; ++$ci104)
    { 
       $A['gitem'] = $carr104[ $ck104[ $ci104 ] ];
       
?><tr >
<td ><i >Group:</i>
<b ><?= $A['gitem']?></b></td>
<td ><a  href="<?= $A['fadmin']['edit_forum_group_link']?>/<?= $ck104[$ci104]?>">[ <?= $A['fadmin']['edit_forum_group_text']?> ] </a></td>
<td ><a  href="<?= $A['fadmin']['del_forum_group_link']?>/<?= $ck104[$ci104]?>">[ <?= $A['fadmin']['del_forum_group_text']?> ] </a></td>
</tr><?
} // END: for loop
    
    $carr105 = $A['fadmin']['forums'];
    $cc105= count( $carr105);
    $ck105= array_keys( $carr105);
    for( $ci105= 0; $ci105< $cc105; ++$ci105)
    { 
       $A['fitem'] = $carr105[ $ck105[ $ci105 ] ];
       
?><tr >
<td ><i >Forum:</i> <?= $A['fitem']['forum_group_name']?>::<b ><?= $A['fitem']['forum_name']?></b></td>
<td ><a  href="<?= $A['fadmin']['edit_forum_link']?>/<?= $A['fitem']['forum_id']?>">[ <?= $A['fadmin']['edit_forum_text']?> ] </a></td>
<td ><a  href="<?= $A['fadmin']['del_forum_link']?>/<?= $A['fitem']['forum_id']?>">[ <?= $A['fadmin']['del_forum_text']?> ] </a></td>
</tr><?
} // END: for loop
    
?></table>
<hr  />
<table >
<tr >
<td >
<a  class="cc_gen_button" href="<?= $A['fadmin']['add_forum_group_link']?>"><span ><?= $A['fadmin']['add_forum_group_text']?></span></a>
</td>
<td >
<?

  if ( !empty($A['fadmin']['forum_groups'])) {
  
?><a  class="cc_gen_button" href="<?= $A['fadmin']['add_forum_link']?>"><span ><?= $A['fadmin']['add_forum_text']?></span></a><?
} // END: if
    
?></td>
</tr>
</table>
<?
} // END: function forum_admin
  


?>