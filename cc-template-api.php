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
*   CUSTOM TEMPLATE API 
*
*    Methods here were designed to be called from templates:
*
* @package cchost
* @subpackage admin
*/


if( !defined('IN_CC_HOST') )
    die('Welcome to CC Host');


/**
* Chop a string and append ellipse if over a given length.
*
* The third parameter '$dochop' allows for runtime decisions about
* whether to chop or not. This is <i>much</i> faster than making the
* branch descision in phpTAL.
*
* @param string $str String to potentially chop
* @param integer $maxlen Maximum number of characters before adding ellipse
* @param boolen $dochop If false then maxlen is ignored and string is returned
* @return string $string Chopped string
*/
function cc_strchop($str,$maxlen,$dochop = true)
{
    if( empty($dochop) || (strlen($str) <= $maxlen) )
        return($str);
    return( substr($str,0,$maxlen) . '...' );
}

/**
* Format a date
* 
* Maps to: date(<i>fmt</i>,strtotime(<i>date</i>))
*
* @param string $date A string representation of a date
* @param string $fmt A PHP date() formatter
* @return string $datestring A formatted date string
*/
function cc_datefmt($date,$fmt)
{
    if( empty($date) )
        return '';
    return( date($fmt,strtotime($date)) );
}

function cc_not_empty(&$val)
{
    return isset($val) && !empty($val) ? $val : '';
}


/**
* Return the values current stored in the configs tables
*
* @param string $configName Name of settings (e.g. 'chart', 'licenses')
* @return mixed Raw config value as stored in db
*/
function cc_get_config($configName)
{
    $configs =& CCConfigs::GetTable();
    return $configs->GetConfig($configName);
}


function cc_get_config_roots()
{
    $configs = CCConfigs::GetTable();
    $roots = $configs->GetConfigRoots();
    $keys = array_keys($roots);
    foreach( $keys as $k )
        $roots[$k]['url'] = ccc($roots[$k]['config_scope']);
    return $roots;
}

function cc_query_fmt($qstring)
{
    if( empty($qstring) )
        return array();
    
    parse_str($qstring,$args);
    require_once('cclib/cc-query.php');
    $query = new CCQuery();
    if( empty($args['format']) )
        $args['format'] = 'php';
    $args = $query->ProcessAdminArgs($args);
    list( $results ) = $query->Query($args);
    return $results;
}

function cc_recent_playlists()
{
    require_once('ccextras/cc-playlist.php');
    return CC_recent_playlists_impl();
}

function cc_hot_playlists()
{
    require_once('ccextras/cc-playlist.php');
    return CC_hot_playlists_impl();
}

function & cc_get_user_details(&$row)
{
    $users =& CCUsers::GetTable();
    return $users->GetFullRecord($row);
}

function cc_get_upload_menu(&$record)
{
    require_once('cclib/cc-upload.php');
    return CCUpload::GetRecordLocalMenu($record);
}

function cc_get_ratings_info(&$record)
{
    $configs =& CCConfigs::GetTable();
    $chart = $configs->GetConfig('chart');
    if( !empty($chart['ratings']) )
    {
        require_once('cclib/cc-ratings.php');
        CCRating::GetRatingsInfo($record);
    }
}

function cc_get_value($arr,$key)
{
    if( is_array($arr) && array_key_exists($key,$arr) )
        return $arr[$key];
    return null;
}

/**
* Returns arrays with the current users playlists:
*  $ret['with'] are playlists that HAVE this upload
*  $ret['without'] are playlists that do NOT have this upload
* This structure can be passed to the playlist_popup menu
* template macro as 'args':
*
*  $A['args'] =& cc_get_playlist_with(&$record);
*  $T->Call('playlist.tpl/playlist_popup')
*/
function & cc_get_playlist_with(&$record)
{
    require_once('ccextras/cc-playlist.inc');
    $pls = new CCPlaylists();
    $ret =& $pls->_playlist_with($record['upload_id']);
    return $ret;
}


function cc_get_user_avatar_sql()
{
    global $CC_GLOBALS;

    if( empty($CC_GLOBALS['avatar-dir']) )
    {
        $aurl = ccd($CC_GLOBALS['user-upload-root']) . '/';
        $aavtr = "user_name,  '/', " ;
    }
    else
    {
        $aurl = ccd($CC_GLOBALS['avatar-dir']) . '/';
        $aavtr = '';
    }
    if( !empty($CC_GLOBALS['default_user_image']) )
    {
        $davurl = ccd($CC_GLOBALS['image-upload-dir'],$CC_GLOBALS['default_user_image']);
    }
    else
    {
        $davurl = '';
    }
 
    return "IF( LENGTH(user_image) > 0, CONCAT( '$aurl', {$aavtr} user_image ), '$davurl' ) as user_avatar_url";
    //return "'$davurl' as user_avatar_url";
}

function cc_get_user_avatar(&$R)
{
    global $CC_GLOBALS;

    if( empty($R['user_image']) )
    {
        return ccd($CC_GLOBALS['image-upload-dir'],$CC_GLOBALS['default_user_image']);
    }

    if( empty($CC_GLOBALS['avatar-dir']) )
    {
        return ccd($CC_GLOBALS['user-upload-root'], $R['user_name'], $R['user_image'] );
    }

    return ccd($CC_GLOBALS['avatar-dir'], $R['user_image'] );
}

?>
