<?/*
[meta]
    type = template_component
    desc = _('Collab topic thread')
    dataview = collab_thread
    embedded = 1
[/meta]
[dataview]
function collab_thread_dataview() 
{
    $urlp = ccl('people') . '/';
    $turl = ccl('thread') . '/';
    $user_avatar_col = cc_get_user_avatar_sql();

    $sql =<<<EOF
SELECT  topic.topic_id, 
        topic.topic_text as _need_topic_html, 
        user_real_name, user_name, user_num_posts,
        CONCAT( '$turl', topic.topic_thread, '#', topic.topic_id ) as topic_url,
        CONCAT( '$urlp', user_name ) as artist_page_url,
        DATE_FORMAT( topic.topic_date, '%a, %b %e, %Y @ %l:%i %p' ) as topic_date_format,
        {$user_avatar_col}
FROM cc_tbl_topics AS topic
JOIN cc_tbl_user AS user ON (topic.topic_user = user_id) 
%where% 
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics AS topic, 
%where%
EOF;
    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array(
                                  CC_EVENT_FILTER_TOPIC_HTML, CC_EVENT_FILTER_TOPICS)
                );
}
[/dataview]
*/?>

<table>
%loop(records,R)%
<tr>
    <td >
        <a name="%(#R/topic_id)%"></a>
        <a href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a>
        <a href="%(#R/artist_page_url)%"><img src="%(#R/user_avatar_url)%" /></a>
    </td>
    <td>
        <div>%(#R/topic_date_format)% </div>
        %(#R/topic_text_html)%
        <div class="topic_commands" id="commands_%(#R/topic_id)%"></div>
    </td>
</tr>
%end_loop%
</table>
