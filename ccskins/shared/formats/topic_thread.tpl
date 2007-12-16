<?/*
[meta]
    type = format
    desc = _('Forum topic thread')
    dataview = topic_thread
    embedded = 1
[/meta]
[dataview]
function topic_thread_dataview() 
{
    $urlp = ccl('people') . '/';
    $turl = ccl('thread') . '/';
    $user_avatar_col = cc_get_user_avatar_sql();

    $sql =<<<EOF
SELECT  topic.topic_id, IF( COUNT(parent.topic_id) > 2, (COUNT(parent.topic_id) - 1) * 30, 0 ) AS margin,
        IF( COUNT(parent.topic_id) > 2, 1, 0 ) as is_reply, 
        topic.topic_text as format_html_topic_text, 
        user_real_name, user_name, user_num_posts,
        CONCAT( '$turl', topic.topic_thread, '#', topic.topic_id ) as topic_url,
        CONCAT( '$urlp', user_name ) as artist_page_url,
        DATE_FORMAT( topic.topic_date, '%a, %b %e, %Y @ %l:%i %p' ) as topic_date_format,
        {$user_avatar_col}
FROM cc_tbl_topics AS topic, 
     cc_tbl_topics AS parent,
     cc_tbl_user AS user
%where% AND (topic.topic_user = user_id) AND (topic.topic_left BETWEEN parent.topic_left AND parent.topic_right)
GROUP BY topic.topic_id
ORDER BY (topic.topic_left)  asc
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics AS topic, 
     cc_tbl_topics AS parent,
     cc_tbl_user AS user
%where% AND (topic.topic_user = user_id) AND (topic.topic_left BETWEEN parent.topic_left AND parent.topic_right)
GROUP BY topic.topic_id
EOF;
    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array(
                                  CC_EVENT_FILTER_FORMAT, CC_EVENT_FILTER_TOPICS)
                );
}
[/dataview]
*/?>
<link rel="stylesheet" href="%url(css/topics.css)%" title="Default Style" type="text/css" />

<table class="cc_topic_thread">
%loop(records,R)%
<? $thread_ids[] = $R['topic_id']; ?>
<tr>
    %if_not_null(#R/is_reply)%
    <td colspan="2" class="cc_topic_reply" style="margin-left: %(#R/margin)%px;" >
        <a name="%(#R/topic_id)%"></a>
        <div><a class="cc_user_link" href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a> %(#R/topic_date_format)% </div>
        <div class="cc_topic_reply_text med_light_bg">%(#R/topic_text_html)%
            <div class="cc_topic_commands" id="commands_%(#R/topic_id)%"></div>
        </div>
    </td>
    %else%
    <td class="cc_topic_head">
        <a name="%(#R/topic_id)%"></a>
        <a class="cc_user_link" href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a>
        <div><a href="%(#R/artist_page_url)%/topics"><?= $T->String(array('str_forum_posts_n',$R['user_num_posts'])); ?></a></div>
        <a href="%(#R/artist_page_url)%"><img src="%(#R/user_avatar_url)%" /></a>
    </td>
    <td class="cc_topic_body">
        <div class="cc_topic_date">%(#R/topic_date_format)% </div>
        <p>
            %(#R/topic_text_html)%
        </p>
        <div class="topic_commands" id="commands_%(#R/topic_id)%"></div>
    </td>
    %end_if%
</tr>
%end_loop%
</table>
<script>
if( user_name )
{
    new userHookup('topic_cmds','ids=<?= join(',',$thread_ids) ?>');
}
</script>