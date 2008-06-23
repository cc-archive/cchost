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
    // disable this until I'm sure it freakin works

    return;

    global $CC_GLOBALS;

    if( empty($CC_GLOBALS[CC_IF_MOD_FLAG]) )
    {
        //
        // This will happen exactly once per installation
        // (or whenever developer changes CC_IF_MOD_FLAG value)
        //
        cc_set_if_modified();
    }

    //
    // When the user transitions from logged in to not (or visa versa) 
    // our pages change radically so you want to make sure that the page's cache
    // is in sync with when the user transitioned.
    //
    // The browser has a copy of the page in it's cache at time http_time
    // The user logged in/out at time user_time
    // The database was last changed at last_db_write_time
    //

    $last_db_write_time  = intval($CC_GLOBALS[CC_IF_MOD_FLAG]);
    $http_time           = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : null;
    $user_time           = 0;

    $ifmod =  $http_time ? ($http_time >= $last_db_write_time) : null; 

    //
    // If the browser's copy is newer than the last db write then continue
    //
    if( $ifmod && ($ifmod !== false) )
    {
        if( CCUser::IsLoggedIn() )
        {
            $user_time = empty($_COOKIE[CC_TRANSITION_COOKIE]) ? 0 : intval($_COOKIE[CC_TRANSITION_COOKIE]);
            if( !$user_time )
            {
                // this will happen when the user has nuked their 
                // cookies or if this code has not been run between
                // login/out transitions
                $user_time = gmmktime();
                cc_setcookie(CC_TRANSITION_COOKIE,$user_time,time()+60*60*24*30);
            }
        }

        //
        // If the browser's copy is newer than user's login/out transition then use it
        //
        if( !$user_time || ($http_time > $user_time) )
        {
            header('HTTP/1.0 304 Not Modified'); 
            _clog('Sending 304');
            exit; 
        }
        else
        {
            _clog("Failed user test");
        }
    } 
    
    $last_db_write_time_fmt = gmdate('D, d M Y H:i:s', $last_db_write_time ) . ' GMT'; 
    header("Last-Modified: $last_db_write_time_fmt"); 
    _clog("Sending page: $last_db_write_time_fmt ($user_time/$http_time/$last_db_write_time) (ifmod:$ifmod)");
}

function cc_send_no_cache_headers()
{
    _clog('Clearing headers');

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
    //return; 
    $debug = CCDebug::Enable(true);
    CCDebug::Log( str_replace(ccl(),'',cc_current_url()) . ' ' . $msg);
    CCDebug::Enable($debug);
}

?>
