<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

global $_TV;
global $CC_GLOBALS;


$str_by      = _('by:');
$str_date    = _('date:');
$str_tags    = _('tags:');
$str_license = _('license:');
$str_nsfw_t  = _('This upload might be ');
$str_nsfw    = _('Not Safe For Work');
$str_edpick  = _('Editorial pick...');
$str_uses    = _('Uses samples from:');
$str_more    = _('More...');
$str_noone   = _('(no one has sampled this)');
$str_usedin  = _('Samples are used in:');
$str_down    = _('Download');
$str_length  = _('length:');
$str_bpm     = _('BPM:');
$str_IEtip   = _('IE: Right-click select \'Save Target As\'');
$str_Mactip  = _('Mac: Control-click select \'Save Link As\'');
$str_flagtip = _('Flag this upload for possible violation of terms');
$str_feature = _('featuring:');
$str_last_ed = _('last change:');
$str_play    = _('Play:');


$R =& $_TV['file_records'][0];

$date           = CC_datefmt($R['upload_date'],'F d, Y h:i a');
$rate_head_show = empty($R['ratings_score']) ? 'none' : 'inline';
$upload_id      = $R['upload_id'];

$aboutsha1 = '';
foreach( $R['files'] as $F )
{
    if( !empty($F['file_extra']['sha1']) )
    {
        $aboutsha1 = 'about="urn:sha1:' . $F['file_extra']['sha1'] . "\"\n";
        break;
    }
}

$micro_decs =<<<EOF
    $aboutsha1 xmlns:cc="http://creativecommons.org/ns#"
    xmlns:hmedia="http://www.microformats.org/2007/12/hmedia/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"               
EOF;

print "<div id=\"upload_page\" $micro_decs >\n";

        //----------------------------------
        //
        // File Info
        //
        //------------------------------------

$html = '';
$root_url = $_TV['root-url'];
$detail = true;
$chop = false;
function _mixter_dump_ratings() { }

$html .= '<table id="upload_detail" cellpadding="0" cellspacing="0">';

// flash player 
if( !empty($R['fplay_url']) )
{

    $html =<<<EOF
<tr>
    <th><span class="cc_player_label">{$str_play}</span> </th>
    <td><a class="cc_player_button cc_player_hear" id="_ep_{$R['upload_id']}" href="{$R['fplay_url']}"> </a></td>
</tr>
EOF;
    print $html;
}

$flag_link = empty($R['flag_url']) ? '' : 
                "<a class=\"cc_flag_url\" title=\"$str_flagtip\" href=\"{$R['flag_url']}\"><span>&nbsp;</span></a>";


if( isset($R['upload_last_edit']) )
{
    $last_date = date('F d, Y h:i a', strtotime($R['upload_last_edit']));
    $last_op = empty($R['last_op_str']) ? '' : '(' . $R['last_op_str'] . ')';

    $html =<<<EOF
  <tr>
    <th>$str_last_ed</th>
    <td>
      <span class="cc_last_op">$last_date</span>
      <span class="cc_last_op_str">$last_op</span>
    </td>
  </tr>
EOF;
    print $html;
}

$html .=<<<EOF
<tr>
  <th>$str_date</th>
  <td property="dc:date">$date</td>
</tr>
<tr>
  <th>$str_tags</th>
  <td>
EOF;

        $taglinks = $R['upload_taglinks'];
        $tcount = count($taglinks);
        for( $n = 0; $n < $tcount; $n++)
        {
            $T =& $taglinks[$n];
            $comma = $n == ($tcount - 1) ? '' : ', ';
            $html .=<<<EOF
               <a href="{$T['tagurl']}" rel="nofollow tag" class="cc_tag_link" >{$T['tag']}</a>$comma
EOF;
        }

        $html .=<<<EOF
          </td>
        </tr>
EOF;

if( !empty($R['files'][0]['file_format_info']['ps']) )
{
    $ps = $R['files'][0]['file_format_info']['ps'];
    $html .=<<<EOF
    <tr>
        <th>$str_length</th>
        <td property="hmedia:duration">$ps</td>
    </tr>
EOF;
}
if( !empty($R['upload_extra']['bpm']) )
{
    $bpm = $R['upload_extra']['bpm'];
    $html .=<<<EOF
    <tr>
        <th>$str_bpm</th>
        <td>$bpm</td>
    </tr>
EOF;
}


