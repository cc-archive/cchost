
<style>
div#remix_search_controls {
}

#pool_select_contaner {
	display: inline;
}
#search_picks {
    border-top: 1px solid black;
    margin-top: 1em;
}
.remix_source_selected {
    color: green;
    font-weight: bold;
}
.remix_no_match {
    color: red;
    margin: 0.1em;
    font-style: italic;
}
#remix_search_toggle {
    float: right;
}
#license_info a {
    font-weight: bold;
}

#debug { display: block; }
</style>
<div style="width:40em;">
    <div id="remix_search_toggle" style="display:none">%if_not_null(field/close_box)%<a href="javascript://toggle" id="remix_toggle_link">%text(str_remix_close)%</a>%end_if%</div>
    <div id="remix_search_controls" style="display:block">
        <select id="remix_search_type">
            <option value="search_remix_artist" selected="selected">%text(str_remix_artist)%</option>
            <option value="search_remix_title" >%text(str_remix_title)%</option>
            <option value="search_remix" >%text(str_remix_full_search)%</option>
        </select>
        <input type="edit" id="remix_search" />
        <div id="pool_select_contaner"></div>
        <a href="javascript://do search" id="do_remix_search">%text(str_remix_do_search)%</a>
        <div class="remix_no_match" id="remix_no_match"></div>
    </div>
    <div id="debug"></div>
    <div id="remix_search_results">
    %if_not_null(field/sourcesof)%
        <? 
            cc_query_fmt("dataview=search_remix&t=remix_checks&f=html&noexit=1&nomime=1&sources=" . $A['field']['sourcesof']); 
            cc_query_fmt("dataview=pool_item&f=html&t=remix_pool_checks&sort=&datasource=pools&noexit=1&nomime=1&sources=" . $A['field']['sourcesof']); 
        ?>
    %end_if%
    </div>
    <div id="remix_search_picks">
    </div>
    <div id="license_info" class="box">
    </div>
</div>

<script src="%url('js/remix_search.js')%" type="text/javascript"></script>
<script> 
var pools = <?= cc_query_fmt("t=pools&f=js&nomime=1&noexit=1"); ?>; 
new ccRemixSearch();
</script>
