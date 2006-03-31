<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Header$
*
*/

//----------------------------------------------------------------------
//
// Attention translators:
//
// (NOTE: As of this writing the admin pages are not internationalized, 
// only the user and non-registered user pages are.)
//
// To enabled language support you must rename this file to have a .php 
// extension.
//
// Then copy the files cclib/lang/en/cc-translation-*.php to the 
// proper local directory (e.g. cclib/lang/fr/cc-translation-*.php)
//
// Then edit the next line to point to your locale's directory:
//

$CC_CURRENT_LANGUAGE = ''; // change this to your locale abbr. e.g. 'fr' 

//
// Ideally you have done all this BEFORE installing ccHost. If not
// you will have to edit the menu settings and 'revert' to system
// defaults. (You will lose any customizations you have done at that
// point.)
//
//
// When you install a new (3rd party) module you should do the 
// following to extract the strings:
//
// 1. Sign in as administrator
// 2. Browse to http://your_server_root/?record_lang=on
// 3. Run the 3rd party module
// 4. Browse to http://your_server_root/?record_lang=off
// 5. Note the differences in cclib/lang/en/cc-translation-*.php and
//    copy them to your language's files to translate them.
//
//----------------------------------------------------------------------

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_APP_INIT,  'cc_lang_start_record');
CCEvents::AddHandler(CC_EVENT_APP_DONE,  'cc_lang_stop_record');

$CC_LANG_RECORD_MODE = false;

function cct($in_text,$mode='u')
{
    global $CC_GLOBALS,$CC_LANG_RECORD_MODE;

    if( !empty($CC_LANG_RECORD_MODE) )
        cct_record($in_text,$mode);

    if( empty($CC_GLOBALS['lang']) || $CC_GLOBALS['lang'] == 'en' )
        return( $in_text );

    $lang = $CC_GLOBALS['lang'];

    static $_trans;

    if( empty($_trans[$mode]) )
    {
        $file = 'cclib/lang/' . $CC_GLOBALS['lang'] . "/cc-translation-$mode.php";
        require_once($file);
        $tname = "cc_translation_table_$mode";
        $_trans[$mode] = $$tname;
    }

    $index = sprintf('x%08X',crc32($in_text));
    if( empty($_trans[$mode][$index]) )
        return($in_text);
    return( $_trans[$mode][$index] );
}

function cc_lang_start_record()
{
    global $CC_LANG_RECORD_MODE, $CC_GLOBALS, $CC_CURRENT_LANGUAGE;

    if( !empty($CURRENT_LANGUAGE) )
        $CC_GLOBALS['lang'] = $CC_CURRENT_LANGUAGE;

    if( !CCUser::IsAdmin() )
        return;

    if( empty($_GET['record_lang']) )
    {
        if( !empty($_COOKIE['record_lang']) )
            $CC_LANG_RECORD_MODE = true;
    }
    else
    {
        if( $_GET['record_lang'] == 'on' )
        {
            cc_setcookie('record_lang','on', time() + (60*60*24*7));
            $CC_LANG_RECORD_MODE = true;
        }
        else
        {
            cc_setcookie('record_lang','',0);
        }
    }

}

function cc_lang_stop_record()
{
    global $CC_LANG_RECORD_MODE;

    if( empty($CC_LANG_RECORD_MODE) )
        return;

    $dir = 'cclib/lang/en';
    CCUtil::MakeSubdirs($dir);
    cc_lang_write($dir,'u');
    cc_lang_write($dir,'a');
}

function cc_lang_write($dir,$mode)
{
    $recorder =& cct_recorder();
    $file = $dir . "/cc-translation-$mode.php";
    $ttname = "cc_translation_table_$mode";
    if( file_exists($file) )
    {
        require_once($file);
        $ttable = array_merge($$ttname,$recorder[$mode]);
    }
    else
    {
        $ttable =& $recorder[$mode];
    }

    ksort($ttable);
    $modes['u'] = 'User Table';
    $modes['a'] = 'Admin Table';
    $f = fopen($file,'w');
    $obracket = '<?';
    $cbracket = '?>';
    
    $header =<<<END
$obracket
        // translation table for ccHost: {$modes[$mode]}

\$$ttname = array( 


END;
    
    fwrite($f,$header);
    $count = count($ttable);
    foreach( $ttable as $key => $t )
    {
        $t = str_replace("'","\'",$t);
        $str = "      '$key' => '$t' , \n";
        fwrite($f,$str);
    }

    $str = "    );\n\n$cbracket";
    fwrite($f,$str);
    fclose($f);
    chmod($file,CC_DEFAULT_FILE_PERMS);
}

function & cct_recorder()
{
    static $_recorder = array();
    return( $_recorder );
}

function cct_record($in_text,$mode)
{
    $recorder =& cct_recorder();
    $index = '' . sprintf('x%08X',crc32($in_text));
    if( !empty($recorder[$mode][$index]) && ($recorder[$mode][$index] != $in_text ) )
    {
        CCDebug::Log("Hash conflict $in_text with " . $recorder[$mode][$index]);
    }
    $recorder[$mode][$index] = $in_text;
}


?>