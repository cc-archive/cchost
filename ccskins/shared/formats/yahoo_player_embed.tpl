%%
[meta]
    type     = embed
    desc     = _('Y!(tm) Easy Listerner Player')
    dataview = passthru
[/meta]
%%

<? 
    $autoplay = empty($_GET['autoplay']) ? '0' : '1';
    $color = empty($_GET['bgcolor']) ? 'e6e6e6' : $_GET['bgcolor'];
    $url = urlencode($A['query-url'] . $A['qstring'] . '&f=xspf'); 
    $src = "http://webjay.org/flash/xspf_player?autoload=1&autoplay={$autoplay}&playlist_url={$url}";
?>

<!-- <?= $src ?>-->
 <embed src="<?=$src?>"
    quality="high"
    bgcolor="e6e6e6"
    width="400"
    height="170"
    name="xspf_player"
    align="middle"
    type="application/x-shockwave-flash"
    />

