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
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
define('CC_STATS_CACHE', 'ccstatscache.txt');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       'cc_stats_on_map_urls');
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  'cc_stats_kill_cache' );
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,    'cc_stats_kill_cache' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    'cc_stats_kill_cache' );
CCEvents::AddHandler(CC_EVENT_USER_ROW,       'cc_stats_on_user_row' );
 
function cc_stats_on_user_row(&$row)
{
    $username = $row['user_real_name'];

    if( empty( $row['user_num_remixes'] ) )
        if(  empty( $row['user_num_remixed'] ) )
            return;

    $str = ngettext( "%s has %d remix.",
               "%s has %d remixes.", $row['user_num_remixes'] );

    $str = sprintf($str, $username, $row['user_num_remixes']);

    $str .= ' ' . ngettext( '%s has been remixed %d time.',
                      '%s has been remixed %d times.', 
                      $row['user_num_remixed'] );

    $str = sprintf($str, $username, $row['user_num_remixed']);
    
    $row['user_fields'][] = array( 'label' => _('Stats'),
                                   'value' => $str,
                                   'id'    => 'user_num_remixes' );
}

function cc_stats_kill_cache()
{
    global $CC_GLOBALS;
    $file = $CC_GLOBALS['logfile-dir'] . CC_STATS_CACHE;
    if( file_exists($file) )
        unlink($file);
}

function cc_stats_on_map_urls()
{
    CCEvents::MapUrl( ccp('stats'),  'cc_stats_show', CC_DONT_CARE_LOGGED_IN );
    CCEvents::MapUrl( ccp('charts'), 'cc_stats_charts', CC_DONT_CARE_LOGGED_IN );
}

function cc_stats_charts($type='upload',$sort_on='rank',$dir='DESC')
{
    $tname = CCPage::GetViewFile('charts.xml');
    $template = new CCTemplate( $tname );
    if( $type == 'upload' )
    {
        $sort_on = 'upload_' . $sort_on;
        $args['upload_recs'] =& cc_stats_chart_upload_data($sort_on,$dir);
        $count = count($args['upload_recs']);
        for( $i = 0; $i < $count; $i++ )
        {
            CCEvents::Invoke( CC_EVENT_UPLOAD_LISTING, array( &$args['upload_recs'][$i] ) );
            //CCDebug::PrintVar($args['upload_recs'][0],false);
        }
    }
    else
    {
        $sort_on = 'user_' . $sort_on;
        $args['user_recs'] =& cc_stats_chart_user_data($sort_on,$dir);
    }
    $args['root-url'] = cc_get_root_url();
    $text = $template->SetAllAndParse($args);
    CCPage::SetTitle(_("Charts"));
    CCPage::AddPrompt('body_text',$text);
}

function _cc_stats_filter($since)
{
    $x = 'wired,admin,criminals,militiamix,fortminor,cibelle,djdolores,apollonove';

    $x = CCTag::TagSplit($x);
    $where = array();
    foreach($x as $u)
        $where[] = "(user_name <> '$u')";
    $where = join( ' AND ', $where );
    if( $since )
    {
        $date = date('Y-m-d', strtotime($since));
        $where .= " AND (upload_date > '$date')";
    }
    return $where;
}

function & cc_stats_chart_upload_data($sort_on,$dir='DESC',$since='')
{
    $uploads =& CCUploads::GetTable();
    $records =& _cc_stats_get_data($uploads,$sort_on,$dir,$since);
    return $records;
}

function & cc_stats_chart_user_data($sort_on,$dir='DESC',$since='')
{
    $uploads =& CCUsers::GetTable();
    $records =& _cc_stats_get_data($uploads,$sort_on,$dir,$since);
    return $records;
}

function & _cc_stats_get_data(&$table,$sort_on,$dir,$since)
{
    $filter = _cc_stats_filter($since);
    $table->SetSort($sort_on,$dir);
    $table->SetOffsetAndLimit(0,25);
    $rows =& $table->GetRecords($filter);
    $table->SetSort('');
    $table->SetOffsetAndLimit(0,0);
    return $rows;
}



function cc_stats_show()
{
    global $CC_GLOBALS;
    $file = $CC_GLOBALS['logfile-dir'] . CC_STATS_CACHE;

    if( file_exists($file) )
    {
        $text = file_get_contents($file);
    }
    else
    {
        $tfile = CCPage::GetViewFile('stats.xml');
        $template = new CCTemplate( $tfile );
        $args = array();
        $text = $template->SetAllAndParse($args);
        $f = fopen($file,'w');
        fwrite($f,$text);
        fclose($f);
        chmod($file,cc_default_file_perms());
    }

    CCPage::SetTitle(_("Charts"));
    CCPage::AddPrompt('body_text',$text);
}

