<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
?>
<!-- template search_results -->
%macro(search_results_all)%
<link rel="stylesheet" type="text/css" href="%url(css/search.css)%" title="Default Style" />
%call('search_results.tpl/search_did_u_mean')%
%if_not_null(search_miss_msg)%
    <div class="search_miss">%text(search_miss_msg)%</div>
%end_if%
%loop(search_results_meta,M)%
<div class="search_result_block">

    <div class="search_result_title">%text(str_results_from)%: %text(#M/meta/title)%</div>
    %if_not_null(#M/total)%
        <div class="search_result_count"><?= $T->String(array('str_search_found_d','<span>'.$M['total'].'</span>')); ?></div>
        %(#M/results)%
        %if_not_null(#M/more_results_link)%
            <div class="search_more_links"><a href="%(#M/more_results_link)%">%text(str_search_more)%<!-- --> %text(#M/meta/title)%</a>...</div>
        %end_if%
    %else%
        <div class="search_result_count"><!-- -->%text(str_search_no_matches)% <!-- -->%text(#M/meta/title)%</div>
    %end_if%
</div>
%end_loop%
%end_macro%


%macro(search_results_head)%
<link rel="stylesheet" type="text/css" href="%url(css/search.css)%" title="Default Style" />
%call('search_results.tpl/search_did_u_mean')%
%if_not_null(search_miss_msg)%
    <div class="search_miss">%text(search_miss_msg)%</div>
%end_if%
%if_not_null(search_result_viewing)%
    <div class="search_result_count">%text(search_result_viewing)%</div>
%end_if%
%end_macro%

%macro(search_did_u_mean)%
<style>
#did_u_mean {
    margin: 4px 15% 11px 0;
    background-color: #DEE;
    padding: 3px 12px;
    color: #777;
}
#did_u_mean b {
    color: green;
    font-weight: normal;
    font-style: italic;
}
a.dum_link {
    font-weight: normal;
    color: #555;
}
a.dum_link:hover {
    color: black;
}

</style>
<div id="did_u_mean" style="display:none"> <b>Did you mean...?</b> <span id="did_u_mean_target">...</span></div>
<script>
function got_did_u_mean(res)
{
    var s_url = home_url + 'search' + q + 'search_type=all&search_in=' + '<?= $_GET['search_in'] ?>' + '&search_text=';    
    var html ='';
    var comma = '';
    var results = eval(res.responseText);
    if( results.length )
    {
        for( var i = 0; i < results.length; i++ )
        {
            var result = results[i];
            var term = result.tag_alias_alias.split(',').join(' ');
            html += comma + '"<a class="dum_link" href="' + s_url + term + '">' + term + '</a>"';
            comma = ', ';
        }
        
        $('did_u_mean').style.display = '';
        $('did_u_mean_target').innerHTML = html;
    }
}
var dum_url = query_url  + 'dataview=tag_alias&f=js&s=' + '<?= urlencode($_GET['search_text']); ?>';
//ajax_debug(dum_url);
new Ajax.Request( dum_url, { method: 'get', onComplete: got_did_u_mean } );
</script>
%end_macro%