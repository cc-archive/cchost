<?/*
[meta]
     type = dynamic_content_page
     desc = _('As Seen on the Web')
      ids = 
      t = ccskins/shared/pages/content_page.tpl
      content_page_columns = 2
      limit = 
      content_page_textformat = format
      content_page_paging = 0
      content_page_box = 1
      sort = date
      ord = desc
      topic_type = seen_on_web

[/meta]
*/
$A['content_page_box'] = '1';
$A['content_page_paging'] = '0';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '2';
$A['topic_url'] = 'http://ccmixter.org/api/query?datasource=topics&t=ccskins/shared/pages/content_page.tpl&ids=';
print "<h1>As Seen on the Web</h1>";
 cc_query_fmt('datasource=topics&f=page&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=seen_on_web&limit=&ids=');
 $A['macro_names'][] = 'prev_next_links';
?>