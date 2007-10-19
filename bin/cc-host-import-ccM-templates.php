<?

function do_file($infile,$outfile)
{
    $parser = new CCTALCompiler();
    print( "Compiling \"$infile\" to \"$outfile\"\n" );
    $parser->compile_phptal_file($infile,$outfile);
}

function recur_mkdir($dir)
{
    $parent = dirname($dir);
    if( !file_exists($parent) )
        recur_mkdir($parent);
    if( !file_exists($dir) )
        mkdir($dir,0777);
}

function main()
{
    chdir('..');
    define('IN_CC_HOST',1);
    define('TC_PRETTY', 0 );
    require_once('cclib/cc-tal-parser.php');

    recur_mkdir( 'cchost_files/pages' );
    $files = glob('mixter-files/*.xml');
    foreach( $files as $F )
    {
        do_file( $F, 'cchost_files/pages/' . basename($F) . '.php' );
    }

    $dirs = glob('mixter-files/*', GLOB_ONLYDIR );
    foreach( $dirs as $D )
    {
        if( $D{0} == '.')
            continue;

        $target = 'cchost_files/pages/' . basename($D);
        recur_mkdir( $target );
        $files = glob( "$D/*.xml" );
        $target .= '/';
        foreach( $files as $F )
            do_file( $F, $target . basename($F) . '.php' );
    }

}

main();


?>