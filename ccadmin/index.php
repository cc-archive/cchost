<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/


error_reporting(E_ALL);

define('IN_CC_HOST', true);
define('IN_CC_INSTALL', true);

chdir('..');

$step = empty($_REQUEST['step']) ? '1' : $_REQUEST['step'];

include( dirname(__FILE__) . '/cc-install-head.php');
$stepfunc = 'step_' . $step;
$stepfunc();
print('</body></html>');


function step_1()
{
    $v = split('\.',phpversion());
    if( intval($v[0]) < 4 )
    {
        $vmsg = "<div class=\"err\">It doesn't look like you're running on PHP 4, you can't run ccHost until you upgrade.</span>";
    }
    elseif( $v[0] >= 5 )
    {
        $vmsg = "<div style='color:orange'>WARNING: Version 5 of PHP has not been officially tested with this code however there are production installations running ccHost on PHP5.</div>"; 
    }      
    else
    {
        $vmsg = "It looks like you're running on a supported version of PHP";
    }

    $id3suggest  = $_SERVER['DOCUMENT_ROOT'] . '/getid3';
    include( dirname(__FILE__) . '/cc-install-intro.php' );
}

function step_1a()
{
?>
<h2>A Warning</h2>

<p>If you have a previous installation of ccHost and you use the same database name as that previous installation,
this installation script with <b>completely and totally destroy</b> all previous data in that database. All records
of uploads and configuation will be wiped completely out.</p>

<h3>If you're OK with that <a href="?step=2">then continue...</a></h3>
<?
}

function step_2()
{
    print('<h2>We Ask, You Answer</h2>');
    
    $v = get_default_values();
    $f = get_install_fields($v);

    print_install_form($f);
}

function step_3()
{
    $f = array();
    $errs = '';

    $ok =        verify_fields($f,$errs);
    $ok = $ok && install_db_config($f,$errs);
    $ok = $ok && install_tables($f,$errs);
    if( !$ok )
    {
        print_install_form($f,$errs);
        return;
    }

    step_3a();
}

function step_4()
{
    require_once('cc-config-db.php');
    require_once('cclib/cc-defines.php');
    require_once('cclib/cc-debug.php');
    require_once('cclib/cc-database.php');
    require_once('cclib/cc-table.php');
    require_once('cclib/cc-config.php');

    $configs =& CCConfigs::GetTable();
    $settings = $configs->GetConfig('settings');
    $admins   = split(',',$settings['admins']);
    $admin    = $admins[0];
    $ttags    = $configs->GetConfig('ttag');
    $root_url = $ttags['root-url'];

    $rnum = rand();

    $html =<<<END
    <h2>Some Unix Questions</h2>

    <ul><li>Are you running on Unix or Unix-like system?</li><li>Does PHP run in a different user and group account
        as you?</li><li>Are you confused by that last question?</li></ul>
    <p>If the answer to most of those questions is 'yes' then please <a href="?step=4a">read this</a>.</p>

    <h2>Securing the Site</h2>

    <p>You must rename the <b>/ccadmin</b> subdirectory to anything else. ccHost 
    won't run until you do this.</p>
    
    <p>In addition it is highly recommended that you change 
    access permissions in order to secure the site from unauthorized usage. </p>
    
    <p>For example from a UNIX command line you do both these operations with
    these commands from the root of your cchost installation:</p>
    <pre>
    mv ccadmin ccadmin-$rnum
    chmod 700 ccadmin-$rnum
    </pre>

    <h2>Go forth...</h2>

    <p>If you've done those steps you can browse to <a href="$root_url">$root_url</a> and log in as "<b>$admin</b>"
    and continue setting up and configuring the site.</p>

END;

    print($html);
}

function step_4a()
{
    define('HEAD_INCLUDED',1);

    include ( dirname(__FILE__) . '/cc-install-perms.php' );
}

