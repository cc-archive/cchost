<?/*
[meta]
    type = dataview
    name = upload_owner
[/meta]
*/

function upload_owner_dataview() 
{
    $sql =<<<EOF
    SELECT user_name, user_id
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