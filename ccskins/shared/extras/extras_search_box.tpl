<?/*
[meta]
    type = extras
    desc = _('Search Box')
[/meta]
*/?>

<form action="%(home-url)%search/results" method="get">
<div>
<input class="cc_search_edit" name="search_text" value="search text"></input>
<input type="hidden" name="search_type" value="any"></input>
<input type="hidden" name="search_in" value="3"></input>
<input type="submit" value="Search"></input>
%if_not_null(advanced_search_url)%
    <a href="%(advanced_search_url)%">%text(str_advanced)%</a>
%end_if%
</div>
</form>
