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

CCEvents::AddHandler(CC_EVENT_MAP_URLS, 'print_page_map_urls');

function print_page_map_urls()
{
    CCEvents::MapUrl( 'sidebar', 'cc_get_sidebar', CC_DONT_CARE_LOGGED_IN );
}

function cc_get_sidebar()
{
    global $CC_GLOBALS;
    

    $configs =& CCConfigs::GetTable();
    $tmacs = $configs->GetConfig('tmacs');
    $ttags = $configs->GetConfig('ttag');
    $args = array_merge($CC_GLOBALS,$ttags);
    $args['logged_in_as'] = empty($CC_GLOBALS['user_name']) ? '' : $CC_GLOBALS['user_name'];
    $args['logout_url'] = ccl('logout');
    $args['auto_execute'][] = 'show_logged_in_as';
    $args['auto_execute'][] = 'show_sidebar_macros';
    foreach( $tmacs as $K => $V )
    {
        if( $V )
        {
            $args['custom_macros'][] = str_replace( '/', '.xml/', $K );
        }
    }

    //CCDebug::PrintVar($args,false);
    require_once('ccextras/cc-reviews.inc');
    $template = new CCTemplate('skin-blank-map.xml');
    $template->SetAllAndPrint($args);
    exit;
}

function & mixter_page_args()
{
    $page = CCPage::GetPage();
    $G =& $page->_page_args;
    return $G;
}

