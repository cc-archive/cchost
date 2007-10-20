<?

function _t_skin_default_map_init($T,&$_TV)
{
$_TV['html_head']               = 'page.php/html_head';
$_TV['main_body']               = 'page.php/main_body';
$_TV['print_head_links']        = 'page.php/print_head_links';
$_TV['print_banner']            = 'page.php/print_banner';
$_TV['print_tabs']              = 'page.php/print_tabs';
$_TV['print_sub_nav_tabs']      = 'page.php/print_sub_nav_tabs';
$_TV['print_end_script_blocks'] = 'page.php/print_end_script_blocks';
$_TV['print_footer']            = 'page.php/print_footer';

$_TV['print_menu']         = 'menu.tpl';

$_TV['list_files']         = 'formats/upload_list.xml';
$_TV['list_file']          = 'formats/upload_page.xml';

$_TV['grow_textarea_script']       = 'form.php/grow_textarea_script';
$_TV['hide_form_on_submit_script'] = 'form.php/hide_form_on_submit_script';
$_TV['html_form']                  = 'form.php/html_form';
$_TV['form_fields']                = 'form.php/form_fields';
$_TV['grid_form_fields']           = 'form.php/grid_form_fields';
$_TV['submit_forms']               = 'form.php/submit_forms';
$_TV['show_form_about']            = 'form.php/show_form_about';

$_TV['license_rdf']          = 'file_macros.php/license_rdf';
$_TV['show_zip_dir']         = 'file_macros.php/show_zip_dir';
$_TV['comment_thread']       = 'file_macros.php/request_reviews';
$_TV['comment_thread_list']  = 'file_macros.php/print_recent_reviews';
$_TV['howididit_link']       = 'file_macros.php/print_howididit_link';
$_TV['upload_not_published'] = 'file_macros.php/upload_not_published';
$_TV['upload_banned']        = 'file_macros.php/upload_banned';

$_TV['show_prompts']       = 'basic_stuff.php/print_prompts';
$_TV['print_bread_crumbs'] = 'basic_stuff.php/print_bread_crumbs';
$_TV['print_client_menu']  = 'basic_stuff.php/print_client_menu';
$_TV['prev_next_links']    = 'basic_stuff.php/prev_next_links';

$_TV['admin_menu_page']    = 'admin.php/print_admin_menu';

$_TV['script_links'][] = 'js/prototype.js';
$_TV['script_links'][] = 'js/selector-addon-v1.js';
$_TV['script_links'][] = 'js/scriptaculous/scriptaculous.js';
$_TV['script_links'][] = 'js/modalbox/modalbox.js';
$_TV['script_links'][] = 'js/round-box.js';
$_TV['script_links'][] = 'js/cchost.js';

$_TV['style_sheets'][] = 'js/modalbox/modalbox.css';
$_TV['style_sheets'][] = 'css/round-box.css';


$GLOBALS['str_album']          = _('Album/CD');
$GLOBALS['str_bpm']            = _('BPM');
$GLOBALS['str_by']             = _('by');
$GLOBALS['str_date']           = _('uploaded');
$GLOBALS['str_down']           = _('Download');
$GLOBALS['str_edpick']         = _('Editorial pick');
$GLOBALS['str_feature']        = _('featuring');
$GLOBALS['str_find']           = _('Search');
$GLOBALS['str_findcontent']    = _('Find content');
$GLOBALS['str_flagtip']        = _('Flag this upload for possible violation of terms');
$GLOBALS['str_i_saw_this']     = _('I Used or Saw %s In A...');
$GLOBALS['str_IEtip']          = _('IE: Right-click select \'Save Target As\'');
$GLOBALS['str_lastmod']        = _('last modified');
$GLOBALS['str_length']         = _('length');
$GLOBALS['str_lic']            = _("Licensed under");
$GLOBALS['str_loggedin']       = _('Logged in as');
$GLOBALS['str_logout']         = _('log out');
$GLOBALS['str_Mactip']         = _('Mac: Control-click select \'Save Link As\'');
$GLOBALS['str_more']           = _('more');
$GLOBALS['str_nsfw']           = _('Not Safe For Work');
$GLOBALS['str_nsfw_t']         = _('This upload might be ');
$GLOBALS['str_play']           = _('Play');
$GLOBALS['str_podcast']        = _('Podcast');
$GLOBALS['str_read_all']       = _('Real all...');
$GLOBALS['str_recent_reviews'] = _('Recent reviews');
$GLOBALS['str_remix']          = _('Remix');
$GLOBALS['str_skip']           = _('Skip to content');
$GLOBALS['str_trackback']      = _('Trackback');
$GLOBALS['str_upload_flag']    = _('Flag this upload for possible violation of terms');
$GLOBALS['str_usedby']         = _('Samples are used in:');
$GLOBALS['str_uses']           = _('Uses samples from:');
$GLOBALS['str_video']          = _('Video');
$GLOBALS['str_web_blog']       = _('Web / Blog / MySpace');
$GLOBALS['str_zip_title']      = _('Contents of ZIP Archive');

$GLOBALS['str_remove_from']   = _('Remove from');
$GLOBALS['str_add_to']   = _('Add to');
$GLOBALS['str_new_playlist'] = _('new playlist');
$GLOBALS['str_created_by'] = _('created by');
$GLOBALS['str_dynamic'] = _('dynamic');
$GLOBALS['str_items'] = _('items');  // as in: number of items

$GLOBALS['str_project'] = _('Project');
$GLOBALS['str_credit'] = _('Credit');
$GLOBALS['str_featuring'] = _('Featuring');

}
?>