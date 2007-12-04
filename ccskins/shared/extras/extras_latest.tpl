<?/*
[meta]
    type = extras
    desc = _('Latest Uploads')
[/meta]
*/?>
<p>%text(str_new_uploads)%</p>
<ul>
%query('t=links_menu&f=html&chop=13&limit=5&noexit=1&nomime=1&cache=latest')%
<li><a href="%(home-url)%files" class="cc_more_menu_link">%text(str_more_newuploads)%</li>
</ul>