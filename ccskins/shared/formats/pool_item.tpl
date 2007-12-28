<?/*%%
[meta]
    type     = template_component
    desc     = _('Display Individual Pool Item')
    dataview = pool_item_list
[/meta]
%%*/?>
<link rel="stylesheet" title="Default Style" type="text/css" href="%url(css/upload_page.css)%" />
<style type="text/css">
div#upload_wrapper{float:left;width:100%}
div#upload_middle{margin: 0 30% 0 5%;padding-left:2.0em;}
div#upload_sidebar_box{float:left;width:30%;margin-left:-30%}
/* div#upload_menu_box{float:left;width:20%;margin-left:-100%;padding-left:1.5em;} */
#pool_info {
    text-align: center;
}
</style>
%map(#R,records/0)%
<div id="upload_wrapper">
  <div id="upload_middle">
        <div class="box">
            <table cellspacing="0" cellpadding="0" id="credit_info">
                <tr><th></th>
                    <td><span class="cc_file_link upload_name" style="font-size:2em;">%(#R/pool_item_name)%</span></td></tr>
                <tr><th>%text(str_by)% </th>
                    <td><i>%(#R/pool_item_artist)%</i></td></tr>
                <tr><th>%text(str_pool_from)% </th>
                    <td><a href="%(#R/pool_url)%">%(#R/pool_name)%</a></td></tr>
                <tr><th>%text(str_external_link)% </th>
                    <td><a class="cc_external_link" href="%(#R/pool_item_url)%"><span>%(#R/pool_item_url)%</span> 
                         <img src="%url(images/remote.gif)%" /></a></td></tr>
            </table>
        </div>

        %if_not_null(#R/remix_children)%
            <div class="box" id="remix_info">
                <h2>%text(str_list_usedby)%</h2>
                <p style="position:relative;top:0px;left:0px;"><img src="%url('images/uploadicon.gif')%" /></p>
            %if_not_null(#R/children_overflow)%
                <div style="overflow: scroll;height:300px;">
            %end_if%
            %loop(#R/remix_children,P)%
                <div><a class="remix_links cc_file_link" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> <span>%text(str_by)%</span>
                     <a class="cc_user_link" href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
            %end_loop%
            %if_not_null(#R/children_overflow)%
                </div>
            %end_if%
            </div>
        %end_if%

    </div>
</div>
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

    <div class="box" id="pool_info">
        <h2>%text(str_pool_info_head)%</h2>
        <a class="pool_name" class="cc_external_link" href="%(#R/pool_site_url)%"><span>%(#R/pool_name)%</span> 
            <img src="%url(images/remote.gif)%" /></a>
        <p>"%(#R/pool_description)%"</p>
    </div>
</div>