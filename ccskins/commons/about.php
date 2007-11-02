<?

require_once( dirname(__FILE__) . '/strings.php');

function _t_about_commons()
{
    global $CC_GLOBALS;

    $html =<<<EOF
<table  class="cc_form_table">
<tr class="cc_form_row"><td  class="cc_form_label">${GLOBALS['str_skin_color_scheme']}:</td>
    <td><select name="skin_properties[color_scheme]" id="skin_properties[color_scheme]">
        <option value="green">{$GLOBALS['str_skin_green']}</option>
        <option value="blue">{$GLOBALS['str_skin_blue']}</option>
        <option value="mono">{$GLOBALS['str_skin_mono']}</option>
    </select></td></tr>
<tr class="cc_form_row"><td  class="cc_form_label">${GLOBALS['str_skin_theme']}:</td>
    <td><select name="skin_properties[skin_theme]}" id="skin_properties[skin_theme]}">
        <option value="all_media">{$GLOBALS['str_skin_all_media']}</option>
        <option value="music">{$GLOBALS['str_skin_music']}</option>
        <option value="video">{$GLOBALS['str_skin_video']}</option>
        <option value="image">{$GLOBALS['str_skin_image']}</option>
        <option value="flashmod">{$GLOBALS['str_skin_flashmod']}</option>
      </select></td></tr>
</table>
EOF;

    if( !empty($CC_GLOBALS['skin_properties']['color_scheme']) )
    {
        $sel_color = $CC_GLOBALS['skin_properties']['color_scheme'];
        $html = preg_replace("/value=\"{$sel_color}\"/",'$1 selected="selected" ',$html);
    }
    if( !empty($CC_GLOBALS['skin_properties']['skin_theme']) )
    {
        $skin_theme = $CC_GLOBALS['skin_properties']['skin_theme'];
        $html = preg_replace("/value=\"{$skin_theme}\"/",'$1 selected="selected" ',$html);
    }

    return $html;
}

?>