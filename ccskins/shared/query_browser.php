
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<div id="dyn_filter_editor_parent" style="float:left;width:35%;">
    <div  id="dyn_filter_editor">
        <div  id="filter_form" >
        </div>
    </div>
</div>
<div id="query_target" style="float:left;width:35%;margin-left:2%">
</div>
<script  src="<?= $T->URL('js/query_filter.js')?>" ></script>
<script  src="<?= $T->URL('js/autocomp.js')?>" ></script>
<script  src="<?= $T->URL('js/autopick.js')?>" ></script>
<script type="text/javascript">
    function submit_callback(event) {
      var url = query_url + filters.queryString() + '&f=html&t=playlist_show_one';
      ajax_debug(url);
      new Ajax.Updater('query_target',url,{method:'get'});
    }

    var filters = new ccQueryBrowserFilters( 
        { submit_text: '<?= $T->String('str_submit') ?>',
          init_values: { 'limit': 25 },
          reqtags: <?= cc_get_config( 'browse_query_tags', 'json' ) ?>,
          onFilterSubmit: submit_callback.bindAsEventListener() } );

</script>
