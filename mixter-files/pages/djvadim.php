<?/*
   This file was generated by ccHost Content Manager

[meta]
     type = dynamic_content_page
     desc = _('DJ Vadim and ccMixter')
     page_title = _('DJ Vadim and ccMixter')
      content_feed = 0
      content_page_box = 0
      content_page_columns = 2
      content_page_textformat = format
      content_page_width = 80%
      content_toc = 0
      limit = 
      ord = asc
      paging = on
      show_bread_crumbs = 1
      sort = date
      t = ccskins/shared/pages/content_page.tpl
      topic_type = killkillkill
      breadcrumbs = home,page_title,topic

[/meta]
*/
$A['content_page_box'] = '0';
$A['content_page_width'] = '80%';
$A['content_page_textformat'] = 'format';
$A['content_page_columns'] = '2';

$topic = empty($_GET['topic']) ? '' : $_GET['topic'];
cc_query_fmt('f=embed&t=ccskins/shared/pages/content_page.tpl&sort=date&ord=asc&type=killkillkill&limit=&paging=on&topic=' . $topic );
cc_add_content_paging_links($A,'killkillkill',$topic,'asc','djvadim', '');
$A['macro_names'][] = 'prev_next_links';
//  
?>

