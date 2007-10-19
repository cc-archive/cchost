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
function CC_strchop($str,$maxlen,$dochop = true)
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
function CC_datefmt($date,$fmt)
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
* Returns a list of remote remixes waiting for admin approval
*
* This applies to situation where your installation is a source
* pool for another site. That site has notified us that there
* has been a remix of our material. This method returns the 
* pool item records of those remixes.
*
* @return array $records Array of remote pool remixes waiting for admin approval
*/ 
function CC_pending_pool_remix()
{
    if( CCUser::IsAdmin() )
    {
        $pool_remixes =& CCPoolRemixes::GetTable();
        if( $pool_remixes->HasUnapproved() )
        {
            $args['url'] = ccl( 'admin', 'pools', 'approve' ) ;
            $args['message'] = _("You've been remixed!") . '<br />' . 
                               _('Click here to make them visible!');
            return( $args );
        }
    }

    return( null );
}

/**
* Return the values current stored in the configs tables
*
* @param string $configName Name of settings (e.g. 'chart', 'licenses')
* @return mixed Raw config value as stored in db
*/
function CC_get_config($configName)
{
    $configs =& CCConfigs::GetTable();
    return $configs->GetConfig($configName);
}

/**
* Return a list of 5 truncated records (the latest uploads) that match a given tag
*
* This method return ONLY two colums: file_page_url and upload_short_name
*
* @param string $tag a specific tag to search for
* @param boolean $cache cache the results
* @return array $trunrecs An array of 5 truncated records
*/
function & CC_quick_list($tags,$cache=1)
{
    global $CC_GLOBALS, $CC_CFG_ROOT;

    if( $cache )
    {
        $cname = $CC_GLOBALS['php-tal-cache-dir'] . '/_ccc_' . $tags . '.txt';
        if( file_exists($cname) )
        {
            include($cname);
            return $rows;
        }
    }

    $furl = ccl('files') . '/';

    $tag = str_replace(',','|',$tags);

    $sql =<<<END
        SELECT CONCAT('$furl',user_name,'/',upload_id) as file_page_url,
               SUBSTRING(upload_name,1,15) as upload_short_name
        FROM cc_tbl_uploads
        JOIN cc_tbl_user on upload_user = user_id
        WHERE upload_tags REGEXP '(^| |,)($tag)(,|$)'
        ORDER BY upload_date DESC
        LIMIT 5
END;

    $rows =& CCDatabase::QueryRows($sql);

    if( $cache && ($CC_CFG_ROOT == CC_GLOBAL_SCOPE) )
    {
        $data = serialize($rows);
        $data = str_replace("'","\\'",$data);
        $text = '<? /* This is a temporary file created by ccHost. It is safe to delete. */ ' .
                 "\n" . '$rows = unserialize(\'' . $data . '\'); ?>';
        $f = fopen($cname,'w+');
        fwrite($f,$text);
        fclose($f);
        chmod($cname,cc_default_file_perms());
        $configs =& CCConfigs::GetTable();
        $tcache = $configs->GetConfig('tcache',CC_GLOBAL_SCOPE);
        if( !in_array($cname,$tcache) )
        {
            $tcache[] = $cname;
            $configs->SaveConfig('tcache',$tcache,CC_GLOBAL_SCOPE);
        }
    }
    return $rows;
}

CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  'cc_tcache_kill' );
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,    'cc_tcache_kill' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    'cc_tcache_kill' );

/**
* @private
*/
function cc_tcache_kill()
{
    $configs =& CCConfigs::GetTable();
    $tcache = $configs->GetConfig('tcache',CC_GLOBAL_SCOPE);
    if( !empty($tcache) )
    {
        foreach($tcache as $cname)
            if( file_exists($cname) )
                @unlink($cname);
        $tcache = array( '' );
        $configs->SaveConfig('tcache',$tcache,CC_GLOBAL_SCOPE);
    }
}

function CC_get_config_roots()
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

function CC_recent_reviews($limit=5)
{
    require_once('ccextras/cc-reviews-table.inc');
    return CC_recent_reviews_impl($limit);
}

function CC_recent_playlists()
{
    require_once('ccextras/cc-playlist.php');
    return CC_recent_playlists_impl();
}

