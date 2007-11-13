%%
[meta]
    type     = list
    desc     = _('Upload listing (narrow)')
    dataview = narrow_list
[/meta]
%%
<div  id="cc_narrow_list">
<ul>
<table cellspacing="0" cellpadding="0">
%loop(records,R)%
   <tr><td colspan="2"><a href="%(#R/file_page_url)%" class="upload_name">%chop(#R/upload_name,chop)%</a></td></tr>
   <tr><td>%text(str_by)%</td><td><a href="%(#R/artist_page_url)%" class="artist_name">%chop(#R/user_real_name,chop)%</a></td></tr>
   <tr><td>%text(str_list_date)%</td><td>%(#R/upload_date_format)%</td></tr>
   <tr><td>%text(str_license)%</td><td><a href="%(#R/license_url)%"><img src="%(root-url)%ccskins/shared/images/lics/small-%(#R/license_logo)%" /></a></td></tr>
   
%end_loop%
</table>
%if_null(skip_format_sig)%
<i class="cc_tagline"><span>%call(format_sig)%</span></i>
%else%
%call(prev_next_links)%
%end_if%
</div>
