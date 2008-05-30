<?
// Call this template: api/query?t=user_feeds&datasource=user&user=USER_NAME
//
// where USER_NAME is the login name of the user
?>
%%
[meta]
    desc = _('User Feeds')
    type = template_component
    embedded = 1
    dataview = user_feeds
[/meta]
[dataview]
function user_feeds_dataview()
{
    $fancy_name = cc_fancy_user_sql();

    $sql =<<<EOF
    SELECT user_name, user_id, user_real_name, {$fancy_name}
    %columns%
    FROM cc_tbl_user
    %where%
EOF;

    return array( 'e' => array(),
                  'sql' => $sql 
                );
}
[/dataview]
%%
%if_empty(records)%
    %return%
%end_if%

<? 
global $urec, $rssfeed, $rssimg, $atomfeed, $atomimg, $xspffeed, $xspfimg, $podimg;

$urec = $A['records']['0'];
$urec['fancy_user_name'] = '<b>' . $urec['fancy_user_name'] . '</b>';
$rssfeed  = $A['query-url'] . 'f=rss&limit=15&';
$rssimg   = '<img src="' . $T->URL('images/feed-icon16x16.png') . '" />';
$podimg   = '<img src="' . $T->URL('images/menu-podcast.png') . '" />';
$atomfeed = $A['query-url'] . 'f=atom&limit=15&';
$atomimg  = '<img src="' . $T->URL('images/feed-atom16x16.png') . '" />';
$xspffeed = $A['query-url'] . 't=xspf_10&f=xspf&limit=15&';
$xspfimg  = '<b>XSPF</b>';
?>
<style>
div.user_feeds {
    width: 600px;
    margin: 0px auto;
}

.keyhead {
    margin: 1px;
    font-weight: bold;
}
table.keytable {
    margin: 12px;
    border: 1px solid #777;
}
table.keytable td.keyimg {
    text-align: right;
}
table.keytable td {
    height: 13px; 
    color: #777;
}
table.linkstable td {
    padding-left: 5px;
}
</style>
<h1>Feeds and Podcasts for <?= $urec['fancy_user_name'] ?></h1>
<div class="user_feeds">

<?
function gen_ufl($q,$title,$pod=true)
{
    global $urec, $rssfeed, $rssimg, $atomfeed, $atomimg, $xspffeed, $xspfimg,$podimg;

    $utitle = urlencode( preg_replace('#</?b>#', '', $title ) );

    $pod  = empty($pod) ? '' : "<a href=\"{$rssfeed}{$q}{$urec['user_name']}&title={$utitle}\">{$podimg}</a>";
    $xspf = empty($pod) ? '' : "<a class=\"small_button\" href=\"{$xspffeed}{$q}{$urec['user_name']}&title={$utitle}\"><span>{$xspfimg}</span></a>";
    $atom = empty($pod) ? '' : "<a href=\"{$atomfeed}{$q}{$urec['user_name']}&title={$utitle}\">{$atomimg}</a>";

    $html =<<<EOF
 <tr>
    <td>{$pod}</td>
    <td>{$atom}</td>
    <td>{$xspf}</td>
    <td><a href="{$rssfeed}{$q}{$urec['user_name']}&title={$utitle}">{$rssimg}</a></td>
    <td>{$title}</td>
 </tr>
EOF;
    print $html;
}

print '<table class="linkstable">';
gen_ufl('user=', 'All uploads by ' . $urec['fancy_user_name']);
gen_ufl('tags=remix&user=', 'Remixes by ' . $urec['fancy_user_name']);
gen_ufl('remixesof=', 'Remixes of ' . $urec['fancy_user_name']);
gen_ufl('tags=trackback&user=', 'Uploads by ' . $urec['fancy_user_name'] . ' featured in videos, podcasts, albums, etc.');
gen_ufl('tags=trackback&remixesof=', 'Remixes of ' . $urec['fancy_user_name'] . ' featured in videos, podcasts, albums, etc.');
gen_ufl('reccby=', 'Uploads recommended by ' . $urec['fancy_user_name']);
gen_ufl('datasource=topics&type=review&user=', 'Reviews left by ' .  $urec['fancy_user_name'], false);
gen_ufl('datasource=topics&type=review&reviewee=', 'Reviews left for ' . $urec['fancy_user_name'], false);
gen_ufl('datasource=topics&thread=-1&user=', 'Forum topics and replies by '  . $urec['fancy_user_name'], false);
print '</table>';

?>

<table class="keytable">
    <tr><td class="keyimg"><?= $podimg ?></td><td><b>Podcast</b> You can drag this link to your music player (e.g. iTunes)</td></tr>
    <tr><td class="keyimg"><?= $rssimg ?></td><td><b>RSS</b> syndication feed</td></tr>
    <tr><td class="keyimg"><?= $atomimg ?></td><td><b>ATOM</b> syndication feed</td></tr>
    <tr><td class="keyimg"><a href="" class="small_button"><?= $xspfimg ?></a></td><td><b>XSPF</b> playlist</td></tr>
</table>
</div>

