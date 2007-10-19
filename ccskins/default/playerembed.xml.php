<?
global $_TV;


//------------------------------------- 
function _t_playerembed_eplayer() {
   global $_TV;

?><link  href="<?= $_TV['root-url']?>cctemplates/playerembed.css" rel="stylesheet" type="text/css" title="Default Style"></link>
<?

if( !empty($_TV['player_options']) ) { $_TV['poptions'] = $_TV['player_options']; } else {  $_TV['poptions'] = null; } 
?><script  type="text/javascript" src="<?= $_TV['root-url']?>cctemplates/js/playerembed.js"></script>
<script  type="text/javascript" src="<?= $_TV['root-url']?>cctemplates/js/swfobject.js"></script>
<div  id="flash_goes_here"></div>
<script  type="text/javascript">
    var swfObj = new SWFObject('<?= $_TV['site-root']?>cclib/fplayer/ccmixter2.swf', 'uploadMovie', '1', '1', '8', "#FFFFFF" );
    swfObj.addVariable('allowScriptAccess','always');
    swfObj.write('flash_goes_here');
    var flashVersion = deconcept.SWFObjectUtil.getPlayerVersion();
    new ccEmbeddedPlayer( { <?= $_TV['poptions']?> }, flashVersion['major'] );
  </script>
<?
} // END: function eplayer

?>