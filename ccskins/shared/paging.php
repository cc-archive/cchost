<?

function _t_paging_prev_next_links(&$T,&$A) 
{
    if( empty($A['paging_stats']) )
        return;
        
    $stats = $A['paging_stats'];
    
    if( empty($stats['paging']) )
        return;
        
    print '<table id="cc_prev_next_links"><tr >';

    if ( !empty($stats['prev_link'])) 
        print "<td ><a class=\"cc_gen_button\" href=\"{$stats['prev_link']}\"><span >{$T->String('str_prev_link')}</span></a></td>\n";

    print '<td  class="cc_list_list_space">&nbsp</td>';

    if ( !empty($stats['next_link'])) 
        print "<td ><a class=\"cc_gen_button\" href=\"{$stats['next_link']}\"><span >{$T->String('str_next_link')}</span></a></td>\n";

    print '</tr></table>';

} // END: function prev_next_links

function _t_paging_google_style_paging(&$T,&$A) 
{
    if( empty($A['paging_stats']) )
        return;
        
    $stats = $A['paging_stats'];
    
    if( empty($stats['paging']) )
        return;
    
    /*
    [paging] => 1
    [limit] => 15
    [all_row_count] => 14360
    [current_page] => 957
    [num_pages] => 958
    [current_url] => http://cchost.org/api/query
    [prev_link] => http://cchost.org/api/query?offset=0
    [prev_offs] => offset=0
    [next_link] => http://cchost.org/api/query?offset=30
    [next_offs] => offset=30
     */

    $mod = $stats['all_row_count'] % $stats['limit'];
    if( !$mod )
        $mod = $stats['limit'];
        
    $url                = url_args($stats['current_url'],'offset=');
    $first_page         = $url . '0';
    $last_page          = $url . ($stats['all_row_count'] - $mod);
    $pagesgroup         = 10;
    $full_numb_of_pages = $stats['num_pages'];
    $page               = $stats['current_page'];
    
    $pagination = '<link rel="stylesheet" type="text/css" href="' . $T->URL('css/paging.css'). '" />';
    
    $pagination .= '<table id="page_buttons"><tr>';
    
    if( !empty($stats['prev_link']) ) {
        $text = $T->String('str_pagination_prev_link');
        $pagination .= "<td><a href=\"{$stats['prev_link']}\"><span >{$text}</span></a></td> ";
    }
    $first = ($stats['limit'] * $page);
    $last  = $first + $stats['limit'];
    if( $last > $stats['all_row_count'] )
        $last = $stats['all_row_count'];
    
    $pagination .= '<td>' .$T->String(array('str_pagination_prompt',
                             number_format($first + 1),
                             number_format($last),
                             number_format($stats['all_row_count']))) . '</td>';
    if ( !empty($stats['next_link'])) {
        $text2 = $T->String('str_pagination_next_link');
        $pagination .= " <td><a href=\"{$stats['next_link']}\"><span >{$text2}</span></a></td>";
    }
    $pagination .= '</tr></table>';
    
    $pagination .= '<table id="page_links"><tr>';

    if( $page ) {
        $pagination .= "<td><a href=\"$first_page\">{$T->String('str_pagination_first')}</a></td>";
    }
    $numpages = $pagesgroup + $page;
    if ($page > ($pagesgroup / 2)){
        $pages_to_display = $page - (int)($pagesgroup / 2);
        $numpages =  $numpages - (int)($pagesgroup / 2);
    }else{
        $pages_to_display = 0;
    }
    if ($numpages > $full_numb_of_pages){
        $numpages = $full_numb_of_pages;
    }
    if( $pages_to_display > 0 ) {
        $pagination .= '<td> ... </td>';  
    }              
    for ($i=$pages_to_display; $i <$numpages; $i++)
    {
        $y = $i+1;
        if ($i == $page){
            $pagination .= "<td class=\"selected_page_link\"><b>{$y}</b></td>";
        }else{
            $next_link = $url . ($stats['limit'] * $i);
            $pagination .= "<td><a href=\"{$next_link}\">{$y}</a></td>";
        }
    }
    if( $i < $full_numb_of_pages ) {
        $pagination .= '<td> ... </td>';     
    }           
    if( $page != ($numpages-1) ) {
        $pagination .= "\n<td><a href=\"{$last_page}\">{$T->String('str_pagination_last')}</a></td>";
    }
 
    $pagination .= "</td></table>";    
    
    print $pagination;
    
    
} // END: function google_style_paging

?>
