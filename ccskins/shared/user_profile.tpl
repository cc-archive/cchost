<?/* %%
[meta]
    type = profile
    desc = _('User profile page')
    dataview = user_profile
    embedded = 1
[/meta]
[dataview]
function user_profile_dataview()
{
    $avatar_sql = cc_get_user_avatar_sql();
    $cce = ccl('people','contact') . '/';

    $sql =<<<EOF
        SELECT $avatar_sql, user_id, user_name, user_real_name, user_favorites, user_num_uploads,
            user_homepage, user_description, user_whatilike, user_whatido, user_lookinfor,
            user_num_reviewed, user_num_reviews,
            CONCAT( '$cce', user_name ) as user_emailurl, user_num_posts,
            DATE_FORMAT( user_registered, '%a, %b %e, %Y' ) as user_date_format
        FROM cc_tbl_user
        %where%
EOF;

    return array( 'sql' => $sql,
                  'e' => array( CC_EVENT_FILTER_USER_PROFILE ) );

}
[/dataview] %%
*/?>

%if_null(records/0)%
    %return%
%end_if%
%map(#U,records/0)%

<link rel="stylesheet" title="Default Style" href="%url(css/user_profile.css)%" />

<div id="user_profile">
        <div class="avatar"><img src="%(#U/user_avatar_url)%" /></div>
        <a href="%(#U/user_emailurl)%" class="contact_link">%text(contact_artist)%</a>
        <div class="member_since">%text(member_since)%: %(#U/user_date_format)%</div>

<div id="user_fields">
    %loop(#U/user_fields,uf)%
        <div class="ufc" %if_attr(#uf/id,id)%><span>%text(#uf/label)%</span> %(#uf/value)%</div>
    %end_loop%
</div>

<div id="user_tag_links">
    %loop(#U/user_tag_links,groups)%
    <div class="user_tag_group">
        <h3>%text(#groups/label)%</h3>
            %loop(#groups/value,link)%
                <a href="%(#link/tagurl)%">%(#link/tag)%</a>%if_not_last(#link)%, %end_if%
            %end_loop%
    </div>
    %end_loop%
</div>

</div>

