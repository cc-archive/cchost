<?/*
[meta]
    type = dataview
    name = count
[/meta]
*/

function count_dataview() 
{
    $sql = 'SELECT COUNT(*) from cc_tbl_uploads JOIN cc_tbl_user ON upload_user=user_id %joins% %where%';

    return array( 'sql' => $sql,
                   'e'  => array( )
                );
}

?>