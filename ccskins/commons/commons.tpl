
%macro(print_banner)%

    <div id="banner">

    %if_not_empty(sticky_search)%
        <div id="banner_search"><a id="search_site_link" href="%!var(advanced_search_url)%">
        <img src="%!url(images/find.png)%" />
        <h3>%!string(find)%</h3><span>%!string(findcontent)%</a></span></div>
    %end_if%

    %if_not_empty(logged_in_as)%
        <div id="login_info">%!string(loggedin)%: <span>%!var(logged_in_as)%</span>
          <a href="%!var(logout_url)%">%!string(logout)%</a></div>
    %end_if%

    %if_not_empty(beta_message)%
        <div id="beta_message">${show_beta_message}</div>
    %end_if%

      <h1 id="site_title"><a href="%!var(root-url)%" title="%!var(site-title)%">%!var(banner-html)%</a></h1>

    %if_not_empty(site_description)%
        <div id="site_description">%!var(site-description)%</div>
    %end_if%

    %call(print_tabs)%

    </div><!-- banner -->

%end_macro%