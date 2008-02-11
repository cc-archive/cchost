<?/*
[meta]
    type = dataview
    name = upload_menu
[/meta]
*/
function upload_menu_dataview() 
{
    $sql =<<<EOF
SELECT upload_id, upload_banned, upload_tags, upload_published, upload_contest,
       user_id, user_name, upload_user, upload_name
    FROM cc_tbl_uploads
    JOIN cc_tbl_user ON upload_user = user_id
%joins%
%where%
%order%
LIMIT 1
EOF;
    return array( 'sql' => $sql,
                  'name' => 'ajax_menu',
                   'e'  => array(CC_EVENT_FILTER_FILES,
                                 CC_EVENT_FILTER_DOWNLOAD_URL,
                                 CC_EVENT_FILTER_UPLOAD_MENU)
                );
}

