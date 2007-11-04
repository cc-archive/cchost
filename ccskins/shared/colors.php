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

switch($scheme)
{
    case 'mono':
    {
        $bg = '#FFFFFF';
        $color = '#000000';
        $dark = '#555555';
        $med = '#888888';
        $light = '#DDDDDD';
        $highlight = '#CCCCCC';
        break;
    }

    case 'blue':
    {
        $bg = '#EEEEFF';
        $color = '#0000FF';
        $dark = '#555577';
        $med = '#8888FF';
        $light = '#DDDDFF';
        $highlight = '#CCCCFF';
        break;
    }

    case 'green':
    {
        $bg = '#EEFFEE';
        $color = '#00FF00';
        $dark = '#557755';
        $med = '#88FF88';
        $light = '#DDFFDD';
        $highlight = '#CCFFCC';
        break; 
    }

    default:
    {
        $bg = '#FFEEEE';
        $color = '#FF0000';
        $dark = '#775555';
        $med = '#FF8888';
        $light = '#FFDDDD';
        $highlight = '#FFCCCC';
        break; 
    }
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
.sel_line { background-color: <?= $dark; ?>; color: <?= $light; ?>; }
.unsel_line { background-color: <?= $light; ?>; color: <?= $dark; ?>; }

a, a:visited { color: <?= $dark ?>; }

<?


$skin_colors = $T->Search('css/colors.css',true,__FILE__);
if( $skin_colors )
    require_once($skin_colors);

exit;
?>