function step_3a()
{
    $v['file_uploads']['v'] = ini_get('file_uploads');
    $v['file_uploads']['s'] = 'On (1)';
    $v['file_uploads']['m'] = 'This is required to be <b>On</b> to allow uploads';
    $v['file_uploads']['k'] = ($v['file_uploads']['v'] && ($v['file_uploads']['v'] != 'Off'));
    $v['file_uploads']['i'] = ' ';

    $v['upload_max_filesize']['v'] = ini_get('upload_max_filesize');
    $v['upload_max_filesize']['s'] = '10M';
    $v['upload_max_filesize']['m'] = 'Determines the overall maxium file upload size. (Typical MP3 song is encoded at 1M per minute.)';
    preg_match('/([0-9]*)/',$v['upload_max_filesize']['v'],$m);
    $i = intval($m[1]);
    $v['upload_max_filesize']['i'] = $i;
    $v['upload_max_filesize']['k'] = $i < 10 ? false : true;

    $v['post_max_size']['v'] = ini_get('post_max_size');
    $v['post_max_size']['s'] = '10M';
    $v['post_max_size']['m'] = 'Determines the maxium file upload size from an HTML form.';
    preg_match('/([0-9]*)/',$v['post_max_size']['v'],$m);
    $i = intval($m[1]);
    $v['post_max_size']['k'] = $i < 10 ? false : true;
    $v['post_max_size']['i'] = $i;

    $v['memory_limit']['v'] = ini_get('memory_limit');
    $v['memory_limit']['s'] = '25';
    if( $v['memory_limit']['v'] )
    {
        $v['memory_limit']['m'] = 'Dealing with large file can consume a lot of memory, being too stingy can have adverse affects.';
        preg_match('/([0-9]*)/',$v['memory_limit']['v'],$m);
        $i = intval($m[1]);
        $v['memory_limit']['k'] = $i < 25 ? false : true;
        $v['memory_limit']['i'] = $i;
    }
    else
    {
        $v['memory_limit']['m'] = '<i>It looks as though your installation of PHP is not compiled to use <a target="_blank"  href="http://us3.php.net/manual/en/ini.core.php#ini.memory-limit">this setting</a>.</i>';
        $v['memory_limit']['k'] = 1;
        $v['memory_limit']['i'] = '';
    }

    $v['max_execution_time']['v'] = ini_get('max_execution_time');
    $v['max_execution_time']['s'] = '120';
    $v['max_execution_time']['m'] = 'Number of seconds a script will execute before aborting. You have to allow for users who upload large files over slow connections.';
    $i = intval($v['max_execution_time']['v']);
    $v['max_execution_time']['i'] = $i;
    $v['max_execution_time']['k'] = $i < 120 ? false : true;

    $v['max_input_time']['v'] = ini_get('max_input_time');
    $v['max_input_time']['s'] = '-1';
    $v['max_input_time']['m'] = 'Number of seconds a form\'s script will execute before aborting. You have to allow for users who upload large files over slow connections. (setting to -1 allows unlimited time)';
    $i = intval($v['max_input_time']['v']);
    $v['max_input_time']['i'] = $i;
    $v['max_input_time']['k'] = ($i > -1) && ($i < 120) ? false : true;

?>
    <h2>Setting up your PHP environment</h2>
    <p>There are several things you should know about uploading files to a PHP environment.</p>
    <p>The default settings for a PHP install may not be the ideal. A list of all PHP settings, where they can
    be changed and what version they apply to can be found <a href="http://us3.php.net/manual/en/ini.php#ini.list">here</a>.</p>
    <p>Below are some settings you should be aware of. You might want to
    print or save this page for future reference.</p>
<?
    
    $ini_location = get_php_ini_location();
    $local_ini = get_cchost_local_root() . '/php.ini';

    if( !empty($ini_location) )
    {
        $inimsg = "These can be updated in your global php initialization file which appears to be located at
     <span class=\"file_name\">$ini_location</span>.";
    }
    else
    {
        $inimsg = 'These can be updated in your php.ini file; on gentoo this is located at: /etc/php/apache2-php4/php.ini';
    }

    $inimsg =<<<EOF
        <p>$inimsg</p>
    <p>If you do not have access to the global php.ini you can create one with just these settings
    and place in them <span class="file_name">$local_ini</span></p>

EOF;

    print $inimsg;

?>
    <table class="ini_table">
    <tr><th>Setting Name</th><th>Description</th><th>Current<br />Value</th><th>Suggested<br />Value</th></tr>
<?
    $html = '';
    foreach( $v as $n => $d )
    {
        $html .= "<tr><td class=\"r\"><b>$n</b></td><td>{$d['m']}</td><td class=\"c\"";
        if( !$d['k'] )
            $html .= " style=\"color:red\" ";
        $html .= ">{$d['v']}</td><td class=\"c\">{$d['s']}</td></tr>\n";
    }
    print($html);
?>
    </table>

    <h3>You're almost done, there's <a href="?step=4">one more step...</a></h3>

<?

}

