<?/*
[meta]
    type = template_component
    dataview = files
[/meta]
*/?>
<div  id="cc_download">
<div id="download_help">
    <div>%text(str_list_IEtip)%</div>
    <div>%text(str_list_Mactip)%</div>
</div>
%loop(records,R)%
<p class="upload_name">%(#R/upload_name)%</p>
<ol>
    %loop(#R/files,F)%
         <li>
            %(#F/file_nicname)% %(#F/file_filesize)%: <a href="%(#F/download_url)%">%(#F/file_name)%</a> 
         </li>
    %end_loop%
</ol>
%end_loop%
</div>