<?/*
[meta]
    type = format
    desc = _('Preview of upload reviews')
    dataview = review_previews
    embedded = 1
[/meta]
[dataview]
function review_previews_dataview() 
{
    $urlp = ccl('people') . '/';
    $turl = ccl('reviews') . '/';
    $user_avatar_col = cc_get_user_avatar_sql();

    $sql =<<<EOF

SELECT  topic.topic_id, ((COUNT(parent.topic_id)-1) * 30) AS margin,
        IF( COUNT(parent.topic_id) > 1, 1, 0 ) as is_reply, 
        topic.topic_text as _need_topic_html, 
        user_real_name, user_name, user_num_reviews,
        CONCAT( '$turl', user_name, '/',  topic.topic_upload, '#', topic.topic_id ) as topic_url,
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

    return array( 'sql' => $sql,
                   'e'  => array(
                                  CC_EVENT_FILTER_TOPIC_HTML)
                );
}
[/dataview]
*/?>

<? cc_query_fmt('noexit=1&nomime=1&f=html&t=list_files&ids=' . $A['topic_upload']); ?>
<table>
%loop(records,R)%
<tr>
    %if_not_null(#R/is_reply)%
    <td colspan="2" style="border-left: solid white %(#R/margin)%px;" >
        <a name="%(#R/topic_id)%"></a>
        <div><a href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a> %(#R/topic_date_format)% </div>
        <div style="background-color:#DDD;float:left;">%(#R/topic_text_html)%</div>
    </td>
    %else%
    <td >
        <a name="%(#R/topic_id)%"></a>
        <a href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a>
        <div><?= $T->String(array('str_reviews_n',$R['user_num_reviews'])); ?></div>
        <a href="%(#R/artist_page_url)%"><img src="%(#R/user_avatar_url)%" /></a>
    </td>
    <td>
        <div>%(#R/topic_date_format)% </div>
        %(#R/topic_text_html)%
    </td>
    %end_if%
</tr>
%end_loop%
</table>
