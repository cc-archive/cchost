<?/*
[meta]
    type = dataview
    name = links_by
[meta]
*/

function links_by_pool_dataview() 
{
    $fhome = ccl('pools/item') . '/';

    $sql =<<<EOF
SELECT IF( LENGTH(pool_item_name) > 20,   CONCAT( SUBSTRING(pool_item_name,1,18),   '...'), pool_item_name ) as upload_name,
       IF( LENGTH(pool_item_artist) > 20, CONCAT( SUBSTRING(pool_item_artist,1,18), '...'), pool_item_artist ) as user_real_name,
       CONCAT('$fhome', pool_item_id) as file_page_url,
       CONCAT('$fhome', pool_item_id) as artist_page_url,
       pool_item_id
    FROM cc_tbl_pool_item 
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                  'name' => 'links_by_pool',
                   'e'  => array()
                );
}

?>