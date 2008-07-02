%%
[meta]
    type     = embed
    desc     = _('Links + Y!(tm) Button Player in iFrame')
    dataview = passthru
[/meta]
%%
<? $url = $A['query-url'] . $A['qstring'] . '&t=yahoo_player_links&f=html'; 
?>
<iframe style="border: 0px;width:90%;margin:0px auto;" id="yframe" src="%(#url)%"></iframe>

