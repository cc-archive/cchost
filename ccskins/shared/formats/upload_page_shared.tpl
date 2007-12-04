<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id: upload_page_shared.php 8092 2007-11-19 06:59:29Z fourstones $
*
*/


?><link rel="stylesheet" type="text/css" title="Default Style" href="%url('css/upload_page.css')%" />

%if_null(records)%
    %return%
%end_if% 
%map(#R,records/0)% 
%map(record,#R)% 


<div id="date_box">%text(str_list_date)%: %(#R/upload_date)%
%if_not_empty(upload_last_edit)%
<span id="modified_date">%text(str_list_lastmod)%: %(#R/upload_last_edit)% %if_not_null(#R/last_op_str)% (%text(#R/last_op_str)%) %end_if%</span>
%end_if%
</div>

<div id="upload_wrapper">
    <div id="upload_middle">
        <div class="box">
            <img src="%(#R/user_avatar_url)%" style="float:right" />
            <table cellspacing="0" cellpadding="0" id="credit_info">
            %if_null(#R/collab_id)%
                <tr><th>%text(str_by)%</th><td><a href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a></td></tr>
            %else%
                %map(#C,#R/collab)%
                 <tr><th>%text(str_collab_project)%:</th><td><a href="%(home-url)%collab/%(#C/collab_id)%">%(#C/collab_name)%</a></td></tr> 
                      <tr><th>%text(str_collab_credit)%:</th><td>
                %loop(#C/users,U)%
                    <a href="%(home-url)%people/%(#U/user_name)%">%(#U/user_real_name)%</a> %(#U/collab_user_credit)% %if_not_last(#U)% <br /> %end_if%
                %end_loop%
                </td></tr>
            %end_if%

            %if_not_null(#R/upload_extra/featuring)%
                <tr><th>%text(str_featuring)%</th><td>%(#R/upload_extra/featuring)%</td></tr>
            %end_if%

            %if_not_null(#R/file_format_info/ps)%
                <tr><th>%text(str_list_length)%</th><td>%(#R/file_format_info/ps)%</td></tr>
            %end_if%

            %if_null(#R/thumbs_up)%
                <tr><th id="rate_label_%(#R/upload_id)%">%if_not_null(#R/ratings)%<!-- -->%text(str_ratings)% %end_if%</th>
                    <td><span id="rate_block_%(#R/upload_id)%">%call('util.tpl/ratings_stars')% </span></td></tr>
            %else%
                %if_not_null(#R/upload_num_scores)%
                    <tr><th>%text(str_recommends)%</th>
                    <td class="recommend_block" id="recommend_block_%(#R/upload_id)%">%(#R/upload_num_scores)%</td></tr>
                %end_if% 
            %end_if%
            </table>

            %if_not_null(#R/upload_description_html)%
            <?
                $lines = preg_split('/<br/',$R['upload_description_html'] );
                $scroll = false; // count($lines) > 16;
                if( $scroll )
                {
                    ?><div style="overflow:scroll;height:11em;border:1px solid #BBB;padding:4px;"><?
                } 
                ?>%(#R/upload_description_html)%<?
                if( $scroll )
                { ?>
                    </div>
             <? } ?>
            %end_if%

            <div id="taglinks">
            %loop(#R/upload_taglinks,tag)%
                <a href="%(#tag/tagurl)%">%(#tag/tag)%</a>%if_not_last(#tag)%, %end_if%
            %end_loop%
            </div>

        </div><!-- info box -->

        %if_not_null(#R/file_macros)%
            <div class="box">
            %loop(#R/file_macros,M)%
                %call(#M)%
            %end_loop%
            </div>
        %end_if%

    </div>

</div><!-- upload_middle/wrapper -->

<div id="upload_sidebar_box">

    <div class="box" id="license_info">
      <p><img src="%(#R/license_logo_url)%" />
        <div id="license_info_t" >
            %text(str_lic)%<br />
            Creative Commons<br />
            <a href="%(#R/license_url)%">%(#R/license_name)%</a><br />
        </div>
      </p>
    </div>

    %if_not_null(#R/edpick)%
        <div class="box" id="pick_box">
            <h2>%text(str_edpick)%</h2>
                <p>
                    <img src="%url('images/big-red-star.gif')%" />
                    %(#R/edpick/review)%
                </p>
                <div class="pick_reviewer">%(#R/edpick/reviewer)%</div>
        </div>
    %end_if%

    %if_not_null(#R/remix_parents)%
        <div class="box" id="remix_info">
            <h2>%text(str_list_uses)%</h2>
            <img src="%url('images/downloadicon.gif')%" />
        %if_not_null(#R/parents_overflow)%
            <div style="overflow: scroll;height:300px;">
        %end_if%
        %loop(#R/remix_parents,P)%
            <div><a class="remix_links" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> <span>%text(str_by)%</span>
                 <a href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
        %end_loop%
        %if_not_null(#R/parents_overflow)%
            </div>
        %end_if%
        </div>
    %end_if%

    %if_not_null(#R/remix_children)%
        <div class="box" id="remix_info"><h2>%text(str_list_usedby)%</h2><img src="%url('images/uploadicon.gif')%" />
        %if_not_null(#R/children_overflow)%
            <div style="overflow: scroll;height:300px;">
        %end_if%
        %loop(#R/remix_children,P)%
            <div><a class="remix_links" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> <span>%text(str_by)%</span>
                 <a href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
        %end_loop%
        %if_not_null(#R/children_overflow)%
            </div>
        %end_if%
        </div>
    %end_if%

</div><!-- sidebar box -->

<div id="upload_menu_box">
</div><!-- upload_menu_box -->

<script>
function menu_cb(resp) {
    $('upload_menu_box').innerHTML = resp.responseText;
    var dl_hook = new popupHookup("download_hook","download",str_download); 
    dl_hook.hookLinks();
    if( cc_round_boxes )
        cc_round_boxes();
}
var menu_url = query_url + 't=upload_menu&f=html&ids=%(#R/upload_id)%';
new Ajax.Request( menu_url, { method: 'get', onComplete: menu_cb } );
</script>
