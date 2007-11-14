<body> <!-- class="dark_bg" -->
<div class="hide">
  <a href="#content">%text(skip)%</a>
</div>

<div id="container" style="background-color:white;">

<div id="header" class="med_dark_bg light_color"> 
    <?
    require_once('cclib/cc-template.inc');
    $layouts = CCTemplateAdmin::GetLayouts('layout');
    ?>
    <script>function onlayout() {
        var box = $('layouts');
        var file = box.options[ box.selectedIndex ].value;
        var junk = document.location.href;
        if( document.location.search )
        {
            junk = junk.replace( document.location.search, '' );
        }
        document.location.href = junk + '?page_layout=' + file;

    }
    </script>
    <div style="position:absolute;top:2px;left:340px;"><select id="layouts" onchange="onlayout()" style="font-size:11px;font-family:verdana;">
    <?
        $sel = empty($_GET['page_layout']) ? '' : $_GET['page_layout'];
        foreach( $layouts as $K => $V )
        {
            $f = $V['id'];
            $selc = ( $f == $sel ) ? 'selected="selected"' : '';

            print "<option {$selc} value=\"{$f}\">{$f}</option>\n";
        }
    ?>
    </select></div>
    %if_not_empty(sticky_search)%
        <div id="header_search"><a id="search_site_link"
        href="%(advanced_search_url)%"><h3 class="light_color">%text(find)%</h3><span class="light_color">%text(findcontent)%</span></a></div>
    %end_if%

    %if_not_empty(logged_in_as)%
        <div id="login_info">%text(loggedin)%: <span>%(logged_in_as)%</span> 
            <a class="light_color" href="%(home-url)%logout">%text(logout)%</a></div>
    %end_if%

    %if_not_empty(beta_message)%
        <div id="beta_message">%(show_beta_message)%</div>
    %end_if%

    <h1 id="site_title"><a href="%(root-url)%" title="%(site-title)%">%(banner-html)%</a></h1>

    %if_not_empty(site-description)%
        <div id="site_description">%(site-description)%</div>
    %end_if%

    %if_not_empty(tab_pos/in_header)%
        %call('tabs.tpl/print_tabs')%
    %end_if%
</div><!-- header -->


    <div id="wrapper">
<div id="content">

%call(print_bread_crumbs)%

%if_not_empty(tab_pos/subclient)%
    %call('tabs.tpl/print_sub_tabs')%
%end_if%

%if_not_empty(page-title)%
    <h1 class="title">%text(page-title)%</h1>
%end_if%
<a name="content" ></a>    

%loop(macro_names,macro)%    %call(#macro)%      %end_loop%
%loop(inc_names,inc_name)%   %call(#inc_name)%   %end_loop%

</div> <!-- content -->
    </div> <!-- wrapper -->

<div id="navigation">

    %if_not_empty(tab_pos/floating)%
        %call('tabs.tpl/print_tabs')%
    %end_if%

    %if_not_empty(tab_pos/nested)%
        %call('tabs.tpl/print_nested_tabs')%
    %end_if%

%if_not_empty(menu_groups)%

<div id="menu">

    %loop(menu_groups,group)%
      <div class="menu_group">
        <p>%(#group/group_name)%</p>
        <ul>%loop(#group/menu_items,mi)%
          <li><a href="%(#mi/action)%" %if_attr(#mi/id,id)%>%(#mi/menu_text)%</a></li>
        %end_loop% </ul>
      </div>
    %end_loop%

</div> <!-- end of menu -->

    %unmap(menu_groups)%

%end_if%
</div>

<div id="extra" class="med_bg light_color">
    %settings(tmacs,custom_macros)%

    %% These are little strange, the value is the flag
       that decides what to print, the key is the macro
    %%

    %loop(custom_macros,flag)%
      %if_not_null(#flag)%
        <div class="menu_group">        
          %call_macro(#k_flag)%
        </div>
      %end_if%
    %end_loop%
</div>


<div id="footer" class="med_light_bg">
  <div id="license"><p>%(site-license)%</p></div>
  <p>%(footer)%</p>
</div><!-- footer -->
</div> <!-- container -->


%loop(end_script_links,script_link)%
    <script type="text/javascript" src="%url(#script_link)%" ></script>
%end_loop%

%loop(end_script_blocks,block)%
    %call(#block)%
%end_loop%

<script> 
    new modalHook( [ 'search_site_link', 'mi_login', 'mi_register']);  
    $$('.selected_tab a').each( function(e) { e.style.cursor = 'default'; e.href = 'javascript:// disabled'; } );
</script>

</body>