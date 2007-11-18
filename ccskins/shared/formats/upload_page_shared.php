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

$css = $T->URL('css/upload_page.css');

?><link rel="stylesheet" type="text/css" title="Default Style" href="<?= $css ?>" /><?

$R =& $A['record'];

$r_args = array( &$R );
cc_get_ratings_info($R);
cc_get_remix_history($r_args,0);
$R['local_menu'] = cc_get_upload_menu($R);

//CCDebug::PrintVar($R,false);

helper_upload_date($R,$T);

print '<div id="upload_wrapper"><div id="upload_middle">' . "\n";
helper_upload_main_info($R,$A,$T);
print '</div></div><!-- upload_middle -->';

print '<div id="upload_sidebar_box">' . "\n";
helper_upload_do_sidebar($R,$A,$T);
print  '</div><!-- sidebar box -->' . "\n";

print '<div id="upload_menu_box">' . "\n";
helper_uploads_do_menus($R,$A,$T);
print '</div><!-- upload_menu_box -->' . "\n";

print '<script>';

if( !empty($A['need_ratings_hooks'] ) )
{
    $empty_star = $T->URL('images/stars/star-empty.gif');
    $edit_star  = $T->URL('images/stars/star-red.gif');
    print "new ratingsHooks('{$empty_star}','{$edit_star}');\n";
}

if( !empty($A['need_recommends_hooks'] ) )
{
}

print 'var dl_hook = new popupHookup("download_hook","download",str_download); dl_hook.hookLinks(); ' . "\n";

if( !empty($R['upload_name_cls']) )
    print "var h1e = \$\$('H1.title')[0]; h1e.innerHTML = '<span class=\"{$R['upload_name_cls']}\">' + h1e.innerHTML + '</span>';\n";

print '</script>';


/*----------------------------------
    Menu stuff
------------------------------------*/

/*
    [play] => Array
            [stream] => Array
                    [menu_text] => Stream
                    [weight] => -1
                    [group_name] => play
                    [id] => cc_streamfile
                    [access] => 4
                    [action] => http://cch5.org/media/files/stream/Transistor_Karma/11706.m3u
                    [type] => 

    [download] => Array
            [1] => Array
                    [action] => http://cch5.org/people/
                    [menu_text] => mp3  (3.44MB)
                    [group_name] => download
                    [type] => audio/mpeg
                    [weight] => 1
                    [tip] => Transistor_Karma_-_The_Waterpipe_Aria_from_Ariane_and_Barbecue_a_remix_opera.mp3
                    [access] => 4
                    [id] => cc_downloadbutton

    [remix] => Array
            [replyremix] => Array
    [share] => Array
            [share_link] => Array
    [comment] => Array
            [comments] (Write Review)
    [owner] => Array
            [editupload] => Array
            [managefiles] => Array
            [manageremixes] => Array
    [admin] => Array
            [publish] => Array  (could be under owner)
            [deleteupload] => Array
            [howididit] => Array
            [editorial] => Array
            [ban] => Array
            [uploadadmin] => Array
    [playlist] => Array
            [playlist_menu] => Array

*/

