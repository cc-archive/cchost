
<div  id="cc_mplayer">
<script >// FORMAT_NAME _("Fabricio Zuardi's Button Player (Requires Flash)"); </script>
<?
$A['url'] = urlencode($A['home-url'] . 'api/query' . $A['q'] . $A['qstring'] . '&format=xspf');

?><div >
<div  style="float:left; margin-right:12px;">
<object  type="application/x-shockwave-flash" data="<?= $A['root-url']?>cclib/xspf_player/musicplayer.swf?&playlist_url=<?= $A['url']?>&" width="17" height="17">
<param  name="movie" value="<?= $A['root-url']?>cclib/xspf_player/musicplayer.swf?&playlist_url=<?= $A['url']?>&"></param>
</object>
</div>
<span  class="cc_songinfo"><a  href="<?= $A['records']['0']['file_page_url']?>" class="cc_songtitle"><?= $A['records']['0']['upload_name']?></a> by <a  href="<?= $A['records']['0']['artist_page_url']?>" class="cc_artistname"><?= $A['records']['0']['user_real_name']?></a></span>&nbsp;

</div>