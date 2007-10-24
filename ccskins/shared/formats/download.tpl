<style>
#cc_download {
	margin: 10px;
}
#cc_download a {
	font-weight: bold;
	font-size: 1.2em;
	display: block;
	padding: 3px;
}
#download_help {
	margin: 5px;
	padding: 3px;
}
</style>
<div  id="cc_download">
<div id="download_help">
    <div>%!string(IEtip)%</div>
    <div>%!string(Mactip)%</div>
</div>
<ol>
%loop(records/0/files,F)%
     <li><a href="%!var(#F/download_url)%">%!var(#F/file_nicname)%</a></li>
%end_loop%
</ol>
</div>