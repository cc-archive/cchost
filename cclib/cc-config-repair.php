<? 
function validate_user()
{
    // If you can't even login then uncomment the next line TEMPORARILY to edit your config
    // return; 
    list( $user_name, $pw ) = unserialize($_COOKIE['lepsog3']) or die('no login cookie. log in to cchost first, otherwise edit this script');
    $qr = q('SELECT user_id FROM cc_tbl_user WHERE user_name=\''.$user_name.'\' AND user_password=\''.$pw.'\'');
    $ok = mysql_num_rows($qr) or die('can not find you in the user database');
    $data = data(array('media','config'));
    if( empty($data['supers']) )
        die('no supers');
    $supers = split(',',$data['supers']);
    if( !in_array($user_name,$supers) )
        die('you are not in the media/config/supers list');
}
error_reporting(E_ALL); validate_user(); ?>
<html>
<head>
<script type="text/javascript" src="../ccskins/shared/js/prototype.js" ></script>
<style>
#config_list {
    float: left;
    width: 200px;
    margin-left: 20px;
    border: 1px solid #AAA;
    padding: 3px;
    margin-bottom: 30px;
}
a.config_path, a.config_key,#del_cmd,a.config_cmd {
    display: block;
    font-family: verdana;
    font-weight: normal;
    font-size: 11px;
    color:black;
    text-decoration: none;
    margin-bottom: 2px;
}
a.config_cmd {
    color: #777;
}
a.config_cmd:hover {
    color: red;
    background-color: #BBB;
}
a.config_path:hover, a.config_key:hover {
    color: white;
    background-color: black;
}

#config_detail{
    float: left;
    margin-left: 30px;
}
#msg {
    float: right;
    margin-right: 30px;
}

</style>
</head>
<body>
<h2>Edit Config</h2>
<?
$path = empty($_GET['path']) ? array() : split('/',$_GET['path']);

if( !empty($_GET['cmd']) )
{
    if( $_GET['cmd'] == 'delete' )
    {
        del($path);
    }
}


$qr = q('SELECT DISTINCT config_scope FROM cc_tbl_config ORDER by config_scope');
print '<div id="config_list">';
while( $row = mysql_fetch_assoc($qr) )
{
    $scope = $row['config_scope'];
    print '<a class="config_path" href="?path='.$scope.'">' . $scope . '</a>';
    if( $path && ($scope == $path[0]) )
    {
        $qr2 = q('SELECT config_type FROM cc_tbl_config WHERE config_scope = \''.$scope . '\' ORDER by config_type ');
        if( $scope != 'media' )
            print '<a class="config_cmd" href="?cmd=delete&path='.$scope.'">[delete entire \''.$scope.'\']</a> ';
        while( $type_row = mysql_fetch_row($qr2) )
        {
            $tpath = $scope . '/' . $type_row[0];
            print '<a class="config_path" href="?path='.$tpath.'">&nbsp;&nbsp;&nbsp;' . $type_row[0]. '</a>';
        }
    }
}
print '</div>';

if( empty($_GET['cmd']) )
{
    if( count($path) > 1 )
    {
        $qr = q('SELECT * FROM cc_tbl_config ORDER by config_scope,config_type');
        $base = $path[0] . '/' . $path[1];
        print "\n" . 
            '<div id="config_detail"><h3>' . $_GET['path']. ' <a id="del_cmd" href="?path='.$_GET['path'] .'&cmd=delete">[delete]</a> </h3>';

        $data = data($path);
        recurse_path($data,$base,$path,2);
        print '</div>';
    }
}
else
{
}


function recurse_path($data,$base,$path,$level)
{
    $key = count($path) > $level ? $path[$level] : null;
    foreach( $data as $K => $V )
    {
        $dpath = $base . '/' . $K;
        $space = str_repeat('&nbsp;',3*$level);
        if( isset($key) && ($K == $key) )
        {
            $type = empty($V) ? 'null' : gettype($V);
            switch($type)
            {
                case 'array':
                    print '<a class="config_key" href="?path='.$dpath.'">'.$space . $K.'</a>';
                    if( count($path) == $level+1 )
                    {
                        print '<a class="config_cmd" href="?cmd=add&path='.$dpath.'">'.$space .'[add key to \''.$K.'\']</a> ' .
                              '<a class="config_cmd" href="?cmd=delete&path='.$dpath.'">'.$space . '[delete \''.$K.'\']</a> ';
                    }
                    recurse_path($V,$dpath,$path,$level+1);
                    continue;
                case 'object':
                    print "$K is an object, sorry, can't edit";
                    break;
                case 'integer':
                case 'null':
                    print $space . "$K: $space <input id='config_edit' value=\"" . htmlentities($V) . '" />';
                    break;
                default:
                    print $space . "$K:<br />$space <textarea id='config_value'>" . htmlentities($V) . '</textarea>';
            }
           print '<a class="config_cmd" href="?cmd=delete&path='.$dpath.'">'.$space . '[delete \''.$K.'\']</a> ';
        }
        else
        {   
            $type = empty($V) ? 'null' : gettype($V);
            print '<a class="config_key" href="?path='.$dpath.'">'.$space . $K.' (' . $type .')</a>';
        }

    }
}

?>
<div style="clear:both">&nbsp;</div>
<script>
jsClass = Class.create();

jsClass.prototype = {

    initialize: function(username) {
    },

    onPathClick: function(e,id) {
    }
}
new jsClass();
</script>
</body>
</html>
<?
function init_db()
{
    static $done;
    if( !isset($done) ) {
        define('IN_CC_HOST',1);
        require_once('../cc-host-db.php');
        $config = $CC_DB_CONFIG;
        $link = mysql_connect( $config['db-server'], 
                                $config['db-user'], 
                                $config['db-password']) or die( mysql_error() );
        
        mysql_select_db( $config['db-name'], $link ) or die( mysql_error() );
        $done = true;
    }
}
function q($sql)
{
    init_db();
    $qr = mysql_query($sql) or die( mysql_error() );
    return $qr;
}
function del($path)
{
    if( empty($path) ) die('wtf');

    if( count($path) == 1 )
    {
        q("DELETE FROM cc_tbl_config WHERE config_scope = '{$path[0]}'");
        msg('Entire scope has been deleted');
    }
    else
    {
        $where = where($path);
        if( count($path) == 2 )
        {
            q('DELETE FROM cc_tbl_config WHERE ' . $where);
            msg('Entire config entry has been deleted');
        }
        else
        {
            $data = data($path);
            $data_path = array_splice($path,2);
            if( count($data_path) == 1 )
            {
                unset($data[$data_path[0]]);
            }
            else
            {
                $str = "unset(\$data['" . join("']['",$data_path) . "']);";
                eval($str);
            }
            $data = addslashes(serialize($data));
            q("UPDATE cc_tbl_config SET config_data = '$data' WHERE $where");
            msg('Config entry has been deleted');
        }
    }
}

function msg($msg)
{
    print '<div id="msg">' . $msg . '</div>';
}

function where($path)
{
    return "config_scope = '{$path[0]}' AND config_type = '{$path[1]}'";
}
function data($path)
{
    $where = where($path);
    $sql = "SELECT * FROM cc_tbl_config WHERE $where";
    $qr = q($sql);
    $row = mysql_fetch_assoc($qr) or die( mysql_error() );
    return unserialize($row['config_data']);
}

function d(&$obj)
{
    print '<pre>';
    if( is_array($obj) )
        print_r($obj);
    else
        var_dump($obj);
    print '</pre>';
    exit;
}
?>