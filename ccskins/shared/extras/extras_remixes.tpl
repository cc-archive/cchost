<?/*
[meta]
    type = extras
    desc = _('Latest Remixes')
[/meta]
*/

$recs =& cc_quick_list('remix'); ?>

<p>%text(str_new_remixes)%</p>
<ul>
%loop(#recs,R)%
  <li><a href="%(#R/file_page_url)%">%(#R/upload_short_name)%</a></li>
%end_loop%
</ul>