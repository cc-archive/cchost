<?/*
[meta]
    type = dataview
    name = default
[/meta]
*/

function default_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/small-'); 

    $sql =<<<EOF
SELECT 
    upload_id, upload_name, upload_extra, upload_contest, user_name, upload_tags,
    upload_description as format_text_upload_description,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url, license_url, license_name,
    DATE_FORMAT( upload_date, '%a, %b %e, %Y @ %l:%i %p' ) as upload_date_format
     %columns% 
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array(   CC_EVENT_FILTER_EXTRA,
                                    CC_EVENT_FILTER_FORMAT,
                                    CC_EVENT_FILTER_FILES )
                );
}

?>