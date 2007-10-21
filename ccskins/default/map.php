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

}
?>