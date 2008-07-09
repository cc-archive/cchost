%%
[meta]
    type = ajax_component
    desc = _('Show a playlist (with styles)')
    dataview = passthru
    required_args = playlist
[/meta]
%%
<!-- template playlist_2_show_one -->
%map(playlist_id,#_GET['playlist'])%
<link  rel="stylesheet" type="text/css" href="%url('css/playlist.css')%" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="%url('css/info.css')%"  title="Default Style"></link>
<script  src="%url('/js/info.js')%"></script>
<script  src="%url('js/playlist.js')%" ></script>
%map(player_options,'autoHook: false')%
%call('playerembed.xml/eplayer')%

%call('playlist_2_nostyle')%

<script type="text/javascript">
cc_playlist_hook( %(playlist_id)%, new ccPagePlayer() );
</script>
