<script>
function grey_me(obj,msgid)
{
    var msg =     document.getElementById(msgid);
    msg.style.display = 'inline';
    msg.innerHTML = 'working...';
    obj.style.color = '#888';
    obj.innerHTML = '';
    return true;
}
function enable_other()
{
    document.getElementById('other_dir').style.display = 'inline';
}
</script>
<?

chdir('..');

require_once('cclib/cc-defines.php');

if( !function_exists('gettext') )
    require_once('ccextras/cc-no-gettext.inc');

$step = empty($_REQUEST['up_step']) ? '1' : $_REQUEST['up_step'];

$install_title = 'ccHost Upgrade';
include( dirname(__FILE__) . '/cc-install-head.php');
$stepfunc = 'up_step_' . $step;
$stepfunc();

function up_step_1()
{
    include( dirname(__FILE__) . '/cc-upgrade-intro.php');
}

function up_step_2()
{
?>
<h2></h2>
<?
    $step = empty($_REQUEST['impf']) ? '1' : $_REQUEST['impf'];
    $impffunc = 'impf_' . $step;
    $impffunc();
}

function dx(&$obj)
{
    print '<pre>';
    if( is_array($obj) )
        print_r($obj);
    else
        var_dump($obj);
    print '</pre>';
    exit;
}
function _filt_path($path)
{
    return array_diff(array_filter(preg_split("#[;\n\r]#",$path)),array('cctemplates','ccfiles')); 
}

function impf_1()
{
    print_dir_form();
}

function print_dir_form($msg='')
{
    $config = get_old_config();

    if( empty($config['template-root']) )
    {
        // this is a pre 3.1 installation
        die('sorry, we are not set up right now to do upgrade on installations before ccHost 3.1');
    }
    else
    {
        $dirs = _filt_path($config['files-root']);
        $keys = array_keys($dirs);
        $first = $dirs[$keys[0]];
        $roots[] = $first;
        while( $first )
        {
            $first = dirname($first);
            if( $first == '.' )
                break;
            if( $first )
                $roots[] = $first;
        }
        $roots = array_reverse($roots);
        $idirs = array();
        if( !empty($config['install-user-root']) )
        {
            $idirs = array(preg_replace('#/$#','',$config['install-user-root']));
        }
        $dirs = array_unique(array_merge($idirs,$roots,$dirs));
        $keys = array_keys($dirs);
        $c = count($keys);
        $checked = 'checked="checked"';

        print $msg;
?>

<p>
    We're going to create some new directories for your custom files. Please make <b>sure</b>
    this directory is 
    <ul>
        <li>Writable to PHP script</li>
        <li>Under your web server root so it's visble and accessable to web browsers</li>
    </ul>
    Where would like us to put these new directories?
</p>
<form method="post" action="?up_step=2&impf=2">
<table id="froot">
<tr><td style="text-align:right">Under:</td><td></td></tr>
<?
        for( $i = 0; $i < $c; $i++ )
        {
            $dir = $dirs[$keys[$i]];
        ?>

<tr><td style="text-align:right;width:220px;"><input <?= $checked ?> type="radio" name="root_file_dir" value="<?=$dir?>"  /></td>
<td><b><?= $dir ?></b></td></tr>
        <?
            $checked = '';
        }
    ?>
    <tr><td style="text-align:right;">Another directory: <input type="radio" name="root_file_dir" onclick="enable_other()" value=".other." /></td>
    <td><input size="45" name="other_dir" id="other_dir" style="display:none" /></td></tr>

    <tr><th colspan="2" style="padding-top:1em;">Domain Change</th></tr>
    <tr><td colspan="2" style="padding-top:1em;">If you moved your data from another domain please let us know the change:</td><tr>
    <? $ttag = get_old_config('ttag');
       $old_domain = preg_replace('%http://([^/]+)/%','$1',$ttag['root-url']); ?>

    <tr><td style="text-align:right">Old domain:</td><td><input name="old_domain" value="<?= $old_domain ?>" /></td></tr>
    <tr><td style="text-align:right">New domain:</td><td><input name="new_domain" value="<?= $old_domain ?>" /></td></tr>

<tr><td></td><td style="padding-top:2em;"><input type="submit" value="Let's go..." /></td></tr>
</table>
</form>
<?
    }
}

