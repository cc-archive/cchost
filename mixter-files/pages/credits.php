<?/*
[meta]
     type = dynamic_content_page
     desc = _('Credits')
      ids = 
      t = ccskins/shared/pages/content_page.tpl
      content_page_columns = 1
      limit = 
      content_page_width = 600px
      content_page_textformat = format
      content_page_paging = 0
      content_page_box = 0
      sort = date
      ord = desc
      topic_type = credits

[/meta]
*/
$A['content_page_box'] = '0';
$A['content_page_paging'] = '0';
$A['content_page_width'] = '600px';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '1';
$A['topic_url'] = 'http://ccmixter.org/api/query?datasource=topics&t=ccskins/shared/pages/content_page.tpl&ids=';
print "<h1>Credits</h1>";
 cc_query_fmt('datasource=topics&f=page&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=credits&limit=&ids=');
 $A['macro_names'][] = 'prev_next_links';
?>