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
* $Id$
*
*/

/**
* International support
*
* Attention translators:
*
* As of this writing the admin pages are not internationalized, 
* only the user and non-registered user pages are.
*
* To start a new translation use:
*
* http://example.com/media/admin/language/en
*
*
* Ideally you have done all this BEFORE installing ccHost. If not
* you will have to edit the menu settings and 'revert' to system
* defaults. (You will lose any customizations you have done at that
* point.)
*
*
* When you install a new (3rd party) module you should do the 
* following to extract the strings:
*
* 1. Sign in as administrator
* 2. Browse to http://your_server_root/?record_lang=on
* 3. Run the 3rd party module
* 4. Browse to http://your_server_root/?record_lang=off
* 5. Note the differences in cclib/lang/en/cc-translation-*.php and
*    copy them to your language's files to translate them.
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_APP_INIT,  'cc_lang_start_record');
CCEvents::AddHandler(CC_EVENT_APP_DONE,  'cc_lang_stop_record');

/**
*/
$CC_LANG_RECORD_MODE = false;

// cct will already exist when doing string editing, we don't need real cct
// see ccextras/cc-language

if( !function_exists('cct') )
{
    function cct_($in_text,$mode='u',$index='')
    {
        global $CC_GLOBALS,$CC_LANG_RECORD_MODE;

        static $_trans;

        if( !empty($CC_LANG_RECORD_MODE) )
            cct_record($in_text,$mode);

        if( empty($CC_GLOBALS['cclang']) || empty($CC_GLOBALS['lang_enabled']) )
            return( $in_text );

        if( empty($_trans[$mode]) )            // have we been here before?
        {
            $file = 'cclib/lang/' . $CC_GLOBALS['cclang'] . "/cc-translation-$mode.php";
            include($file);
            $tname = "cc_translation_table_$mode";
            $_trans[$mode] = $$tname;
        }

        if( empty($index) )
            $index = sprintf('x%08X',crc32($in_text));
        if( empty($_trans[$mode][$index]) )
            return($in_text);
        return( $_trans[$mode][$index] );
    }
}

function cc_lang_translate(&$arr,$field,$mode='u')
{
    $tf = $field . '__crc';
    $text = $arr[$field];
    if( !array_key_exists($tf,$arr) )
    {
        $index = sprintf('x%08X',crc32($text));
        $arr[$tf] = $index;
    }
    $arr[$field] = cct('',$mode,$arr[$tf]);
    if( empty($arr[$field]) )
        $arr[$field] = $text;
}

function cc_lang_start_record()
{
    global $CC_LANG_RECORD_MODE, $CC_GLOBALS;

    if( !CCUser::IsAdmin() )
    {
        if( empty($_COOKIE['record_lang']) );
            return;
    }

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


/**
* Event handler for {@link CC_EVENT_APP_DONE}
* 
*/
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
    cc_lang_write_inner($dir,$mode,$recorder);
}

function cc_lang_write_inner($dir,$mode,$recorder)
{
    $file = $dir . "/cc-translation-$mode.php";

    $ttname = "cc_translation_table_$mode";
    if( file_exists($file) )
    {
        include($file);
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
        if( !empty($t) )  // n.b. this should never be empty, but it does
        {
            $t = str_replace("'","\'",$t);
            $str = "      '$key' => '$t' , \n";
            fwrite($f,$str);
        }
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
