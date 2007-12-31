<?
header('Content-type: text/javascript');
?>

var str_action_menu               ='<?=addslashes($T->String('str_action_menu'))?>';
var str_artist                    ='<?=addslashes($T->String('str_artist'))?>';
var str_cancel                    ='<?=addslashes($T->String('str_cancel'))?>';
var str_close                     ='<?=addslashes($T->String('str_close'))?>';
var str_enter_role                ='<?=addslashes($T->String('str_collab_enter_role'))?>';  //Enter%s'srole(e.g. bass, producer, vocals)
var str_hide                      ='<?=addslashes($T->String('str_collab_hide'))?>';
var str_invite_other_artists      ='<?=addslashes($T->String('str_collab_invite'))?>';
var str_publish                   ='<?=addslashes($T->String('str_collab_publish'))?>';
var str_send_mail_to              ='<?=addslashes($T->String('str_collab_send_mail_to'))?>';
var str_tags_label                ='<?=addslashes($T->String('str_collab_tags_label'))?>';   //tags(e.g.bass, beat, keys)
var str_default                   ='<?=addslashes($T->String('str_default'))?>';
var str_download                  ='<?=addslashes($T->String('str_download'))?>';
var str_2_weeks_ago               ='<?=addslashes($T->String('str_filter_2_weeks_ago'))?>';
var str_3_months_ago              ='<?=addslashes($T->String('str_filter_3_months_ago'))?>';
var str_a_week_ago                ='<?=addslashes($T->String('str_filter_a_week_ago'))?>';
var str_a_year_ago                ='<?=addslashes($T->String('str_filter_a_year_ago'))?>';
var str_all                       ='<?=addslashes($T->String('str_filter_all'))?>';
var str_all_time                  ='<?=addslashes($T->String('str_filter_all_time'))?>';
var str_clear                     ='<?=addslashes($T->String('str_filter_clear'))?>';
var str_enter_user_below          ='<?=addslashes($T->String('str_filter_enter_user'))?>';
var str_hide_list                 ='<?=addslashes($T->String('str_filter_hide_list'))?>';
var str_last_month                ='<?=addslashes($T->String('str_filter_last_month'))?>';
var str_license                   ='<?=addslashes($T->String('str_filter_license'))?>';
var str_limit                     ='<?=addslashes($T->String('str_filter_limit'))?>';
var str_match                     ='<?=addslashes($T->String('str_filter_match'))?>';
var str_match_all_tags            ='<?=addslashes($T->String('str_filter_match_all_tags'))?>';
var str_match_any_tags            ='<?=addslashes($T->String('str_filter_match_any_tags'))?>';
var str_no_records_match          ='<?=addslashes($T->String('str_filter_no_records_match'))?>';
var str_remixes_of                ='<?=addslashes($T->String('str_filter_remixes_of'))?>';
var str_show_list                 ='<?=addslashes($T->String('str_filter_show_list'))?>';
var str_since                     ='<?=addslashes($T->String('str_filter_since'))?>';
var str_yesterday                 ='<?=addslashes($T->String('str_filter_yesterday'))?>';
var str_attribution               ='<?=addslashes($T->String('str_lic_attribution'))?>';
var str_nc_sampling_plus          ='<?=addslashes($T->String('str_lic_nc_sampling_plus'))?>';
var str_nc_share_alike            ='<?=addslashes($T->String('str_lic_nc_share_alike'))?>';
var str_non_commercial            ='<?=addslashes($T->String('str_lic_non_commercial'))?>';
var str_public                    ='<?=addslashes($T->String('str_lic_public'))?>';
var str_sampling                  ='<?=addslashes($T->String('str_lic_sampling'))?>';
var str_sampling_plus             ='<?=addslashes($T->String('str_lic_sampling_plus'))?>';
var str_share_alike               ='<?=addslashes($T->String('str_lic_share_alike'))?>';

var str_this_site                 ='<?=addslashes($T->String('str_remix_this_site'))?>';
var str_no_search_term            ='<?=addslashes($T->String('str_remix_no_search_term'))?>';
var str_no_matches                ='<?=addslashes($T->String('str_remix_no_matches'))?>';
var str_remix_close               ='<?=addslashes($T->String('str_remix_close'))?>';
var str_remix_open                ='<?=addslashes($T->String('str_remix_open'))?>';
var str_remix_lic                 ='<?=addslashes($T->String('str_remix_lic'))?>';

var str_loading                   ='<?=addslashes($T->String('str_loading'))?>';
var str_ok                        ='<?=addslashes($T->String('str_ok'))?>';
var str_ratings                   ='<?=addslashes($T->String('str_ratings')) ?>';
var str_see_results               ='<?=addslashes($T->String('str_see_results'))?>';
var str_tags                      ='<?=addslashes($T->String('str_tags'))?>';
var str_thinking                  ='<?=addslashes($T->String('str_thinking'))?>';
var str_working                   ='<?=addslashes($T->String('str_working'))?>';
var str_new_row                   ='<?=addslashes($T->String('str_new_row'))?>';

var str_pl_dynamic_changed        ='<?=addslashes($T->String('str_pl_dynamic_changed'))?>';
var str_pl_new_playlist_created   ='<?=addslashes($T->String('str_pl_new_playlist_created'))?>';
var str_pl_track_added            ='<?=addslashes($T->String('str_pl_track_added'))?>';
var str_pl_track_has_been_removed ='<?=addslashes($T->String('str_pl_track_has_been_removed'))?>';
var str_trackback_title           ='<?=addslashes($T->String('str_trackback_title'))?>';
str_trackback_no_email            ='<?=addslashes($T->String('str_trackback_no_email'))?>';
str_trackback_no_link             ='<?=addslashes($T->String('str_trackback_no_link'))?>';
str_trackback_type_album          ='<?=addslashes($T->String('str_trackback_type_album'))?>';      
str_trackback_type_web            ='<?=addslashes($T->String('str_trackback_type_web'))?>';
str_trackback_type_video          ='<?=addslashes($T->String('str_trackback_type_video'))?>';
str_trackback_type_remix          ='<?=addslashes($T->String('str_trackback_type_remix'))?>';
str_trackback_type_podcast        ='<?=addslashes($T->String('str_trackback_type_podcast'))?>';
str_trackback_response            ='<?=addslashes($T->String('str_trackback_response'))?>';

<? exit; ?>