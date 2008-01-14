<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css'); ?>" title="Default Style"></link>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css'); ?>" title="Default Style"></link>
<style type="text/css">

#browser_filter {
    margin: 4px;
}
#browser {
    float: left;
}


.filterform div {
	margin-bottom: 4px;
}


.close_button {
	display: none;
}

#dyn_filter_editor .cc_autocomp_edit {
	margin: 4px 0px 5px 0px;
}

p.cc_autocomp_picked{
    font-style: italic;
    color: blue;
}
.cc_autocomp_list {
    background-color: #FFF; 
	z-index: 201;
	cursor: pointer;
}

#_ap_stat_tags {
	font-weight: bold;
}

p.cc_autocomp_line {
	margin: 1px;
}

#dyn_filter_editor p.cc_autocomp_selected {
    /* background-color: #CCC; */
}

#dyn_filter_editor p.cc_autocomp_picked {
	font-style: italic;
	background-color: white;
/*	color: blue; */
}

.cc_autocomp_clear {
	text-align: right;
}

.cc_autocomp_show {
	text-align: left;
}

.cc_autocomp_stat {
}

.cc_autocomp_border {
    border-width: 1px;
	border-style: solid;
	/* #666; */
}


.filterform span.th  {
    text-align: left;
    margin: 12px 0px 2px 0px;
    padding: 0px;
    display:block;
    padding: 4px 4px 4px 6px;
}

.filterform span.field  {
    float: none;
    display: block;
    margin: 0px;
    padding: 0px;
    width: 100%;
}
#ff_filter_inner {
    margin-bottom: 8px;
    display: block;
}

#dyn_filter_editor {
    margin: 0px;
    padding: 4px;
    width:180px;
}
#stream_link_container, #play_link_container {
  float: left;
  margin-left: 21px;
  margin-bottom: 1em;
  width: 140px;
}

#play_link_container #mi_play_page,
#stream_link_container #mi_stream_page {
    padding-left: 22px;
    font-size: 16px;
}
#filter_controls {
   width: 190px;
   padding-right:10px;
   float: left;
}
.filterbuttontray {
    margin-top: 20px;
    text-align: center;
}
</style>

        <div id="filter_controls" >
                <div id="browser_filter"  >
                </div>
        </div>
            <? if( $GLOBALS['strings-profile'] == 'audio' ) { ?>
            <div class="cc_stream_page_link" id="stream_link_container">
                <a href="javascript://stream" id="mi_stream_page"  style="display:none;"><span ><?= $T->String('str_stream') ?></span></a>
            </div>
            <div class="cc_stream_page_link" id="play_link_container">
                <a href="javascript://play win" id="mi_play_page"  style="display:none;"><span ><?= $T->String('str_play') ;?></span></a>
            </div>
            <? } ?>
            <div id="browser">
                <?= $T->String('str_getting_data') ;?>
            </div>

<table id="cc_prev_next_links">
<tr>
    <td class="cc_list_list_space">&nbsp;</td>
    <td><a id="browser_prev" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_prev">
        <span >&lt;&lt;&lt; <?= $T->String('str_prev') ?></span></a>
    </td>
    <td><a id="browser_next" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_next">
        <span><?= $T->String('str_more') ?> &gt;&gt;&gt;</span></a></td>
</tr>
</table>

<script  src="<?= $T->URL('js/query_browser.js')?>" ></script>
<script  src="<?= $T->URL('js/query_filter.js')?>" ></script>
<script  src="<?= $T->URL('js/autocomp.js')?>" ></script>
<script  src="<?= $T->URL('js/autopick.js')?>" ></script>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js'); ?>"></script>
<?$T->Call('playerembed.xml/eplayer'); ?>
<script type="text/javascript">
<? $qargs = empty($A['browse_args']) ? "{ 'limit': 25, 'reqtags': '*' }" : $A['browse_args']; ?>
var filters = new ccQueryBrowserFilters( 
                    { filter_form: 'browser_filter',
                      submit_text: '<?= $T->String('str_see_results') ?>',
                      init_values: <?= $qargs ?>,
                      template: 'reccby',
                      reqtags: <?= cc_get_config( 'browse_query_tags', 'json' ) ?>} );

new ccQueryBrowser( { filters: filters } );
$$('.th').each( function(e) {
    Element.addClassName(e,'med_dark_bg light_color');
});
</script>
