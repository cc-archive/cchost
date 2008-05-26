<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

// this is hand crafted here, I promise this will be
// admin editable by release

$submit_types = cc_get_submit_types(false,'(Select type)');

?>
%if_empty(records)%
    %return%
%end_if%

<!-- template manage_files -->
<table class="edit_files_submit">
%loop(records,R)%
<tr>
    <td class="edit_upload_name">%(#R/upload_name)%</td>
</tr>
<tr>
    <td class="edit_file_commands"><table><tr>
            <td><a href="/files/edit/%(#R/user_name)%/%(#R/upload_id)%" class="small_button"><span>%text(str_file_properties_v)%</span></a></td>
            <td><a href="/file/remixes/%(#R/upload_id)%" class="small_button"><span>%text(str_files_manage_remixes_v)%</span></a></td>
            <td><a href="/file/manage/%(#R/upload_id)%" class="small_button"><span>%text(str_files_manage_v)%</span></a></td>
            <td><a href="/file/manage/%(#R/upload_id)%" class="small_button"><span>%text(str_files_manage)%</span></a></td>
            </tr>
        </table>
    </td>
</tr>
<tr><td><table class="edit_file_names">
    %loop(#R/files,F)%
        <tr><th>%(#F/file_nicname)%:</th><td class="edit_file_name">%(#F/file_name)%</td></tr>
    %end_loop%
</table></td></tr>
<tr><td class="edit_picker_container" >
    Add: <select id="add_file_picker_%(#R/upload_id)%" class="add_file_picker">
        %loop(#submit_types,stype)%
            <option value="%(#k_stype)%">%(#stype)%</option>
        %end_loop%
    </select>
</td></tr>
<tr><td class="edit_files_breaker"></td></tr>
%end_loop%
</table>
