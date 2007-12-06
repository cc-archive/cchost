<body>
<div id="debug" style="background-color:white; padding: 2px; color:black; float:right;position:absolute;right:0px;top:110px;z-index:200;font-size:10px;"></div>
<div class="hide">
  <a href="#content">%text(skip)%</a>
</div>

<div id="container" style="background-color:white;">

<div id="header" class="light_color">

    %if_not_empty(logged_in_as)%
        <div id="login_info">%text(loggedin)%: <span>%(logged_in_as)%</span> 
            <a class="light_color" href="%(home-url)%logout">%text(logout)%</a></div>
    %end_if%

    %if_not_empty(sticky_search)%
        <div id="header_search"><img id="header_search_img" height="50" width="70" src="%url(images/find.PNG)%" /><a id="search_site_link" href="%(advanced_search_url)%"><h3>%text(find)%</h3><span class="light_color">%text(findcontent)%</span></a></div>
    %end_if%

    %if_not_empty(banner_message)%
        <div id="beta_message">%(show_beta_message)%</div>
    %end_if%

    <h1 id="site_title"><a href="%(root-url)%" title="%(site-title)%">
        %if_not_null(logo/src)% 
            <? $bimg = ccd($A['logo']['src']); ?><img src="%(#bimg)%" style="width:%(logo/w)%px;height:%(logo/h)%px"/> 
        %else% 
            <span class="light_color">%(site-title)%</span>
        %end_if%
    </a></h1>

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

%loop(macro_names,macro)%    %call(#macro)%    %end_loop%
%loop(inc_names,inc_name)%   %call(#inc_name)% %end_loop%

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

<div id="extra">
  %if_null(edit_extra)%
    %settings(extras,custom_macros)%
    %loop(custom_macros/macros,mac)%
        <div class="menu_group">        
          %call_macro(#mac)%
        </div>
    %end_loop%
  %else%
    <!-- editing extras -->
    %(edit_extra)%
  %end_if%
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

<!--[if lt IE 7.]>
<script defer type="text/javascript" src="%url(js/pngfix.js)%"></script>
<![endif]-->

</body>