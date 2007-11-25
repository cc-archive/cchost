
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
#debug { display: none; }
</style>
<div style="width:40em;">
    <div id="remix_search_toggle" style="display:none"><a href="javascript://toggle" id="remix_toggle_link">%text(str_remix_close)%</a></div>
    <div>%text(str_remix_i_used_samples)%: </div>
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
    </div>
    <div id="remix_search_picks">
    </div>
</div>

<script src="%url('js/remix_search.js')%" type="text/javascript"></script>
<script> 
var pools = <?= cc_query_fmt("t=pools&f=js&nomime=1&noexit=1"); ?>; 
new ccRemixSearch();
</script>
