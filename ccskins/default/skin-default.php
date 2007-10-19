<?


function _t_skin_default_init($T,&$A)
{
    $T->Call('skin-default-map.php');

    $A['style_sheets'][] = 'css/skin-default.css';
    $A['end_script_blocks'][] = 'skin-default.xml/post_script';

    $T->Call('html_head');
    $T->Call('main_body');
}

function _t_skin_default_post_script()
{
?>
<script>
new modalHook( [ 'mi_login', 'mi_register', 'search_site_link' ]); 
</script>
<?
}
?>