function helper_uploads_do_menus(&$R,&$A,$T)
{
    $menu = $R['local_menu'];

    /** OWNER menu *****/

    if( !empty($menu['owner']) )
    {
        print "  <div class=\"box\" id=\"download_box\"><ul>\n";

        foreach( $menu['owner'] as $mi )
            helper_upload_menu_item($mi);

        print "     </ul></div>\n";
    }

    /** PLAY/DOWNLOAD menu *****/

    print "  <div class=\"box\" id=\"download_box\"><ul>\n";

    if( !empty($R['fplay_url']) ) {
        $mi = array();
        $mi['pre'] = $T->String('str_play');
        $mi['class'] = 'cc_player_button cc_player_hear';
        $mi['id'] = "_ep_{$R['upload_id']}";
        $mi['action'] = $R['fplay_url'];
        helper_upload_menu_item($mi);
    }

    if( !empty($menu['play']) )
        foreach( $menu['play'] as $mi )
            helper_upload_menu_item($mi);

    $mi = array();
    $mi['action'] = "javascript://download";
    $mi['id'] = "_ed_{$R['upload_id']}";
    $mi['menu_text'] = $T->String('str_list_download');
    $mi['class'] = 'download_hook';
    helper_upload_menu_item($mi);

    print "</ul></div>\n";

    /** REVIEW/RATE/SHARE menu ******/

    print "<div class=\"box\" id=\"download_box\"><ul>\n";

    if( !empty($menu['comment']['comments']) )
        helper_upload_menu_item($menu['comment']['comments']);

    if( !empty($menu['playlist']['playlist_menu']) )
        helper_upload_menu_item($menu['playlist']['playlist_menu']);

    if( !empty($menu['share']['share_link']) )
        helper_upload_menu_item($menu['share']['share_link']);

    print "</ul></div>\n";

    /** TRACKBACK menu *****/

    $str = sprintf($T->String('str_list_i_saw_this'), '"' . $R['upload_name'] . '"');
    print "<div class=\"box\" id=\"download_box\">\n" .
          "<h2>{$T->String('str_list_trackback')}</h2>\n<a name=\"trackback\"></a>" .
          "<p>{$str}</p><ul>\n";

    $mi = array();
    $mi['action'] = 'javascript:// noted';
    $saws = array( array( 'remix',    $T->String('str_remix')),
                   array( 'podcast',  $T->String('str_podcast')),
                   array( 'video',    $T->String('str_video')),
                   array( 'web',      $T->String('str_list_web_blog')),
                   array( 'album',    $T->String('str_list_album'), ) );
    $url = "upload_trackback('{$R['upload_id']}', '";
    foreach( $saws as $saw )
    {
        $mi['menu_text'] = $saw[1];
        $mi['onclick'] = $url . $saw[0] . "');";
        helper_upload_menu_item($mi);
    }

    print "</ul></div>";

    /** ADMIN menu *****/

    if( !empty($menu['admin']) )
    {
        print "  <div class=\"box\" id=\"download_box\"><ul>\n";

        foreach( $menu['admin'] as $mi )
            helper_upload_menu_item($mi);

        print "     </ul></div>\n";
    }
}


function helper_upload_menu_item(&$item) 
{
    if( empty($item['parent_id']) )
        print '<li>';
    else
        print "<li id=\"{$item['parent_id']}\">";

    if( !empty($item['pre']) )
        print $item['pre'];

    print '<a ';

    $attrs = array( 'action' => 'href', 
                    'tip'    => 'title',
                    'id'     => 'id',
                    'class'  => 'class',
                    'type'   => 'type',
                    'onclick'=> 'onclick' );

    foreach( $attrs as $K => $V )
        if( !empty($item[$K]) )
            print "$V=\"{$item[$K]}\" ";

    print '>';
    
    if( !empty($item['menu_text']) )
        print $item['menu_text'];
    
    print "</a></li>\n";
}

/*-----------------------------------
    Date info 
*------------------------------------*/

function helper_upload_date(&$R,$T)
{
    $date = CC_datefmt($R['upload_date'],'M d, Y h:i a');
    if( empty($R['upload_last_edit']) )
    {
        $mod_date = '';
    }
    else
    {
        $mod_date = '<span id="modified_date">' . $T->String('str_list_lastmod') . ': ' .  CC_datefmt($R['upload_last_edit'],'M d, Y h:i a');
        if( !empty($R['last_op_str']) )
            $mod_date .= ' (' . $R['last_op_str'] . ')';
        $mod_date .= '</span>';
    }
    print "<div id=\"date_box\">{$T->String('str_list_date')}: $date $mod_date</div>\n";
}

