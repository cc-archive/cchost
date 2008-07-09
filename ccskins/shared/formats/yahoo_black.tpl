%%
[meta]
    type     = format
    desc     = _('Y!(tm) Embed Player (sleek black)')
    dataview = passthru
[/meta]
%%

<? 
    $autoplay = empty($_GET['autoplay']) ? '0' : '1';
    $bgcolor = empty($_GET['bgcolor']) ? 'e6e6e6' : $_GET['bgcolor'];
    $height = empty($_GET['height']) ? '40' : $_GET['height'];
    $url = $A['query-url'] . urlencode($A['qstring'] . '&f=xspf'); 
?>

 <embed src="http://webjay.org/flash/dark_player"
   flashVars='playlist_url=%(#url)%&rounded_corner=1&skin_color_1=0,-100,-29,18&skin_color_2=0,-100,-27,20'
    width="300"
    height="%(#height)%"
    name="xspf_player"
    id="xspf_player"
    wmode='transparent' 
    pluginspage='http://www.adobe.com/go/getflashplayer'
    type="application/x-shockwave-flash"
    />