function install_tables(&$f,&$errs)
{
    //print("<pre>");print_r($f);print("</pre>");exit;
    require_once( 'cc-config-db.php');
    require_once( 'cclib/cc-defines.php');
    require_once( 'cclib/cc-debug.php');
    require_once( 'cclib/cc-database.php' );
    require_once( 'cclib/cc-table.php' );
    require_once( 'cclib/cc-config.php');
    require_once( 'cclib/cc-remix-tree.php' );
    require_once( 'cclib/cc-pools.php' );
    require_once( dirname(__FILE__) . '/cc-install-db.php');
    require_once( 'cclib/cc-lics-install.php');
    
    CCDebug::Enable(true) ;

    if( !cc_install_tables($f,$errs) )
        return(false);

    print "Created tables<br />";

    cc_install_licenses();

    print "Licenses installed<br />";

    CCPool::InstallPools();

//    print "Sample pools installed<br />";

    $pw = md5( $f['pw']['v'] );
    $user = $f['admin']['v'];
    $date = date('Y-m-d H:i:00');
    $sql =<<<END
        INSERT INTO cc_tbl_user (user_name,user_real_name,user_password,user_registered) VALUES ('$user','$user','$pw','$date')
END;

    if( !mysql_query($sql) )
    {
        $errs = "Error creating admin account: " . mysql_error();
        return( false );
    }

    print "Created admin account <br />";

    return( true );

}

function clean_post()
{
    if( get_magic_quotes_gpc() == 1 )
    {
        $keys = array_keys($_POST);
        $c = count($keys);
        for( $i = 0; $i < $c; $i++ )
            $_POST[$keys[$i]] = trim(stripslashes( $_POST[$keys[$i]] ));
    }
    
}

function verify_fields(&$f,&$errs)
{
    clean_post();

    $f = get_install_fields($_POST);

    $ok = true;

    foreach( $f as $id => $data )
    {
        if( empty($f[$id]['v']) && $f[$id]['q'] )
        {
            $ok = false;
            $f[$id]['e'] = 'Must be filled in:';
        }
    }

    verify_password($f,$ok);
    verify_mysql($f,$ok);
    verify_getid3($f,$ok);

    $f['rooturl']['v']     = empty($f['rooturl']['v']) ? ''     : check_dir($f['rooturl']['v'],     true);
    $f['getid3']['v']      = empty($f['getid3']['v']) ? ''      : check_dir($f['getid3']['v'],      false);
    $f['logfile_dir']['v'] = empty($f['logfile_dir']['v']) ? '' : check_dir($f['logfile_dir']['v'], true);

    if( !$ok )
        $errs = 'There were problems, please correct them below';

    return($ok);

}


function verify_password(&$f,&$ok)
{
    $value = $f['pw']['v'];

    if( strlen($value) < 5 )
    {
        $f['pw']['e'] = "Must be at least 5 characters";
        $ok = false;
    }
    if( preg_match('/[^A-Za-z0-9]/', $value) )
    {
        $f['pw']['e'] = "Must letters or numbers";
        $ok = false;
    }
}

function verify_getid3(&$f,&$ok)
{
    if( !empty($f['getid3']['v'] ) )
    {
        $dir = check_dir($f['getid3']['v'],false);

        if( !file_exists($dir) )
        {
            $ok = false;
            $f['getid3']['e'] = "GetID3 directory ($dir) does not exist";
        }
        elseif( !file_exists( $dir . '/getid3.php' ) )
        {
            $f['getid3']['e'] = "Can't find getid3.php in " . $dir;
        }
    }
}

function verify_mysql(&$f, &$ok)
{
    $link = 0;
    if( !empty($f['dbuser']['v'] ) && !empty($f['dbpw']['v']) ) 
    {
        if( !function_exists('mysql_connect') )
        {
            $url = "http://www.php.net/manual/en/faq.databases.php#faq.databases.mysql.php5";

            $f['database']['e'] = "MySQL does not seem to be installed into PHP<br />The problem might be related to".
                                    " <a href=\"$url\" target=\"_blank\">this</a>.";
            $ok = false;
        }
        else
        {
            $link = @mysql_connect( $f['dbserver']['v'], $f['dbuser']['v'], $f['dbpw']['v'] );

            if( !$link )
            {
                $f['dbuser']['e'] = 'MySQL Error: ' . mysql_error() . " for CONNECT";
                $ok = false;
            }
        }
    }

    if( $link && !empty($f['database']['v']) )
    {
        if( !@mysql_select_db($f['database']['v']) )
        {
            $f['database']['e'] = "MySQL Error: " . mysql_error() . " for SELECT";
            $ok = false;
        }
        else
        {
            if( !mysql_query("CREATE TABLE table_test ( test_column int(1) )") )
            {
                $ok = false;
                $f['database']['e'] = "MySQL Error: " . mysql_error() . " for CREATE.";
            }
            else
            {
                $table_ok = false;
                $qr = mysql_query("SHOW TABLES");
                $row = mysql_fetch_row($qr);
                if( $row[0] == 'table_test' )
                {
                    $qr = mysql_query("DESCRIBE table_test");
                    $row = mysql_fetch_row($qr);
                    $ok = $table_ok = $row[0] == 'test_column';
                }
                if( !$table_ok )
                {
                    $f['database']['e'] = "Error creating tables: " . mysql_error();
                }
                mysql_query("DROP TABLE table_test");
            }
        }
    }

    if( $link )
        @mysql_close($link);
}

