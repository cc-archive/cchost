<?/*
[meta]
    type = dataview
    name = list_narrow
[meta]
*/

function list_narrow_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/small-'); 

    $sql =<<<EOF
SELECT 
    upload_id, 
    upload_name, 
    upload_description as _need_description_text,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    user_name,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url,
    DATE_FORMAT( upload_date, '%W, %M %e, %Y @ %l:%i %p' ) as upload_date_format,
    file_name, file_format_info, file_extra, upload_contest
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
JOIN cc_tbl_files as file ON upload_id = file_upload
%joins%
WHERE %where% file_order = 0
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_DESCRIPTION_TEXT,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL )
                );
}

?>