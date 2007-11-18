<?


if( !defined('IN_CC_HOST') )
    die( 'Welcome to ccHost' );


function cc_filter_download_url(&$records)
{
    $c = count($records);
    $k = array_keys($records);
    for( $i = 0; $i < $c; $i++ )
    {
        $R =& $records[$k[$i]];
        global $CC_GLOBALS;
        if( $R['upload_contest'] )
            $R['download_url'] = ccd($CC_GLOBALS['contests'][($R['upload_contest']+1)],$R['user_name'],$R['file_name']);
        else
            $R['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$R['file_name']);
    }
}


?>