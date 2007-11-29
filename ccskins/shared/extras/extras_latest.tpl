<?/*
[meta]
    type = extras
    desc = _('Latest Uploads')
[/meta]
*/

$recs =& cc_quick_list(CC_GLOBAL_SCOPE); ?>

<p>%text(str_new_uploads)%</p>
<ul>
%loop(#recs,R)%
  <li><a href="%(#R/file_page_url)%">%(#R/upload_name)%</a></li>
%end_loop%
<li><a href="%(home-url)%files" class="cc_more_menu_link">%text(str_more_newuploads)%</li>
</ul>