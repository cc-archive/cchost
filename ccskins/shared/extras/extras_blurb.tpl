<?/*
[meta]
    type = extras
    desc = _('Blurb Topics')
[/meta]
*/?>
<p>%text(str_news)%</p>
<ul  id="blurbs">
%query('t=content_page_blurb&datasource=topics&type=sidebar_blurb&f=embed&limit=3&noexit=1&nomime=1&cache=blurb')%
</ul>