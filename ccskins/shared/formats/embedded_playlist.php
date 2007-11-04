<div >
%map(reguser,is_logged_in);
<? $A['R'] = array_combine( array('cart_id'), array( 1 ) ); ?>
%call('playlist.xml/playlist_list_lines')%
</div>