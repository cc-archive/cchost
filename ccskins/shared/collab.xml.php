<?


//------------------------------------- 
function _t_collab_show_collabs($T,&$A) {
   
    $collabcss = ccd($T->Search( 'css/collab.css' ));
    $playlistcss = ccd($T->Search( 'css/playlist.css' ));
    $bg  = ccd($T->Search( 'images/bg_fade.gif' )); 
    if( !empty($bg) )
        print "<style>.collab_entry {background: url('{$bg}') top repeat-x;}</style>";
?>
<link  rel="stylesheet" type="text/css" href="<?= $collabcss ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $playlistcss ?>" title="Default Style"></link>
<table  style="width:95%">
<?
$A['rows'] = array_chunk($A['collabs']['collab_rows'], 2);

$carr101 = $A['rows'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['col'] = $carr101[ $ck101[ $ci101 ] ];
   
?><tr >
<?

$carr102 = $A['col'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $A['row'] = $carr102[ $ck102[ $ci102 ] ];
   
?><td  style="vertical-align: top;width:50%;">
<div  class="collab_entry">
<span  class="collab_date"><?= CC_datefmt($A['row']['collab_date'],'M d, Y');?></span>
<a  class="collab_name" href="<?= $A['home-url']?>collab/<?= $A['row']['collab_id']?>"><?= CC_strchop($A['row']['collab_name'],35);?></a>
<br  />
<?

$carr103 = $A['row']['users'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $A['u'] = $carr103[ $ck103[ $ci103 ] ];
   
?><a  href="<?= $A['home-url']?>people/<?= $A['u']['user_name']?>"><?= $A['u']['user_real_name']?></a><?

if ( !($ci103 == ($cc103-1)) ) {

?>, <?
} // END: if
} // END: for loop

if ( !empty($A['row']['uploads'])) {

$carr104 = $A['row']['uploads'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $A['item'] = $carr104[ $ck104[ $ci104 ] ];
   
if ( !empty($A['item']['fplay_url'])) {

?><div  class="tdc cc_playlist_pcontainer"><a  class="cc_player_button cc_player_hear" id="_ep_1_<?= $A['item']['upload_id']?>" href="<?= $A['item']['fplay_url']?>">
</a>
</div><?
} // END: if
} // END: for loop
} // END: if

?><br  style="clear:both" />
</div>
</td><?
} // END: for loop

?></tr><?
} // END: for loop

?></table>
<?
//_template_call_template($A['prev_next_links']);
//_template_call_template('playerembed.xml/eplayer');
} // END: function show_collabs