function check_dir($dir,$slash_required)
{
    $dir = str_replace('\\','/',$dir);
    if( preg_match('#^(.*)/$#',$dir,$m) )
    {
        if( $slash_required )
            return($dir);
        return( $m[1] );
    }
    if( $slash_required )
        return( $dir . '/' );
    return( $dir );
}


function get_default_values()
{
    $v['getid3'] = route_around('getid3');

    if( !file_exists($v['getid3'] . '/getid3.php') )
        if( file_exists($v['getid3'] . '/getid3/getid3.php') )
            $v['getid3'] .= '/getid3'; 


    $v['sitename']   = 'ccHost - ' . $_SERVER['HTTP_HOST'];
    $v['cookiedom']  = '';
    $v['rooturl']    = 'http://' . $_SERVER['HTTP_HOST'] . get_script_base(); 
    $v['dbserver']   = 'localhost';
    $v['admin']      = 'admin';
    $v['site-description'] = 'Download, Sample, Cut-up, Share.';

    return($v);
}

function get_script_base()
{
    $me = $_SERVER['REQUEST_URI'];

    if( !empty($me) )
    {
        if( preg_match( '%^(.+/)[^/]+/(\?.*)?$%', $me, $m ) )
        {
            $base = $m[1];
        }
    }

    if( empty($base) )
        $base = '/';
    return $base;
}

function get_php_ini_location()
{
    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();
    preg_match( '#(?:>|=> )+([^\s]+php\.ini)#', $info, $m );
    if( !empty($m[1]) )
        return $m[1];
    return '';
}

function get_cchost_local_root()
{
    $dir = getcwd();
    $dir = preg_replace('#/ccadmin/?#','',$dir);
    return $dir;
}

