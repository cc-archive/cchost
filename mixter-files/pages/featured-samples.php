<?/* This file was generated by ccHost Content Manager 
[meta]
     type = dynamic_content_page
     desc = _('Featured Samples')
           t = ccskins/shared/pages/content_page.tpl
      content_page_columns = 3
      limit = 40
      content_page_width = 850px
      content_page_textformat = format
      content_toc = 0
      paging = on
      content_page_box = 1
      sort = date
      ord = desc
      content_feed = 1
      topic_type = feat_samples

[/meta]
*/
$A['content_page_box'] = '1';
$A['content_page_width'] = '850px';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '3';

$topic = empty($_GET['topic']) ? '' : $_GET['topic'];
print "<h1>Featured Samples</h1>";
cc_query_fmt('f=embed&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=feat_samples&limit=40&paging=on&topic=' . $topic );
$A['macro_names'][] = 'prev_next_links';

 cc_content_feed('datasource=topics&type=feat_samples&page=featured-samples','Featured Samples','topics');
?>