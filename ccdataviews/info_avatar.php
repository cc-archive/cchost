<?/*
[meta]
    type = dataview
    desc = _('Deep info (no remixes, user avatar')
    name = info_avatar
[/meta]

*/

function info_avatar_dataview() 
{
    global $CC_GLOBALS;

    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/'); 
    $avatar_sql = cc_get_user_avatar_sql();

    $sql =<<<EOF
SELECT 
    upload_banned, upload_tags, upload_published, 
    user_id, upload_user, upload_id, upload_name,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    user_name,
    $avatar_sql,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url,
    license_url, license_name,
    file_name, file_format_info, file_extra, upload_contest
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
JOIN cc_tbl_files as file ON upload_id = file_upload
%joins%
WHERE %where% file_order = 0
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL )
                );
}

?>