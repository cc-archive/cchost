<?/*
<?/*%%
[meta]
    type     = dataview
    desc     = _('Pool Item Listing')
    dataview = pool_item_list
[/meta]
*/
function pool_item_list_dataview()
{
    $urll = ccd('ccskins/shared/images/lics/'); 
    $urlls = ccd('ccskins/shared/images/lics/small-'); 
    $urlp = ccl('pools','pool') . '/';
    $urli = ccl('pools','item') . '/';

    $sql =<<<EOF
    SELECT 
        pool_item_id, pool_item_url, pool_item_name, pool_item_artist,
        pool_name, pool_short_name, pool_description, pool_site_url,
        CONCAT( '$urli', pool_item_id) as pool_item_page,
        CONCAT( '$urlp', pool_id ) as pool_url,
        CONCAT( '$urll', license_logo ) as license_logo_url, license_url,
        CONCAT( '$urlls', license_logo ) as license_logo_url_small,
        license_name, pool_item_extra
    FROM cc_tbl_pool_item
    JOIN cc_tbl_pools ON pool_item_pool = pool_id
    JOIN cc_tbl_licenses ON pool_item_license = license_id
%joins%
%where%
%order%
%limit%
EOF;

    $sql_count =<<<EOF
    SELECT COUNT(*)
    FROM cc_tbl_pool_item
    JOIN cc_tbl_pools ON pool_item_pool = pool_id
    JOIN cc_tbl_licenses ON pool_item_license = license_id
        %where%
EOF;

    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                  'e' => array(CC_EVENT_FILTER_REMIXES_FULL,CC_EVENT_FILTER_POOL_ITEMS) );
}
?>