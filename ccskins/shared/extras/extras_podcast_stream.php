<?/*

[meta]
    type  = extras
    desc  = _('Podcast and Stream this page (for audio)')
[/meta]

*/

if( empty($A['qstring']) ) 
    return;

$qstring = $A['qstring'];
$media_args = parse_str($qstring);
if( !empty($media_args['datasource']) && ($media_args['datasource'] != 'uploads') )
    return;

$q = $A['q'];

?>  
<p><?=$T->String('str_media')?></p>
<ul>
<?

    if ( !empty($A['enable_playlists'])) 
    {
        $script = true;
        if( !empty($A['get']['offset']) ) 
        {
            $offs = $A['get']['offset']; 
        } 
        else
        {
            $offs = 0;
        } 
        ?><li><a id="mi_play_page" href="javascript://play page" onclick="ppage()"><?=$T->String('str_play_this_page')?></a></li><?
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

