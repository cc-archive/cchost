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
<ol>
%loop(records/0/files,F)%
     <li><a href="%(#F/download_url)%">%(#F/file_nicname)%</a></li>
%end_loop%
</ol>
</div>