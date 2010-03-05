<?
/*
[meta]
    type = string_profile
    desc = _('ccMixter specific strings')
[/meta]
*/

include('ccskins/shared/strings/audio.php');

$_x3 = _('<p>NOTE: Not all trackbacks are automatically approved. Please read <a href="/thread/1387">this</a>.</p>');

$GLOBALS['str_trackback_remix']         = _('If you know of a remix of "%s" by %s please enter the link below') . $_x3;
$GLOBALS['str_trackback_podcast']       = _('If you heard "%s" by %s in a podcast please enter a link to the podcast\'s home page below') . $_x3;
$GLOBALS['str_trackback_video']         = _('If you know of a video that uses "%s" by %s paste the embedding tag below') . $_x3;
$GLOBALS['str_trackback_web']           = _('If you know of a web page or blog the refers to "%s" by %s please enter the link below') . $_x3;
$GLOBALS['str_trackback_album']         = _('For an album or collection that includes "%s" by %s please enter the a link to its page below') . $_x3;

// yes, need to move this somewhere else
require_once('dig/config.php');

$GLOBALS['str_search_help_generic'] =
    '<div style="text-align:center"><h2>Looking for music?</h2>' .
    '<h3>Try our music discovery tool <a style="text-decoration:underline;" href="' . $GLOBALS['DIG_ROOT_URL'] . '">dig.ccMixter</a></h3><br />' .
    '<a href="' . $GLOBALS['DIG_ROOT_URL'] . '"><img src="/dig/images/logo-black.png" /></a>'.
    '</div>' .
    '<hr />Otherwise, use the form below to search for text, a specific user, a forum post, etc.'
    ;

?>
