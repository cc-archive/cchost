<?
$T->ImportMap('ccskins/shared');
$T->ImportMap('ccskins/shared/formats');

require_once('ccskins/default/strings.php');

$A['html_head']               = 'page.php/html_head';
$A['main_body']               = 'page.php/main_body';
$A['print_head_links']        = 'page.php/print_head_links';
$A['print_page_title']        = 'page.php/print_page_title';
$A['print_banner']            = 'page.php/print_banner';
$A['print_tabs']              = 'page.php/print_tabs';
$A['print_sub_nav_tabs']      = 'page.php/print_sub_nav_tabs';
$A['print_end_script_blocks'] = 'page.php/print_end_script_blocks';
$A['print_footer']            = 'page.php/print_footer';

$A['print_menu']         = 'menu.tpl';

$A['html_form']                  = 'form.php/html_form';
$A['form_fields']                = 'form.php/form_fields';
$A['grid_form_fields']           = 'form.php/grid_form_fields';
$A['submit_forms']               = 'form.php/submit_forms';
$A['show_form_about']            = 'form.php/show_form_about';

$A['show_prompts']       = 'basic_stuff.php/print_prompts';
$A['print_bread_crumbs'] = 'basic_stuff.php/print_bread_crumbs';
$A['print_client_menu']  = 'basic_stuff.php/print_client_menu';
$A['prev_next_links']    = 'basic_stuff.php/prev_next_links';

$A['script_links'][] = 'js/round-box.js';
$A['style_sheets'][] = 'css/round-box.css';

?>