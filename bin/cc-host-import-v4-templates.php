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

    recur_mkdir( 'ccskins/pages' );

    do_file( 'cctemplates/custom.xml', 'ccskins/shared/custom.xml.php' );

    recur_mkdir( 'ccskins/simple' );
    $files = glob('cctemplates/*.xml');
    foreach( $files as $F )
    {
        do_file( $F, 'ccskins/simple/' . basename($F) . '.php' );
    }
    $files = glob('cctemplates/*.css');
    foreach( $files as $F )
    {
        $filename = basename($F);
        copy( $F, 'ccskins/simple/' . $filename );
    }
    $dirs = glob('cctemplates/*', GLOB_ONLYDIR );
    foreach( $dirs as $D )
    {
        if( $D{0} == '.')
            continue;

        $target = 'ccskins/simple/' . basename($D);
        recur_mkdir( $target );
        $files = glob( "$D/*.*" );
        $target .= '/';
        foreach( $files as $F )
        {
            if( strstr( $F, '.xml' ) )
            {
                do_file( $F, $target . basename($F) . '.php' );
            }
            else
            {
                copy( $F, $target . basename( $F ) );
            }
        }
    }
    recur_mkdir( 'ccskins/shared' );
    $files = glob('ccfiles/*.xml');
    foreach( $files as $F )
    {
        do_file( $F, 'ccskins/shared/' . basename($F) . '.php' );
    }

}

main();


?>