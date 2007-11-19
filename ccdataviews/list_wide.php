<?/*
[meta]
    type = dataview
    name = upload_list_wide
[meta]
*/

function list_wide_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/small-'); 
    $configs =& CCConfigs::GetTable();
    $chart = $configs->GetConfig('chart');
    $is_thumbs_up = empty($chart['thumbs_up']) ? '0' : '1';
    $ratings_on = empty( $chart['ratings'] ) ? '0' : '1';

    $sql =<<<EOF
SELECT 
    upload_id, 
    upload_name, 
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    upload_score,
    upload_num_scores, 
    upload_extra,
    $is_thumbs_up as thumbs_up,
    $ratings_on as ratings_enabled,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url, license_url, license_name,
    DATE_FORMAT( upload_date, '%W, %M %e, %Y @ %l:%i %p' ) as upload_date_format,
    file_name, file_format_info, file_extra, upload_contest, upload_name
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
                   'e'  => array( 
                                  CC_EVENT_FILTER_UPLOAD_USER_TAGS, //$tgg['tagurl']}\">{$tgg['tag']}</
                                  CC_EVENT_FILTER_REMIXES_SHORT,
                                  CC_EVENT_FILTER_RATINGS_STARS,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL )
                );
}

/*
    $R['local_menu'] = cc_get_upload_menu($R);

   if( !empty($R['remix_parents']) )
        $murl = empty($R['more_parents_link']) ? '' : $R['more_parents_link'];
        helper_list_remix_info( $T->String('str_list_uses'), 'downloadicon.gif', $R['remix_parents'], $murl, $T );
    if( !empty($R['remix_children']) )
        $murl = empty($R['more_children_link']) ? '' : $R['more_children_link'];
        helper_list_remix_info( $T->String('str_list_usedby'), 'uploadicon.gif', $R['remix_children'], $murl, $T );

        $fname = !empty($P['upload_name']) ? $P['upload_name'] : $P['pool_item_name'];
        $aname = !empty($P['user_real_name']) ? $P['user_real_name'] : $P['pool_item_artist'];
*/

?>