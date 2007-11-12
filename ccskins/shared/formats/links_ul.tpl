%%
[meta]
    type     = list
    desc     = _('Links to upload page (unordered list)')
    dataview = page_links
[/meta]
%%
<div  id="cc_list">
<ul>
%loop(records,R)%
   <li><a href="%(#R/file_page_url)%" class="cc_file_link">%chop(#R/upload_name,chop)%</a></li>
%end_loop%
</ul>
<i class="cc_tagline"><span>%call(format_sig)%</span></i>
</div>
