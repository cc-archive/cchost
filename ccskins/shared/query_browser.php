<h1><?= $T->String('str_upload_browser') ?></h1>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css'); ?>" title="Default Style"></link>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css'); ?>" title="Default Style"></link>
<style type="text/css">
#browser {
}
#qtable {
    width: 95%;

}
#qtable td {
    vertical-align: top;
}
#dyn_filter_editor {
    margin: 0px;
    padding: 4px;
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
</style>
<table id="qtable">
<tr><td style="width:270px">
    <div  id="dyn_filter_editor" class="light_bg">
        <div  id="filter_form"  >
        </div>
    </div>
</td>
<td>
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
<table id="cc_prev_next_links"><tbody ><tr >
    <td class="cc_list_list_space">&nbsp;</td>
    <td><a id="browser_prev" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_prev">
        <span >&lt;&lt;&lt; <?= $T->String('str_prev') ?></span></a>
    </td>
    <td><a id="browser_next" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_next">
        <span><?= $T->String('str_more') ?> &gt;&gt;&gt;</span></a></td>
</tr></tbody></table>
<div style="clear:both">&nbsp;</div>
</td></table>
<script  src="<?= $T->URL('js/query_browser.js')?>" ></script>
<script  src="<?= $T->URL('js/query_filter.js')?>" ></script>
<script  src="<?= $T->URL('js/autocomp.js')?>" ></script>
<script  src="<?= $T->URL('js/autopick.js')?>" ></script>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js'); ?>"></script>
<?$T->Call('playerembed.xml/eplayer'); ?>
<script type="text/javascript">

var filters = new ccQueryBrowserFilters( 
                    { submit_text: '<?= $T->String('str_see_results') ?>',
                      init_values: { 'limit': 25 },
                      template: 'reccby',
                      reqtags: <?= cc_get_config( 'browse_query_tags', 'json' ) ?>} );

new ccQueryBrowser( { filters: filters } );



</script>
