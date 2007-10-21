<?

$T->ImportMap('ccskins/default');

function _t_skin_init($T,&$A)
{
    $A['style_sheets'][] = 'css/skin-default.css';
    $A['end_script_blocks'][] = 'skin.php/post_script';

    $T->Call('html_head');
    $T->Call('main_body');
}

function _t_skin_post_script()
{
?>
<script>
new modalHook( [ 'mi_login', 'mi_register', 'search_site_link' ]); 
</script>
<?
}
?>