function mixter_print_head_1(&$G)
{
    $output = '';
    $output .= '<head>';
    $output .= "\n<title>{$G['site-title']} ";
    if( !empty($G['page-caption']) )
        $output .= $G['page-caption'];
    else if( !empty( $G['page-title'] ) )
        $output .= $G['page-title'];
    elseif( !empty( $G['backup-title'] ) )
        $output .= $G['backup-title']; 
    $output .= ('</title>' . "\n");
    if( !empty($G['site-meta-keywords']) )
        $output .= "<meta name=\"keywords\" content=\"{$G['site-meta-keywords']}\" />\n";
    if( !empty($G['site-meta-description']) )
        $output .= "<meta name=\"description\" content=\"{$G['site-meta-description']}\" />\n";
    $output .= '
  <meta name="robots" content="index, follow" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <META name="verify-v1" content="PEEIrYmur1ilJAiOk6Ky5lEDaJYIltd3L3qgUsbdGco=" />
'      ;
    if( !empty($G['lang']) )
        $output .= "<!-- Global Language: {$G['lang']} -->\n";
    if( !empty($G['user_language']) )
        $output .= "<!-- User Language: {$G['user_language']} -->\n";

    $lang_skip = _('Skip the site navigation to go directly to the content');
    $lang_skip2 = _('Skip to content');
    $lang_main_site = _('Main Site Sections');
    $lang_adv_search = _('Advanced search');
    $lang_search = _('Search');
    $lang_get_ratings = _('getting ratings...');
    $form_id = empty($G['form_id']) ? '' : "var form_id = '{$G['form_id']}';";
    $output .= <<<EOF
<script>
//<!--
var home_url = '{$G['home-url']}';
var root_url = '{$G['root-url']}';
var q        = '{$G['q']}';
var lang_get_ratings = '${lang_get_ratings}';
$form_id
//-->
</script>

EOF;
 
    if( !empty($G['head_links']) )
    {
        foreach( $G['head_links'] as $head_link )
        {
            $H =& $head_link;
            $output .= "<link rel=\"{$H['rel']}\" type=\"{$H['type']}\" href=\"{$H['href']}\" title=\"{$H['title']}\"/>\n";
        }
    }
    $output .= <<<EOF

  <script type="text/javascript" src="{$G['root-url']}cctemplates/js/prototype.js" ></script>
  <script type="text/javascript" src="{$G['root-url']}cctemplates/js/rico.js" ></script>
  <script type="text/javascript" src="{$G['root-url']}cctemplates/cc_obj.js" ></script>
EOF;


    if( !empty($G['script_links']) )
    {
        foreach( $G['script_links'] as $SL )
        {
            $output .= "<script type=\"text/javascript\" src=\"{$SL}\" ></script>\n";
        }
    }

    $output .= <<<END

</head>
<body>

  <div id="cc_tab"></div>
  <div class="cc_hide">
      <a href="#content" title="$lang_skip">$lang_skip2</a>
  </div>
  <div id="cc_wrapper1">
    <div id="cc_wrapper2">

END;
    if( !empty($G['show_body_header']) )
    {
        $beta_message =  empty($G['beta_message']) ? '' : "<div class=\"cc_beta_message\">{$G['beta_message']}</div>";
        $site_desc = '<p>' . (empty($G['site-description']) ? '' : $G['site-description']) . '</p>';

        $output .= <<<END
  <div class="cc_header" id="cc_header">
    <div class="cc_headerpadding">  $beta_message
      <h1 id="cc_site_name"><a href='{$G['root-url']}' title="{$G['site-title']}">{$G['banner-html']}</a></h1>
      $site_desc

END;
        if( !empty($G['tab_info']) )
        {
            $rtabs = array_reverse($G['tab_info']['tabs']);
            $output .=  "<ul title=\"{$lang_main_site}\" >\n";
            foreach( $rtabs as $T )
            {
                $class = isset($T['selected']) ? 'class = "selected_tab"' : '';
                $output .= "<li $class><a href=\"{$T['url']}\">{$T['text']}</a></li>\n";
            }
            $output .= "\n</ul>";
        }

        if( !empty($G['sticky_search']) )
        {
            $output .= <<<END
          <form id="cc_search" action="{$G['home-url']}search/results" method="post" >
            <div>
              <a href="{$G['home-url']}search" id="cc_adv_search">$lang_adv_search</a>
              <input class="cc_form_text" id="search_text" name="search_text" value="" />
              <input type="hidden" name="search_type" value="any" />
              <input type="hidden" name="search_in" value="3" />
              <input class="cc_form_submit" id="cc_submit" value="{$lang_search}" type="submit" />
            </div>
          </form>
END;
        }
    
        $output .= <<<END

    </div> <!-- headerpadding -->
  </div> <!-- cc_header -->

        <hr class="cc_hide" /> 

END;
    }

    $output .= '<div id="cc_content">' . "\n";

    if( !empty($G['show_body_header']) )
    {
        $output .= <<<END
  <div id="cc_leftside">
    <div class="cc_sidebar">
END;
        if( !empty($G['menu_groups']) )
        {
            foreach( $G['menu_groups'] as $MG )
            {
                $output .= "<p>{$MG['group_name']}</p>\n<ul>\n";
                foreach( $MG['menu_items'] as $MI )
                    $output .= "<li><a href=\"{$MI['action']}\">{$MI['menu_text']}</a></li>\n";
                $output .= "\n</ul>\n";
            }
        }

        $output .= '<div id="delay_sidebar"></div>';

        if( !empty($G['feed_links']) )
        {
            $output .= '<p>';

            foreach( $G['feed_links'] as $FL )
            {
                $output .= "<span class=\"cc_feed_link\"><a class=\"cc_feed_button\" type=\"{$FL['type']}\" href=\"{$FL['href']}\" " .
                           "title=\"{$FL['title']}\">{$FL['link_text']}</a> {$FL['link_help']}</span>\n";
            }

            $output .= '</p>';
        }

        $output .=  <<<END

    </div>        <!-- sidebar -->
  </div><!-- leftside -->
END;

         // <metal:block use-macro="{$G['show_logged_in_as}" />
    }

    $output .= '<div id="cc_centercontent">';

    if( !empty($G['bread_crumbs']) )
    {
        $output .= '<div class="cc_breadcrumbs" >' . "\n";
        $count = count($G['bread_crumbs']);
        $keys = array_keys($G['bread_crumbs']);
        for( $i = 0; $i < $count; $i++ )
        {
            $CB = $G['bread_crumbs'][$keys[$i]];
            if( $i < ($count-1) )
                $output .= "<a href=\"{$CB['url']}\"><span>{$CB['text']}</span></a> &raquo; ";
            else
                $output .= "<apan>{$CB['text']}</span>\n";
        }
        if( !empty($G['crumb_tags']) )
        {
            $output .= '<select onchange="document.location = this.options[this.selectedIndex].value;" style="font-size:smaller;">' . "\n";
            foreach( $G['crumb_tags'] as $CT )
            {
                $sel = empty($CT['selected']) ? '' : 'selected="selected';
                $output .= "<option $sel value=\"{$CT['url']}\">{$CT['text']}</option>\n";
            }
        }
        $output .= "</select>\n</div>\n";
    }

    if( !empty($G['sub_nav_tabs']) )
    {
        $tabs = $G['sub_nav_tabs']['tabs'];
        $output .= '<div id="cc_sub_header"><ul>';
        foreach( $tabs as $tab )
        {
            $sel = empty($tab['selected']) ? '' : 'class="selected_tab"';
            $title = empty($tab['help']) ? '' : "title = \"{$tab['help']}\"";
            $output .= "\n<li $sel $title><a href=\"${tab['url']}\">{$tab['text']}</a></li>\n";
        }
        $output .= "</ul></div>\n";
    }

    if( !empty($G['page-title']) )
    {
        $output .= "<h1>{$G['page-title']}</h1>\n";
    }

    $output .= '<a name="content"></a>';

    return $output;
}

