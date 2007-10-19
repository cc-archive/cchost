<?

global $_TV;

_template_call_template('skin-default-map.php');

$_TV['style_sheets'][] = 'css/skin-default.css';

$_TV['end_script_blocks'][] = 'skin-default.xml/post_script';

_template_call_template('html_head');
_template_call_template('main_body');


function _t_skin_default_post_script()
{
?>
<script>
new modalHook( [ 'mi_login', 'mi_register', 'search_site_link' ]); 
</script>
<?
}
?>