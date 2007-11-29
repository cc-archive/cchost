<?/*
[meta]
    type = dataview
    name = upload_page
[/meta]
*/

function upload_page_dataview() 
{
    global $CC_GLOBALS;

    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/'); 
    $avatar_sql = cc_get_user_avatar_sql();

    $sql =<<<EOF
SELECT 
    upload_banned, upload_tags, upload_published, 
    user_id, upload_user, upload_id, upload_name, upload_extra, 
    upload_description as _need_description_html,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    user_name,
    $avatar_sql,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url,
    license_url, license_name,
    DATE_FORMAT( upload_date, '%a, %b %e, %Y @ %l:%i %p' ) as upload_date,
    file_name, file_format_info, file_extra, upload_contest,
    collab_upload_collab as collab_id
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
JOIN cc_tbl_files as file ON upload_id = file_upload
LEFT OUTER JOIN cc_tbl_collab_uploads ON upload_id = collab_upload_upload
%joins%
WHERE %where% file_order = 0
LIMIT 1
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_UPLOAD_TAGS,
                                  CC_EVENT_FILTER_ED_PICK,
                                  CC_EVENT_FILTER_COLLAB_CREDIT,
                                  CC_EVENT_FILTER_DESCRIPTION_HTML,
                                  CC_EVENT_FILTER_REMIXES_FULL,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_MACROS, 
                                  CC_EVENT_FILTER_FILES,
                                  CC_EVENT_FILTER_PLAY_URL )
                );
}

?>