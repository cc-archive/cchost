<?/*
[meta]
    type = extras
    desc = _('Recent Reviews')
[/meta]
*/?>

<p>%text(str_recent_reviewers)%</p>
<ul>
%query('t=reviewers_recent&f=html&noexit=1&nomime=1')%
<li><a href="%(home-url)%reviews" class="cc_more_menu_link">%text(str_more_reviewers)%...</a></li>
</ul>

