<?
global $_TV;

?><div >
<?
$_TV['reguser'] = $_TV['is_logged_in'];
$_TV['R'] = array_combine( array('cart_id'), array( 1 ) );
_template_call_template('playlist.xml/playlist_list_lines');

?></div>