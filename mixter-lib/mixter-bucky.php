<?

error_reporting(E_ALL);

if( !empty($_GET['bucky_format']) )
{
    require_once('cchost_lib/snoopy/Snoopy.class.php');
    $snoopy = new Snoopy();
    $snoopy->fetch('http://ccmixtermedia.org/buckyjonson');
    print $snoopy->results;
    exit;
}


?>
