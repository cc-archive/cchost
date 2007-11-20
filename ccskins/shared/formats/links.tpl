<?/* 
[meta]
    type     = format
    desc     = _('Links to upload page')
    dataview = links
    embedded = 1
[/meta]
[dataview]
function links_by_dataview() 
{
    $urlf = ccl('files') . '/';

    $sql =<<<EOF
SELECT 
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    upload_name,
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
%joins%
%where%
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'e'  => array()
                );
}
[/dataview]
*/?>
<div  id="cc_list">
%loop(records,R)%
<div><a href="%(#R/file_page_url)%" class="cc_file_link">%chop(#R/upload_name,chop)%</a></div>
%end_loop%
%if_not_empty(format_sig)%
   <i class="cc_tagline"><span>%call(format_sig)%</span></i>
%end_if%
</div>