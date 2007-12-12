
%macro(search_results_all)%
%loop(search_results_meta,M)%
<div class="search_result_block">

    <h2>%text(str_results_from)%: %text(#M/meta/title)%</h2>
    %if_not_null(#M/total)%
        <div class="search_result_count"><?= $T->String(array('str_search_found_d',$M['total'])); ?></div>
        %(#M/results)%
        %if_not_null(#M/more_results_link)%
            <div class="search_more_links"><a href="%(#M/more_results_link)%">%text(str_search_more)%<!-- --> %text(#M/meta/title)%</a></div>
        %end_if%
    %else%
        <!-- -->%text(str_search_no_matches)% %text(#M/meta/title)%
    %end_if%
</div>
%end_loop%
%end_macro%


%macro(search_results_form)%
<form method="get" id="search_result_form"><table><tr><td><input name="search_text" id="search_text" value="%(search_form/search_text)%" /></td>
<td><select name="search_in" id="search_in" >
%loop(search_form/options,v)%
    <option value="%(#k_v)%">%text(#v)%</option>
%end_loop%
</select>
<script>
$('search_in').selectedIndex = %(search_form/selected)%;
</script>
</td><td><input type="submit" value="%text(str_search)%" name="submit_search" /></td></tr></table></form>
%end_macro%