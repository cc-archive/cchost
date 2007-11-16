%%
[meta]
    type     = list
    desc     = _('Upload listing (narrow)')
    dataview = narrow_list
[/meta]
%%
<link rel="stylesheet" href="<?= $T->URL('css/uploads_narrow.css'); ?>"  title="Default Style" type="text/css" />
<div  id="cc_narrow_list">
<ul>
<table cellspacing="0" cellpadding="0">
%loop(records,R)%
   <tr><td colspan="2"><a href="%(#R/file_page_url)%" class="upload_name">%chop(#R/upload_name,60)%</a></td></tr>
   <tr><th>%text(str_by)%</th><td><a href="%(#R/artist_page_url)%" class="artist_name">%chop(#R/user_real_name,chop)%</a></td></tr>
   <tr><th>%text(str_list_date)%</th><td>%(#R/upload_date_format)%</td></tr>
   <tr><th>%text(str_license)%</th>
     <td><a href="%(#R/license_url)%"><img src="%(root-url)%ccskins/shared/images/lics/small-%(#R/license_logo)%" /></a></td></tr>
   
%end_loop%
</table>
%call(prev_next_links)%
</div>
