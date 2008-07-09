%%
[meta]
    desc     = _('Browse a list of expandable playlists')
    type     = template_component
    dataview = playlists
[/meta]
%%
<!-- template playlist_2_browse -->
<link  rel="stylesheet" type="text/css" href="%url('css/playlist.css')%" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="%url('css/info.css')%"  title="Default Style"></link>
<script  src="%url('/js/info.js')%"></script>
<script  src="%url('js/playlist.js')%" ></script>
<div  id="playlist_browser">
%loop(records,PL)%  
  <div  class="cc_playlist_line med_bg" id="_pl_%(#PL/cart_id)%">%(#PL/cart_name)% 
        <span class="cc_playlist_dyn_user">%text(str_pl_created_by)% <!-- -->%(#PL/user_real_name)%</span>
    %if_not_null(#PL/cart_dynamic)%
       <span class="cc_playlist_dyn_label">(%text(str_pl_dynamic)%)</span>
    %end_if%
    %if_null(#PL/cart_dynamic)%
       <span> %text(str_pl_items)%: %(#PL/cart_num_items)%</span>
    %end_if%
    <span>%(#PL/cart_tags_munged)%</span>
    </div>
%end_loop%
</div>

%map(player_options,'autoHook: false')%
%call('playerembed.xml/eplayer')%

<script type="text/javascript">
    var plb = new ccPlaylistBrowser( 'playlist_browser', '' );
</script>

%call(prev_next_links)%
