<div id="ajax_msg"></div>
<?
$collab = $A['collab'];
$C = $collab['collab'];
$collab_id = $C['collab_id'];
?>
<link  rel="stylesheet" type="text/css" href="%url('css/collab.css')%" title="Default Style"></link>
<fieldset>
<legend class="dark_bg light_color">%text(str_info)%</legend>
    %if_not_null(#collab/is_owner)%
    <div  style="float:right">
        <a  id="commentcommand" href="%(home-url)%collab/edit/%(#collab_id)%"><span >%text(str_collab_edit)%</span></a>
    </div>
    %end_if%

    <div  class="cc_collab_by">
        %text(str_collab_created_by)%: <a href="%(home-url)%people/%(#C/user_name)%">%(#C/user_real_name)%</a> 
        %(#C/collab_date)%
   </div>
   <div  class="cc_collab_desc light_bg dark_border">%(#C/collab_desc)%</div>
</fieldset>

 <div  class="cc_collab_fields">
 <fieldset >
 <legend class="dark_bg light_color" >%text(str_artists)%</legend>
    <div  class="user_lines">
        <div  id="user_inserter"></div>
    </div>
    <div  id="invite_container">
    </div>
</fieldset>

<fieldset>
<legend class="dark_bg light_color" >%text(str_files)%</legend>
    <div  class="file_list" id="file_list">
    </div>
    %if_not_null(#collab/is_member)%
       <iframe  style="display:block;width:100%;height:4em;" name="upload_frame"></iframe>
        <form  target="upload_frame" 
                enctype="multipart/form-data" 
                action="<?= $A['home-url']?>collab/upload/file/%(#collab_id)%" 
                method="post" id="upform" name="upform">%text(str_collab_upload_file)%: 
            <?
                // suck out the form types so we have a notion of what
                // types an upload might be...

                $_c_forms = cc_get_config('submit_forms');
                print '<select  name="uptype" id="uptype">';
                foreach($_c_forms as $_c_form)
                {
                    if( !$_c_form['enabled'] ) 
                        continue;
                    $tags = $_c_form['tags'];
                    if( is_array($tags) )
                        $tags = join(',',$tags);
                    $name = $_c_form['submit_type'];
                    print "<option value=\"{$tags}\">" . substr($T->String($name),0,10) . "</option>\n";
                }
                print '</select>';
             ?>
        <input type="file" id="upfile" name="upfile"></input> %text(str_collab_name)%: <input  name="upname" id="upname" type="text"></input><select  name="lic" id="lic">
        %loop(#collab/lics,lic)%
            <option  value="%(#lic/license_id)%">%(#lic/license_name)%</option>
        %end_loop%</select>
        <button  id="fileok">%text(str_collab_ok)%</button>
        </form>
        <div  id="upcover" style="position:absolute;display:none;" class="light_bg"> 
        <img  style="margin-left:45%" src="%url('images/spinner.gif')%" /></div>
    %end_if%
</fieldset>
%if_not_null(#collab/is_member)%
<fieldset>
<legend class="dark_bg light_color" >%text(str_conversation)%</legend>
    <p >%text(str_collab_this_conv)%:</p>
    <?= cc_query_fmt('noexit=1&nomime=1&f=html&t=collab_thread&datasource=topics&ord=ASC&type=collab&upload='.$collab_id); ?>
    <div class="c_commands">
        <a href="%(home-url)%collab/topic/add/%(#collab_id)%" id="commentcommand"><span >%text(str_collab_add_topic)%</span></a>
    </div>
</fieldset>
%end_if%       
</div><!-- collab_fields -->

<script  src="%url('js/autocomp.js')%" type="text/javascript" ></script>
<script  src="%url('js/collab.js')%"   type="text/javascript" ></script>
<script type="text/javascript">
var str_credit = '%text(str_collab_credit2)%';
var str_remove = '%text(str_collab_remove2)%';
var str_send_email = '%text(str_collab_send_email)%';
var collab_template = 
    '<div class="user_line" id="_user_line_#{user_name}">' +
    '<div class="user" ><a href="'+home_url+'people/#{user_name}">#{user_real_name}</a></div>' +
    '<div class="role">#{role}</div>' +
    '<div class="credit" id="_credit_#{user_name}">#{credit}</div>' +
%if_not_null(#collab/is_owner)%
    '<div><a href="javascript://edit credit" id="_user_credit_#{user_name}" class="user_cmd edit_credit"><' 
          + 'span>credit</span></a></div>' +
    '<div><a href="javascript://remove user" id="_user_remove_#{user_name}" class="user_cmd"><' 
          + 'span>remove</span></a></div>' +
%end_if%
%if_not_null(#collab/is_member)%
    '<div>' +
    '    <a href="javascript://contact" id="_contact_#{user_name}" class="user_cmd edit_contact"><span>send email' 
         + '</span></a> ' +
    '</div>' +
%end_if%
    '</div>';

var cu = new ccCollab('%(#collab_id)%','%(#collab/is_member)%','%(#collab/is_owner)%');
cu.updateFiles('%(#collab_id)%');
%loop(#collab/users,_u)%
  cu.addUser( '%(#_u/user_name)%', '%(#_u/user_real_name)%', '%(#_u/collab_user_role)%', '%(#_u/collab_user_credit)%' );
%end_loop%

    function upload_done(upload_id,msg)
    {
      $('upcover').style.display = 'none';
      if( upload_id )
      {
        cu.updateFiles('%(#collab_id)%');
        cu.msg('%text(str_collab_upload_succeeded)%.','green');
      }
      else
      {
        cu.msg('%text(str_collab_upload_failed)%: ' . msg,'red');
      }
    }
</script>
