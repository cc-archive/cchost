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
  <li><a href="%(#R/file_page_url)%">%(#R/upload_short_name)%</a></li>
%end_loop%
</ul>