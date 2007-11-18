<?/*
[meta]
    type = dataview
    name = count
[meta]
*/

function count_dataview() 
{
    $sql = 'SELECT COUNT(*) %columns% %joins% %where% %order% %limit%';

    return array( 'sql' => $sql,
                   'e'  => array( )
                );
}

?>