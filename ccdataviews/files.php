<?/*
[meta]
    type = dataview
    name = files
[/meta]
*/

function files_dataview() 
{
    $sql =<<<EOF
        SELECT upload_id, upload_contest, user_name
        %columns% 
FROM cc_tbl_uploads 
JOIN cc_tbl_user ON upload_user = user_id
%joins% %where% %order% %limit%
EOF;

    return array( 'sql' => $sql,
                   'e'  => array(CC_EVENT_FILTER_FILES)
                );
}

?>