%%
[meta]
    type     = list
    desc     = _('Multiple pload listing (narrow)')
    dataview = narrow_list
[/meta]
%%
<link rel="stylesheet" href="<?= $T->URL('css/uploads_narrow.css'); ?>"  title="Default Style" type="text/css" />
<div  id="cc_narrow_list">
<ul>
<table cellspacing="0" cellpadding="0"  >
%map(dochop,'1')%
%loop(records,R)%
   <tr><td>
          <div class="box">
            <div><a href="javascript://download" class="download_hook" id="_ed__%(#R/upload_id)%">%text(str_list_download)%</a></div>
            <div><a href="%(#R/file_page_url)%" >%text(str_detail)%</a></div>
            <div><a href="javascript://action" class="menuup_hook" id="_emup_%(#R/upload_id)%" >%text(str_action)%</a></div>
          </div>
       </td>
       <td><a href="%(#R/file_page_url)%" class="upload_name"><span %if_attr(#R/upload_name_cls,class)%>%chop(#R/upload_name,60)%</span></a>
   %if_not_null(#R/upload_description_text)%
        <div>%chop(#R/upload_description_text,75)% <a href="%(#R/file_page_url)%">(%text(str_more)%)</a></div>
   %end_if%
     </td></tr>
   %if_not_null(#R/fplay_url)%
   <tr><th>%text(str_play)%</th><td><a class="cc_player_button cc_player_hear" id="_ep_%(#R/upload_id)%"> </a><script>
    $('_ep_%(#R/upload_id)%').href = '%(#R/fplay_url)%'</script></tr>
   %end_if%
   <tr><th>%text(str_by)%</th><td><a href="%(#R/artist_page_url)%" class="artist_name">%chop(#R/user_real_name,chop)%</a> 
                              <span class="upload_date">%(#R/upload_date_format)%</span></td></tr>
   <tr><th>%text(str_license)%</th>
     <td><a href="%(#R/license_url)%"><img src="%(root-url)%ccskins/shared/images/lics/small-%(#R/license_logo)%" /></a></td>
   </tr>
    <tr><td /><td>
   %if_null(thumbs_up)%
        %if_not_null(#R/upload_num_scores)%
            <? cc_get_ratings_info($R); ?>
            %map(record,#R)%
            %call('util.php/ratings_stars_small')%
        %end_if%
   %end_if%
   %if_not_null(#R/reviews_link)%
        <a href="%(#R/reviews_link/url)%">%text(str_reviews)% (%(#R/reviews_link/count)%)</a>
   %end_if%
   </td></tr>
    <tr><td class="rec_end" colspan="2"></td></tr>
   
%end_loop%
</table>
%call(prev_next_links)%
</div>
%if_not_null(enable_playlists)%
 %call('playerembed.xml/eplayer')%
 <script>
 ccEPlayer.hookElements($('cc_narrow_list'));
var dl_hook = new popupHookup("download_hook","download",str_download); 
dl_hook.hookLinks(); 
var menu_hook = new popupHookup("menuup_hook","ajax_menu",'');
menu_hook.hookLinks();
</script>
%end_if%