function CC_hot_playlists()
{
    require_once('ccextras/cc-playlist.php');
    return CC_hot_playlists_impl();
}

function & CC_get_details($upload_id,$menu=true)
{
    global $CC_GLOBALS;
    require_once('cclib/cc-upload.php');
    require_once('cclib/cc-upload-table.php');
    $uploads =& CCUploads::GetTable();
    $rec = $uploads->GetRecordFromID($upload_id);
    $CC_GLOBALS['works_page'] = 1;  // this assumes we're calling 
    $rec['works_page'] = true;      // in an ajax context (!)
    if( $menu )
        $rec['local_menu'] = CCUpload::GetRecordLocalMenu($rec);
    $recs = array( &$rec );
    cc_get_remix_history( $recs, 0 );
    return $rec;
}

function cc_get_upload_menu(&$record)
{
    require_once('cclib/cc-upload.php');
    return CCUpload::GetRecordLocalMenu($record);
}

function cc_get_ratings_info(&$record)
{
    require_once('cclib/cc-ratings.php');
    CCRating::GetRatingsInfo($record);
}
function cc_get_remix_history(&$records,$max)
{
    require_once('cclib/cc-remix-hv.php');
    CCRemixHV::GetRemixHistory($records,$max);
    require_once('cclib/cc-pools-hv.php');
    CCPoolHV::GetPoolHistory( $records, $max );
}

function cc_get_value($arr,$key)
{
    if( is_array($arr) && array_key_exists($key,$arr) )
        return $arr[$key];
    return null;
}

if( !function_exists('array_combine') )
{
    function array_combine($keys,$values)
    {
        $c = count($keys);
        $dest = array();
        for( $i = 0; $i < $c; $i++ )
            $dest[$keys[$i]] = $values[$i];

        return $dest;
    }
}

/**#@+
* @access private
* @deprecated
*/
function list_all_users()
{
   $users = new CCUsers();
   $users->SetOrder('user_name');
   $records = $users->GetRecords('');
   return $records;
}

function CC_badcall($to)
{
    print( sprintf(_('A bad call happened to "%s" in a template.'), $to) );
    exit;
}

function CC_split_tags($tagstr)
{
    $a = explode(',',$tagstr);
    return($a);
}

function CC_lang($string)
{
    return(_($string));
}

function CC_count($obj)
{
    if( isset($obj) && is_array($obj) )
        return( count($obj) );
    return( 0 );
}

function CC_test($obj)
{
    return( isset($obj) && !empty($obj) && !PEAR::IsError($obj) );
}

function CC_query($tablename,$func,$module='')
{
    if( !empty($module) )
        require_once($module);
    if( substr($tablename,0,2) != 'CC' )
        return(array());
    $table = new $tablename;
    return( $table->$func() );
}

function CC_ratings_chart($limit,$since='')
{
    require_once('cclib/cc-ratings.php');
    $retval = CCRating::GetChart($limit,$since);
    return($retval);
}

function CC_ids_for_records(&$records)
{
    $count = count($records);
    $ids = '';
    for( $i = 0; $i < $count; $i++ )
    {
        $ids .= $records[$i]['upload_id'];
        if( $i != $count - 1 )
            $ids .= ';';
    }
    return($ids);
}

function CC_debug_dump($obj)
{
    $isenabled = CCDebug::IsEnabled();
    CCDebug::Enable(true);
    CCDebug::PrintVar($obj,false);
    CCDebug::Enable($isenabled);
}

function CC_log_dump($name,$obj)
{
    $isenabled = CCDebug::IsEnabled();
    CCDebug::Enable(true);
    CCDebug::LogVar($name,$obj);
    CCDebug::Enable($isenabled);
}

