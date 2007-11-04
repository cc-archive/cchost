
<div  id="cc_list">
<table >
%loop(records,R)%   
<tr ><td class="cc_list_fileinfo">
    <h3><a href="%(#R/file_page_url)%" class="cc_file_link">%chop(#R/upload_name,chop)%</a> </h3>
    
    %if_not_null(#R/upload_description_html)%
        <div  class="cc_description" >%(#R/upload_description_html)%</div>
    %end_if%

    %string(by)% <a href="%(#R/artist_page_url)%">%chop(#R/user_real_name,chop)%</a>
    <span class="cc_upload_date">%(#R/upload_date_format)%</span>

    %if_not_null(#R/fplay_url)%
       <table><tr><td>%string(play)%:</td><td><a  style="display: inline;" class="cc_player_button cc_player_hear" id="_ep_%(#R/upload_id)%" href="%(#R/fplay_url)%"> </a></td></tr></table>
    %end_if%

    <div>%string(download)%: 
    %loop(#R/files,F)%
        %if_not_null(#F/download_url)%
            <a class="cc_download_url" href="%(#F/download_url)%">%(#F/file_nicname)%</a>
        %end_if% 
    %end_loop%
    </div>
    <div  class="cc_tags">%string(tags)%:
    %map(tag_array,#R/usertag_links)%
    %call('tags.php/taglinks')%
    </div>

    
    <a href="%(#R/license_url)%" title="%(#R/license_name)%" class="cc_liclogo">
        <img  src="%(root-url)%ccskins/shared/images/lics/small-%(#R/license_logo)%" />
    </a>
    <hr />
    </td>
</tr>
%end_loop%
</table>
%if_not_empty(enable_playlists)%
    %call('playerembed.xml/eplayer')%
    %call('playlist.xml/playlist_menu')%
%end_if%
%call(prev_next_links)%
</div>