function impf_2()
{
    if( empty($_POST['root_file_dir']) )
    {
        $msg = '<p style="color:red">Please specify a directory (?)</p>';
        print_dir_form($msg);
        return;
    }

    if( $_POST['root_file_dir'] == '.other.' )
    {
        if( empty($_POST['other_dir']) )
        {
            $msg = '<p style="color:red">Please specify a directory</p>';
            print_dir_form($msg);
            return;
        }
        $local_base_dir = $_POST['other_dir'];
    }
    else
    {
        $local_base_dir = $_POST['root_file_dir'];
    }

    $new_config = array(
            'dataview-dir'        => $local_base_dir . '/dataviews/',
            'template-root'       => $local_base_dir . '/skins/' , 
            'image-upload-dir'    => $local_base_dir . '/skins/images/',
            'files-root'          => $local_base_dir . '/pages/',
            'extra-lib'           => $local_base_dir . '/lib/',
            'temp-dir'            => $local_base_dir . '/temp',
        );

    foreach( $new_config as $newdir )
    {
        $newdir = preg_replace('#/$#','',$newdir);
        if( file_exists($newdir) )
        {
            $i = 0;
            do {
                $new_base = basename($newdir) . '_' . ++$i;
                $renamed = dirname($newdir) . '/' . $new_base;
            } while( file_exists($renamed) );
            rename($newdir, $renamed);
            chmod($renamed,0777);
            print("Renamed '$newdir' to '$renamed' to get it out of the way<br />\n");
        }
    }

    install_local_files($local_base_dir);

    print("Created new directories<br />\n");

    $new_config['install-user-root']   = $local_base_dir . '/';
    $new_config['cc-host-version']   = CC_HOST_VERSION;
    $config = addslashes(serialize(array_merge( get_old_config(), $new_config )));
    CCDatabase::Query("UPDATE cc_tbl_config SET config_data = '$config' WHERE config_scope = 'media' AND config_type = 'config'");

    $skin_settings = addslashes( serialize( array (
            'skin-file' => 'ccskins/plain/skin.tpl',
            'string_profile' => 'ccskins/shared/strings/all_media.php',
            'list_file' => 'ccskins/shared/formats/upload_page_wide.php',
            'list_files' => 'ccskins/shared/formats/upload_list_wide.tpl',
            'max-listing' => 12,
            'html_form' => 'html_form.tpl/html_form',
            'form_fields' => 'form_fields.tpl/form_fields',
            'grid_form_fields' => 'form_fields.tpl/grid_form_fields',
            'tab_pos' => 'ccskins/shared/layouts/tab_pos_header.php',
            'box_shape' => 'ccskins/shared/layouts/box_round.php',
            'page_layout' => 'ccskins/shared/layouts/layout024.php',
            'color_scheme' => 'ccskins/shared/colors/color_mono.php',
            'font_scheme' => 'ccskins/shared/colors/font_verdana.php',
            'font_size' => 'ccskins/shared/colors/fontsize_sz_small.php',
            'skin_profile' => 'ccskins/shared/profiles/profile_cchost.php',
        ) ) );

    CCDatabase::Query("INSERT INTO cc_tbl_config ( config_data, config_scope, config_type ) VALUES( '$skin_settings', 'media', 'skin-settings')");
    
    $extras = addslashes( serialize( array (
            'macros' => array (
                0 => $local_base_dir . '/skins/extras/extras_links.tpl',
                1 => 'ccskins/shared/extras/extras_edpicks.tpl',
                2 => 'ccskins/shared/extras/extras_latest.tpl',
                3 => 'ccskins/shared/extras/extras_podcast_stream.php',
                4 => 'ccskins/shared/extras/extras_search_box.tpl',
                5 => 'ccskins/shared/extras/extras_support_cc.php',
                ),
            'macros_order' => 'targetmacros[]=1&targetmacros[]=2&targetmacros[]=3&targetmacros[]=5&targetmacros[]=9&targetmacros[]=10',
            ) ) );

    CCDatabase::Query("INSERT INTO cc_tbl_config ( config_data, config_scope, config_type ) VALUES( '$extras', 'media', 'extras')");

    print("Installed New Skin Settings<br />\n");

    if( $_POST['old_domain'] != $_POST['new_domain'] )
    {
        require_once('ccextras/cc-export-settings.inc');
        require_once('cclib/cc-table.php');
        require_once('cclib/cc-config.php');
        require_once('cclib/cc-util.php');
        $ex = new CCSettingsExporter();
        ob_start();
        $ex->ExportPrint(true,false);
        $config_text = ob_get_contents();
        ob_end_clean();
        $config_text = str_replace($_POST['old_domain'],$_POST['new_domain'],$config_text);
        $fname = 'temp_config_dump.txt';
        $f = fopen($fname,'w');
        fwrite($f, $config_text);
        fclose($f);
        $ex->ImportRead($fname);
        //unlink($fname);

        print("Domain switched from {$_POST['old_domain']} to {$_POST['new_domain']}<br />\n");
    }


?>
<p>
The next step involves updating the structure of your database. Depending on the amount
of data and the speed of your server this could take a few minutes. (WARNING: Your
ccHost installation is in an imcomplete, unusable state until you finish this upgrade.)
</p>
<p>
<h3>Continue on to the next upgrade step: <span id="msg"></span><a onclick="grey_me(this,'msg')" href="?up_step=2&impf=3">Do it...</a></h3>
<?
}

