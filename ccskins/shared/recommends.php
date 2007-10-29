<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

?><div >
<?
    $A['page-title-str'] = 'recommends_browser';
    $T->Call('print_page_title');
?>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css'); ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/detail.css'); ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/recommends.css'); ?>" title="Default Style"></link>

<div  id="browser_head">
<div  id="limit_picker_container">
    Display: <select  id="limit_picker"></select>
</div>
<div  class="cc_stream_page_link" id="stream_link_container" style="display:none;">
<a  href="javascript://stream" id="mi_stream_page"><span ><?= $GLOBALS['str_stream'] ?></span></a></div>
<div  class="cc_stream_page_link" id="play_link_container" style="display:none;">
<a  href="javascript://play win" id="mi_play_page"><span ><?= $GLOBALS['str_play'] ;?></span></a></div>
</div>
<div  id="featured">
<h3 ><?= sprintf($GLOBALS['str_recommended_by_s'],$A['get']['fullname']);?></h3>
<div  class="featured_info"><?= sprintf($GLOBALS['str_s_recommends'] ,$A['get']['fullname']);?></div>
</div>
<div  id="browser"><?= $GLOBALS['str_getting_data'] ;?></div>
<div  id="q"></div>
<table  id="cc_prev_next_links"><tbody ><tr >
<td  class="cc_list_list_space">&nbsp;</td>
<td ><a  id="browser_prev" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_prev"><span >&lt;&lt;&lt; <?= $GLOBALS['str_prev'] ?></span></a>
</td>
<td ><a id="browser_next" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_next"><span><?= $GLOBALS['str_more'] ?> &gt;&gt;&gt;</span></a></td>
</tr></tbody></table>
<div  id="feed_links" style="display:none">
<span  class="cc_feed_link">
<a  id="rss_feed" class="cc_feed_button" type="application/rss+xml" href="" title="RSS 2.0">RSS </a>
<span  id="feed_name"></span>
</span>
</div>
<div  id="bottom_breaker">&nbsp;</div>
<script  src="<?= $T->URL('js/playlist.js'); ?>"></script>
<?$T->Call('playerembed.xml/eplayer'); ?>
<script>
var ruser = '<?= $A['get']['ruser']?>';
var fullname = '<?= $A['get']['fullname']?>';
</script>
<script  src="<?= $T->URL('js/recommends.js'); ?>" /></script>
<script>
new ccReccommendBrowser();
</script>

</div>