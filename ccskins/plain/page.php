<?

function _t_page_html_head($T,&$_TV)
{
    if( !empty($_TV['ajax']) )
        return;

    $caption = !empty($_TV['page-title']) ? $_TV['page-title'] : '';

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

    $T->Call('print_head_links');

    print "\n</head>\n";
}


function _t_page_print_head_links($T,&$_TV)
{
    if( !empty($_TV['head_links']) )
        foreach( $_TV['head_links'] as $head )
            print "<link rel=\"{$head['rel']}\" type=\"{$head['type']}\" href=\"{$head['href']}\" title=\"{$head['title']}\"/>\n";

    if( !empty($_TV['style_sheets']) )
    {
        foreach( $_TV['style_sheets'] as $css )
        {
            $path = $T->Search($css);
            if( empty($path) ) die( "Can't find stylesheet '$css'" );
            $path = ccd($path);
            print "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\" title=\"Default Style\" />\n";
        }
    }

    if( !empty($_TV['script_links']) )
        _t_page_script_link_helper($_TV['script_links'],$T);

    if( !empty($_TV['script_blocks']) )
    {
        foreach( $_TV['script_blocks'] as $script_block )
            $T->Call($script_block);
    }

}

function _t_page_script_link_helper($links,$T)
{
    foreach( $links as $script_link )
    {
        if( substr($script_link,0,7) == 'http://' )
        {
            $path = $script_link;
        }
        else
        {
            $path = $T->Search($script_link);
            if( empty($path) ) die( "Can't find script '$script_link'" );
            $path = ccd($path);
        }
        print "<script type=\"text/javascript\" src=\"${path}\" ></script>\n";
    }
}

function _t_page_print_banner($T,&$_TV)
{
    print('<div id="banner">' . "\n");

    if( !empty($_TV['sticky_search']) )
    {
        print "<div id=\"banner_search\"><a id=\"search_site_link\" href=\"{$_TV['advanced_search_url']}\">\n" .
              "<h3>{$GLOBALS['str_find']}</h3><span>{$GLOBALS['str_findcontent']}</a></span></div>\n";
    }

    if( !empty($_TV['logged_in_as']) )
    {
        print "<div id=\"login_info\">{$GLOBALS['str_loggedin']}: <span>${_TV['logged_in_as']}</span>\n".
              "<a href=\"{$_TV['logout_url']}\">{$GLOBALS['str_logout']}</a></div>\n";
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

    print("</div><!-- banner -->\n");
}

function _t_page_print_tabs($T,&$_TV)
{
    if( !empty($_TV['tab_info']) )
    {
        $tabs = $_TV['tab_info']['tabs'];
        page_inner_print_tabs($tabs,'tabs');
        print '<div class="post_tab_breaker"></div>' . "\n";
        unset($_TV['tab_info']); // this will prevent duplication from multiple skins
    }
}


function _t_page_print_sub_nav_tabs($T,&$_TV)
{
    if( !empty($_TV['sub_nav_tabs']) )
    {
        $tabs = $_TV['sub_nav_tabs']['tabs'];
        page_inner_print_tabs($tabs,'sub_tabs');
        print '<div class="post_sub_tab_breaker"></div>' . "\n";
        unset($_TV['sub_nav_tabs']); // this will prevent duplication from multiple skins
    }
}

function page_inner_print_tabs($tabs,$id)
{
    print "<ul id=\"{$id}\" >\n";
    foreach( $tabs as $tab )
    {
        $selected = empty($tab['selected']) ? '' : 'class="selected_tab"';
        print "<li $selected><a href=\"{$tab['url']}\" title=\"{$tab['help']}\"><span>{$tab['text']}</span></a></li>\n";
    }
    print '</ul>';
}

function _t_page_main_body($T,&$_TV)
{
    if( empty($_TV['show_body_header'])  )
    {
        print("<body>\n");
    }
    else
    {
        if( empty($_TV['ajax']) )
        {
            $HTML =<<<EOF
<body>
<div class="hide">
  <a href="#content">{$GLOBALS['str_skip']}</a>
</div>

EOF;
            print $HTML;

            $T->Call('print_banner');
            $T->Call('print_tabs');
            $T->Call('print_menu');

            print("<div id=\"main_content\">\n");

        }
    }

    if( !empty($_TV['bread_crumbs'] ) )
        $T->Call('print_bread_crumbs');

    if( !empty($_TV['sub_nav_tabs'] ) )
        $T->Call('print_sub_nav_tabs');

    if( !empty($_TV['page-title'] ) )
        $T->Call('print_page_title');

    if( empty($_TV['ajax']) )
        print '<a name="content" ></a>' . "\n";

    if( !empty($_TV['macro_names'] ) ) 
        foreach( $_TV['macro_names'] as $macro )
            $T->Call($macro);

    if( !empty($_TV['inc_names'] ) ) 
        foreach( $_TV['inc_names'] as $inc_name )
            $T->Call($inc_name);

    if( empty($_TV['ajax']) )
    {
        print "</div> <!-- main_content -->\n<div class=\"post_content_breaker\"></div>";

        if( !empty($_TV['show_body_footer']) )
        {
            $T->Call('print_footer');
            $T->Call('print_end_script_blocks');
        }

        print "\n</body>\n</html>\n";
    }

}

function _t_page_print_page_title($T,&$_TV) 
{
    if( !empty($_TV['page-title'] ) )
    {
        print "<h1 class=\"title\">{$_TV['page-title']}</h1>";
        if( empty($_TV['ajax']) )
        {
            $title = addslashes($_TV['page-title']);
            $html =<<<EOF
<script>document.title = '{$_TV['site-title']} {$title}';</script>
EOF;
            print $html;
        }
    }
}

function _t_page_print_end_script_blocks($T,&$_TV) 
{
    if ( !empty($_TV['end_script_blocks'])) 
        foreach( $_TV['end_script_blocks'] as $block )
            $T->Call($block);

    if ( !empty($_TV['end_script_links'])) 
        _t_page_script_link_helper($_TV['end_script_links'],$T);

} // END: function show_end_script_blocks


function _t_page_print_footer($T,&$_TV) 
{
    print "\n<div id=\"footer\">\n<div id=\"license\">{$_TV['site-license']}</div>\n{$_TV['footer']}</div>\n";
}

?>
