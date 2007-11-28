<?/*
[meta]
    type = dataview
    name = count_ratings
[/meta]
*/

function count_ratings_dataview() 
{
    $sql = 'SELECT COUNT(*) from cc_tbl_ratings %columns% %joins% %where% %order% %limit%';

    return array( 'sql' => $sql,
                   'e'  => array( )
                );
}

?>