<? if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
?>
<!-- template query_browser -->
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css'); ?>" title="Default Style"></link>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css'); ?>" title="Default Style"></link>
<style type="text/css">

#browser_client {
    width: 840px;
    margin: 0px auto;
}

.cc_playlist_item {
	width: 420px;
}

.cc_playlist_pagelink {
    display: block;
    float: left;
    width: 300px;
    overflow: hidden;
    margin-right: 8px;
}

#browser_filter {
    margin: 4px;
}
#browser_area {
    float: left;
}

#filter_controls {
   width: 190px;
   padding-right:5px;
   float: left;
}

#filter_controls select {
    width: 180px;
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
    margin: 0px;
    padding: 0px;
    padding: 2px 2px 2px 6px;
    font-size: 0.8em;
    display: block;
    font-variant: small-caps;
    letter-spacing: 0.2em;
    text-transform: lowercase;
}

.filterform span.field  {
    float: none;
    display: block;
    margin: 0px;
    padding: 5px 0px 10px 0px;
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
#podcast_link_container, #stream_link_container, #play_link_container {
  float: left;
  margin-left: 21px;
  margin-bottom: 1em;
  width: 140px;
}

#podcast_link_container #mi_podcast_page,
#play_link_container #mi_play_page,
#play_link_container #mi_download_page,
#stream_link_container #mi_stream_page {
    padding-left: 18px;
    font-size: 14px;
}

.filterbuttontray {
    margin-top: 20px;
    text-align: center;
}
</style>
<div id="browser_client">
    <div id="filter_controls" >
            <div id="browser_filter">
            </div>
    </div>
    <div id="browser_area">
        <? if( $GLOBALS['strings-profile'] == 'audio' ) { ?>
        <div id="stream_link_container">
            <a href="javascript://stream" id="mi_stream_page"  style="display:none;"><span ><?= $T->String('str_stream') ?></span></a>
        </div>
        <div id="play_link_container">
            <a href="javascript://play win" id="mi_play_page"  style="display:none;"><span ><?= $T->String('str_play') ;?></span></a>
        </div>
        <div id="podcast_link_container">
            <a href="javascript://podcast" id="mi_podcast_page" style="display:none;"><span ><?= $T->String('str_podcast')?></span></a>
        </div>
        <div id="podcast_link_container">
            <a href="javascript://download" id="mi_download_page" style="display:none;"><span ><?= $T->String('str_download')?></span></a>
        </div>
		<div style="clear:both">&nbsp;</div>
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
    </div>
</div>
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
