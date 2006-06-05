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

/* 
    CUSTOM TEMPLATE API 

    Methods here are called from templates/custom.xml

    It's pretty easy to break the site if you are not familiar with the
    rest of the site
*/

function CC_badcall($to)
{
    print("there was a call to \"$to\" in a template");
    exit;
}

function CC_split_tags($tagstr)
{
    $a = explode(',',$tagstr);
    return($a);
}

// This allows templates to put up a string and go through the 
// language engine...
function CC_lang($string)
{
    return(cct($string));
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

function CC_strchop($str,$maxlen,$dochop = true)
{
    if( empty($dochop) || (strlen($str) <= $maxlen) )
        return($str);
    return( substr($str,0,$maxlen) . '...' );
}

function CC_datefmt($date,$fmt)
{
    if( empty($date) )
        return '';
    return( date($fmt,strtotime($date)) );
}

function CC_query($tablename,$func)
{
    if( substr($tablename,0,2) != 'CC' )
        return(array());
    $table = new $tablename;
    return( $table->$func() );
}

function CC_ratings_chart($limit,$since='')
{
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

function CC_pending_pool_remix()
{
    if( CCUser::IsAdmin() )
    {
        $pool_remixes =& CCPoolRemixes::GetTable();
        if( $pool_remixes->HasUnapproved() )
        {
            $args['url'] = ccl( 'admin', 'pools', 'approve' ) ;
            $args['message'] = "You've been remixed!<br />Click here to make them visible!";
            return( $args );
        }
    }

    return( null );
}

function & 
CC_quick_list($tag)
{
    return(array());

    $furl = ccl('files') . '/';

    $sql =<<<END
        SELECT CONCAT('$furl',user_name,'/',upload_id) as file_page_url,
               SUBSTRING(upload_name,1,10) as upload_short_name
        FROM cc_tbl_uploads
        JOIN cc_tbl_user on upload_user = user_id
        WHERE upload_tags REGEXP '(^| |,)($tag)(,|$)'
        ORDER BY upload_date DESC
        LIMIT 5
END;

     $rows =& CCDatabase::QueryRows($sql);

     return $rows;
}

CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  'cc_tcache_kill' );
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,    'cc_tcache_kill' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    'cc_tcache_kill' );

function cc_tcache_kill()
{
    $configs =& CCConfigs::GetTable();
    $tcache = $configs->GetConfig('tcache',CC_GLOBAL_SCOPE);
    if( !empty($tcache) )
    {
        foreach($tcache as $cname)
        {
            if( file_exists($cname) )
            {
                @unlink($cname);
            }
        }
    }
}

function & 
CC_cache_query($tags,$search_type='all',$sort_on='',$order='',$limit='',$with_menus=false,$with_remixes=false)
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
        chmod($cname,CC_DEFAULT_FILE_PERMS);
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

function & 
CC_tag_query($tags,$search_type='all',$sort_on='',$order='',$limit='',$with_menus=false,$with_remixes=false)
{
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


function list_all_users()
{
   $users = new CCUsers();
   $users->SetOrder('user_name');
   $records = $users->GetRecords('');
   return $records;
}


if( class_exists('CCReviews') )
{
    function CC_recent_reviews($limit=5)
    {
        $lim = 5 * $limit; // inc the limit to cover user's multiple reviews and banned,
                           // unpublished
        $uploads = new CCUploads();
        $uploads->SetDefaultFilter(true,true);
        $reviews =& CCReviews::GetTable();
        $reviews->SetOrder('topic_date','DESC');
        $reviews->SetOffsetAndLimit(0,$lim);
        $rows = $reviews->QueryRows('');
        $reviewers = array();
        $reviews = array();
        $count = count($rows);
        $users =& CCUsers::GetTable();
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $rows[$i];
            if( in_array($R['user_name'],$reviewers) )
                continue;

            // weed out unpublished and banned recs
            $uprow = $uploads->QueryKeyRow($R['topic_upload']);
            if( !empty($uprow) )
            {
                $reviewers[] = $R['user_name'];
                $reviewee = $users->QueryItemFromKey('user_name',$uprow['upload_user']);
                $R['topic_permalink'] = ccl( 'reviews', $reviewee,
                                             $R['topic_upload'] . '#' . $R['topic_id'] );
                $reviews[] = $R;
                if( count($reviews) == $limit )
                    break;
            }
        }

        return $reviews;
    }
}

?>