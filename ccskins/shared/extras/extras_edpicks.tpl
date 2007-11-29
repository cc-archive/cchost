<?/*
[meta]
    type = extras
    desc = _('Editorial Picks')
[/meta]
*/

$recs =& cc_quick_list('editorial_pick'); ?>

<p>%text(str_editors_picks)%</p>
<ul>
%loop(#recs,R)%
  <li><a href="%(#R/file_page_url)%">%(#R/upload_name)%</a></li>
%end_loop%
<li><a href="%(home-url)%editorial/picks" class="cc_more_menu_link">%text(str_more_edpicks)%</li>
</ul>