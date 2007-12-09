<?/*
[meta]
    type = format
    desc = _('Browse reviews')
    dataview = reviews_browse
    embedded = 1
[/meta]
[dataview]
function reviews_browse_dataview() 
{
    $baseurl = ccl('reviews') . '/';
    $baseup  = ccl('files') . '/';
    $baseus  = ccl('people') . '/';

    $sql =<<<END
        SELECT ups.upload_name, 
               reviewee.user_real_name as reviewee_user_real_name, 
               reviewer.user_real_name as reviewer_user_real_name,
               CONCAT( '$baseurl', reviewee.user_name, '/', upload_id, '#', topic_id ) as topic_url,
               CONCAT( '$baseup',  reviewee.user_name, '/', upload_id ) as file_page_url,
               CONCAT( '$baseus',  reviewee.user_name ) as artist_page_url,
               CONCAT( '$baseus',  reviewer.user_name ) as reviewer_page_url,
               topic_text as _need_topic_text, topic_left,
               DATE_FORMAT( topic_date, '%a, %b %e, %Y @ %l:%i %p' ) as topic_date_format
        FROM cc_tbl_topics
        JOIN cc_tbl_uploads ups      ON topic_upload = ups.upload_id
        JOIN cc_tbl_user    reviewee ON ups.upload_user = reviewee.user_id
        JOIN cc_tbl_user    reviewer ON topic_user = reviewer.user_id
        %where% AND (topic_type = 'review')
        %order%
        %limit%
END;

    $sql_count =<<<END
        SELECT COUNT(*)
        FROM cc_tbl_topics
        JOIN cc_tbl_uploads ups      ON topic_upload = ups.upload_id
        JOIN cc_tbl_user    reviewee ON ups.upload_user = reviewee.user_id
        JOIN cc_tbl_user    reviewer ON topic_user = reviewer.user_id
        %where% AND (topic_type = 'review')
END;

    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array( CC_EVENT_FILTER_TOPIC_TEXT,
                                  CC_EVENT_FILTER_REVIEWS )
                );
}
[/dataview]
*/?>

<table class="cc_topic_table"  cellpadding="0" cellspacing="0">
  %loop(records,R)%
<tr>
    <td><span class="cc_topic_date">%(#R/topic_date_format)%</span></td>
    <td class="cc_topic_thumb_head">
        <a class="cc_user_link" href="%(#R/reviewer_page_url)%">%(#R/reviewer_user_real_name)%</a> 
        <span>%text(str_review_of)%</span> <a class="cc_file_link" href="%(#R/file_page_url)%">%(#R/upload_name)%</a> 
        <span>%text(str_by)%</span> <a class="cc_user_link" href="%(#R/artist_page_url)%">%(#R/reviewee_user_real_name)%</a> 
    </td>
</tr>
<tr>
    <td><div class="cc_topic_see"><a id="commentcommand" href="%(#R/topic_url)%"><span>%text(str_reviews_see)%</span></a></div></td>
    <td class="cc_topic_thumb"><a href="%(#R/topic_url)%"><span>%chop(#R/topic_text_plain,80)%</span></a></td>
</tr>
%end_loop%
</table>
%call(prev_next_links)%