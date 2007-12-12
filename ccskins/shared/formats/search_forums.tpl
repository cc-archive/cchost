<?/*
[meta]
    type     = search_forums
    desc     = _('For forums search results')
    dataview = search_forums
    embedded = 1
[/meta]
[dataview]
function search_forum_dataview() 
{
    $cct = ccl('thread') . '/';

    $sql =<<<EOF
SELECT 
    topic_name,
    CONCAT( '$cct', topic_thread, topic_id) as topic_url,
    LOWER(CONCAT_WS(' ', topic_name, topic_text)) as qsearch
     %columns% 
FROM cc_tbl_topics
JOIN cc_tbl_uploads ON topic_upload=upload_id
JOIN cc_tbl_user reviewee ON upload_user=user_id
%joins%
%where% AND (topic_upload > 0) AND MATCH(topic_name, topic_text) AGAINST( '%search%' IN BOOLEAN MODE )
%group%
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics
%where% AND (topic_upload > 0) AND MATCH(topic_name, topic_text) AGAINST( '%search%' IN BOOLEAN MODE )
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
   <div>
     <a href="%(#R/topic_url)%">%(#R/topic_name)%</a>
   </div>
   <div class="search_results" >
    %(#R/qsearch)%
   </div>
%end_loop%
</div>
%call(prev_next_links)%
