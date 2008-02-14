<?/*
[meta]
    type     = search_results
    desc     = _('For reviews search results')
    dataview = search_reviews
    embedded = 1
[/meta]
[dataview]
function search_reviews_dataview() 
{
    $cct = ccl('reviews') . '/';

    $sql =<<<EOF
SELECT 
    upload_name, reviewers.user_real_name as reviewer,
    CONCAT( '$cct', reviewee.user_name, '/', topic_upload, '#', topic_id) as topic_url,
    LOWER(CONCAT_WS(' ', topic_name, topic_text)) as qsearch
     %columns% 
FROM cc_tbl_topics
JOIN cc_tbl_user reviewers ON topic_user=reviewers.user_id
JOIN cc_tbl_uploads ON topic_upload=upload_id
JOIN cc_tbl_user reviewee ON upload_user=reviewee.user_id
%joins%
%where%
%group%
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics
JOIN cc_tbl_uploads ON topic_upload=upload_id
%where%
EOF;

    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array( CC_EVENT_FILTER_SEARCH_RESULTS )
                );
}
[/dataview]
*/?>
<div  id="search_result_list">
%loop(records,R)%
   <div class="search_results_link" >
     <a href="%(#R/topic_url)%">%(#R/reviewer)% - %text(str_review_of)%: %(#R/upload_name)%</a>
   </div>
   <div class="search_results" >
    %(#R/qsearch)%
   </div>
%end_loop%
</div>
%call(prev_next_links)%