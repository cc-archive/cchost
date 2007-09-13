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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

function & _mixter_dump_ratings(&$record)
{
    $html = '';

    if( empty($record['ok_to_rate']) && empty($record['ratings_score']) && empty($record['reviews_link'])  )
        return $html;

    $upload_id = $record['upload_id'];
    $root_url  = cc_get_root_url();

    $html = <<<EOF

    <!-- ratings -->
    <tr>
        <td colspan="2" style="padding:4px 0px 4px 2px;vertical-align:bottom;"><span id="cc_rate_head_$upload_id"></span>
        <div class="cc_rate_it_line">
EOF;

    if( !empty($record['ok_to_rate']) )
    {
        $html .= <<<EOF
            <div class="rcmd" id="cc_star_block_$upload_id" > <a id="ratingscommand" onclick="cc_star_pick($upload_id,5)"
                href="javascript://say DAAAAAAAAAAAAAM!"><span>Recommend</span></a></div>
EOF;
    }

    if( !empty($record['reviews_link']) )
    {
        $html .=<<<EOF
            <a href="{$record['reviews_link']['url']}" class="rvlink">{$record['reviews_link']['text']}</a>
EOF;
    }

    $html .= <<<EOF
        <span id="cc_rate_block_$upload_id" class="rate_block">
EOF;

    if( !empty($record['ratings_score']) )
    {
        $html .= <<<EOF
            {$record['upload_num_scores']} Recommends</b><img src="{$root_url}/ccimages/rate_it.png" />
EOF;
    }


    $html .= "</span></div></td></tr>\n<!-- end ratings -->\n";

    // ---- End of Ratings 

    return $html;

}

