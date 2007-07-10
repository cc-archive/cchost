<?

error_reporting(E_ALL);

function bbe_main()
{
    require_once('../cclib/snoopy/Snoopy.class.php');
    $snoopy = new Snoopy();
    $snoopy->fetch('http://ccmixtermedia.org/djvadim/tracks');
    print $snoopy->results;
    exit;
}

if( !empty($_GET['bbe_format']) )
    bbe_main();

?>