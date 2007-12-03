<?/*
[meta]
    type = dataview
    name = upload_row
[/meta]
*/

function upload_row_dataview() 
{
    $sql =<<<EOF
    SELECT *
    FROM cc_tbl_uploads
%joins%
%where%
%group%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_EXTRA )
                );
}

?>