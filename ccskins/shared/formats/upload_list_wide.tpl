<? /*
[meta]
    type     = list
    desc     = _('Multiple upload listing (wide)')
    dataview = upload_list_wide
    embedded = 1
[/meta]
[dataview]
function upload_list_wide_dataview() 
{
    global $CC_GLOBALS;

    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/small-'); 
    $configs =& CCConfigs::GetTable();
    $chart = $configs->GetConfig('chart');
    $is_thumbs_up = empty($chart['thumbs_up']) ? '0' : '1';
    $ratings_on = empty( $chart['ratings'] ) ? '0' : '1';

    if( empty($CC_GLOBALS['avatar-dir']) )
    {
        $aurl = ccd($CC_GLOBALS['user-upload-root']) . '/';
        $aavtr = "user_name,  '/', " ;
    }
    else
    {
        $aurl = ccd($CC_GLOBALS['avatar-dir']) . '/';
        $aavtr = '';
    }
    if( !empty($CC_GLOBALS['default_user_image']) )
    {
        $davurl = ccd($CC_GLOBALS['default_user_image']);
    }
    else
    {
        $davurl = '';
    }

    $stream_url = url_args( ccl('api','query','stream.m3u'), 'f=m3u&ids=' );

    $sql =<<<EOF
SELECT 
    upload_id, 
    IF( LENGTH(upload_name) > 35, CONCAT( SUBSTRING(upload_name,1,33), '...'), upload_name ) as upload_name_chop,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    IF( LENGTH(user_image) > 0, CONCAT( '$aurl', {$aavtr} user_image ), '$davurl' ) as user_avatar_url,
    user_real_name, user_name, upload_score, upload_num_scores, upload_extra,
    $is_thumbs_up as thumbs_up, $ratings_on as ratings_enabled,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url, license_url, license_name,
    IF( upload_tags REGEXP '(^|,)(audio)(,|$)', CONCAT( '$stream_url', upload_id ) , '' ) as stream_url,
    DATE_FORMAT( upload_date, '%W, %M %e, %Y @ %l:%i %p' ) as upload_date_format,
    file_name, file_format_info, file_extra, upload_contest, upload_name
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
JOIN cc_tbl_files as file ON upload_id = file_upload
%joins%
WHERE %where% file_order = 0
%order%
%limit%
EOF;
    return array( 'sql' => $sql,
                   'name' => 'list_wide',
                   'e'  => array( 
                                  CC_EVENT_FILTER_UPLOAD_USER_TAGS, 
                                  CC_EVENT_FILTER_REMIXES_SHORT,
                                  CC_EVENT_FILTER_RATINGS_STARS,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL )
                );
}
[/dataview]
*/ ?>
<link rel="stylesheet" type="text/css" title="Default Style" href="%url('css/upload_list_wide.css')%" />

<div id="upload_listing">
%loop(records,R)%

    <div class="upload" ><!--   %(#R/upload_name)%   -->
    <div class="upload_avatar"><img src="%(#R/user_avatar_url)%" /></div>
    <div class="upload_info">
        <a class="lic_link" href="%(#R/license_url)%" about="%(#R/download_url)%"
                  rel="license" title="%(#R/license_name)%" ><img src="%(#R/license_logo_url)%" /></a> 
        <a href="%(#R/file_page_url)%" class="upload_name">%(#R/upload_name_chop)%</a><br />%text(str_by)% 
               <a href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a>
        <div class="upload_date">
            %if_empty(#R/thumbs_up)%
                %map(record,#R)%
                %call('util.php/ratings_stars_small')%
            %else%
                %if_not_null(#R/upload_num_scores)%
                    <div class="recommend_block" id="recommend_block_%(#R/upload_id)%">%text(str_recommends)% %(#R/upload_num_scores)% </div>
                %end_if%
            %end_if%
            %(#R/upload_date_format)%
        </div>
    
        <div class="taglinks">
            %loop(#R/usertag_links,tgg)%
                <a href="%(#tgg/tagurl)%">%(#tgg/tag)%</a>%if_not_last(tgg)%, %end_if%
            %end_loop%
        </div><!-- tags -->

        %if_not_empty(#R/fplay_url)%
            <div class="playerdiv"><span class="playerlabel">%text(str_play)%</span><a class="cc_player_button cc_player_hear" id="_ep_%(#R/upload_id)%"> </a></div>
            <script> $('_ep_%(#R/upload_id)%').href = '%(#R/fplay_url)%' </script>
        %end_if%

    </div><!-- upload info -->

    <div class="list_menu">
        %if_not_null(#R/stream_url)%
        <div><a href="%(#R/stream_url)%" type="audio/x-mpegurl">%text(str_stream)%</a></div>
        %end_if%
        <div><a href="javascript://download" class="download_hook" id="_ed__%(#R/upload_id)%">%text(str_list_download)%</a></div>
        <div><a href="%(#R/file_page_url)%">%text(str_detail)%</a></div>
        <div><a href="javascript://action" class="menuup_hook" id="_emup_%(#R/upload_id)%" >%text(str_action)%</a></div>
    </div>

    %if_not_null(#R/remix_parents)%
        <div id="remix_info"><h2>%text(str_list_uses)%</h2>
        %loop(#R/remix_parents,P)%
            <div><a class="remix_links" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> <span>%text(str_by)%</span>
                 <a href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
        %end_loop%
        %if_not_null(#R/more_parents_link)%
            <a class="remix_more_link" href="%(#R/more_parents_link)%">%text(str_more)%...</a>
        %end_if%
        </div>
    %end_if%

    %if_not_null(#R/remix_children)%
        <div id="remix_info"><h2>%text(str_list_usedby)%</h2>
        %loop(#R/remix_children,P)%
            <div><a class="remix_links" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> <span>%text(str_by)%</span>
                 <a href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
        %end_loop%
        %if_not_null(#R/more_children_link)%
            <a class="remix_more_link" href="%(#R/more_children_link)%">%text(str_more)%...</a>
        %end_if%
        </div>
    %end_if%

    <br style="clear:both" />
    </div><!--  end upload  -->

%end_loop%

</div><!-- end listing -->

%call(prev_next_links)%

%if_not_null(enable_playlists)%
    %call('playerembed.xml/eplayer')%
    <script>
        ccEPlayer.hookElements($('cc_narrow_list'));
    </script>
%end_if%

<script>
    var dl_hook = new popupHookup("download_hook","download",str_download); 
    dl_hook.hookLinks(); 
    var menu_hook = new popupHookup("menuup_hook","ajax_menu",'');
    menu_hook.hookLinks();
</script>
