<link rel="stylesheet" href="%!url(css/user_list.css)%" title="Default Style" />

%title(user_listing)%

<div id="user_index">
    %loop(user_index,ui)%
    <a href="%!var(#ui/url)%" title="%!var(#ui/text)%">%!var(#ui/text)%</a> 
    %end_loop%
        <div class="user_breaker"></div>
</div>

<div id="user_listing">
%loop(user_record,u)%
    <div class="user_record">
        <div class="avatar"><img src="%!var(#u/user_avatar_url)%" /></div>
        <a href="%!var(#u/artist_page_url)%" class="user_link">%!var(#u/user_real_name)% <span>(%!var(#u/user_name)%)</span></a>
        <a href="%!var(#u/user_emailurl)%" class="contact_link">%!string(contact_artist)%</a>
        <div class="member_since">%!string(member_since)%: %!var(#u/user_date_format)%</div>
        <div class="user_breaker"></div>
    </div>
%end_loop%
</div>

%call(prev_next_links)%