<?/* This file was generated by ccHost Content Manager 
[meta]
     type = dynamic_content_page
     desc = _('OLPC Berklee Sample Pool')
      t = ccskins/shared/pages/content_page.tpl
      content_page_columns = 1
      limit = 
      content_page_width = 600px
      content_page_textformat = format
      content_page_paging = 0
      content_page_box = 0
      sort = date
      ord = desc
      topic_type = olpc

[/meta]
*/
$A['content_page_box'] = '0';
$A['content_page_paging'] = '0';
$A['content_page_width'] = '600px';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '1';
$id = empty($_GET['ids']) ? '' : $_GET['ids'];
$topic = empty($_GET['topic']) ? '' : $_GET['topic'];
print "<h1>OLPC Berklee Sample Pool</h1>";
cc_query_fmt('f=page&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=olpc&limit=' . $id . '&topic=' . $topic );
$A['macro_names'][] = 'prev_next_links';
?>