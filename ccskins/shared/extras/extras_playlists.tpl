<?/*
[meta]
    type = extras
    desc = _('Recent Playlists')
[/meta]
*/

$pl_lists = cc_recent_playlists(); ?>
<p>%text(str_recent_playlists)%</p>
<ul>
%loop(pl_lists,pl_list)%
  <li><a href="%(home-url)%playlist/browse/%(#pl_list/cart_id)%">%chop(#pl_list/cart_name,12)%</a></li>
%end_loop%
</ul>
<a href="%(home-url)%playlist/browse" class="cc_more_menu_link">%text(str_more_playlists)%...</a>

