<?/*
[meta]
    type = dataview
    name = count
[/meta]
*/

function count_dataview() 
{
    $sql = 'SELECT upload_id  %columns% %joins% %where% %order% %limit%';

    return array( 'sql' => $sql,
                   'e'  => array( )
                );
}

?>