/**
* Fetch a list of records, cache if you have to or retrieve if you can
*
* This function perform a query the first time it is called and cache
* the results. If a request is made for the exact same tags set then
* the query is retrieved from the cache. This is the fast version of
* CC_tag_query.
*
* The cache is automatically cleared whenever a new file has been 
* uploaded, or an old one has been deleted or modified in any way
* 
* Note that $with_menus and $with_remixes <i>significantly</i> slows
* down this call, even when cached.
*
* @param string $tags Comma separated tags to limit query
* @param string $search_type Valid types are 'all' or 'any' referring to how to treat multiple tags
* @param string $sort_on Name of field to sort on (default is 'upload_date')
* @param string $order Valid orders are 'ASC' or 'DESC' (default is 'DESC')
* @param integer $limit Maximum number of records to cache/retrieve
* @param boolean $with_menus true means include all commands possible in each record
* @param boolean $with_remixes true means include total remix history in each record
* @return array $records An array of matching records
* @see CC_tag_query
*/
function & CC_cache_query( 
        $tags, $search_type='all', $sort_on='', $order='',
        $limit='', $with_menus=false, $with_remixes=false)
{
    global $CC_GLOBALS, $CC_CFG_ROOT;
    $cname = $CC_GLOBALS['php-tal-cache-dir'] . '/_ccc_' . $tags . '.txt';
    if( file_exists($cname) )
    {
        include($cname);
        return $rows;
    }
    
    $rows =& CC_tag_query($tags,$search_type,$sort_on,$order,$limit,$with_menus,$with_remixes);

    // this is hack fix to prevent random vroots from
    // showing up in the cache for these cached lists
    // hardly a permanent solution but we need to stop the
    // bleeding on mixter for now and keep all song pages
    // in the 'media' vroot

    if( $CC_CFG_ROOT == CC_GLOBAL_SCOPE )
    {
        $data = serialize($rows);
        $data = str_replace("'","\\'",$data);
        $text = '<? /* This is a temporary file created by ccHost. It is safe to delete. */ ' .
                 "\n" . '$rows = unserialize(\'' . $data . '\'); ?>';
        $f = fopen($cname,'w+');
        fwrite($f,$text);
        fclose($f);
        chmod($cname,cc_default_file_perms());
        $configs =& CCConfigs::GetTable();
        $tcache = $configs->GetConfig('tcache',CC_GLOBAL_SCOPE);
        if( !in_array($cname,$tcache) )
        {
            $tcache[] = $cname;
            $configs->SaveConfig('tcache',$tcache,CC_GLOBAL_SCOPE);
        }
    }
    return $rows;
}
/**
* Fetch a list of records
*
* This is the (very) slow version of CC_cache_query. Try to use that
* instead if you can.
*
* Note that $with_menus and $with_remixes <i>significantly</i> slows
* down this call, even when cached.
*
* @param string $tags Comma separated tags to limit query
* @param string $search_type Valid types are 'all' or 'any' referring to how to treat multiple tags
* @param string $sort_on Name of field to sort on (default is 'upload_date')
* @param string $order Valid orders are 'ASC' or 'DESC' (default is 'DESC')
* @param integer $limit Maximum number of records to cache/retrieve
* @param boolean $with_menus true means include all commands possible in each record
* @param boolean $with_remixes true means include total remix history in each record
* @return array $records An array of matching records
* @see CC_cache_query
*/
function & CC_tag_query( 
   $tags,$search_type='all',$sort_on='',$order='',$limit='',$with_menus=false,$with_remixes=false)
{
    require_once('cclib/cc-upload-table.php');
    $uploads =& CCUploads::GetTable();
    $uploads->SetDefaultFilter(true,true); // second true means treat like anonymous
    $uploads->SetTagFilter(CCUtil::StripText($tags),$search_type);
    if( $sort_on )
        $uploads->SetOrder($sort_on,$order);
    if( $limit )
        $uploads->SetOffsetAndLimit(0,$limit);
    $records =& $uploads->GetRecords('');
    $uploads->SetTagFilter('');
    $uploads->SetOffsetAndLimit(0,0);
    if( $with_menus || $with_remixes )
    {
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $with_menus )
            {
                $menu = 
                   CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,
                                        array(&$records[$i]),
                                        CC_EVENT_BUILD_UPLOAD_MENU);

                $records[$i]['local_menu'] = $menu;
            }
            if( $with_remixes )
            {
                CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$records[$i]));
            }
        }
    }
    $uploads->SetDefaultFilter(true,false); 
    return $records;
}


/**#@-*/

?>
