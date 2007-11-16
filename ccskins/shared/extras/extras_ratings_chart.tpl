<?/*
[meta]
    type = extras
    desc = _('Hightest rated')
[/meta]
*/?>

<p>%text(str_highest_rated)%</p>
<? $chart = cc_ratings_chart(7); ?>
<ul>
%loop(#charts,CI)%
  <li><a href="%(#CI/file_page_url)%">%(#CI/upload_short_name)%</a></li>
%end_loop%
%if_null(#charts)%
  <li>%text(str_no_chart)%</li>
%end_if%
</ul>