function cc_stats_uploads_by_month()
{
    $sql =<<<END
        SELECT COUNT(*) c, SUBSTRING(upload_date,1,7) month
            FROM cc_tbl_uploads
            GROUP BY month
            ORDER BY month desc        
END;

    $rows = CCDatabase::QueryRows($sql);
    return $rows;
}

function cc_stats_signups_by_month($after)
{
    $sql =<<<END
select count(*) c, SUBSTRING(user_registered,1,7) month
from cc_tbl_user
where SUBSTRING(user_registered,1,7) > '$after'
group by month
order by month desc        
END;

    $rows = CCDatabase::QueryRows($sql);
    return $rows;
}

function cc_stats_total_uploads($tag='')
{
    $uploads =& CCUploads::GetTable();
    if( !empty($tag) )
        $uploads->SetTagFilter($tag);
    $total = $uploads->CountRows('');
    if( !empty($tag) )
        $uploads->SetTagFilter('');
    return $total;
}

function cc_stats_percent_remixed($tag)
{
    $uploads =& CCUploads::GetTable();
    $uploads->SetTagFilter($tag);
    $ret['type'] = $tag;
    $ret['total'] = $uploads->CountRows('');
    $sql =<<<END
        SELECT upload_id
        FROM cc_tbl_uploads
        JOIN cc_tbl_tree j4 ON cc_tbl_uploads.upload_id = j4.tree_parent
        WHERE upload_tags REGEXP '(^| |,)($tag)(,|$)'
        GROUP BY upload_id
        ORDER BY upload_id
END;
    $rows = CCDatabase::QueryRows($sql);
    $ret['remixed'] = count($rows);
    if ( $ret['total'] > 0 )
        $ret['percent'] = round($ret['remixed'] / $ret['total'] * 100);
    else
        $ret['percent'] = 0;
    $uploads->SetTagFilter('');
    return $ret;
}

function cc_stats_most_uploads()
{
    $sql =<<<END
select count(*) c, user_id
from cc_tbl_uploads
join cc_tbl_user on upload_user = user_id
group by user_id
order by c desc
END;
    return _cc_stats_get_user_counts($sql);
}

function cc_stats_most_of_type($tag,$max=20)
{
    $uploads = new CCUploads();
    $uploads->_key_field = 'user_id';
    $uploads->SetTagFilter($tag);
    $uploads->SetSort('c','DESC');
    $uploads->GroupOnKey();
    $uploads->SetOffsetAndLimit(0,$max);
    $uploads->AddExtraColumn('COUNT(*) c');
    $rows = $uploads->QueryRows(''); // ,'COUNT(*) c,user_real_name,user_name');
    $records = $uploads->GetRecordsFromRows($rows);
    return $records;
}

function cc_stats_remixes_of_type($tag,$max = 10)
{
    $sql =<<<END
select count(*) c, upload_id
   from cc_tbl_tree
   join cc_tbl_uploads on tree_parent = upload_id
   WHERE upload_tags REGEXP '(^| |,)($tag)(,|$)' AND
         upload_extra NOT LIKE '%contest_s%' 
   group by tree_parent
   order by c desc
   LIMIT $max
END;

    $rows = CCDatabase::QueryRows($sql);
    $count = count($rows);
    $records = array();
    $uploads =& CCUploads::GetTable();
    for( $i = 0; $i < $count; $i++ )
    {
        $record = $uploads->GetRecordFromKey($rows[$i]['upload_id']);
        $record['num_remixed'] = $rows[$i]['c'];
        $records[] = $record;
    }
    return $records;
}

function cc_stats_most_remixed()
{
    $max = 15;

    $where = _cc_stats_filter('');

    $sql =<<<END
SELECT *, user_num_remixed c, user_num_remixed num_remixed
   FROM cc_tbl_user
   WHERE $where
   ORDER BY c DESC
   LIMIT $max
END;

    $rows = CCDatabase::QueryRows($sql);
    $users =& CCUsers::GetTable();
    $records = $users->GetRecordsFromRows($rows);
    return $records;
}

function _cc_stats_get_user_counts($sql)
{
    $rows = CCDatabase::QueryRows($sql);

    $count = count($rows);
    $users =& CCUsers::GetTable();
    $records = array();
    for( $i = 0; $i < $count; $i++ )
    {
        $record = $users->GetRecordFromKey($rows[$i]['user_id']);
        $record['num_remixed'] = $rows[$i]['c'];
        $records[] = $record;
    }

   return $records;
}
?>