function helper_upload_do_sidebar(&$R,&$A,$T)
{
    /*----------------------------------
        License 
    ------------------------------------*/

    /*
        [license_id] => noncommercial_3
        [license_url] => http://creativecommons.org/licenses/by-nc/3.0/
        [license_name] => Attribution Noncommercial  (3.0)
        [license_jurisdiction] => 
        [license_permits] => DerivativeWorks,Reproduction,Distribution
        [license_required] => Attribution,Notice
        [license_prohibits] => CommercialUse
        [license_logo] => by-nc-3.png
        [license_tag] => non_commercial
        [license_strict] => 20
        [license_text] => <strong>Attribution Noncommercial</strong>
    */

    print "<div class=\"box\" id=\"license_info\"><p><img src=\"" . $T->URL('images/lics/' . $R['license_logo'] ) . "\" />".
          "  <div id=\"license_info_t\" >\n" .
          "    {$T->String('str_lic')}<br />Creative Commons<br />" .
          "<a href=\"{$R['license_url']}\">" .
          $R['license_name'] . "\n   <br />" .
          "</a></div></p></div>\n";


    /*  Editorial pick */

    if( !empty($R['upload_extra']['edpicks']) )
    {
        $E = $R['upload_extra']['edpicks'];
        $keys = array_keys($E);
        $pick = $E[ $keys[0] ];
        $url = $A['home-url'] . 'people/' . $pick['reviewer'];
        $img = $T->URL('images/big-red-star.gif');
        print "<div class=\"box\" id=\"pick_box\">" .
              "<h2>{$T->String('str_edpick')}</h2>" .
              "<p><img src=\"$img\" />" .
              $pick['review'] . "</p><div class=\"pick_reviewer\">{$pick['reviewer']}</div></div>\n";
    }

    /*----------------------------------
        Remix Info
    ------------------------------------*/

    if( !empty($R['remix_parents']) )
        helper_upload_remix_info( $T->String('str_list_uses'), 'downloadicon.gif', $R['remix_parents'], $T );

    if( !empty($R['remix_children']) )
        helper_upload_remix_info( $T->String('str_list_usedby'), 'uploadicon.gif', $R['remix_children'], $T );
}


function helper_upload_remix_info($caption,$icon,$p,$T)
{
    $icon = $T->URL('images/' . $icon);
    print "<div class=\"box\" id=\"remix_info\">" .
          "<h2>{$caption}</h2>\n<p><img src=\"{$icon}\" />";

    $c = count($p);
    $max = 25;
    if( $c > $max )
        print '<div style="overflow: scroll;height:300px;">';
    $k = array_keys($p);
    for( $i = 0; $i < $c; $i++ )
    {
        $P =& $p[$k[$i]];

        $fname = !empty($P['upload_name']) ? $P['upload_name'] : $P['pool_item_name'];
        $aname = !empty($P['user_real_name']) ? $P['user_real_name'] : $P['pool_item_artist'];
        $fnamex = CC_StrChop($fname,22);
        $anamex = CC_StrChop($aname,22);
        print "<div><a class=\"remix_link\" href=\"{$P['file_page_url']}\" title=\"{$fname}\">{$fnamex}</a><span>\n    " . 
              "<a href=\"{$P['artist_page_url']}\" title=\"{$aname}\">{$anamex}</a></span></div>\n";
    }

    if( $c > $max )
        print '</div>';

    print "</p></div>\n";
}

/*----------------------------------
    Attribution/Description
------------------------------------*/

