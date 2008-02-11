<?/*
[meta]
    type = dataview
    name = playlist_line
[/meta]
*/

function playlist_line_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';

    $sql =<<<EOF
SELECT 
    upload_id, upload_name, user_real_name, user_name, 
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    upload_contest
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_FILES,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL,
                                )
                );
}
?>