<?

error_reporting(E_ALL);

CCEvents::AddHandler(CC_EVENT_CONFIG_CHAGNED, 'config_changed' );

function config_changed( &$spec, &$old_value )
{
    $config_data = CCDatabase::QueryItem("SELECT config_data FROM cc_tbl_config WHERE config_type = 'config'");
    $config_data = unserialize($config_data);
    CCDebug::Enable(true);
    $str = '';
    if( CCUser::IsLoggedIn() )
        $str = '[' . CCUser::CurrentUserName() . ']';
    $str .= str_replace(ccl(),'',cc_current_url()) . ' ';
    //$diff = array_diff( $config_data, $old_value );
    //d($diff);
    CCDebug::Log("Config changed: {$str} type:{$spec['config_type']}");
    if( $config_data['cc-host-version'] != CC_HOST_VERSION )
    {
        CCDebug::Log("CONFIG IS WRONG! Correcting");
        $config_data['cc-host-version'] = CC_HOST_VERSION;
        $config_data = serialize($config_data);
        $table = new CCTable('cc_tbl_config','config_type');
        $args['config_type'] = 'config';
        $args['config_data'] = $config_data;
        $table->Update($args);
    }
}


?>