//------------------------------------- 
function _t_collab_show_collab($T,&$A) {
   

?><div  id="collab_ajax_msg"></div>
<?
$A['collab_id'] = $A['collab']['collab']['collab_id'];

?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/collab.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/topics.css') ?>" title="Default Style"></link>
<fieldset >
<legend ><?= $GLOBALS['str_info']?></legend>
<?

if ( !empty($A['collab']['is_owner'])) {

?><div  style="float:right">
<a  id="commentcommand" href="<?= $A['home-url']?>collab/edit/<?= $A['collab_id']?>"><span >edit</span></a>
</div><?
} // END: if

?><div  class="cc_collab_by">Created by: <a  href="<?= $A['home-url']?>people/<?= $A['collab']['collab']['user_name']?>"><?= $A['collab']['collab']['user_real_name']?></a> 
          <?= $A['collab']['collab']['collab_date']?></div>
<div  class="cc_collab_desc"><?= $A['collab']['collab']['collab_desc']?></div>
</fieldset>
<div  class="cc_collab_fields">
<fieldset >
<legend ><?= $GLOBALS['str_artists'] ?></legend>
<div  class="user_lines">
<div  id="user_inserter"></div>
</div>
<div  id="invite_container">
</div>
</fieldset>
<fieldset >
<legend ><?= $GLOBALS['str_files'] ?> </legend>
<div  class="file_list" id="file_list">
</div>
<?

if ( !empty($A['collab']['is_member'])) {

?><iframe  style="display:none;" name="upload_frame"></iframe>
<form  target="upload_frame" enctype="multipart/form-data" action="<?= $A['home-url']?>collab/upload/file/<?= $A['collab_id']?>" method="post" id="upform" name="upform">Upload file: <select  name="uptype" id="uptype">
<option  value="remix">remix</option><option  value="sample">sample</option><option  value="acappella">a cappella</option></select>
<input  type="file" id="upfile" name="upfile"></input> name: <input  name="upname" id="upname" type="text"></input><select  name="lic" id="lic">
<?

$carr105 = $A['collab']['lics'];
$cc105= count( $carr105);
$ck105= array_keys( $carr105);
for( $ci105= 0; $ci105< $cc105; ++$ci105)
{ 
   $A['lic'] = $carr105[ $ck105[ $ci105 ] ];
   
?><option  value="<?= $A['lic']['license_id']?>"><?= $A['lic']['license_name']?></option><?
} // END: for loop

?></select>
<button  id="fileok">ok</button>
</form>
<div  id="upcover" style="position:absolute;display:none;background-color:white;">
<img  style="margin-left:45%" src="<?= $T->URL('images/spinner.gif') ?>" /></div>
<?
} // END: if

?></fieldset>
<?

if ( !empty($A['collab']['is_member'])) {

?><fieldset >
<legend ><?= $GLOBALS['str_conversation'] ?></legend>
<p >This conversation thread is private and only visible to members of the project:</p>
<?
$A['topics'] = $A['collab']['topics'];
_template_call_template('topics.xml/list_topics');

?><div  class="c_commands">
<a  href="<?= $A['home-url']?>collab/topic/add/<?= $A['collab_id']?>" id="commentcommand"><span >Add Topic</span></a>
</div>
</fieldset><?
} // END: if

?></div>
<script  src="<?= $T->URL('js/autocomp.js'); ?>"></script>
<script  src="<?= $T->URL('js/collab.js'); ?>"></script>
<script >
    var collab_template = '<' + 'div class="user_line" id="_user_line_#{user_name}">' +
            '<' + 'div class="user" ><' + 'a href="'+home_url+'people/#{user_name}">#{user_real_name}<' + '/a><' + '/div>' +
            '<' + 'div class="role">#{role}<' + '/div>' +
            '<' + 'div class="credit" id="_credit_#{user_name}">#{credit}<' + '/div>' +
  <?

if ( !empty($A['collab']['is_owner'])) {

?>
     '<' + 'div><' + 'a href="javascript://edit credit" id="_user_credit_#{user_name}" class="user_cmd edit_credit"><' 
              + 'span>credit<' + '/span><' + '/a><' + '/div>' +
     '<' + 'div><' + 'a href="javascript://remove user" id="_user_remove_#{user_name}" class="user_cmd"><' 
              + 'span>remove<' + '/span><' + '/a><' + '/div>' +
  <?
} // END: if

if ( !empty($A['collab']['is_member'])) {

?>
            '<' + 'div>' +
            '    <' + 'a href="javascript://contact" id="_contact_#{user_name}" class="user_cmd edit_contact"><' + 'span>send email' 
                 + '<' + '/span><' + '/a> ' +
            '<' + '/div>' +
  <?
} // END: if

?>
           '<' + '/div>';
     var cu = new ccCollab('<?= $A['collab_id']?>','<?= $A['collab']['is_member']?>','<?= $A['collab']['is_owner']?>');
     cu.updateFiles('<?= $A['collab_id']?>');
     <?

$carr106 = $A['collab']['users'];
$cc106= count( $carr106);
$ck106= array_keys( $carr106);
for( $ci106= 0; $ci106< $cc106; ++$ci106)
{ 
   $A['user'] = $carr106[ $ck106[ $ci106 ] ];
   
?>
        cu.addUser( '<?= $A['user']['user_name']?>', '<?= $A['user']['user_real_name']?>', '<?= $A['user']['collab_user_role']?>', '<?= $A['user']['collab_user_credit']?>' );
     <?
} // END: for loop

?>
    function upload_done(upload_id,msg)
    {
      $('upcover').style.display = 'none';
      if( upload_id )
      {
        cu.updateFiles('<?= $A['collab_id']?>');
        cu.msg('Upload succeeded.','green');
      }
      else
      {
        cu.msg('Upload failed: ' . msg,'red');
      }
    }

  </script>
<?
} // END: function show_collab


//------------------------------------- 
function _t_collab_show_collab_files($T,&$A) {
   

$carr107 = $A['uploads'];
$cc107= count( $carr107);
$ck107= array_keys( $carr107);
for( $ci107= 0; $ci107< $cc107; ++$ci107)
{ 
   $A['upload'] = $carr107[ $ck107[ $ci107 ] ];
   
?><div  class="file_line <?= $A['upload']['collab_type']?>_line" id="_file_line_<?= $A['upload']['upload_id']?>">
<div  class="file_info"><a  class="fname" href="<?= $A['upload']['file_page_url']?>"><?= $A['upload']['upload_name']?></a> by
               <a  href="<?= $A['upload']['artist_page_url']?>"><?= $A['upload']['user_real_name']?></a></div>
<?

if ( !empty($A['upload']['is_collab_owner'])) {

?><div ><a  href="javascript://remove " id="_remove_<?= $A['upload']['upload_id']?>" class="file_cmd file_remove"><span >remove</span></a></div>
<div ><a  href="javascript://publish" id="_publish_<?= $A['upload']['upload_id']?>" class="file_cmd file_publish">
<span  id="_pubtext_<?= $A['upload']['upload_id']?>"><?

if ( !empty($A['upload']['upload_published'])) {

?>hide<?
} // END: if

if ( !($A['upload']['upload_published']) ) {

?>publish<?
} // END: if

?></span></a>
</div>
<div ><a  href="javascript://tags" id="_tags_<?= $A['upload']['upload_id']?>" class="file_cmd file_tags">tags</a></div>
<?
} // END: if

?><div  class="ccud"><?= $A['upload']['collab_type']?></div>
<div  class="tags" id="_user_tags_<?= $A['upload']['upload_id']?>"><?= $A['upload']['upload_extra']['usertags']?></div>
<br  />
<table  class="file_dl_table">
<?

$carr108 = $A['upload']['files'];
$cc108= count( $carr108);
$ck108= array_keys( $carr108);
for( $ci108= 0; $ci108< $cc108; ++$ci108)
{ 
   $A['file'] = $carr108[ $ck108[ $ci108 ] ];
   
?><tr ><td  class="nic"><?= $A['file']['file_nicname']?></td>
<td ><a  href="<?= $A['file']['download_url']?>" class="down_button">
</a></td>
<td ><?= $A['file']['file_name']?></td>
</tr>
<?
} // END: for loop

?></table>
</div>
<?
} // END: for loop
} // END: function show_collab_files

?>