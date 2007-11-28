<?/*

[meta]
    type  = extras
    desc  = _('Podcast and Stream this page (for audio)')
[/meta]

*/

if( empty($A['artist_page']) ) 
{ 
    if( empty($A['qstring']) ) 
        return;

    $qstring = $A['qstring']; 
}
else 
{
    $qstring = 'user=' . $A['artist_page'];
}

$qstring .= '&limit=15';
$q = $A['q'];

?>  
<p><?=$T->String('str_media')?></p>
<ul>
<?

    if ( !empty($A['enable_playlists'])) 
    {
        $script = true;
        if( !empty($A['get']['offset']) ) { $offs = $A['get']['offset']; } else {  $offs = 0;} 
        if( !empty($A['fplay_title']) )   { $fplayt = $A['fplay_title']; } else {  $fplayt = $T->String('str_play_this_page'); } 
        ?><li><a id="mi_play_page" href="javascript://play page" onclick="ppage()"><?=$fplayt?></a></li><?
    }
    else
    {
        $script = false;
    }

    $url  = $A['home-url'] . 'api/query/stream.m3u' . $q .'f=m3u&' . $qstring;
    $url2 = $A['query-url'] . 'f=rss&' . $qstring;

?>   
<li><a id="mi_stream_page" href="<?=$url?>"><?= $T->String('str_stream_this_page') ?></a></li>
<li><a id="mi_podcast_page" title="<?= $T->String('str_drag_this_link') ?>" href="<?=$url2?>"><?= $T->String('str_podcast_this_page')?></a></li>
</ul>
<?
if( $script )
{
?>
<script>
function ppage() { 
    var url = home_url + 'playlist/popup' + q + 'offset=<?= $offs ?>&<?= $qstring ?>';
    var dim = "height=300,width=550";
    var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                  "resizable=1,scrollbars=1," + dim );
}
</script>
        <?
}

