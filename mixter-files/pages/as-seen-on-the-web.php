<?/* This file was generated by ccHost Content Manager 
[meta]
     type = dynamic_content_page
     desc = _('As Seen on the Web')
           t = ccskins/shared/pages/content_page.tpl
      content_page_columns = 2
      limit = 
      content_page_width = 
      content_page_textformat = format
      content_toc = 0
      paging = off
      content_page_box = 1
      sort = date
      ord = desc
      content_feed = 0
      topic_type = seen_on_web

[/meta]
*/
$A['content_page_box'] = '1';
$A['content_page_width'] = '';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '2';

$topic = empty($_GET['topic']) ? '' : $_GET['topic'];
print "<h1>As Seen on the Web</h1>";
cc_query_fmt('f=embed&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=seen_on_web&limit=&paging=off&topic=' . $topic );

//  cc_content_feed('datasource=topics&type=seen_on_web&page=as-seen-on-the-web','As Seen on the Web','topics');
?>