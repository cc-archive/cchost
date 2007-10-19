<?

function _t_admin_print_admin_menu()
{
    global $_TV;

    $menu = $_TV['admin_menu'];

    if( empty($menu['do_local']) )
    {
        $_TV['client_menu_help'] = $menu['global_help'];
        $_TV['client_menu'] = $menu['global_items'];
    }
    else
    {
        $help = $menu['local_help'] . ' <select id="vroot_selector">';
        $vroots = $menu['config_roots'];
        foreach( $vroots as $VR )
        {
            $selected = $VR['selected'] ? 'selected="selected" ' : '';
            $help .= "<option value=\"{$VR['cfg']}\" $selected>{$VR['text']}</option>\n";
        }
        $_TV['client_menu_help'] = $help . "</select>";
        $_TV['client_menu_hint'] = $menu['local_hint'];
        $_TV['client_menu']      = $menu['local_items'];
        $_TV['end_script_blocks'][] = 'admin.php/print_admin_menu_hook';
    }

    _template_call_template('print_client_menu');
}

function _t_admin_print_admin_menu_hook()
{
    ?>
<script>
function vroot_hook()
{
    var e = $('vroot_selector');
    var cfg = e.options[ e.selectedIndex ].value;
    document.location = root_url + cfg + '/admin/site/local';
}
Event.observe( 'vroot_selector', 'change',  vroot_hook );

</script>
    <?
}
?>