function impf_3()
{
    setup_old_db();

    require_once( dirname(__FILE__) . '/cc-upgrade-db.php');

    print("Database structure upgraded<br />\n");

?>
<p>
The next step involves updating the some internal pointers in the database. If you have a lot of reviews
or forum messages this could take a few minutes. (WARNING: Your
ccHost installation is in an imcomplete, unusable state until you finish this upgrade.)
</p>
<p>
<h3>Continue on to the next upgrade step: <span id="msg"></span><a onclick="grey_me(this,'msg')" href="?up_step=2&impf=4">Do it...</a></h3>
<?
}
function impf_4()
{
    setup_old_db();

    require_once( dirname(__FILE__) . '/cc-upgrade-data.php');

    update_config_db($err);
    if( $err )
    {
        print "$err\n<br />";
        return;
    }
   ?>
<p>Please rename the 'ccadmin' directory to something secure and then you are now ready to start using the upgraded ccHost.</p>
<?

}

function update_config_db(&$err)
{
    include('cc-config-db.php');
    $dbconfig ['database']['v'] = $CC_DB_CONFIG['db-name'];
    $dbconfig ['dbserver']['v'] = $CC_DB_CONFIG['db-server'];
    $dbconfig ['dbuser']['v']   = $CC_DB_CONFIG['db-user'];
    $dbconfig ['dbpw']['v']     = $CC_DB_CONFIG['db-password'];
    install_db_config($dbconfig,$err);
    rename('cc-config-db.php','cc-config-db-OLD-.php');
}

function setup_old_db()
{
    require_once('cclib/cc-debug.php');
    require_once('cclib/cc-database.php');
    CCDebug::Enable(true);
    CCDatabase::_config_db('cc-config-db.php');
}

function get_old_config($config='config')
{
    setup_old_db();
    $row = CCDatabase::QueryItem("SELECT config_data FROM cc_tbl_config WHERE config_scope = 'media' AND config_type = '$config'");
    return unserialize($row);
}

print '</body></html>';
exit;
?>