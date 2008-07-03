<?/*
[meta]
    type = topic_format
    desc = _('Content topic links (set page=content_page_name')
    dataview = page_topic_links
    embedded = 1
    datasource = topics
    required_args = page
[/meta]
[dataview]
function topic_page_links_dataview()
{
    list( $page, $topic_type ) = cc_get_current_content_type();
    $page_url = url_args(ccl($page),'');
    global $CC_GLOBALS;

    $sql =<<<EOF
        SELECT topic_name, 
        CONCAT('{$page_url}', 'topic=', LOWER(REPLACE(topic_name,' ','-')) ) as topic_url
        %columns%
        FROM cc_tbl_topics
        %joins%
        %where% AND (topic_type = '{$topic_type}')
        %order%
        %limit%
EOF;
        return array( 'sql' => $sql, 'e' => array() );
}
[/dataview]
*/?>
<div class="topic_links">
%loop(records,R)%
<div><a href="%(#R/topic_url)%" class="topic_link">%(#R/topic_name)%</a></div>
%end_loop%
</div>
