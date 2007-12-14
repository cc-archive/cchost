%if_null(records)%
    %return%
%end_if%
%loop(records,R)%
<div class="trr">
  <div class="tdc cc_playlist_item" id="_pli_%(#R/upload_id)%">
    <span>
      <a class="cc_playlist_pagelink cc_file_link" id="_plk_%(#R/upload_id)%" target="_parent" href="%(#R/file_page_url)%">%chop(#R/upload_name,30)%</a>
     </span>%text(str_by)% <a target="_parent" class="cc_user_link" href="%(#R/artist_page_url)%">%chop(#R/user_real_name,30)%</a>
  </div>
  <div class="tdc"><a class="info_button" id="_plinfo_%(#R/upload_id)%"></a></div>
  %if_not_null(reg_user)%
    <div  id="playlist_menu_%(#R/upload_id)%" class="cc_playlist_action tdc light_bg dark_border">
        <a class="cc_playlist_button" href="javascript://playlist_menu_%(#R/upload_id)%">
        <span>%text(str_pl_add_to)% ...</span></a>
        </div>
    %end_if%
    %if_not_null(#R/fplay_url)%
      <div class="tdc cc_playlist_pcontainer">
        <a class="cc_player_button cc_player_hear" id="_ep_%(#R/cart_id)%_%(#R/upload_id)%" href="%(#R/fplay_url)%">
        </a></div>
    %end_if%<div class="hrc"> </div>
    </div>
%end_loop%