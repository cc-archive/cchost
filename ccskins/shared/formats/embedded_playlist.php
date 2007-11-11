<div >
<?
$A['reguser'] = $A['is_logged_in'];
$A['R'] = array_combine( array('cart_id'), array( 1 ) ); 
$T->Call('playlist.tpl/playlist_list_lines');
?>
</div>