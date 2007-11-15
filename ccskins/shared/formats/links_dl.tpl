%%
[meta]
    type     = format
    desc     = _('Links to upload page with download links')
    dataview = page_links
[/meta]
%%
<div  id="cc_list">
%loop(records,R)%
   <div>
     <a href="%(#R/file_page_url)%" class="cc_file_link">%chop(#R/upload_name,chop)%</a> 
     <a href="%(#R/files/0/download_url)%">%(#R/files/0/file_nicname)%</a>
   </div>
%end_loop%
%if_not_empty(format_sig)%
   <i class="cc_tagline"><span>%call(format_sig)%</span></i>
%end_if%
</div>