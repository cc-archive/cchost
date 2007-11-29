<?/*
[meta]
    type = dataview
    name = links_short
[/meta]
*/

function links_short_dataview() 
{
    $urlf = ccl('files') . '/';

    $sql =<<<EOF
SELECT 
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    IF( LENGTH(upload_name) > 18, CONCAT( SUBSTRING(upload_name, 1, 15), '...' ), upload_name ) as upload_name
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array(  )
                );
}

?>