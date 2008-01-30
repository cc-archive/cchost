<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

/*
[meta]
    type = list
    desc = _('Playlist style')
    dataview = playlist_line
[/meta]
*/?>
<!-- template playlist_show_one -->
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css') ?>"  title="Default Style"></link>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<? $A['player_options'] = 'autoHook: false';?>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<?
    $T->Call('playlist.tpl/playlist_list');
    $T->Call('playerembed.xml/eplayer');
?>
<script type="text/javascript">
    new ccPlaylistMenu();
    new ccPagePlayer(<?= $A['args']['playlist']['cart_id']?>);
</script>

