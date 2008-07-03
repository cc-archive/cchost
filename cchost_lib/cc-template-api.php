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
    if( empty($maxlen) || empty($dochop) || (strlen($str) <= $maxlen) )
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


/**
* Return the values current stored in the configs tables
*
* @param string $configName Name of settings (e.g. 'chart', 'licenses')
* @return mixed Raw config value as stored in db
*/
function cc_get_config($configName,$format='php')
{
    $configs =& CCConfigs::GetTable();
    $v = $configs->GetConfig($configName);
    if( $format == 'php' )
        return $v;
    if( $format == 'json' )
        return cc_php_to_json($v);
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

function cc_get_submit_types($allow_remix=false,$default_title='')
{
    if( empty($default_title) )
        $default_title = _('(Default)');

    require_once('cchost_lib/cc-submit.php');
    $sapi = new CCSubmit();
    $types = $sapi->GetSubmitTypes();
    foreach( $types as $typekey => $typeinfo )
    {
        if( empty($types['quota_reached']) && ($allow_remix || !$typeinfo['isremix']) )
            $submit_types[$typekey] = $typeinfo['submit_type'];
    }

    return array_merge(    array( '' => $default_title ),
                        $submit_types, 
                        array( 'alternate_mix' => _('Alternate mix'),
                               'rough_mix'  => _('Rough mix'),
                               'multiple_formats'  => _('Alternate format'),
                               'preview' => _('Preview') ));
}

function cc_query_default_args($required_args=array())
{
    require_once('cchost_lib/cc-query.php');
    require_once('cchost_lib/zend/json-encoder.php');
    $query = new CCQuery();
    $args = array_merge( $query->ProcessUriArgs(), $required_args );
    $json_args = CCZend_Json_Encoder::encode($args);
    return array( $args, $json_args );
}

function cc_query_fmt($qstring,$debug=0)
{
    if( empty($qstring) )
        return array();
    
    parse_str($qstring,$args);
    require_once('cchost_lib/cc-query.php');
    $query = new CCQuery();
    if( empty($args['format']) )
        $args['format'] = 'php';
    $args = $query->ProcessAdminArgs($args);
    list( $results ) = $query->Query($args);
    return $results;
}

function cc_query_get_optset( $optset_name = 'default', $fmt = 'php' )
{
    $optsets = cc_get_config('query-browser-opts');
    if( empty($optsets[$optset_name]) )
    {
        $optset = array( 'template' => 'reccby',
                         'css'      => 'css/qbrowser_wide.css',
                         'limit'    => 25,
                         'reqtags'  => '*',
                         'license'  => 1,
                         'user'     => 1,
                        );
    }
    else
    {
        $optset = $optsets[$optset_name];
    }
    $reqtags_all = cc_get_config('query-browser-reqtags');
    if( empty($optset['types_key']) || empty($reqtags_all[$optset['types_key']]) )
    {
        $reqtags = array( 
                        array( 'tags' => '*',
                               'text' => 'str_filter_all' )
                         );
    }
    else
    {
        $reqtags = $reqtags_all[$optset['types_key']];
    }

    $optset['types'] = $reqtags;

    if( $fmt != 'json' )
        return $optset;

    require_once('cchost_lib/zend/json-encoder.php');
    return CCZend_Json_Encoder::encode($optset);
}

if( !function_exists('http_build_query') )
{
    function http_build_query($args)
    {
        $qargs = array();
        foreach( $args as $K => $V )
            $qargs[] = $K . '=' . $V;
        return join( '&', $qargs );
    }
}

function cc_recent_playlists()
{
    require_once('cchost_lib/ccextras/cc-playlist.php');
    return CC_recent_playlists_impl();
}

function cc_hot_playlists()
{
    require_once('cchost_lib/ccextras/cc-playlist.php');
    return CC_hot_playlists_impl();
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
    require_once('cchost_lib/ccextras/cc-playlist.inc');
    $pls = new CCPlaylists();
    $ret =& $pls->_playlist_with($record['upload_id']);
    return $ret;
}


function cc_get_user_role($user_name)
{
    return CCUser::IsAdmin($user_name) ? 'admin' : '';
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
        $davurl = ccd($CC_GLOBALS['default_user_image']);
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

function cc_php_to_json(&$obj)
{
    require_once('cchost_lib/zend/json-encoder.php');
    return CCZend_Json_Encoder::encode($obj);
}

function cc_wrap_user_tags(&$tag)
{
    $tag = '<p class="cc_autocomp_line" id="_ac_'.$tag.'">'.$tag.'</p>';
}

function cc_content_feed($query,$title)
{
    $img = '<img src="' . ccd('ccskins','shared','images','feed-icon16x16.png') . '" title="[ RSS 2.0 ]" /> ';
    $url = url_args( ccl('api','query'), 'f=rss&datasource=topics&title=' . urlencode($title) . '&' . $query );
    CCPage::AddLink('feed_links', 'alternate', 'application/rss+xml', $url, $title, $img . $title, 'feed_topics' );
}

function cc_fancy_user_sql($colname='fancy_user_name',$table='')
{
    if( !empty($table) && substr($table,-1) != '.' )
        $table .= '.';

    $sql =<<<EOF
        IF( {$table}user_name = REPLACE({$table}user_real_name,' ','_'), 
            {$table}user_real_name, 
            CONCAT( {$table}user_real_name, ' (', {$table}user_name, ')' ) ) as {$colname}
EOF;
    
    return $sql;
}

function cc_get_content_page_type($page)
{
    require_once('cchost_lib/cc-template.php');
    $skinmac = new CCSkinMacro($page);
    $props = $skinmac->GetProps();
    if( empty($props['topic_type']) )
        die("Content Page '{$page}' does not have 'topic_type' property");
    return $props['topic_type'];
}
?>
