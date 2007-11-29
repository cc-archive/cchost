
<link rel="stylesheet" title="Default Style" href="%url(css/user_profile.css)%" />

<? cc_get_user_details($A['user_record']); ?>

<div id="user_profile">
        <div class="avatar"><img src="%(user_record/user_avatar_url)%" /></div>
        <a href="%(user_record/user_emailurl)%" class="contact_link">%text(contact_artist)%</a>
        <div class="member_since">%text(member_since)%: %(user_record/user_date_format)%</div>

<div id="user_fields">
    %loop(user_record/user_fields,uf)%
        <div class="ufc" %if_attr(#uf/id,id)%><span>%text(#uf/label)%</span> %(#uf/value)%</div>
    %end_loop%
</div>

<div id="user_tag_links">

    %loop(user_record/user_tag_links,groups)%
    <div class="user_tag_group">
        <h3>%text(#groups/label)%</h3>
            %loop(#groups/value,link)%
                <a href="%(#link/tagurl)%">%(#link/tag)%</a>%if_not_last(#link)%, %end_if%
            %end_loop%
    </div>
    %end_loop%
</div>

<? /*
    [thumbs_up] => 1
    [ratings_score] => 508
*/ ?>
</div>

