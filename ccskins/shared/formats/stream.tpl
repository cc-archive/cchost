<div  id="cc_list">
%loop(records,R)%
    %if_not_empty(#R/stream_link)%
        <span ><a href="%(#R/stream_link/url']?>" class="cc_streamlink">&nbsp;</a></span><?
    %end_if%
  <a href="%(#R/file_page_url)%" class="cc_file_link">%chop(#R/upload_name,chop)%</a> %text(by)% 
  <a class="cc_user_link" href="%(#R/artist_page_url)%">%chop(#R/user_real_name,chop)%</a>
</div>
%if_not_empty(format_sig)%
   <i class="cc_tagline"><span>%call(format_sig)%</span></i>
%end_if%
</div>