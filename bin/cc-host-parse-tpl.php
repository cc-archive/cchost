<?

error_reporting(E_ALL);

chdir('..');

require_once('cchost_lib/cc-tpl-parser.php');

if( empty($argv[1]) || (!empty($argv[2]) && ($argv[2] != '-#')) )
    die( syntax() );

$num = !empty($argv[2]);
$fname = str_replace('.tpl','',basename($argv[1]));
$fname = '_t_' . preg_replace('/[^a-z]+/i', '_', $fname) . '_';
$text = cc_tpl_parse_file($argv[1],$fname);

if( $num )
{
    $lines = split("\n",$text);
    $i = 0;
    foreach( $lines as $line )
    {
        ++$i;
        print "$i: $line\n";
    }
}
else
{
    print $text;
}

function syntax()
{
    print "Syntax: php -f cc-host-parse-tpl.php file-to-parse.tpl [-#]\n     -# Line numbers\n";
}

?>