function & _mixter_dump_records(&$records,$xclass)
{
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

    $chop = CCPage::GetPageArg('chop');

    $html = '';

    $count = count($records);
    for( $i = 0; $i < $count; $i++ )
    {
        $record =& $records[$i];

        $date           = CC_datefmt($record['upload_date'],'F d, Y h:i a');
        $rate_head_show = empty($record['ratings_score']) ? 'none' : 'inline';
        $upload_id      = $record['upload_id'];
        $root_url       = cc_get_root_url();
        $detail         = !empty($record['works_page']);

        if( !$detail )
        {
            $html .=<<<EOF
  <div id="cc_record_listing"> 
    <div class="$xclass">
EOF;
        }

        //----------------------------------
        //
        // File Info
        //
        //------------------------------------

        $name_cls = empty($record['upload_name_cls']) ? '' : $record['upload_name_cls'];

        $html .= '<table id="cc_file_info" cellpadding="0" cellspacing="0">';

        if( !$detail )
        {
            $html .=<<<EOF
         <tr>
            <td colspan="2">
                <h2><a href="{$record['file_page_url']}" ><span
                  class="$name_cls" >{$record['upload_name']}</span></a></h2>
            </td>
        </tr>
EOF;
        }

        // flash player 
        if( !empty($record['fplay_url']) )
        {
            $url = $record['fplay_url'];

            $html .=<<<EOF
            <tr>
                <th><span class="cc_player_label">{$str_play}</span> </th>
                <td><a class="cc_player_button cc_player_hear" id="_ep_{$upload_id}" href="{$url}"> </a></td>
            </tr>
EOF;
        }

        if( $detail && !empty($record['flag_url']) )
        {
            $flag_link = "<a class=\"cc_flag_url\" title=\"$str_flagtip\" href=\"{$record['flag_url']}\"><span>&nbsp;</span></a>";
        }
        else
        {
            $flag_link = '';
        }

        // attribution (including collab projects)
        //
        if( empty($record['upload_extra']['collab']) )
        {
            $html .=<<<EOF
            <tr>
              <th>$str_by</th>
              <td>$flag_link<a href="{$record['artist_page_url']}" class="cc_user_link">{$record['user_real_name']}</a></td>
            </tr>
EOF;
        }
        else
        {
            require_once('ccextras/cc-collab.inc');
            $collab_id = $record['upload_extra']['collab'];
            $collaborators = CCCollab::_get_collab_users($collab_id);
            $collab_links = '';
            $comma = '';

            foreach( $collaborators as $CLB )
            {
                $collab_links .= $comma . '<a href="' . ccl('people',$CLB['user_name']) . '">' 
                                        . $CLB['user_real_name'] . '</a>';
                $comma = ', ';
            }

            $collabs = CCCollabs::GetTable();
            $collab_name = $collabs->QueryItemFromKey('collab_name',$collab_id);
            $collab_url = ccl('collab',$collab_id);
            $html .=<<<EOF
            <tr>
              <th>attribution:</th>
              <td><a  href="{$collab_url}"><span style="font-weight:bold">{$collab_name}</span></a></td>
            </tr>
            <tr>
              <th>collaborators:</th>
              <td>{$collab_links}</td>
            </tr>
EOF;
        }

        if( $detail && !empty($record['upload_extra']['featuring']) )
        {
            $html .=<<<EOF
            <tr>
              <th>$str_feature</th>
              <td><span class="cc_featuring">{$record['upload_extra']['featuring']}</td>
            </tr>
EOF;
        }

        if( $detail && isset($record['upload_last_edit']) )
        {
            $last_date = date('F d, Y h:i a', strtotime($record['upload_last_edit']));
            $last_op = empty($record['last_op_str']) ? '' : '(' . $record['last_op_str'] . ')';

            $html .=<<<EOF
          <tr>
            <th>$str_last_ed</th>
            <td>
              <span class="cc_last_op">$last_date</span>
              <span class="cc_last_op_str">$last_op</span>
            </td>
          </tr>
EOF;
        }

        $html .=<<<EOF
        <tr>
          <th>$str_date</th>
          <td>$date</td>
        </tr>
        <tr>
          <th>$str_tags</th>
          <td>
EOF;

        $taglinks = $detail ? $record['upload_taglinks'] : $record['usertag_links'];
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

        if( $detail )
        {
            if( !empty($record['files'][0]['file_format_info']['ps']) )
            {
                $ps = $record['files'][0]['file_format_info']['ps'];
                $html .=<<<EOF
                <tr>
                    <th>$str_length</th>
                    <td>$ps</td>
                </tr>
EOF;
            }
            if( !empty($record['upload_extra']['bpm']) )
            {
                $bpm = $record['upload_extra']['bpm'];
                $html .=<<<EOF
                <tr>
                    <th>$str_bpm</th>
                    <td>$bpm</td>
                </tr>
EOF;
            }
        }

        $rurl = $root_url . '/ccimages/lics/small';
        if( empty($record['local_menu']['download'][0]['action']) )
            $about = '';
        else
            $about = "about=\"{$record['local_menu']['download'][0]['action']}\"";

        $html .=<<<EOF
        <tr>
            <th>$str_license</th>
            <td><a href="{$record['license_url']}" 
                 rel="license"
                 $about         
                 title="{$record['license_name']}" ><img 
                  src="$rurl-{$record['license_logo']}" /></a></td>
        </tr>
EOF;
        if( !empty($record['upload_extra']['nsfw']) )
        {
            $html .=<<<EOF
        <tr>
           <td colspan="2"><span class="cc_nsfw">$str_nsfw_t "<a 
               href="http://en.wikipedia.org/wiki/NSFW"
                target="_blank">$str_nsfw</a>"</span></td>
        </tr>
EOF;
        }

        $html .= _mixter_dump_ratings($record);

        if( !empty($record['upload_extra']['edpicks']) )
        {         
            $edpicks =& $record['upload_extra']['edpicks'];
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

        if( !empty($record['result_info']) )
        {
            $html .=<<<EOF
                <tr>
                    <td colspan="2" class="cc_search_result_info" >
                       {$record['result_info']}
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

        if( !empty($record['script_link']) )
        {
            $scriptlink =<<<EOF
          <a id="cc_streamfile" href="javascript:void(0)" onclick="{$record['script_link']['url']}"><span 
                >{$record['script_link']['text']}</span></a> 
EOF;
        }

        if( $detail )
        {
            $html .= '<table class="cc_buttonpadding">';

            if( !empty($scriptlink) )
                $html .= "<tr><td>$scriptlink</td></tr>";

            $menu =& $record['local_menu'];
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
                    $parent_id = empty($item['parent_id']) ? '' : 'id="' . $item['parent_id'] . '"';
                    if( !empty($item['action']) )
                    {
                        $html .=<<<END
                        <tr>
                          <td {$parent_id} ><a href="{$item['action']}" $type $class
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

            if( !empty($record['stream_link']) )
            {
                $html .=<<<EOF
              <a id="cc_streamfile" href="{$record['stream_link']['url']}"><span>{$record['stream_link']['text']}</span></a> 
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
            $dl_items =& $record['local_menu']['download'];
            $dcount = count($dl_items);
            if( $dcount )
                $keys = array_keys($dl_items);
            for( $n = 0; $n < $dcount; $n++ )
            {
                $k = $keys[$n];
                $mi =& $dl_items[$k];
                $html .=<<<END
                  <p> <a href="{$mi['action']}" id="{$mi['id']}" type="{$mi['type']}" title="{$mi['tip']}"><span>{$mi['menu_text']}</span></a>
                  </p>
END;
            }

            $html .= '</div>';

            if( !empty($record['local_menu']['playlist']) )
            {
                $M =& $record['local_menu']['playlist']['playlist_menu'];
                if( !empty($M['parent_id']) ) // this can happen on banned or hidden admin records
                {
                    $html .=<<<END
                    <div id="{$M['parent_id']}">
                    <a id="commentcommand" class="cc_playlist_button" href="{$M['action']}"><span>{$M['menu_text']}</span></a>
                    </div>
END;
                }
            }

            if( !empty($record['local_menu']['comment']['comments']) )
            {
                $M =& $record['local_menu']['comment']['comments'];
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

        if( empty($record['skip_remixes']) )
        {

            $html .=<<<EOF

        <div id="cc_sample_history">

EOF;

            if( !empty($record['has_parents']) )
            {
                $html .=<<<EOF
            <div id="cc_downstream_mixes">
                <h3>$str_uses</h3>
                <div id="cc_history_wrapper">
EOF;
                $pcount = count($record['remix_parents']);
                $keys = array_keys($record['remix_parents']);
                for( $n = 0; $n < $pcount; $n++)
                {
                    $k = $keys[$n];
                    $rs =& $record['remix_parents'][$k];
                    $hack = 14;
                    if( ($n == ($pcount-1)) && !empty($record['more_parents_link']) )
                    {
                        $hack = 6;
                        $html .=<<<EOF
                          <a class="cc_remix_more_link" href="{$record['more_parents_link']}">$str_more</a>
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

            if( !empty($record['local_menu']['remix']['replyremix']) )
                $reply = $record['local_menu']['remix']['replyremix'];
            else
                $reply = '';

            $is_orphan    = !empty($record['is_orphan_original']);
            $has_children = !empty($record['has_children']) ;
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
                    $ccount = count($record['remix_children']);
                    $keys = array_keys($record['remix_children']);
                    $more_link = empty($record['more_children_link']) ? '' : $record['more_children_link'];
                    for( $n = 0; $n < $ccount; $n++ )
                    {
                        $k = $keys[$n];
                        $rs =& $record['remix_children'][$k];
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

        if( !$detail )
        {
            $html .=<<<EOF

    </div> <!-- xclass -->
  </div> <!-- record_listing -->
  <div style=""><br clear="all"/></div>
EOF;
        }

        if( $i != ($count-1) )
        {
        $html .=<<<END
      <p class="cc_songbreak" id="$xclass">&nbsp;</p>
END;
        }
    }

    return $html;
}


?>