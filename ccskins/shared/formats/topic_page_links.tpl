<?/*
[meta]
    type = topic_format
    desc = _('Content topic links (set page=content_page_name)')
    dataview = topic_page_links
    embedded = 1
    datasource = topics
    required_args = page
[/meta]
[dataview]
function topic_page_links_dataview($queryObj)
{
    $page       = $queryObj->args['page'];
    $topic_type = cc_get_content_page_type($page);
    $page_url   = url_args(ccl($page),'topic=');

    $sql =<<<EOF
        SELECT topic_name, 
            CONCAT( '{$page_url}', LOWER(REPLACE(topic_name,' ','-')) ) as topic_url
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
%loop(records,R)%
<li><a href="%(#R/topic_url)%" class="topic_link">%(#R/topic_name)%</a></li>
%end_loop%

