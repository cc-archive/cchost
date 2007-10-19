<?
global $_TV;


//------------------------------------- 
function _t_collab_show_collabs() {
   global $_TV;

?><link  rel="stylesheet" type="text/css" href="<?= $_TV['root-url']?>cctemplates/playlist.css" title="Default Style"></link>
<style >
  .collab_entry {
    background: url('<?= $_TV['root-url']?>cctemplates/ccmixter/bg_head.gif') top repeat-x;
    margin: 4px;
    padding: 6px;
  }
  .collab_name {
    font-weight: bold;
    font-size: 13px;
    color: brown;
    margin: 0px 0px 4px 0px;
  }
  .collab_date {
    color: #888;
    font-style: italic;
    margin: 4px;
    display: block;
    float: right;
    width: 90px;
    text-align: right;
  }
   </style>
<table  style="width:95%">
<?
$_TV['rows'] = array_chunk($_TV['collabs']['collab_rows'], 2);

$carr101 = $_TV['rows'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['col'] = $carr101[ $ck101[ $ci101 ] ];
   
?><tr >
<?

$carr102 = $_TV['col'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $_TV['row'] = $carr102[ $ck102[ $ci102 ] ];
   
?><td  style="vertical-align: top;width:50%;">
<div  class="collab_entry">
<span  class="collab_date"><?= CC_datefmt($_TV['row']['collab_date'],'M d, Y');?></span>
<a  class="collab_name" href="<?= $_TV['home-url']?>collab/<?= $_TV['row']['collab_id']?>"><?= CC_strchop($_TV['row']['collab_name'],35);?></a>
<br  />
<?

$carr103 = $_TV['row']['users'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $_TV['u'] = $carr103[ $ck103[ $ci103 ] ];
   
?><a  href="<?= $_TV['home-url']?>people/<?= $_TV['u']['user_name']?>"><?= $_TV['u']['user_real_name']?></a><?

if ( !($ci103 == ($cc103-1)) ) {

?>, <?
} // END: if
} // END: for loop

if ( !empty($_TV['row']['uploads'])) {

$carr104 = $_TV['row']['uploads'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $_TV['item'] = $carr104[ $ck104[ $ci104 ] ];
   
if ( !empty($_TV['item']['fplay_url'])) {

?><div  class="tdc cc_playlist_pcontainer"><a  class="cc_player_button cc_player_hear" id="_ep_1_<?= $_TV['item']['upload_id']?>" href="<?= $_TV['item']['fplay_url']?>">
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
//_template_call_template($_TV['prev_next_links']);
//_template_call_template('playerembed.xml/eplayer');
} // END: function show_collabs


//------------------------------------- 
function _t_collab_show_collab() {
   global $_TV;

?><div  id="collab_ajax_msg"></div>
<?
$_TV['collab_id'] = $_TV['collab']['collab']['collab_id'];

?><link  rel="stylesheet" type="text/css" href="<?= $_TV['root-url']?>cctemplates/collab.css" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $_TV['root-url']?>cctemplates/skin-simple-topics.css" title="Default Style"></link>
<fieldset >
<legend ><?= _('Info')?></legend>
<?

if ( !empty($_TV['collab']['is_owner'])) {

?><div  style="float:right">
<a  id="commentcommand" href="<?= $_TV['home-url']?>collab/edit/<?= $_TV['collab_id']?>"><span >edit</span></a>
</div><?
} // END: if

?><div  class="cc_collab_by">Created by: <a  href="<?= $_TV['home-url']?>people/<?= $_TV['collab']['collab']['user_name']?>"><?= $_TV['collab']['collab']['user_real_name']?></a> 
          <?= $_TV['collab']['collab']['collab_date']?></div>
<div  class="cc_collab_desc"><?= $_TV['collab']['collab']['collab_desc']?></div>
</fieldset>
<div  class="cc_collab_fields">
<fieldset >
<legend ><?= _('Artists')?></legend>
<div  class="user_lines">
<div  id="user_inserter"></div>
</div>
<div  id="invite_container">
</div>
</fieldset>
<fieldset >
<legend ><?= _('Files')?></legend>
<div  class="file_list" id="file_list">
</div>
<?

if ( !empty($_TV['collab']['is_member'])) {

?><iframe  style="display:none;" name="upload_frame"></iframe>
<form  target="upload_frame" enctype="multipart/form-data" action="<?= $_TV['home-url']?>collab/upload/file/<?= $_TV['collab_id']?>" method="post" id="upform" name="upform">Upload file: <select  name="uptype" id="uptype">
<option  value="remix">remix</option><option  value="sample">sample</option><option  value="acappella">a cappella</option></select>
<input  type="file" id="upfile" name="upfile"></input> name: <input  name="upname" id="upname" type="text"></input><select  name="lic" id="lic">
<?

$carr105 = $_TV['collab']['lics'];
$cc105= count( $carr105);
$ck105= array_keys( $carr105);
for( $ci105= 0; $ci105< $cc105; ++$ci105)
{ 
   $_TV['lic'] = $carr105[ $ck105[ $ci105 ] ];
   
?><option  value="<?= $_TV['lic']['license_id']?>"><?= $_TV['lic']['license_name']?></option><?
} // END: for loop

?></select>
<button  id="fileok">ok</button>
</form>
<div  id="upcover" style="position:absolute;display:none;background-color:white;"><img  style="margin-left:45%" src="<?= $_TV['root-url']?>ccimages/spinner.gif" /></div>
<?
} // END: if

?></fieldset>
<?

if ( !empty($_TV['collab']['is_member'])) {

?><fieldset >
<legend ><?= _('Conversation')?></legend>
<p >This conversation thread is private and only visible to members of the project:</p>
<?
$_TV['topics'] = $_TV['collab']['topics'];
_template_call_template('topics.xml/list_topics');

?><div  class="c_commands">
<a  href="<?= $_TV['home-url']?>collab/topic/add/<?= $_TV['collab_id']?>" id="commentcommand"><span >Add Topic</span></a>
</div>
</fieldset><?
} // END: if

?></div>
<script  src="<?= $_TV['root-url']?>cctemplates/js/mini-rico.js"></script>
<script  src="<?= $_TV['root-url']?>cctemplates/js/autocomp.js"></script>
<script  src="<?= $_TV['root-url']?>cctemplates/js/collab.js"></script>
<script >
    var collab_template = '<' + 'div class="user_line" id="_user_line_#{user_name}">' +
            '<' + 'div class="user" ><' + 'a href="'+home_url+'people/#{user_name}">#{user_real_name}<' + '/a><' + '/div>' +
            '<' + 'div class="role">#{role}<' + '/div>' +
            '<' + 'div class="credit" id="_credit_#{user_name}">#{credit}<' + '/div>' +
  <?

if ( !empty($_TV['collab']['is_owner'])) {

?>
     '<' + 'div><' + 'a href="javascript://edit credit" id="_user_credit_#{user_name}" class="user_cmd edit_credit"><' 
              + 'span>credit<' + '/span><' + '/a><' + '/div>' +
     '<' + 'div><' + 'a href="javascript://remove user" id="_user_remove_#{user_name}" class="user_cmd"><' 
              + 'span>remove<' + '/span><' + '/a><' + '/div>' +
  <?
} // END: if

if ( !empty($_TV['collab']['is_member'])) {

?>
            '<' + 'div>' +
            '    <' + 'a href="javascript://contact" id="_contact_#{user_name}" class="user_cmd edit_contact"><' + 'span>send email' 
                 + '<' + '/span><' + '/a> ' +
            '<' + '/div>' +
  <?
} // END: if

?>
           '<' + '/div>';
     var cu = new ccCollab('<?= $_TV['collab_id']?>','<?= $_TV['collab']['is_member']?>','<?= $_TV['collab']['is_owner']?>');
     cu.updateFiles('<?= $_TV['collab_id']?>');
     <?

$carr106 = $_TV['collab']['users'];
$cc106= count( $carr106);
$ck106= array_keys( $carr106);
for( $ci106= 0; $ci106< $cc106; ++$ci106)
{ 
   $_TV['user'] = $carr106[ $ck106[ $ci106 ] ];
   
?>
        cu.addUser( '<?= $_TV['user']['user_name']?>', '<?= $_TV['user']['user_real_name']?>', '<?= $_TV['user']['collab_user_role']?>', '<?= $_TV['user']['collab_user_credit']?>' );
     <?
} // END: for loop

?>
    function upload_done(upload_id,msg)
    {
      $('upcover').style.display = 'none';
      if( upload_id )
      {
        cu.updateFiles('<?= $_TV['collab_id']?>');
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
function _t_collab_show_collab_files() {
   global $_TV;

$carr107 = $_TV['uploads'];
$cc107= count( $carr107);
$ck107= array_keys( $carr107);
for( $ci107= 0; $ci107< $cc107; ++$ci107)
{ 
   $_TV['upload'] = $carr107[ $ck107[ $ci107 ] ];
   
?><div  class="file_line <?= $_TV['upload']['collab_type']?>_line" id="_file_line_<?= $_TV['upload']['upload_id']?>">
<div  class="file_info"><a  class="fname" href="<?= $_TV['upload']['file_page_url']?>"><?= $_TV['upload']['upload_name']?></a> by
               <a  href="<?= $_TV['upload']['artist_page_url']?>"><?= $_TV['upload']['user_real_name']?></a></div>
<?

if ( !empty($_TV['upload']['is_collab_owner'])) {

?><div ><a  href="javascript://remove " id="_remove_<?= $_TV['upload']['upload_id']?>" class="file_cmd file_remove"><span >remove</span></a></div>
<div ><a  href="javascript://publish" id="_publish_<?= $_TV['upload']['upload_id']?>" class="file_cmd file_publish">
<span  id="_pubtext_<?= $_TV['upload']['upload_id']?>"><?

if ( !empty($_TV['upload']['upload_published'])) {

?>hide<?
} // END: if

if ( !($_TV['upload']['upload_published']) ) {

?>publish<?
} // END: if

?></span></a>
</div>
<div ><a  href="javascript://tags" id="_tags_<?= $_TV['upload']['upload_id']?>" class="file_cmd file_tags">tags</a></div>
<?
} // END: if

?><div  class="ccud"><?= $_TV['upload']['collab_type']?></div>
<div  class="tags" id="_user_tags_<?= $_TV['upload']['upload_id']?>"><?= $_TV['upload']['upload_extra']['usertags']?></div>
<br  />
<table  class="file_dl_table">
<?

$carr108 = $_TV['upload']['files'];
$cc108= count( $carr108);
$ck108= array_keys( $carr108);
for( $ci108= 0; $ci108< $cc108; ++$ci108)
{ 
   $_TV['file'] = $carr108[ $ck108[ $ci108 ] ];
   
?><tr ><td  class="nic"><?= $_TV['file']['file_nicname']?></td>
<td ><a  href="<?= $_TV['file']['download_url']?>" class="down_button">
</a></td>
<td ><?= $_TV['file']['file_name']?></td>
</tr>
<?
} // END: for loop

?></table>
</div>
<?
} // END: for loop
} // END: function show_collab_files

?>