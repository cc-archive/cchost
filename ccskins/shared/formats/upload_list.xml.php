<link rel="stylesheet" type="text/css" title="Default Style" href="<?= $T->URL('css/upload_list.css') ?>" />
<?

function _t_upload_list_init($T,&$A)
{
    $carr103 = empty( $A['records'] ) ? $A['file_records'] : $A['records'];
    $cc103   = count( $carr103);
    $ck103   = array_keys( $carr103);

    cc_get_remix_history($carr103,3);

    print "<div id=\"upload_listing\">\n";

    for( $ci103= 0; $ci103< $cc103; ++$ci103)
    { 
        $R =& $carr103[ $ck103[ $ci103 ] ];
        $R['local_menu'] = cc_get_upload_menu($R);

        print "  <div class=\"upload\" ><!--  %%% {$R['upload_name']}  %%% -->\n";

        helper_list_info($R,$A,$T);
        helper_list_menu($R);
        helper_list_remixex($R);

        print "    <br style=\"clear:both\" />\n  </div><!--  end upload  -->\n";
    }
    print "</div><!-- end listing -->\n";

    $T->Call('prev_next_links');

    if( !empty($A['enable_playlists']) )
    {
        $T->Call('playerembed.xml/eplayer');
        $T->Call('playlist.xml/playlist_menu');
    }

    print '<script> var dl_hook = new downloadHook(); dl_hook.hookLinks(); </script>';
}

function helper_list_menu(&$R)
{
    // see upload_page.xml.php for menu structure

    $menu =& $R['local_menu'];

    print "\n    <div class=\"list_menu\">\n";
    if( !empty($menu['play']['stream']) )
        helper_list_menu_item($menu['play']['stream']);

    //if( !empty($menu['play']['download']) )
    $mi = array();
    $mi['action'] = "javascript://download";
    $mi['id'] = "_ed_{$R['upload_id']}";
    $mi['menu_text'] = $GLOBALS['str_list_download'];
    $mi['class'] = 'download_hook';
    helper_list_menu_item($mi);

    if( !empty($menu['comment']['comments']) )
        helper_list_menu_item($menu['comment']['comments']);

    if( !empty($R['ok_to_rate']) )
    {
        $mi = array();
        $mi['action'] = 'javascript://rate';
        if( !empty($R['thumbs_up']) )
        {
            $mi['menu_text'] = $GLOBALS['str_recommend'] ;
            $tu = 'true';
        }
        else
        {
            $mi['menu_text'] = $GLOBALS['str_list_rate_now'] ;
            $tu = 'false';
        }
        $mi['onclick'] = "upload_rate('{$R['upload_id']}', $tu );";
        helper_list_menu_item($mi);
    }

    if( !empty($menu['playlist']['playlist_menu']) )
        helper_list_menu_item($menu['playlist']['playlist_menu']);

    $mi = array();
    $mi['action'] = $R['file_page_url'] . '#trackback';
    $mi['menu_text'] = $GLOBALS['str_list_trackback'];
    helper_list_menu_item($mi);

    if( !empty($menu['share']['share_link']) )
        helper_list_menu_item($menu['share']['share_link']);

    print "\n   </div><!-- menu -->";
}

function helper_list_menu_item(&$item) 
{
    if( !empty($item['parent_id']) )
    {
        $close_span = true;
        print "<span id=\"{$item['parent_id']}\">";
    }

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
    
    print '</a>';
    if( !empty($close_span) )
        print '</span>';
}

function helper_list_info(&$R,&$A,$T)
{
    $furl = $R['file_page_url'];
    $aurl = $R['artist_page_url'];
    $name = CC_StrChop($R['upload_name'],27);
    $about_url =  empty($R['local_menu']['download'][0]['action']) ? '' : "about=\"{$R['local_menu']['download'][0]['action']}\"";
    $date = CC_datefmt($R['upload_date'],'M d, Y h:i a');
    $licurl = $T->URL('images/lics/small-' . $R['license_logo']);
    $html =<<<EOF
    <div class="upload_avatar"><img src="{$R['user_avatar_url']}" /></div>
    <div class="upload_info">
        <a class="lic_link" href="{$R['license_url']}" 
                  {$about_url}
                  rel="license"
                  title="{$R['license_name']}" >
                  <img src="{$licurl}" /></a> 
        <a href="{$furl}" class="upload_link">{$name}</a><br /> {$GLOBALS['str_by']} <a href="{$aurl}">{$R['user_real_name']}</a>
        <div class="upload_date">$date </div>
        <div class="taglinks">
            
EOF;
    print $html;
    if( !empty($R['usertag_links']) )
    {
        $comma = '';
        foreach( $R['usertag_links'] as $T )
        {
            print "$comma<a href=\"{$T['tagurl']}\">{$T['tag']}</a>";
            $comma = ",\n         ";
        }
    }
    
    print "\n     </div><!-- tags -->\n    ";
    
    if( !empty($A['enable_playlists']) && !empty($R['fplay_url']) )
    {
        preg_match('%(http://[^/]+/)(.*)%',$R['fplay_url'],$m); // do this so no mp3 urls show up in the page
        if( $m[1] == $A['root-url'] )
            $root = 'root_url';
        else
            $root = "'${m[1]}'";

        print "        <div class=\"playerdiv\"><span class=\"playerlabel\">{$GLOBALS['str_play']}: </span><a class=\"cc_player_button cc_player_hear\" id=\"_ep_{$R['upload_id']}\"> </a></div>\n" .
              "<script> \$('_ep_${R['upload_id']}').href = {$root} + '{$m[2]}'; </script>\n";
    }

    print "    </div><!-- upload info -->\n";
}

function helper_list_remixex(&$R)
{
   if( !empty($R['remix_parents']) )
    {
        $murl = empty($R['more_parents_link']) ? '' : $R['more_parents_link'];
        helper_list_remix_info( $GLOBALS['str_list_uses'], 'downloadicon.gif', $R['remix_parents'], $murl );
    }

    if( !empty($R['remix_children']) )
    {
        $murl = empty($R['more_children_link']) ? '' : $R['more_children_link'];
        helper_list_remix_info( $GLOBALS['str_list_usedby'], 'uploadicon.gif', $R['remix_children'], $murl );
    }
}

function helper_list_remix_info($caption,$icon,$p,$murl)
{
    // 
    print '<div id="remix_info" > '.
          "<h2>{$caption}</h2>\n";

    $c = count($p);
    $k = array_keys($p);
    for( $i = 0; $i < $c; $i++ )
    {
        $P =& $p[$k[$i]];

        $fname = !empty($P['upload_name']) ? $P['upload_name'] : $P['pool_item_name'];
        $aname = !empty($P['user_real_name']) ? $P['user_real_name'] : $P['pool_item_artist'];
        $fnamex = CC_StrChop($fname,18);
        $anamex = CC_StrChop($aname,18);
        print "<div><a class=\"remix_link\" href=\"{$P['file_page_url']}\" title=\"{$fname}\">{$fnamex}</a> <span>{$GLOBALS['str_by']} \n    " . 
              "<a href=\"{$P['artist_page_url']}\" title=\"{$aname}\">{$anamex}</a></span></div>\n";
    }

    if( $murl)
        print "<a class=\"remix_more_link\" href=\"{$murl}\">{$GLOBALS['str_more']}...</a>";

    print "</div>\n";
}

?>