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
* Sets if-modifed date on various user activity
*
* @package cchost
* @subpackage core
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('CC_IF_MOD_FLAG','mod-if-304');

function cc_set_if_modified()
{
    global $CC_GLOBALS;

    $cfg = new CCConfigs();
    $time = gmmktime(); 
    $CC_GLOBALS['in_if_modified'] = true;
    $cfg->SetValue('config', CC_IF_MOD_FLAG, $time, CC_GLOBAL_SCOPE);
    unset($CC_GLOBALS['in_if_modified']);
    $CC_GLOBALS[CC_IF_MOD_FLAG]  = $time;
    _clog('setting if-mod: ' . $time);
}

function cc_check_if_modified()
{
    global $CC_GLOBALS;

    if( empty($CC_GLOBALS[CC_IF_MOD_FLAG]) )
    {
        cc_set_if_modified();
    }

    $contentDate = intval($CC_GLOBALS[CC_IF_MOD_FLAG]);
    $lastmod     = gmdate('D, d M Y H:i:s', $contentDate ) . ' GMT'; 
    $etag        = '"' . md5($lastmod) . '"'; // ETag is sent even with 304 header 
    header("ETag: $etag"); 

    $http_check  = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null;
    $http_etag   = isset($_SERVER['HTTP_IF_NONE_MATCH'])     ? $_SERVER['HTTP_IF_NONE_MATCH']                : null;

    $ifmod =  $http_check ? ($http_check >= $contentDate) : null; 

    /*
    $iftag =  $http_etag  ? ($http_etag == $etag)         : null; 
    $iftag = true;

    if (($ifmod || $iftag) && ($ifmod !== false && $iftag !== false)) 
    */

    if( $ifmod && ($ifmod !== false) )
    {
        header('HTTP/1.0 304 Not Modified'); 
        _clog('Sending 304');
        exit; 
    } 
    
    // Last-Modified doesn't need to be sent with 304 response 
    header("Last-Modified: $lastmod"); 
    _clog("Sending page: $lastmod ($http_check/$contentDate) (ifmod:$ifmod)");
}

function cc_send_no_cache_headers()
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    // always modified
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
     
    // HTTP/1.1
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);

    // HTTP/1.0
    header("Pragma: no-cache");
}


function _clog($msg)
{
    return; 
    $debug = CCDebug::Enable(true);
    CCDebug::Log( str_replace(ccl(),'',cc_current_url()) . ' ' . $msg);
    CCDebug::Enable($debug);
}

?>
