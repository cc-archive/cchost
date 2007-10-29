%macro(main_body)%
    %if_empty(show_body_header)%
<body>
    %else%

        %if_empty(ajax)%
<body>
<div class="hide">
  <a href="#content">%string(skip)%</a>
</div>
            %call(print_banner)%
            %call(print_tabs)%
            %call(print_menu)%

            <div id="main_content">
        %end_if%

        %call(print_bread_crumbs)%
        %call(print_sub_nav_tabs)%
        %call(print_page_title)%
        %if_empty(ajax)% 
            <a name="content" ></a>    
        %end_if%
        %loop(macro_names,macro)%    %call(#macro)%             %end_loop%
        %loop(inc_names,inc_name)%   %call(#inc_name)%          %end_loop%

        %if_empty(ajax)%
            </div> <!-- main_content -->
            <div class="post_content_breaker"></div>
        %end_if%

        %if_not_empty(show_body_footer)%
            %call(print_footer)%
            %call(print_end_script_blocks)%
        %end_if%

    </body>
</html>
    %end_if%
%end_macro%


%macro(print_banner)%
    <div id="banner">

    %if_not_empty(sticky_search)%
        <div id="banner_search"><a id="search_site_link" href="%(advanced_search_url)%"><h3>%string(find)%</h3><span>%string(findcontent)%</a></span></div>
    %end_if%

    %if_not_empty(logged_in_as)%
        <div id="login_info">%string(loggedin)%: <span>%(logged_in_as)%</span>
              <a href="%(home-url)%logout">
                    %string(logout)%
                    </a></div>
    %end_if%

    %if_not_empty(beta_message)%
        <div id="beta_message">%(show_beta_message)%</div>
    %end_if%

      <h1 id="site_title"><a href="%(root-url)%" title="%(site-title)%">%(banner-html)%</a></h1>

    %if_not_empty(site-description)%
        <div id="site_description">%(site-description)%</div>";
    %end_if%

    </div><!-- banner -->
%end_macro%

%macro(print_tabs)%
    %if_not_empty(tab_info)%
        <? page_inner_print_tabs($A['tab_info']['tabs'],'tabs'); ?>
        <div class="post_tab_breaker"></div>
        %unmap(tab_info)%   %% this will prevent duplication from multiple skins %%
    %end_if%
%end_macro%


%macro(print_sub_nav_tabs)%
    %if_not_empty(sub_nav_tabs)%
        <? page_inner_print_tabs($A['sub_nav_tabs']['tabs'],'sub_tabs'); ?>
        <div class="post_sub_tab_breaker"></div>
        %unmap(sub_nav_tabs)% %% this will prevent duplication from multiple skins %%
    %end_if%
%end_macro%

<?
function page_inner_print_tabs($tabs,$id)
{
    print "<ul id=\"{$id}\" >";
    foreach( $tabs as $tab )
    {
        $selected = empty($tab['selected']) ? '' : 'class="selected_tab"';
        print "<li $selected><a href=\"{$tab['url']}\" title=\"{$tab['help']}\"><span>{$tab['text']}</span></a></li>";
    }
    print '</ul>';
}
?>

%macro(print_page_title)% 

    %if_not_empty(page-title-str)%
        %string_get(page-title-str,page-title)%
    %end_if%

    %if_not_empty(page-title)%
        <h1 class="title">%(page-title)%</h1>
        %if_empty(ajax)%
            <? $title = addslashes($A['page-title']); ?>
            <script>document.title = '%(site-title)% - %(#title)%'</script>
        %end_if%
    %end_if%

%end_macro%


%macro(print_end_script_blocks)% 
    %loop(end_script_blocks,block)%
        %call(#block)%
    %end_loop%

    %if_not_empty(end_script_links)%
        <? page_script_link_helper($A['end_script_links'],$T); ?>
    %end_if%
%end_macro%


%macro(print_footer)% 
<div id="footer">
  <div id="license">%(site-license)%</div>
  %(footer)%
</div><!-- footer -->
%end_macro%