print "<table id=\"uload_attribution\" cellspacing=\"0\" cellpadding=\"0\" >\n";

// attribution (including collab projects)
//
if( empty($R['upload_extra']['collab']) )
{
    $html .=<<<EOF
    <tr>
      <th>$str_by</th>
      <td>$flag_link<a rel="dc:creator" href="{$R['artist_page_url']}" class="cc_user_link">{$R['user_real_name']}</a></td>
    </tr>
EOF;
}
else
{
    $collab_id = $R['upload_extra']['collab'];
    $rurl = $R['collab']['base_purl'];
    $culinks = array();
    foreach( $R['collab']['users'] as $U )
    {
        $xurl = $url . '/' . $U['user_name'];
        $culinks[] = "<a href=\"$xurl\">{$U['user_real_name']}</a>";
    }
    $collab_links = join( ', ', $culinks );
    $collab_url = ccl('collab',$collab_id);
    $html .=<<<EOF
    <tr>
      <th>project:</th>
      <td><a  href="{$collab_url}"><span style="font-weight:bold">{$R['collab']['collab_name']}</span></a></td>
    </tr>
    <tr>
      <th>collaborators:</th>
      <td>{$collab_links}</td>
    </tr>
EOF;
}

if( !empty($R['upload_extra']['featuring']) )
{
    $html .=<<<EOF
    <tr>
      <th>$str_feature</th>
      <td><span class="cc_featuring">{$R['upload_extra']['featuring']}</td>
    </tr>
EOF;
}

print "</table>\n";

        $rurl = $root_url . '/ccimages/lics/small';
        if( empty($R['local_menu']['download'][0]['action']) )
            $about = '';
        else
            $about = "about=\"{$R['local_menu']['download'][0]['action']}\"";

        $html .=<<<EOF
        <tr>
            <th>$str_license</th>
            <td><a href="{$R['license_url']}" 
                 rel="license"
                 $about         
                 title="{$R['license_name']}" ><img 
                  src="$rurl-{$R['license_logo']}" /></a></td>
        </tr>
EOF;
        if( !empty($R['upload_extra']['nsfw']) )
        {
            $html .=<<<EOF
        <tr>
           <td colspan="2"><span class="cc_nsfw">$str_nsfw_t "<a 
               href="http://en.wikipedia.org/wiki/NSFW"
                target="_blank">$str_nsfw</a>"</span></td>
        </tr>
EOF;
        }

        $html .= _mixter_dump_ratings($R);

        if( !empty($R['upload_extra']['edpicks']) )
        {         
            $edpicks =& $R['upload_extra']['edpicks'];
            $ecount = count($edpicks);
            $keys = array_keys($edpicks);
            $redstar = $root_url . '/ccimages/stars/star-red.gif';
            for( $n = 0; $n < $ecount; $n++ )
            {
                $pick =& $edpicks[ $keys[$n] ];
                $html .=<<<EOF
                <tr>
                    <td colspan="2">
                        <img src="$redstar" height="17" width="17" /> 
                        <a href="{$pick['review_url']}">$str_edpick</a>
                    </td>
                </tr>
EOF;
            }
        }

        if( !empty($R['result_info']) )
        {
            $html .=<<<EOF
                <tr>
                    <td colspan="2" class="cc_search_result_info" >
                       {$R['result_info']}
                    </td>
                </tr>
EOF;
        }

        $html .=<<<EOF
    </table><!-- table:file_info -->
EOF;

        //----------------------------------
        //
        // Action Buttons/Menus
        //
        //------------------------------------
        $html .=<<<EOF
    <div id="cc_action_buttons">

