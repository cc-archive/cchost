<?/*
[meta]
    type = download_template
    desc = _('Download files [managed]')
    dataview = files
[/meta]
*/?>
<div  id="cc_download">

%loop(records,R)%
<? 
    // Enable counting, checksum, RDF license at admin/download
    
    $url = ccl('download',$R['upload_id']);
?>
<a href="%(#url)%">%(#R/upload_name)%</a><br />
%end_loop%
</div>
