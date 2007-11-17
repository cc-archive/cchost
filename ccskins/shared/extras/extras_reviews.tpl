<?/*
[meta]
    type = extras
    desc = _('Recent Reviews')
[/meta]
*/

$Rs = cc_recent_reviews();
<p>%text(str_recent_reviewers)%</p>
<ul>
%loop(#Rs,_r)%
  <li><a href="%(#_r/topic_permalink)%">%chop(#_r/user_real_name,12)%</a></li>
%end_loop%
</ul>
<a href="%(home-url)%reviews" class="cc_more_menu_link">%text(str_more_reviews)%...</a>

