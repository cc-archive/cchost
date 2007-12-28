<?

error_reporting(E_ALL);

if( !empty($_GET['bbe_format']) )
{
    require_once('../cclib/snoopy/Snoopy.class.php');
    $snoopy = new Snoopy();
    $snoopy->fetch('http://ccmixtermedia.org/djvadim/tracks');
    print $snoopy->results;
    exit;
}


if( !empty($_GET['bbe_edit']) )
{
    $f1 = @file_get_contents('cchost_files/pages/djvadim/bbe_1.txt');
    $f2 = @file_get_contents('cchost_files/pages/djvadim/bbe_2.txt');
    $f3 = @file_get_contents('cchost_files/pages/djvadim/bbe_3.txt');
    $html =<<<EOF
    <style type="text/css">
        textarea {
            width: 80%;
            height: 200px;
            margin: 6px;
            font-family: verdana;
            font-size: 11px;
        }
    </style>
    <form action="/mixter-lib/mixter-bbe.php?bbe_post=1" method="post">
    <textarea name="bbe_1">$f1</textarea><br />
    <textarea name="bbe_2">$f2</textarea><br />
    <textarea name="bbe_3">$f3</textarea><br />
    <input type="submit" value="submit changes" />
    </form>
EOF;
    print $html;
    exit;
}

if( !empty($_REQUEST['bbe_post']) )
{
    error_reporting(E_ALL); 
    bbe_write(1);
    bbe_write(2);
    bbe_write(3);
    header("Location: /bbe");
    exit;
}

function bbe_write($num)
{
    $name = 'bbe_' . $num;
    $text = $_POST[$name];
    if( get_magic_quotes_gpc() == 1 )
        $text = trim(stripslashes( $text ));
    $f = fopen("cchost_files/pages/djvadim/$name.txt",'w'); fwrite($f,$text); fclose($f);
 }
?>