<? /*
[meta]
    type     = format
    dataview = links_by_dl
    desc     = _('Fabricio Zuardi's Button Player - Requires Flash')
[/meta]
*/ ?>

<div  id="cc_mplayer">
<?
$A['url'] = urlencode($A['query-url'] . $A['qstring'] . '&format=xspf');

?><div >
<div  style="float:left; margin-right:12px;">
<object  type="application/x-shockwave-flash" data="<?= $A['root-url']?>cchost_lib/xspf_player/musicplayer.swf?&playlist_url=<?= $A['url']?>&" width="17" height="17">
<param  name="movie" value="<?= $A['root-url']?>cchost_lib/xspf_player/musicplayer.swf?&playlist_url=<?= $A['url']?>&"></param>
</object>
</div>
<span  class="cc_songinfo"><a  class="cc_file_link" href="<?= $A['records']['0']['file_page_url']?>" class="cc_songtitle"><?= $A['records']['0']['upload_name']?></a> by <a  href="<?= $A['records']['0']['artist_page_url']?>" class="cc_artistname cc_user_link"><?= $A['records']['0']['user_real_name']?></a></span>&nbsp;

</div>