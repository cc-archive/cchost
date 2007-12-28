<?/*
[meta]
    type = content_page
    desc = _('Generic Page')
    dataview = content_page
    embedded = 1
[/meta]
[dataview]
function content_page_dataview() 
{
    $sql =<<<EOF
SELECT  topic_text as format_html_topic_text, 
        topic_text as format_text_topic_text, 
        topic_text,
        topic_id,
        topic_name
FROM cc_tbl_topics AS topic
%where% 
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics AS topic
%where%
EOF;
    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array(
                                  CC_EVENT_FILTER_FORMAT)
                );
}
[/dataview]
*/
if( empty($A['content_page_textformat']) )
    $A['content_page_textformat'] = 'format';
?>

<table class="cc_content_page" cellspacing="0" cellspacing="0" >
<?  $num_cols = empty($A['content_page_cols']) ? 2 : $A['content_page_cols'];
    $wid = intval(100/$num_cols);
    $rows = array_chunk($A['records'],$num_cols); ?>
%loop(#rows,row)%
<tr>
    %loop(#row,R)%
        <td style="vertical-align:top;width:<?=$wid?>%">%if_not_null(content_page_box)%<div class="box">%end_if%
            <h2>%(#R/topic_name)%</h2>
            <? switch($A['content_page_textformat']) {
                case 'format': print $R['topic_text_html']; break;
                case 'text':   print $R['topic_text_plain']; break;
                case 'raw':    print $R['topic_text']; break;
            } ?>
        %if_not_null(content_page_box)%</div>%end_if%</td>
    %end_loop%
</tr>
%end_loop%
</table>
%if_not_null(content_page_paging)%
    <!-- -->%call(prev_next_links)%
%end_if%