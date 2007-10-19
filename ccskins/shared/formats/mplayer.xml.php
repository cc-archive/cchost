<?
global $_TV;

?><div  id="cc_mplayer">
<script >// FORMAT_NAME _("Fabricio Zuardi's Music Player (Requires Flash)"); </script>
<?

if( !empty($_TV['height']) ) { $_TV['h'] = $_TV['height']; } else {  $_TV['h'] = 15;} 
if( !empty($_TV['player']) ) { $_TV['ply'] = $_TV['player']; } else {  $_TV['ply'] = 'xspf_player_slim.swf'; } $_TV['w'] = 400;
$_TV['turl'] = $_TV['home-url'] . 'api/query' . $_TV['q'] . $_TV['qstring'] . '&format=xspf';
$_TV['url'] = urlencode($_TV['home-url'] . 'api/query' . $_TV['q'] . $_TV['qstring'] . '&format=xspf');

?><object  classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" id="xspf_player" align="middle" height="<?= $_TV['h']?>" width="<?= $_TV['w']?>" player_title="ccHost Player">
<param  name="movie" value="<?= $_TV['root-url']?>cclib/xspf_player/<?= $_TV['ply']?>?playlist_url=<?= $_TV['url']?>&1=1"></param>
<param  name="quality" value="high"></param>
<param  name="bgcolor" value="#e6e6e6"></param>
<embed  src="<?= $_TV['root-url']?>cclib/xspf_player/<?= $_TV['ply']?>?playlist_url=<?= $_TV['url']?>&1=1" quality="high" bgcolor="#e6e6e6" name="xspf_player" player_title="ccHost Player" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" align="center" height="<?= $_TV['h']?>" width="<?= $_TV['w']?>"></embed>
</object>
</div>