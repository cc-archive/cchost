<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');



//------------------------------------- 
function _t_publicize_publicize(&$T,&$A) {
  
  if ( !empty($A['PUB']['intro'])) {
  
?><div  id="pubintro">
<?

    if ( !empty($A['PUB']['user_avatar_url'])) {
    
?><img  src="<?= $A['PUB']['user_avatar_url']?>" /><?
} // END: if
      
?><div ><?= $A['PUB']['intro']?></div>
</div><?
} // END: if
    
?><div  id="pubinstructions">
<p ><?= $A['PUB']['step1']?></p>
</div>
<form  id="puboptions_form">
<table  id="puboptions1">
<?

    $carr101 = $A['PUB']['combos'];
    $cc101= count( $carr101);
    $ck101= array_keys( $carr101);
    for( $ci101= 0; $ci101< $cc101; ++$ci101)
    { 
       $A['combo'] = $carr101[ $ck101[ $ci101 ] ];
       
?><tr >
<th ><?= $A['combo']['title']?><?

    if ( !empty($A['combo']['help'])) {
    
?><span  class="pubhelp"><?= $A['combo']['help']?></span><?
} // END: if
      
?></th>
<td >
<select  id="<?= $A['combo']['id']?>" name="<?= $A['combo']['name']?>" class="<?= $A['combo']['class']?>">
<?

      $carr102 = $A['combo']['opts'];
      $cc102= count( $carr102);
      $ck102= array_keys( $carr102);
      for( $ci102= 0; $ci102< $cc102; ++$ci102)
      { 
         $A['opt'] = $carr102[ $ck102[ $ci102 ] ];
         
      if ( !empty($A['opt']['selected'])) {
      
?><option  value="<?= $A['opt']['value']?>" selected="selected"><?= $A['opt']['text']?></option><?
} // END: if
        
      if ( !($A['opt']['selected']) ) {
      
?><option  value="<?= $A['opt']['value']?>"><?= $A['opt']['text']?></option><?
} // END: if
        } // END: for loop
      
?></select>
</td>
</tr><?
} // END: for loop
    
?></table>
<?

    $carr103 = $A['PUB']['hiddens'];
    $cc103= count( $carr103);
    $ck103= array_keys( $carr103);
    for( $ci103= 0; $ci103< $cc103; ++$ci103)
    { 
       $A['hide'] = $carr103[ $ck103[ $ci103 ] ];
       
?><input  type="hidden" value="<?= $A['hide']['value']?>" name="<?= $A['hide']['name']?>" id="<?= $A['hide']['name']?>"></input>
<?
} // END: for loop
    
?><span  id="type_target"></span>
</form>
<div  id="pubinstructions">
<p ><?= $A['PUB']['step2']?></p>
</div>
<p  id="target_text_p">
<textarea  id="target_text" name="target_text">
</textarea>
</p>
<div  id="preview_container" class="light_color med_bg dark_border">
<table  id="seehtml">
<tr ><td ><a  href="javascript://toggle preview" id="preview_button_link" class="cc_gen_button">
<span  id="preview_button"><?= $A['PUB']['seehtml']?></span></a></td></tr>
</table>
<b ><?= $A['PUB']['preview']?>:</b>
<p  id="preview_warn" style="display:block"><?= $A['PUB']['previewwarn']?></p>
<p  id="html_warn" style="display:none"><?= $A['PUB']['htmlwarn']?></p>
<div  id="preview_block">
<div  id="preview" style="display:block"><span >&nbsp;</span>
</div>
<div  id="src_preview" style="display:none;">
</div>
</div>
</div>
<script  type="text/javascript" src="<?= $T->URL('js/publicize.js') ?>"></script>
<script >
  //<!--
  seeHTML = '<?= $A['PUB']['seehtml']?>';
  showFormatted = '<?= $A['PUB']['showformatted']?>';
  username = '<?= $A['PUB']['user_name']?>';
  new ccPublicize('<?= $A['PUB']['user_name']?>');
  //-->
</script>
<?
} // END: function publicize
  
?>