<? /*
[meta]
    type     = format
    desc     = _('Fabricio Zuardi's Music Player - Requires Flash')
[/meta]
*/ ?>
<div  id="cc_mplayer">
<?

if( !empty($A['height']) ) { $A['h'] = $A['height']; } else {  $A['h'] = 15;} 
if( !empty($A['player']) ) { $A['ply'] = $A['player']; } else {  $A['ply'] = 'xspf_player_slim.swf'; } $A['w'] = 400;
$A['turl'] = $A['home-url'] . 'api/query' . $A['q'] . $A['qstring'] . '&format=xspf';
$A['url'] = urlencode($A['home-url'] . 'api/query' . $A['q'] . $A['qstring'] . '&format=xspf');

?><object  classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" id="xspf_player" align="middle" height="<?= $A['h']?>" width="<?= $A['w']?>" player_title="ccHost Player">
<param  name="movie" value="<?= $A['root-url']?>cclib/xspf_player/<?= $A['ply']?>?playlist_url=<?= $A['url']?>&1=1"></param>
<param  name="quality" value="high"></param>
<param  name="bgcolor" value="#e6e6e6"></param>
<embed  src="<?= $A['root-url']?>cclib/xspf_player/<?= $A['ply']?>?playlist_url=<?= $A['url']?>&1=1" quality="high" bgcolor="#e6e6e6" name="xspf_player" player_title="ccHost Player" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" align="center" height="<?= $A['h']?>" width="<?= $A['w']?>"></embed>
</object>
</div>