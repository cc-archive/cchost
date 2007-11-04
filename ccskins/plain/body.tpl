<body>
<div class="hide">
  <a href="#content">%string(skip)%</a>
</div>
<div id="banner" class="dark_bg light_color">

    %if_not_empty(sticky_search)%
        <div id="banner_search"><a id="search_site_link"
        href="%(advanced_search_url)%"><h3 class="light_color">%string(find)%</h3><span class="light_color">%string(findcontent)%</span></a></div>
    %end_if%

    %if_not_empty(logged_in_as)%
        <div id="login_info">%string(loggedin)%: <span>%(logged_in_as)%</span> 
            <a class="light_color" href="%(home-url)%logout">%string(logout)%</a></div>
    %end_if%

    %if_not_empty(beta_message)%
        <div id="beta_message">%(show_beta_message)%</div>
    %end_if%

    <h1 id="site_title"><a href="%(root-url)%" title="%(site-title)%">%(banner-html)%</a></h1>

    %if_not_empty(site-description)%
        <div id="site_description">%(site-description)%</div>
    %end_if%

</div><!-- banner -->

%if_not_empty(tab_info)%
    <ul id="tabs">
    %loop(tab_info/tabs,tab)%
        <li %if_class(#tab/selected,selected_tab)%><a href="%(#tab/url)%" title="%(#tab/help)%"><span>%(#tab/text)%</span></a></li>
    %end_loop%
    %unmap(tab_info)%
    </ul>
    <div class="post_tab_breaker"></div>
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

    %% Get the custom sidebar items from settings() %%

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

</div> <!-- end of menu -->

    %unmap(menu_groups)%

%end_if%

<div id="main_content">

%call(print_bread_crumbs)%

%if_not_empty(sub_nav_tabs)%
    <ul id="sub_tabs">
    %loop(sub_nav_tabs/tabs,tab)%
        <li %if_class(#tab/selected,selected_tab)%><a href="%(#tab/url)%" title="%(#tab/help)%"><span>%(#tab/text)%</span></a></li>
    %end_loop%
    %unmap(sub_nav_tabs)%
    </ul>
    <div class="post_sub_tab_breaker"></div>
%end_if%

%if_not_empty(page-title)%
    <h1 class="title">%text(page-title)%</h1>
%end_if%
<a name="content" ></a>    

%loop(macro_names,macro)%    %call(#macro)%             %end_loop%
%loop(inc_names,inc_name)%   %call(#inc_name)%          %end_loop%

</div> <!-- main_content -->

<div class="post_content_breaker"></div>

<div id="page_footer" class="light_bg">
  <div id="license">%(site-license)%</div>
  %(footer)%
</div><!-- footer -->

%loop(end_script_blocks,block)%
    %call(#block)%
%end_loop%

<script> 
    new modalHook( [ 'search_site_link', 'mi_login', 'mi_register']);  
    $$('.selected_tab a').each( function(e) { e.style.cursor = 'default'; e.href = 'javascript:// disabled'; } );
</script>

%loop(end_script_links,script_link)%
    <script type="text/javascript" src="%url(#script_link)%" ></script>
%end_loop%

</body>