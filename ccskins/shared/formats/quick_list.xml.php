<?


$carr103 =& $A['records'];
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

    $furl = $A['home-url'] . 'files/' . $R['u'] . '/' . $R['i'];
    $aurl = $A['home-url'] . 'people/' . $R['u'];
    $html =<<<EOF
        <tr><td><a href="{$furl}" class="upload_link">{$R['n']}</a> {$T->String('str_by')} <a href="{$aurl}">{$R['r']}</a></td></tr>
        <tr><td class="taglinks">
EOF;
    print $html;
    $tags = explode(',',$R['t']);
    $comma = '';
    $turl = $A['home-url'] . 'tags/';
    foreach( $tags as $Tgg )
    {
        print "$comma<a href=\"{$turl}{$Tgg}\">{$Tgg}</a>";
        $comma = ', ';
    }
    print "</td></tr>";
}
print "  </table>\n";

print "</div>\n";

$T->Call('prev_next_links');

?>