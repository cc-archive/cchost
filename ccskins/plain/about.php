<?


$GLOBALS['str_skin_green'] = _('Green');
$GLOBALS['str_skin_blue'] = _('Blue');
$GLOBALS['str_skin_mono'] = _('Monochrome');
$GLOBALS['str_skin_all_media'] = _('All multi-media');
$GLOBALS['str_skin_music'] = _('Music');
$GLOBALS['str_skin_image'] = _('Pictures');
$GLOBALS['str_skin_video'] = _('Video');
$GLOBALS['str_skin_flashmod'] = _('Flash Mods');
$GLOBALS['str_skin_color_scheme'] = _('Color scheme');
$GLOBALS['str_skin_theme'] = _('Theme');

function _t_about_plain()
{
    $schemes = cc_get_config('color-schemes');
    $opts= '';
    if( !empty($schemes) )
    {
        foreach( $schemes as $scheme )
            $opts .= "<option value=\"{$scheme['name']}\">{$scheme['display_name']}</option>\n";
    }
    else
    {
        $opts=<<<EOF
        <option value="green">{$GLOBALS['str_skin_green']}</option>
        <option value="blue">{$GLOBALS['str_skin_blue']}</option>
        <option value="mono">{$GLOBALS['str_skin_mono']}</option>
EOF;
    }

    $html =<<<EOF
<table  class="cc_form_table">
<tr class="cc_form_row"><td  class="cc_form_label">${GLOBALS['str_skin_color_scheme']}:</td>
    <td><select name="skin_properties[color_scheme]" id="skin_properties[color_scheme]">
{$opts}
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

    if( !empty($GLOBALS['CC_GLOBALS']['skin_properties']['color_scheme']) )
    {
        $sel_color = $GLOBALS['CC_GLOBALS']['skin_properties']['color_scheme'];
        $html = preg_replace("/value=\"{$sel_color}\"/",'$0 selected="selected" ',$html);
    }
    if( !empty($GLOBALS['CC_GLOBALS']['skin_properties']['skin_theme']) )
    {
        $skin_theme = $GLOBALS['CC_GLOBALS']['skin_properties']['skin_theme'];
        $html = preg_replace("/value=\"{$skin_theme}\"/",'$0 selected="selected" ',$html);
    }

    return $html;
}

?>