function get_install_fields($values)
{
        $sbase = get_script_base();
        $local_root = get_cchost_local_root();

        $pretty_help =<<<EOF
In order to enable Rewrite rules ('pretty URLs') you must locate or create the file <span class="file_name">$local_root/.htaccess</span> and include the following lines:

<div style="text-align:left;white-space:pre;font-family:Courier New, courier, serif;font-size:smaller;
  margin-bottom: 12px;">
RewriteEngine On
RewriteBase $sbase
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ {$sbase}index.php?ccm=/$1 [L,QSA]
</div>

Optionally if you have access to your global Apache configuration files you can add
the following <i>instead</i>:

<div style="text-align:left;white-space:pre;font-family:Courier New, courier, serif;font-size:smaller;
  margin-bottom: 12px;">
&lt;Directory "$local_root"&gt;
  RewriteEngine On
  RewriteBase $sbase
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ {$sbase}index.php?ccm=/$1 [L,QSA]
&lt;/Directory&gt;
</div>

This method is preferred for performance reasons but you'll need to restart Apache in order for this 
version to take effect.
EOF;
        // n - Name
        // t - Input type (see print_install_form())
        // e - Error (filled in at _POST)
        // v - Value 
        // q - Required (1 = yes, 0 = no)
        // h - Help hint

    $f = array(
    'sitename'    => array( 'n' => 'Site Name',              'e' => '', 't' => 'text', 'v' => '' , 'q' => 0,
        'h' => 'The name of your site' ),

    'site-description'    => array( 'n' => 'Site Description', 'e' => '', 't' => 'text', 'v' => '' , 'q' => 0,
        'h' => 'A short tag-line for the site' ),

    'rooturl'     => array( 'n' => 'Root URL',               'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'The URL of your main installation' ),

    '_help'        => array( 'n' => '',  'e' => '', 't'  => 'static', 'v' => $pretty_help , 'q' => 0, 'h' => ''),

    'pretty_urls'        => array( 'n' => 'Enabled \'pretty URLs\'',  'e' => '', 't'  => 'checkbox', 'v' => '' , 'q' => 0,
        'h' => '' ),

    'admin'       => array( 'n' => 'Admin name',             'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'A ccHost account will be created with this name' ),

    'pw'          => array( 'n' => 'Admin password',         'e' => '', 't' => 'password', 'v' => '' , 'q' => 1,
        'h' => '(Remember this, you\'ll need it. Must be at least 5 characters long, letters and numbers only.)' ),

    'database'    => array( 'n' => 'Database name',          'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'Name of the mySQL database to use (this must exist already)' ),

    'dbuser'      => array( 'n' => 'Database user',          'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'mySQL account name to use to access the database' ),

    'dbpw'        => array( 'n' => 'Database password',      'e' => '', 't' => 'password', 'v' => '' , 'q' => 1,
        'h' => 'Password for the mySQL database account ' ),

    'dbserver'    => array( 'n' => 'Database server',        'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'Almost always \'localhost\'' ),

    'logfile_dir' => array( 'n' => 'Path to ccHost logfiles',      'e' => '', 't'  => 'text', 'v' => '' , 'q' => 0,
        'h' => 'Where should ccHost write log files to? (e.g. \'/var/log/cchost\')' ),

    'getid3'      => array( 'n' => 'Path to GetID3',         'e' => '', 't'  => 'text', 'v' => '' , 'q' => 0,
        'h' => "Root directory of GetID3 Library (the one with " .
                 "getid3.php in it, e.g. '$local_root/getid3/getid3')" ),

    'cookiedom'   => array( 'n' => 'Cookie Domain',          'e' => '', 't'  => 'text', 'v' => '' , 'q' => 0,
        'h' => 'Leaving this blank is fine (and may be necessary in some configurations'),
    );
                                   
                                      
    foreach($values as $n => $v )
    {
        $f[$n]['v'] = $v;
    }

    return($f);
}

function print_install_form($f,$err='')
{
    $fields = '';
    foreach( $f as $id => $data )
    {
        if( $data['t'] == 'static' )
        {
            $fields .= "<tr><td></td>".
                       "<td class=\"fv\">{$data['v']}</td></tr>\n";
            continue;
        }

        $required = $data['q'] ? '<span class="rq">*</span>' : '';

        if( $data['e'] )
            $fields .= "<tr><td></td><td class=\"fe\">{$data['e']}</td></tr>\n";

        $fields .= "<tr><td class=\"fh\">$required{$data['n']}: <div class=\"ft\">{$data['h']}</div></td>".
                   "<td class=\"fv\"><input type=\"{$data['t']}\" " .
                   "id=\"$id\" name=\"$id\" ";

        if( $data['t'] == 'checkbox' )
        {
            if( $data['v'] )
                $fields .= " checked=\"checked\" ";
        }
        else
        {
            $fields .= "value=\"{$data['v']}\" ";
        }
        
        $fields .= "/></td></tr>\n";
    }
    if( $err )
        $err = "<div class=\"err\">$err</div>";

    $html =<<<END
$err
<div class="rqmsg">Fields marked '*' are required</div>
<form action="?step=3" method="post">
<table>
     $fields
<tr><td></td><td><input type="submit" value="Continue &gt;&gt;&gt;" /></td>
</table>
</form>
END;

    print($html);
}

function install_db_config($f,&$err)
{
    require_once('cclib/cc-defines.php');

    $varname = "\$CC_DB_CONFIG";
    $text = "<?PHP";
    $text .= <<<END
        
// This file is generated as part of install and config editing

if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

$varname = array (
   'db-name'     =>   '{$f['database']['v']}',
   'db-server'   =>   '{$f['dbserver']['v']}',
   'db-user'     =>   '{$f['dbuser']['v']}',
   'db-password' =>   '{$f['dbpw']['v']}',
 
  ); 

END;

    $text .= "?>";

    $err = '';
    $fname = 'cc-config-db.php';
    $fh = @fopen($fname,'w+');
    if( !$fh )
    {
        $err = "Could not open a configuration file for writing in ccHost directory.  Please make sure the directory is writable and try again.";
    }
    else
    {
        if( fwrite($fh,$text) === false )
        {
            $err = "Could not write to configuration file in ccHost directory. Please make sure the directory is writable and try again.";
        }

        fclose($fh);
    }

    if( !$err )
    {
        chmod($fname,CC_DEFAULT_FILE_PERMS);
        print("Database config written<br />");
    }

    return( empty($err) );
}

function route_around($dir)
{
    if( file_exists($dir) )
        return($dir);

    if( file_exists( '../' . $dir ) )
        return( realpath( '../' . $dir ) );

    if( file_exists( '../../' . $dir ) )
        return( realpath( '../../' . $dir ) );

    return( null );
}
?>
