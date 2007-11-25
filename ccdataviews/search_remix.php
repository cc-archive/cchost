<?/*
[meta]
    type = dataview
    name = search_remix
[meta]
*/

function search_remix_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';

    $sql =<<<EOF
SELECT 
    upload_id, user_name,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    upload_name,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    user_real_name,
    LOWER(CONCAT_WS(' ', upload_name, upload_description, upload_tags, user_name, user_real_name)) as qsearch
     %columns% 
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
%joins%
%where%
%group%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( )
                );
}

?>