EOF;

        if( !empty($R['script_link']) )
        {
            $scriptlink =<<<EOF
          <a id="cc_streamfile" href="javascript:void(0)" onclick="{$R['script_link']['url']}"><span 
                >{$R['script_link']['text']}</span></a> 
EOF;
        }

        if( $detail )
        {
            $html .= '<table class="cc_buttonpadding">';

            if( !empty($scriptlink) )
                $html .= "<tr><td>$scriptlink</td></tr>";

            $menu =& $R['local_menu'];
            $mcount = count($menu);
            $keys = array_keys($menu);
            for( $n = 0; $n < $mcount; $n++ )
            {
                $grp =& $menu[$keys[$n]];
                $gkeys = array_keys($grp);
                $gcount = count($grp);
                for( $m = 0; $m < $gcount; $m++ )
                {
                    $item =& $grp[$gkeys[$m]];
                    $type  = empty($item['type']) ? '' : 'type="' . $item['type'] . '"';
                    $class = empty($item['class']) ? '' : 'class="' . $item['class'] . '"';
                    $title = empty($item['tip']) ? '' : 'title="' . $item['tip'] . '"';
                    $onclick = empty($item['onclick']) ? '' : 'onclick="' . $item['onclick'] . '"';
                    $parent_id = empty($item['parent_id']) ? '' : 'id="' . $item['parent_id'] . '"';
                    $rel = (empty($item['id']) || ($item['id'] != 'cc_downloadbutton')) ? '' : 'rel="hmedia:download"';
                    if( !empty($item['action']) )
                    {
                        $html .=<<<END
                        <tr>
                          <td {$parent_id} ><a href="{$item['action']}" $type $class $onclick $title $rel
                                id="{$item['id']}"><span>{$item['menu_text']}</span></a></td>
                        </tr>
END;
                    }
                }
            }

            $html .= '</table> <!-- button padding -->';
        }
        else // not detail:
        {
                $html .=<<<EOF
            <div class="cc_buttonpadding">
EOF;
            if( !empty($scriptlink) )
                $html .= $scriptlink;

            if( !empty($R['stream_link']) )
            {
                $html .=<<<EOF
              <a id="cc_streamfile" href="{$R['stream_link']['url']}"><span>{$R['stream_link']['text']}</span></a> 
EOF;
            }

            $html .=<<<EOF
              <a id="cc_downloadbutton" href="javascript: void(0);" 
                     onclick="cc_show_dl_box(event,'$upload_id',this);" ><span>$str_down</span></a>
              <div class="cc_file_download_popup" id="dlmenu$upload_id" style="display:none;position:absolute;">
                  <p>
                      $str_IEtip<br />
                      $str_Mactip<br />
                  </p>
EOF;
            $dl_items =& $R['local_menu']['download'];
            $dcount = count($dl_items);
            if( $dcount )
                $keys = array_keys($dl_items);
            for( $n = 0; $n < $dcount; $n++ )
            {
                $k = $keys[$n];
                $mi =& $dl_items[$k];
                $html .=<<<END
                  <p> <a rel="hmedia:download" href="{$mi['action']}" id="{$mi['id']}" type="{$mi['type']}" title="{$mi['tip']}"><span>{$mi['menu_text']}</span></a>
                  </p>
END;
            }

            $html .= '</div>';

            if( !empty($R['local_menu']['share']) )
            {
                $mi =& $R['local_menu']['share']['share_link'];
                $onclick = empty($mi['onlick']) ? '' : "onclick=\"{$mi['onclick']}\"";
                $html .=<<<END
                    <p><a href="{$mi['action']}" id="{$mi['id']}" $onclick  title="{$mi['tip']}"><span>{$mi['menu_text']}</span></a></p>
END;
            }

            if( !empty($R['local_menu']['playlist']) )
            {
                $M =& $R['local_menu']['playlist']['playlist_menu'];
                if( !empty($M['parent_id']) ) // this can happen on banned or hidden admin records
                {
                    $html .=<<<END
                    <div id="{$M['parent_id']}">
                    <a id="commentcommand" class="cc_playlist_button" href="{$M['action']}"><span>{$M['menu_text']}</span></a>
                    </div>
END;
                }
            }

            if( !empty($R['local_menu']['comment']['comments']) )
            {
                $M =& $R['local_menu']['comment']['comments'];
                $html .=<<<EOF
            <a id="commentcommand" href="{$M['action']}"><span>{$M['menu_text']}</span></a>
EOF;
            }

            $html .=<<<EOF

        </div><!-- buttonpadding -->
EOF;

        } // end if($detail)

        $html .=<<<EOF

    </div><!-- action_buttons -->
