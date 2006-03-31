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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

// Sat, 07 Sep 2002 00:00:01 GMT
// ..actually 'T' prints the entire acronym out
define('RFC822_FORMAT', 'D, d M Y H:i:s '); // T');
define('W3CDTF_FORMAT', 'Y-m-d\TH:i:sO');

function cc_setcookie($name,$value,$expire,$path='',$domain='')
{
    global $CC_GLOBALS;

    if( empty($path) )
        $path = '/';

    // Domain might still be null, that should be ok 
    if( empty($domain) )
        $domain = $CC_GLOBALS['cookie-domain'];

    // if it's empty it must be numeric
    if( empty($expire) )
        $expire = 0; 
    
    $ok = setcookie($name,$value,$expire,$path,$domain);

    return( $ok );
}

function cc_exit()
{
    CCEvents::Invoke(CC_EVENT_APP_DONE);    
    exit();
}

class CCUtil
{
    function StripText(&$text)
    {
        if( is_integer($text) )
            return($text);
        if( empty($text) )
            return(null);
        $text = trim(strip_tags(CCUtil::StripSlash($text)));
        return($text);
    }

    function StripSlash(&$mixed)
    {
        if( get_magic_quotes_gpc() == 1 )
        {
            if( is_array($mixed) )
            {
                $keys = array_keys($mixed);
                $c = count($keys);
                for( $i = 0; $i < $c; $i++ )
                    $mixed[$keys[$i]] = CCUtil::StripSlash($mixed[$keys[$i]]);
            }
            else
            {
                $mixed = trim(stripslashes( $mixed ));
            }
        }
        return($mixed);
    }

    function TextToHTML($text,$convert_nl=true)
    {
        if( empty($text) )
            return('');

        $text = str_replace('--','&#8212;', htmlentities($text));
        
        if( $convert_nl )
            $text = nl2br($text);

        $regex = '#http://(.*)(\s|$|<)#Ue';
        $repl  = "'<a class=\"cc_external_link\" target=\"_blank\" " .
                 "href=\"http://$1\">' . (strlen('$1') > 25 ? substr('$1',0,25) . '...' : '$1') . '</a> $2'";
        $text = preg_replace($regex,$repl,$text);

        return($text);
    }

    function CheckTrailingSlash($dir,$slash_required)
    {
        $dir = str_replace('\\','/',$dir);
        if( preg_match('#^(.*)/$#',$dir,$m) )
        {
            if( $slash_required )
                return($dir);
            return( $m[1] );
        }
        if( $slash_required )
            return( $dir . '/' );
        return( $dir );
    }

    function AccessError($file='',$lineo='')
    {
        $str = "Access attempt from: {$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}\n{$_SERVER['HTTP_USER_AGENT']}";
        CCDebug::Log($str);
        print("<pre>$str</pre>");
        if( CCUser::IsAdmin() )
            CCDebug::StackTrace();
        else
            exit;
    }

    function SendBrowserTo($newurl='')
    {
        if( empty($newurl) )
        {
            if( !empty($_POST['http_referer']) )
                $newurl = htmlspecialchars(urldecode($_POST['http_referer']));
        }
        if( empty($newurl) )
        {
            $newurl = cc_get_root_url();
        }
        header("Location: $newurl");
        exit;
    }

    function IsHTTP()
    {
        return( !empty($_SERVER['HTTP_HOST']) );
    }

    function MakeSubdirs($pathname,$mode=CC_DEFAULT_DIR_PERM)
    {
        // Check if directory already exists
        if (is_dir($pathname) || empty($pathname)) {
            return true;
        }
     
        // Ensure a file does not already exist with the same name
        if (is_file($pathname)) {
            trigger_error('MakeSubdirs() File exists', E_USER_WARNING);
            return false;
        }
     
        // Crawl up the directory tree
        $next_pathname = substr($pathname, 0, strrpos($pathname, '/'));
        if (CCUtil::MakeSubdirs($next_pathname, $mode)) {
            if (!file_exists($pathname)) {
                $umask = umask(0);
                $ok = mkdir($pathname, $mode);
                umask($umask);
                return($ok);
            }
        }
     
        return false;
    }

    function BaseFile($path)
    {
        $base = basename($path);
        $ex = explode('.',$base);
        if( count($ex) > 1 )
            $base = basename($path, '.' . $ex[ count($ex)-1 ]);
        return($base);
    }

    function LegalFileName($name_to_cleans)
    {
        if( strlen($name_to_cleans) > 255 )
        {
            $ext = '';
            if( preg_match( '/\.[^\.]+$/',$name_to_cleans,$m) )
                $ext = $m[0];
            $name_to_cleans = substr( $name_to_cleans, 0, 254 - strlen($ext) );
        }
        $goodchars = preg_quote('a-zA-Z0-9.()-');
        return( preg_replace( "/[^$goodchars]+/", '_', $name_to_cleans ) );
    }

    function FormatDate($fmt,$date)
    {
        static $TZ;
        if( !isset($TZ) )
        {
            $TZ = date('T');
            if ( strlen($TZ) > 3 )
                $TZ = preg_replace('/[^A-Z]/','',$TZ);
        }

        $d = date($fmt,$date);
        if( $fmt == RFC822_FORMAT )
        {
            $d .= $TZ;
        }

        return( $d );
    }

    // rippped from phpBB2

    function EncodeIP($dotquad_ip)
    {
        $ip_sep = explode('.', $dotquad_ip);
        return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
    }

    function DecodeIP($int_ip)
    {
        $hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
        return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
    }

}



?>