<?
global $_TV;

$upload_flag_str     = $GLOBALS['str_flag_this_upload'] ;
$play_str            = $GLOBALS['str_play'] ;
$by_str              = $GLOBALS['str_by'] ;

$carr103 =& $_TV['records'];
$cc103   = count( $carr103);
$ck103   = array_keys( $carr103);

print "<div class=\"upload_listing\">\n" .
      "  <table class=\"upload_info\" cellpadding=\"0\" cellspacing=\"0\">\n";

/*
    [d] => 2007-09-28 17:41:40
    [i] => 11833
    [l] => noncommercial_3
    [n] => Calendar Girl (close enough remix)
    [r] => fourstones
    [t] => media,remix,bpm_115_120,non_co ...
    [u] => victor
*/

for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
    $R =& $carr103[ $ck103[ $ci103 ] ];

    $furl = $_TV['home-url'] . 'files/' . $R['u'] . '/' . $R['i'];
    $aurl = $_TV['home-url'] . 'people/' . $R['u'];
    $html =<<<EOF
        <tr><td><a href="{$furl}" class="upload_link">{$R['n']}</a> {$GLOBALS['str_by']} <a href="{$aurl}">{$R['r']}</a></td></tr>
        <tr><td class="taglinks">
EOF;
    print $html;
    $tags = explode(',',$R['t']);
    $comma = '';
    $turl = $_TV['home-url'] . 'tags/';
    foreach( $tags as $T )
    {
        print "$comma<a href=\"{$turl}{$T}\">{$T}</a>";
        $comma = ', ';
    }
    print "</td></tr>";
}
print "  </table>\n";

print "</div>\n";

_template_call_template('prev_next_links');

?>