function helper_upload_main_info(&$R,&$A,$T)
{
    print "<div class=\"box\">\n";

    print "<img src=\"{$R['user_avatar_url']}\" style=\"float:right\" />\n";

    print '<table cellspacing="0" cellpadding="0" id="credit_info">';

    if( empty($R['collab']) )
    {
        print "<tr><th>{$T->String('str_by')}</th><td><a href=\"{$R['artist_page_url']}\">{$R['user_real_name']}</a></td></tr>\n";
    }
    else
    {
        $C =& $R['collab'];
        $url = $A['home-url'] . 'collab/' . $C['collab_id'];
        print "<tr><th>{$T->String('str_collab_project')}:</th><td><a href=\"$url\">{$C['collab_name']}</a></td></tr>\n" . 
              "<tr><th>{$T->String('str_collab_credit')}:</th><td>";
        $comma = '';
        foreach( $C['users'] as $U )
        {
            $url = $A['home-url'] . 'people/' . $U['user_name'];
            print "$comma<a href=\"{$url}\">{$U['user_real_name']}</a> ";
            if( empty($U['collab_user_credit']) )
                $credit = $U['collab_user_role'];
            else
                $credit = $U['collab_user_credit'];
            print " ($credit)";
            $comma = '<br />';
        }
        print "</td></tr>\n";
    }

    if( !empty($R['upload_extra']['featuring']) )
        print "<tr><th>{$T->String('str_featuring')}</th><td>{$R['upload_extra']['featuring']}</td></tr>\n";

    if( !empty($R['files']['0']['file_format_info']['ps']) )
        print "<tr><th>{$T->String('str_list_length')}</th><td>{$R['files']['0']['file_format_info']['ps']}</td></tr>\n";
    
    if( empty($R['thumbs_up']) )
    {
    
        /* We output the structure, even if it's empty because if this user 
           can rate it will be filled by an ajax call back
        */
        $str_ratings = empty($R['ratings']) ? '' : $T->String('str_ratings');
        print '<tr><th id="rate_label_' . $R['upload_id'] . '">'.$str_ratings.'</th><td> <span id="rate_block_' . $R['upload_id'] . '">';
        $A['record'] =& $R;
        helper_print_stars($T,$A);
        print '</span></td></tr>';
        
        if( $R['ok_to_rate'] )
        {
            $A['need_ratings_hooks'] = true;
            $src = $T->URL('images/stars/star-empty.gif');
            print '<tr><th id="rate_head_'.$R['upload_id'].'">' . $T->String('str_rate') . 
                     '</th><td style="padding:0px" id="rate_edit_'.$R['upload_id'].'">';
            for( $i = 1; $i < 6; $i++ )
            {
                print '<img id="rate_star_' . $i . '_' . $R['upload_id'] . '" style="height:17px;width:17px;margin:0px;" class="rate_star" src="' . $src . '" />';
            }
            print '</td></tr>';
        }
    }
    else
    {
        if( !empty($R['upload_num_ratings']) || !empty($R['ok_to_rate']) ) 
        {
            cc_get_ratings_info($R);
            if( !empty($A['ok_to_rate']) )
                $A['need_recommend_hooks'] = true;
            print '<tr><th>' . $T->String('str_recommends') . '</th><td class="recommend_block" id="recommend_block_' . $R['upload_id'] . '>'
                    . $R['ratings_score'] . '</td></tr>';
        } 
    }

    print "</table>\n";

    if( !empty( $R['upload_description_html'] ) )
    {
        $lines = preg_split('/<br/',$R['upload_description_html'] );
        $scroll = count($lines) > 14;
        if( $scroll )
            print '<div style="overflow:scroll;height:11em;border:1px solid #BBB;padding:4px;">';
        print $R['upload_description_html'] ;
        if( $scroll )
            print '</div>';
    }

    if( !empty($R['upload_taglinks']) )
    {
        print "<div id=\"taglinks\">";
        $comma = '';
        foreach( $R['upload_taglinks'] as $tag )
        {
            print "$comma<a href=\"{$tag['tagurl']}\">{$tag['tag']}</a>";
            $comma = ', ';
        }
        print "</div>";
    }

    print "</div>\n";


    if( !empty($R['file_macros']) )
    {
        // see file_macros.php for what these
        // might be (review links, zip dirs, etc.)

        print "<div class=\"box\">\n";
        $A['record'] =& $R;
        foreach( $R['file_macros'] as $M )
        {
            $T->Call($M);
        }
        print "</div>\n";
    }

}

function helper_print_stars($T,&$A)
{
    require_once('ccskins/shared/util.php');
    _t_util_ratings_stars($T,$A);
}

?>
