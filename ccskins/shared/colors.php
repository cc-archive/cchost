<?
if( defined('SHARED_COLORS') )
    return;

define('SHARED_COLORS',1);

header('Content-type: text/css');

if( !defined('IN_CC_HOST') )
   die('/* Welcome to ccHost */');

global $CC_GLOBALS;

if( empty($CC_GLOBALS['skin_properties']['color_scheme']) )
    exit;

$scheme = $CC_GLOBALS['skin_properties']['color_scheme'];

print "/* Color scheme: {$scheme} */\n";


$schemes = cc_get_config('color-schemes');
if( !empty($schemes) )
{
    foreach( $schemes as $S )
    {
        if( $S['name'] == $scheme )
        {
            eval($S['scheme']);
            break;
        }
    }
}
else
{
    $bg = '#FFEEEE';
    $color = '#FF0000';
    $dark = '#775555';
    $med = '#FF8888';
    $light = '#FFDDDD';
    $highlight = '#FFCCCC';
}

?>

.bg { background-color: <?= $bg ?>; }
.color { color: <?= $color ?>; }
.dark_bg { background-color: <?= $dark ?>; }
.dark_color { color: <?= $dark ?>; }
.dark_border { border-color: <?= $dark ?>; }
.med_bg { background-color: <?= $med ?>; }
.med_border { border-color: <?= $med ?>; }
.med_color { color: <?= $med ?>; }
.light_bg { background-color: <?= $light ?>; }
.light_border { border-color: <?= $light ?>; }
.light_color { color: <?= $light ?>; }
.highlight_bg { background-color: <?= $highlight ?>; }
.highlight_border { border-color: <?= $highlight ?>; }
.highlight_color { color: <?= $highlight ?>; }
.selected_area { background-color: <?= $dark; ?>; color: <?= $light; ?>; }
a, a:visited { color: <?= $dark ?>; }

<?


$skin_colors = $T->Search('css/colors.css');
if( $skin_colors )
    require_once($skin_colors);

exit;
?>