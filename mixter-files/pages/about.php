<?/*
   This file was generated by ccHost Content Manager

[meta]
     type = dynamic_content_page
     desc = _('About')
      content_feed = 0
      content_page_box = 0
      content_page_columns = 1
      content_page_textformat = format
      content_page_width = 600px
      content_toc = 0
      limit = 
      ord = desc
      paging = off
      sort = date
      t = ccskins/shared/pages/content_page.tpl
      topic_type = faq

[/meta]
*/
$A['content_page_box'] = '0';
$A['content_page_width'] = '600px';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '1';

$topic = empty($_GET['topic']) ? '' : $_GET['topic'];
print "<h1>About</h1>";
cc_query_fmt('f=embed&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=desc&type=faq&limit=&paging=off&topic=' . $topic );
// no paging 
//  
?>

