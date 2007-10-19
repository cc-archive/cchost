<?

global $_TV;

function _t_page_html_head()
{
    global $_TV;

    if( !empty($_TV['ajax']) )
        return;

    $caption = !empty($_TV['page-caption']) ? $_TV['page-caption'] : (!empty($_TV['page-title']) ? $_TV['page-title'] : (!empty($_TV['backup-title']) ? $_TV['backup-title'] : ('')));

    $HTML =<<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 //EN">
<head>
<title>{$_TV['site-title']} $caption</title>

EOF;
    print $HTML;

    if( !empty($_TV['site-meta-keywords']) )
        print "<meta name=\"keywords\" content=\"{$_TV['site-meta-keywords']}\" />\n";
    if( !empty($_TV['site-meta-description']) )
        print "<meta name=\"description\" content=\"{$_TV['site-meta-description']}\" />\n";

     $HTML =<<<EOF
<meta name="robots" content="index, follow" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script>
//<!--
var home_url = '{$_TV['home-url']}';
var root_url = '{$_TV['root-url']}';
var q        = '{$_TV['q']}';
//-->
</script>

EOF;

    print $HTML;

    _template_call_template('print_head_links');

    print "\n</head>\n";
}


function _t_page_print_head_links()
{
    global $_TV;

    if( !empty($_TV['style_sheets']) )
    {
        foreach( $_TV['style_sheets'] as $css )
        {
            $path = _template_search($css);
            if( empty($path) ) die( "Can't find stylesheet '$css'" );
            $path = ccd($path);
            print "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\" title=\"Default Style\" />\n";
        }
    }

    if( !empty($_TV['script_links']) )
    {
        foreach( $_TV['script_links'] as $script_link )
        {
            $path = _template_search($script_link);
            if( empty($path) ) die( "Can't find script '$script_link'" );
            $path = ccd($path);
            print "<script type=\"text/javascript\" src=\"${path}\" ></script>\n";
        }
    }

    if( !empty($_TV['script_blocks']) )
    {
        foreach( $_TV['script_blocks'] as $script_block )
            _template_call_template($script_block);
    }

    if( !empty($_TV['head_links']) )
        foreach( $_TV['head_links'] as $head )
            print "<link rel=\"{$head['rel']}\" type=\"{$head['type']}\" href=\"{$head['href']}\" title=\"{$head['title']}\"/>\n";

}

function _t_page_print_banner()
{
    global $_TV;

    print('<div id="banner">' . "\n");

    if( !empty($_TV['sticky_search']) )
    {
        $path = ccd(_template_search('images/find.png'));
        print "<div id=\"banner_search\"><a id=\"search_site_link\" href=\"{$_TV['advanced_search_url']}\">" .
              "<img src=\"{$path}\" />" .
              "<h3>{$GLOBALS['str_find']}</h3>{$GLOBALS['str_findcontent']}</a></div>";
    }

    if( !empty($_TV['logged_in_as']) )
    {
        print "<div id=\"login_info\">{$GLOBALS['str_loggedin']}: <span>${_TV['logged_in_as']}</span>".
              "<a href=\"{$_TV['logout_url']}\">{$GLOBALS['str_logout']}</a></div>";
    }

    if( !empty($_TV['beta_message']) )
    {
        print "<div id=\"beta_message\">${show_beta_message}</div>\n";
    }

    $HTML =<<<EOF
      <h1 id="site_title"><a href="{$_TV['root-url']}" title="{$_TV['site-title']}">{$_TV['banner-html']}</a></h1>

EOF;

    print $HTML;

    if( !empty($_TV['site-description']) )
        print "<div id=\"site_description\">{$_TV['site-description']}</div>";

    if( !empty($_TV['tab_info']) )
        _template_call_template('print_tabs');

    print("</div><!-- banner -->\n");
}

function _t_page_print_tabs()
{
    global $_TV;

    $tabs = $_TV['tab_info']['tabs'];
    page_inner_print_tabs($tabs,'tabs');
}


function _t_page_print_sub_nav_tabs()
{
    global $_TV;

    $tabs = $_TV['sub_nav_tabs']['tabs'];
    page_inner_print_tabs($tabs,'sub_tabs');
}

function page_inner_print_tabs($tabs,$id)
{
    global $_TV;

    print "<ul id=\"{$id}\" >\n";
    foreach( $tabs as $tab )
    {
        $selected = empty($tab['selected']) ? '' : 'class="selected_tab"';
        print "<li $selected><a href=\"{$tab['url']}\" title=\"{$tab['help']}\"><span>{$tab['text']}</span></a></li>\n";
    }
    print '</ul>';
}

function _t_page_main_body()
{
    global $_TV;

    if( !empty($_TV['show_body_header']) && empty($_TV['ajax']) )
    {
        print("<body>\n");

        $HTML =<<<EOF
        <body>
        <div class="hide">
          <a href="#content">{$GLOBALS['str_skip']}</a>
        </div>

EOF;

        _template_call_template('print_banner');
        _template_call_template('print_menu');

        print("<div id=\"main_content\">\n");

        print "<a name=\"content\"></a>\n";
    }


    if( !empty($_TV['bread_crumbs'] ) )
        _template_call_template('print_bread_crumbs');

    if( !empty($_TV['sub_nav_tabs'] ) )
        _template_call_template('print_sub_nav_tabs');

    if( !empty($_TV['page-title'] ) )
        print "<h1 class=\"title\">{$_TV['page-title']}</h1>\n";

    if( !empty($_TV['macro_names'] ) ) 
        foreach( $_TV['macro_names'] as $macro )
            _template_call_template($macro);

    if( !empty($_TV['inc_names'] ) ) 
        foreach( $_TV['inc_names'] as $inc_name )
            _template_call_template($inc_name);

    if( empty($_TV['ajax']) )
    {
        print "</div> <!-- main_content -->\n";

        if( !empty($_TV['show_body_footer']) )
        {
            _template_call_template('print_footer');
            _template_call_template('print_end_script_blocks');
        }

        print "\n</body>\n</html>\n";
    }

}

function _t_page_print_end_script_blocks() 
{
    global $_TV;

    if ( !empty($_TV['end_script_blocks'])) 
    {
        $carr111 = $_TV['end_script_blocks'];
        $cc111= count( $carr111);
        $ck111= array_keys( $carr111);
        for( $ci111= 0; $ci111< $cc111; ++$ci111)
            _template_call_template($carr111[ $ck111[ $ci111 ] ]);
    } 

    if ( !empty($_TV['end_script_links'])) 
    {
        $carr112 = $_TV['end_script_links'];
        $cc112= count( $carr112);
        $ck112= array_keys( $carr112);
        for( $ci112= 0; $ci112< $cc112; ++$ci112)
        { 
            $_TV['eslink'] = $carr112[ $ck112[ $ci112 ] ];
            ?><script  type="text/javascript" src="<?= $_TV['eslink']?>"></script><?
        }
    }

    print "<script>cc_round_boxes();</script>\n";

} // END: function show_end_script_blocks


function _t_page_print_footer() 
{
    global $_TV;

    print "\n<div id=\"footer\">\n<div id=\"license\">{$_TV['site-license']}</div>\n{$_TV['footer']}</div>\n";
}

?>