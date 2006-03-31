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
    //CCDebug::PrintVar($retval,false);
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

function & CC_tag_query($tags,$search_type='all',$sort_on='',$order='',$limit='',$with_menus=false,$with_remixes=false)
{
    $uploads =& CCUploads::GetTable();
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
                $menu = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$records[$i]));
                $records[$i]['local_menu'] = $menu;
            }
            if( $with_remixes )
            {
                CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$records[$i]));
            }
        }
    }
    return $records;
}



?>