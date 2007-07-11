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
    $f1 = @file_get_contents('mixter-files/bbe_1.txt');
    $f2 = @file_get_contents('mixter-files/bbe_2.txt');
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
    <form action="/mixter-lib/mixter-bbe.php?bbe_post=1" method="post">
    <textarea name="bbe_1">$f1</textarea><br />
    <textarea name="bbe_2">$f2</textarea><br />
    <input type="submit" value="submit changes" />
    </form>
EOF;
    print $html;
    exit;
}

if( !empty($_REQUEST['bbe_post']) )
{
 error_reporting(E_ALL); 
   $text = $_POST['bbe_1'];
    $f = fopen('../mixter-files/bbe_1.txt','w'); fwrite($f,$text); fclose($f);
    $text = $_POST['bbe_2'];
    $f = fopen('../mixter-files/bbe_2.txt','w'); fwrite($f,$text); fclose($f);
    header("Location: /bbe");
    exit;
}

?>