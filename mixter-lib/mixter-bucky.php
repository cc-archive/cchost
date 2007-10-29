<?

error_reporting(E_ALL);

if( !empty($_GET['bucky_format']) )
{
    require_once('../cclib/snoopy/Snoopy.class.php');
    $snoopy = new Snoopy();
    $snoopy->fetch('http://ccmixtermedia.org/buckyjonson');
    print $snoopy->results;
    exit;
}


if( !empty($_GET['bucky_edit']) )
{
    $f1 = @file_get_contents('mixter-files/bucky_1.txt');
    $f2 = @file_get_contents('mixter-files/bucky_2.txt');
    $f3 = @file_get_contents('mixter-files/bucky_3.txt');
    $html =<<<EOF
    <style>
        textarea {
            width: 80%;
            height: 200px;
            margin: 6px;
            font-family: verdana;
            font-size: 11px;
        }
    </style>
    <form action="/mixter-lib/mixter-bucky.php?bucky_post=1" method="post">
    <textarea name="bucky_1">$f1</textarea><br />
    <textarea name="bucky_2">$f2</textarea><br />
    <textarea name="bucky_3">$f3</textarea><br />
    <input type="submit" value="submit changes" />
    </form>
EOF;
    print $html;
    exit;
}

if( !empty($_REQUEST['bucky_post']) )
{
    error_reporting(E_ALL); 
    bucky_write(1);
    bucky_write(2);
    bucky_write(3);
    header("Location: /bucky");
    exit;
}

function bucky_write($num)
{
    $name = 'bucky_' . $num;
    $text = $_POST[$name];
    if( get_magic_quotes_gpc() == 1 )
        $text = trim(stripslashes( $text ));
    $f = fopen("../mixter-files/$name.txt",'w'); fwrite($f,$text); fclose($f);
 }
?>