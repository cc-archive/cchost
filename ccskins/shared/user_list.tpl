<link rel="stylesheet" href="%url(css/user_list.css)%" title="Default Style" />

<h1>%string(people)%</h1>

<div id="user_index" class="light_bg dark_border">
    %loop(user_index,ui)%
    <a href="%(#ui/url)%" title="%(#ui/text)%">%(#ui/text)%</a> 
    %end_loop%
        <div class="user_breaker"></div>
</div>

<div id="user_listing">
%loop(user_record,u)%
    <div class="user_record">
        <div class="avatar"><img src="%(#u/user_avatar_url)%" /></div>
        <a href="%(#u/artist_page_url)%" class="user_link">%(#u/user_real_name)% <span>(%(#u/user_name)%)</span></a>
        <a href="%(#u/user_emailurl)%" class="contact_link">%string(contact_artist)%</a>
        <div class="member_since">%string(member_since)%: %(#u/user_date_format)%</div>
        <div class="user_breaker"></div>
    </div>
%end_loop%
</div>

%call(prev_next_links)%