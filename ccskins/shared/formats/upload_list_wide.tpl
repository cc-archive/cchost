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

    $user_avatar_col = cc_get_user_avatar_sql();

    $stream_url = url_args( ccl('api','query','stream.m3u'), 'f=m3u&ids=' );

    $sql =<<<EOF
SELECT 
    upload_id, 
    IF( LENGTH(upload_name) > 35, CONCAT( SUBSTRING(upload_name,1,33), '...'), upload_name ) as upload_name_chop,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    {$user_avatar_col},
    user_real_name, user_name, upload_score, upload_num_scores, upload_extra,
    $is_thumbs_up as thumbs_up, $ratings_on as ratings_enabled,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url, license_url, license_name,
    IF( upload_tags LIKE '%,audio,%', CONCAT( '$stream_url', upload_id ) , '' ) as stream_url,
    DATE_FORMAT( upload_date, '%a, %b %e, %Y @ %l:%i %p' ) as upload_date_format,
    upload_contest, upload_name,
    upload_num_remixes, upload_num_sources, upload_num_pool_sources
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
%joins%
%where%
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
%joins%
%where%
EOF;

    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'name' => 'list_wide',
                   'e'  => array( CC_EVENT_FILTER_FILES,
                                  CC_EVENT_FILTER_UPLOAD_USER_TAGS, 
                                  CC_EVENT_FILTER_REMIXES_SHORT,
                                  CC_EVENT_FILTER_RATINGS_STARS,
                                  CC_EVENT_FILTER_DOWNLOAD_URL,
                                  CC_EVENT_FILTER_PLAY_URL,
                                  CC_EVENT_FILTER_UPLOAD_LIST, )
                );
}
[/dataview]
*/ ?>

<link rel="stylesheet" type="text/css" title="Default Style" href="%url('css/upload_list_wide.css')%" />
<script>
  var ratings_enabled = %if_not_null(records/0/ratings_enabled)% true %else% false %end_if%;
</script>
<div id="upload_listing">
<? $rec_ids = array(); ?>
%loop(records,R)%
    <? $rec_ids[] = $R['upload_id']; ?>
    <div class="upload" ><!--   %(#R/upload_name)%   -->
    <div class="upload_avatar"><img src="%(#R/user_avatar_url)%" /></div>
    <div class="upload_info">
        <a class="lic_link" href="%(#R/license_url)%" about="%(#R/download_url)%"
                  rel="license" title="%(#R/license_name)%" ><img src="%(#R/license_logo_url)%" /></a> 
        <a href="%(#R/file_page_url)%" class="cc_file_link upload_name">%(#R/upload_name_chop)%</a><br />%text(str_by)% 
               <a class="cc_user_link" href="%(#R/artist_page_url)%">%(#R/user_real_name)%</a>
        <div class="upload_date">
            %if_not_null(#R/ratings_enabled)%
                %map(record,#R)%
                %if_empty(#R/thumbs_up)%
                    %call('util.php/ratings_stars_small_user')%
                %else%
                    %call('util.php/recommends')%
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
        <div><a class="cc_file_link" href="%(#R/file_page_url)%">%text(str_detail)%</a></div>
        <div><a href="javascript://action" class="menuup_hook" id="_emup_%(#R/upload_id)%" >%text(str_action)%</a></div>
    </div>

    %if_not_null(#R/remix_parents)%
        <div id="remix_info"><h2>%text(str_list_uses)%</h2>
        %loop(#R/remix_parents,P)%
            <div><a class="remix_links cc_file_link" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> %text(str_by)%
                 <a class="cc_user_link" href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
        %end_loop%
        %if_not_null(#R/more_parents_link)%
            <a class="remix_more_link" href="%(#R/more_parents_link)%">%text(str_more)%...</a>
        %end_if%
        </div>
    %end_if%

    %if_not_null(#R/remix_children)%
        <div id="remix_info"><h2>%text(str_list_usedby)%</h2>
        %loop(#R/remix_children,P)%
            <div><a class="remix_links cc_file_link" href="%(#P/file_page_url)%">%(#P/upload_name)%</a> %text(str_by)%
                 <a class="cc_user_link" href="%(#P/artist_page_url)%">%(#P/user_real_name)%</a></div>
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
<!-- -->
%if_null(#_GET/noscripts)%
    %if_not_null(enable_playlists)%
        %call('playerembed.xml/eplayer')%
        <script>
            ccEPlayer.hookElements($('upload_listing'));
        </script>
    %end_if%

    <script>
        var dl_hook = new popupHookup("download_hook","download",str_download); 
        dl_hook.hookLinks(); 
        var menu_hook = new popupHookup("menuup_hook","ajax_menu",'');
        menu_hook.hookLinks();
        if( user_name && ratings_enabled )
        {
            null_star = '%url('images/stars/star-empty-s.gif')%';
            full_star = '%url('images/stars/star-red-s.gif')%';
            rate_return_t = 'ratings_stars_small_user';
            recommend_return_t = 'recommends';
            new userHookup('upload_list', 'ids=<?= join(',',$rec_ids) ?>');
        }
    </script>
%end_if%