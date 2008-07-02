%%
[meta]
    type     = embed
    desc     = _('Yahoo! (tm) Player (Flash Version)')
    dataview = passthru
[/meta]
%%

<? $url = urlencode($A['query-url'] . $A['qstring'] . '&format=xspf'); ?>

 <embed src="http://webjay.org/flash/xspf_player?autoload=1&autoplay=1&playlist_url=%(#url)%"
    quality="high"
    bgcolor="e6e6e6"
    width="400"
    height="170"
    name="xspf_player"
    align="middle"
    type="application/x-shockwave-flash"
    />

