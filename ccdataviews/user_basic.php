<?/*
[meta]
    type = dataview
    desc = _('Basic user info for data mining')
    name = user_basic
    datasource = user
[/meta]
*/
function user_basic_dataview() 
{
    $ccp = ccp('people');
    $avatar_sql = cc_get_user_avatar_sql();

    $sql =<<<EOF
SELECT user_id, user_name, user_real_name, CONCAT('{$ccp}',user_name) as artist_page_url, {$avatar_sql},
       user_registered, user_homepage
    FROM cc_tbl_user 
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array()
                );
}

