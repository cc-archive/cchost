<?/*
[meta]
    type = dataview
    desc = _('Basic tags')
    name = tags
    datasource = tags
[/meta]
*/
function tags_dataview() 
{
    $sql =<<<EOF
SELECT tags_count, tags_tag, tag_category
    FROM cc_tbl_tags
    LEFT OUTER JOIN cc_tbl_tag_category ON tags_category = tag_category_id
%joins%
%where%
%order%
%limit%
EOF;
    $sql_count =<<<EOF
SELECT COUNT(*)
    FROM cc_tbl_tags
    LEFT OUTER JOIN cc_tbl_tag_category ON tags_category = tag_category_id
%joins%
%where%
EOF;
    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array()
                );
}