function mixter_print_tail()
{
    $page = CCPage::GetPage();
    $G =& $page->_page_args;

    $sidebarurl = ccl('sidebar');

    $output = '';

    $output .= <<<END
          <br clear="all" />

        </div><!-- centerconent -->
END;

    $output .= <<<END

  <hr class="cc_hide" />  
  <br clear="all" />

  <div class="cc_rbroundbox">
    <div class="cc_rbtop"><div></div></div>
    <div class="cc_rbcontent">
      <div class="cc_tinytext">{$G['site-license']} {$G['footer']}  <!-- skin: fast-php --></div>
    </div><!-- /cc_rbcontent -->
    <div class="cc_rbbot"><div></div></div>
  </div><!-- /cc_rbroundbox -->

      </div><!-- content -->
    </div><!-- wrapper2 -->
  </div><!-- wrapper1 -->
  <script>
    var t = (new Date()).getTime();

    var myAjax = new Ajax.Request( 
        '$sidebarurl' + q + t, 
        { 
            method: 'get', 
            onComplete: got_sidebar
        }
    );    

    function got_sidebar(req)
    {
        $('delay_sidebar').innerHTML = req.responseText;
        $$('.cc_sidebar p').each( function(e) {
                roundCorners(e, {corners: "tl tr"});
        });
        $$('.cc_more_menu_link').each( function(e) {
                roundCorners(e, {corners: "bl br"});
        });
    }
  </script>
END;

    if( !empty($G['end_script_links']) )
    {
        foreach( $G['end_script_links'] as $ESL )
        {
            $output .= "<script type=\"text/javascript\" src=\"{$ESL}\"></script>\n";
        }
    }

    $output .=<<<END

<script>
var roundCorners = Rico.Corner.round.bind(Rico.Corner);

$$('.cc_headerpadding ul li').each( function(e) {
    if( Element.hasClassName(e,'selected_tab') )
        roundCorners(e, {bgColor:"#543927",color: "#FFF",border:"brown"});
    else
        roundCorners(e); //, {color:"#543927",bgColor: "#FFF"});
});


$$('.cc_system_prompt').each( function(e)  {
    e.innerHTML = '<p style="padding: 4px;margin: 0px;">' + e.innerHTML + '</p>';
    roundCorners(e, {bgColor:"#FFF",color: "orange",border:"orange"});
});

var sub_head = $('cc_sub_header');
if( sub_head )
{
  Rico.Corner.round('cc_sub_header', { color: '#B5BEA5', bgColor: '#FFF'} );
}

</script>
END;

    $output .= "\n</body>\n";

    return $output;
}

?>