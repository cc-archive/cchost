<?
global $_TV;

?><div  id="cc_mplayer">
<script >// FORMAT_NAME _("Fabricio Zuardi's Button Player (Requires Flash)"); </script>
<?
$_TV['url'] = urlencode($_TV['home-url'] . 'api/query' . $_TV['q'] . $_TV['qstring'] . '&format=xspf');

?><div >
<div  style="float:left; margin-right:12px;">
<object  type="application/x-shockwave-flash" data="<?= $_TV['root-url']?>cclib/xspf_player/musicplayer.swf?&playlist_url=<?= $_TV['url']?>&" width="17" height="17">
<param  name="movie" value="<?= $_TV['root-url']?>cclib/xspf_player/musicplayer.swf?&playlist_url=<?= $_TV['url']?>&"></param>
</object>
</div>
<span  class="cc_songinfo"><a  href="<?= $_TV['records']['0']['file_page_url']?>" class="cc_songtitle"><?= $_TV['records']['0']['upload_name']?></a> by <a  href="<?= $_TV['records']['0']['artist_page_url']?>" class="cc_artistname"><?= $_TV['records']['0']['user_real_name']?></a></span>&nbsp;<i  class="cc_tagline"><span >
<?

if( !empty($_TV['format_sig']) ) { $_TV['format_signature'] = $_TV['format_sig']; } else {  $_TV['format_signature'] = 'format_signature.xml/signature'; } _template_call_template($_TV['format_signature']);

?></span></i>
</div>