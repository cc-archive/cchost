<?

error_reporting(E_ALL);

if( !empty($_GET['bbe_format']) )
{
    $module = '../cchost_lib/snoopy/Snoopy.class.php';
    if( !file_exists($module) )
        die(); // this is a bot
    require_once($module);
    $snoopy = new Snoopy();
    $snoopy->fetch('http://ccmixtermedia.org/djvadim/tracks');
    print $snoopy->results;
    exit;
}

?>
