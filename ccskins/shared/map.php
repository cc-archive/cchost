<?


require_once('ccskins/shared/strings.php');

$A['picks_links']        = 'picks.xml/picks_links';

$A['print_forms']        = 'util.php/print_forms';
$A['print_html_content'] = 'util.php/print_html_content';
$A['hide_upload_form']   = 'util.php/hide_upload_form';
$A['print_prompts']      = 'util.php/print_prompts';
$A['prev_next_links']      = 'util.php/prev_next_links';
$A['print_bread_crumbs'] = 'util.php/print_bread_crumbs';
$A['print_client_menu'] = 'util.php/print_client_menu';
$A['format_sig'] = 'util.php/format_signature';

$A['admin_menu_page']    = 'admin.php/print_admin_menu';

$A['popular_tags'] = 'tags.xml/popular_tags';
$A['license_choice'] = 'license.xml/license_choice';

$A['license_rdf'] = 'file_macros.php/license_rdf';
$A['print_howididit_link'] = 'file_macros.php/print_howididit_link';
$A['comment_thread_list'] = 'file_macros.php/print_recent_reviews';
$A['comment_thread'] = 'file_macros.php/request_reviews';
$A['show_zip_dir'] = 'file_macros.php/show_zip_dir';
$A['upload_banned'] = 'file_macros.php/upload_banned';
$A['upload_not_published'] = 'file_macros.php/upload_not_published';

$A['user_listings'] = 'user_list.tpl';
$A['user_listing']  = 'user_profile.tpl';


$A['tags'] = 'tags.php/tags';

$A['script_links'][] = 'js/selector-addon-v1.js';
$A['script_links'][] = 'js/scriptaculous/scriptaculous.js';
$A['script_links'][] = url_args(ccl('docs/strings_js.php'),'ajax=1');
$A['script_links'][] = 'js/modalbox/modalbox.js';
$A['script_links'][] = 'js/cchost.js';

array_unshift($A['script_links'],'js/prototype.js');

$A['style_sheets'][] = 'js/modalbox/modalbox.css';

?>