EOF;

        if( $detail )
            $html .= '<br clear="left">';

        
        //----------------------------------
        //
        // Sample/Remix History
        //
        //------------------------------------

        if( empty($R['skip_remixes']) )
        {

            $html .=<<<EOF

        <div id="cc_sample_history">

EOF;

            if( !empty($R['has_parents']) )
            {
                $html .=<<<EOF
            <div id="cc_downstream_mixes">
                <h3>$str_uses</h3>
                <div id="cc_history_wrapper">
EOF;
                $pcount = count($R['remix_parents']);
                $keys = array_keys($R['remix_parents']);
                for( $n = 0; $n < $pcount; $n++)
                {
                    $k = $keys[$n];
                    $rs =& $R['remix_parents'][$k];
                    $hack = 14;
                    if( ($n == ($pcount-1)) && !empty($R['more_parents_link']) )
                    {
                        $hack = 6;
                        $html .=<<<EOF
                          <a class="cc_remix_more_link" href="{$R['more_parents_link']}">$str_more</a>
EOF;
                    }
                    $fname = CC_strchop($rs['upload_name'],$hack,$chop);
                    $aname = CC_strchop($rs['user_real_name'],$hack,$chop);
                    $furl = $rs['file_page_url'];
                    $aurl = $rs['artist_page_url'];
                    $html .=<<<EOF
                      <p>
                        <a href="$furl" class="cc_file_link">$fname</a> $str_by
                        <a href="$aurl" class="cc_user_link">$aname</a>
                      </p>

EOF;
                }

                $html .=<<<EOF

                </div> <!-- history_wrapper -->

            </div><!-- downstream_mixes -->

EOF;
            }

            if( !empty($R['local_menu']['remix']['replyremix']) )
                $reply = $R['local_menu']['remix']['replyremix'];
            else
                $reply = '';

            $is_orphan    = !empty($R['is_orphan_original']);
            $has_children = !empty($R['has_children']) ;
            $do_children  = $has_children || $reply || $is_orphan;

            if( $do_children )
            {
                $html .= '<div id="cc_upstream_mixes">';
                if( $is_orphan )
                {
                    $html .= '<p id="noonesampled">' . $str_noone . '</p>';
                }

                if( $has_children )
                {
                    $html .=<<<EOF
                <h3>$str_usedin</h3>
                <div id="cc_history_wrapper">

EOF;
                    $ccount = count($R['remix_children']);
                    $keys = array_keys($R['remix_children']);
                    $more_link = empty($R['more_children_link']) ? '' : $R['more_children_link'];
                    for( $n = 0; $n < $ccount; $n++ )
                    {
                        $k = $keys[$n];
                        $rs =& $R['remix_children'][$k];
                        $hack = 14;
                        if( ($n == ($ccount-1)) && $more_link )
                        {
                            $html .=<<<EOF
                            <a class="cc_remix_more_link" href="$more_link">$str_more</a>
EOF;
                            $hack = 6;
                        }

                    $fname = CC_strchop($rs['upload_name'],$hack,$chop);
                    $aname = CC_strchop($rs['user_real_name'],$hack,$chop);
                    $furl = $rs['file_page_url'];
                    $aurl = $rs['artist_page_url'];
                    $html .=<<<EOF
                      <p>
                        <a href="$furl" class="cc_file_link">$fname</a> $str_by
                        <a href="$aurl" class="cc_user_link">$aname</a>
                      </p>
EOF;
                    }
                    $html .=<<<EOF
                </div><!-- history_wrapper -->
EOF;
                }

                if( $reply )
                {
                    $html .=<<<EOF
             <p class="cc_postreply" ><a href="{$reply['action']}">{$reply['menu_text']}...</a></p>

EOF;
                }

                $html .=<<<EOF
            </div><!-- upstream_mixes -->

EOF;
            }
            
            $html .= '</div><!-- sample_history -->';

        } // not skip remixes

        if( $detail )
        {
            $html .= '</div>'; // microdecs
        }
        else
        {
            $html .=<<<EOF

    </div> <!-- xclass -->
  </div> <!-- record_listing -->
  <div style=""><br clear="all"/></div>
EOF;
        }


print $html;



?>
