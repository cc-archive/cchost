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
* Module for sample pools
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to ccHost');

/**
*/
require_once('cclib/cc-feedreader.php');

/**
* Remote sample pools we know about
* 
* @package cchost
* @subpackage api
*/
class CCPools extends CCTable
{
    function CCPools()
    {
        $this->CCTable('cc_tbl_pools', 'pool_id');

        if( !defined('IN_CC_INSTALL') )
        {
            $baseurl = ccl('pools','pool') . '/';
            $this->AddExtraColumn("CONCAT('$baseurl', pool_id) as pool_page_url");
        }
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCPools();
        return $_table;
    }
}

/**
* Items we share samples with (remote version of uploads)
* 
* @package cchost
* @subpackage api
*/
class CCPoolItems extends CCTable
{
    function CCPoolItems()
    {
        $this->CCTable('cc_tbl_pool_item', 'pool_item_id');
        $this->AddJoin( new CCPools(), 'pool_item_pool' );

        if( !defined('IN_CC_INSTALL') )
        {
            require_once('cclib/cc-license.php');

            $this->AddJoin( new CCLicenses(), 'pool_item_license' );

            $baseurl = ccl('pools','item') . '/'; 
            $this->AddExtraColumn("CONCAT('$baseurl', pool_item_id) as file_page_url");
            $this->AddExtraColumn("CONCAT('$baseurl', pool_item_id) as artist_page_url");
            $baseurl = ccl('pools','pool') . '/'; 
            $this->AddExtraColumn("CONCAT('$baseurl', pool_item_pool) as pool_item_pool_url");
        }
    }

    function & GetRecordFromRow(&$row)
    {
        $row['pool_item_extra'] = unserialize($row['pool_item_extra']);
        $row['pool_item_guid'] = empty($row['pool_item_extra']['guid'])
                                   ? $row['pool_item_url']
                                   : $row['pool_item_extra']['guid'];
        return $row;
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCPoolItems();
        return $_table;
    }
}

/**
* Remix tree between local and pool items
* 
* This table wrapper answers the question: given a local upload
* what are the remote remixes and remix sources out there?
*
* @package cchost
* @subpackage api
*/
class CCPoolTree extends CCTable
{
    var $_bind_to_query;
    var $_pool_items;

    function CCPoolTree($bind_to_pool,$bind_to_query)
    {
        $this->CCTable('cc_tbl_pool_tree',$bind_to_pool);
        $this->_bind_to_query = $bind_to_query;
    }

    function & _get_relatives($id,$need_approval,$limit)
    {
        if( !isset($this->_pool_items) )
        {
            $this->_pool_items = new CCPoolItems();
        }

        $joinid = $this->_pool_items->AddJoin($this,'pool_item_id');
        $approval = $need_approval ? ' AND (pool_item_approved > 0)' : '';
        if( $limit )
            $this->_pool_items->SetOffsetAndLimit(0,CC_MAX_SHORT_REMIX_DISPLAY);
        
        $where = $joinid . '.' . $this->_bind_to_query . " = '$id' $approval";
        //$sql = $this->_pool_items->_get_select($where);
        //CCDebug::PrintVar($sql);
        $rows = $this->_pool_items->QueryRows( $where );
        $this->_pool_items->RemoveJoin($joinid);
        $records =& $this->_pool_items->GetRecordsFromRows($rows);
        return $records;
    }
}


/**
* Table wrapper used for returning remote sources given local remixes
*
* @package cchost
* @subpackage api
*/
class CCPoolSources extends CCPoolTree
{
    function CCPoolSources()
    {
        $this->CCPoolTree('pool_tree_pool_parent', 'pool_tree_child');
    }

    /**
    * Return the pool remix sources for a given record
    *
    * I think this method is dead
    *
    * @param array $record Record to get remixes of
    * @param boolean $limit_by_works_page (See method comments)
    */
    function & GetSources($record,$limit_by_works_page=false)
    {
        $do_limit = $limit_by_works_page ? empty($record['works_page']) : false;
        $r =& $this->_get_relatives($record['upload_id'],false,$do_limit);
        return $r;
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCPoolSources();
        return $_table;
    }
}

/**
* @package cchost
* @subpackage api
*/
class CCPool
{
    function Search($pool_id)
    {
        $query = CCUtil::Strip($_GET['search']);

        list( $type, $pool_results ) = CCPool::PoolQuery($pool_id, $query);

        if( !empty($pool_results) )
        {
            if( $type == 'rss' )
            {
                // we got a remote rss feed, we add the items right into the
                // table even if the use doesn't pick them just to have them
                // around.

                $pools =& CCPools::GetTable();
                $pool = $pools->QueryKeyRow($pool_id);
    
                $items = array();
                foreach( $pool_results as $pool_result )
                {
                    $item = CCPool::AddItemToPool( $pool, $pool_result );
                    if( is_array($item) )
                    {
                        $items[] = array_merge( $item, $pool );
                    }
                }
            }
            else
            {
                // the items were fetched locally
                $items = $pool_results;
            }
        }
    
        require_once('cclib/cc-template.php');
        $template = new CCSkinMacro( CCUtil::Strip($_GET['t']) );
        $args['records'] =& $items;
        $template->SetAllAndPrint($args);
        exit; // this is an ajax call
    }

    function PoolQuery($pool_id, $query, $type='full')
    {
        $pools =& CCPools::GetTable();
        $pool = $pools->QueryKeyRow($pool_id);
        $api_url = $pool['pool_api_url'];
        $query = urlencode($query);
        if( substr($api_url,0,7) == 'http://' )
        {
            require_once('cclib/cc-api.php');
            $query_url = CCRestAPI::MakeUrl( $api_url, 'search', 'query=' . $query );
            //CCDebug::PrintVar($query_url);
            //CCDebug::LogVar('query_url',$query_url);
            $fr = new CCFeedReader();
            $rss = $fr->cc_parse_url( $query_url );
            if( !empty($rss->ERROR) )
            {
                CCDebug::Log("Feed read error on $query_url: " . $rss->ERROR);
                return(false);
            }

            if( empty($rss->channel) )
            {
                CCDebug::Log("Feed read error on $query_url: Can't find channel info");
                return(false);
            }
            $retval = array( 'rss', $rss->items );
        }
        else
        {
            $parts = split(':',$api_url);
            if( !empty($parts[1]) )
                require_once($parts[1]);
            $api_url = $parts[0];
            $obj = new $api_url();
            $retval = array( 'pool_items', $obj->LocalSearch($pool_id,$query,$type) );
        }

        return( $retval );
    }

    function NotifyPoolsOfRemix($pool_items, $remixguid)
    {
        require_once('cclib/snoopy/Snoopy.class.php');
        require_once('cclib/cc-api.php');

        $myurl = urlencode(ccl('api'));
        $remixguid = urlencode($remixguid);
        $snoopy = new Snoopy();
        $snoopy->accept = 'text/*, application/xml'; // heaven forbid this points to a media file
        //$snoopy->_httpmethod = 'HEAD';
        $count = count($pool_items);
        for( $i = 0; $i < $count; $i++ )
        {
            $row  = $pool_items[$i];

            if( substr($row['pool_api_url'],0,5) != 'http:' )
                continue;

            $guid = urlencode($row['pool_item_guid']);
            $args = "remixguid=$remixguid&guid=$guid&poolsite=$myurl";
            $url  = CCRestAPI::MakeUrl( $row['pool_api_url'], 'ubeensampled', $args );
            $ok = $snoopy->fetch($url);

            if( $ok )
            {
                //CCDebug::LogVar('http: resp', $snoopy->results );
            }
            else
            {
                $error_msg = "Error connecting to {$row['pool_item_api_url']}";
                CCDebug::Log($error_msg);
            }
        }
    }

    function AddPool($pool_site_api_url)
    {
        require_once('cclib/cc-api.php');

        $info_url = CCRestAPI::MakeUrl( $pool_site_api_url, 'info' );
        $fr = new CCFeedReader();
        $rss = $fr->cc_parse_url( $info_url );

        if( !empty($rss->ERROR) )
        {
            $err = "Feed read error on $pool_site_api_url: " . $rss->ERROR;
            CCDebug::Log($err);
            return(false);
        }

        if( empty($rss->channel) )
        {
            CCDebug::Log("Feed read error on $pool_site_api_url: Can't find channel info");
            return(false);
        }

        $parts = parse_url($pool_site_api_url);
        $host = str_replace('www.','',$parts['host']);

        $pools =& CCPools::GetTable();

        $C =& $rss->channel;
        $args['pool_id']           = $pools->NextID();
        $args['pool_name']         = empty($C['title']) ? $host : $C['title'];
        $args['pool_description']  = empty($C['description']) ? "Pool of samples at $host" : $C['description'];
        $args['pool_short_name']   = preg_replace('/[^a-zA-Z0-9_]/', '_', $host );
        $args['pool_api_url']      = $pool_site_api_url;
        $args['pool_site_url']     = empty($C['link']) ? $pool_site_api_url : $C['link'];
        $args['pool_ip']           = CCUtil::EncodeIP($_SERVER['REMOTE_ADDR']);
        $args['pool_banned']       = false;
        $args['pool_auto_approve'] = false;

        $pools->Insert($args);

        return( $args );
    }

    function AddItemtoPool( &$pool, &$item  )
    {
        // not sure why but some of the rss items are 
        // coming in with spaces
        cc_trim_array($item);
        $pool_items =& CCPoolItems::GetTable();

        $link = $item['link'];
        $where['pool_item_url'] = $link;
        $where['pool_item_timestamp'] = $item['date_timestamp'];
        $row = $pool_items->QueryRow($where);

        if( !empty($row) )
        {
            $row['pool_item_extra'] = unserialize($row['pool_item_extra']);
            return($row);
        }

        $errmsg = CCPool::_dig_out_license($pool,$item);
        if( !empty($errmsg) )
            return($errmsg);

        $licenses =& CCLicenses::GetTable();
        $licargs['license_url'] = $item['license_url'];
        $license = $licenses->QueryKey($licargs);

        if( empty($license) )
        {
            return( "Could not determine or unrecognized license from {$item['license_url']}" );
        }

        $args['pool_item_id']            = $pool_items->NextID();
        $args['pool_item_pool']          = $pool['pool_id'];
        $args['pool_item_url']           = $link;
        $args['pool_item_download_url']  = $item['enclosure']['url'];
        $args['pool_item_license']       = $license;
        $args['pool_item_name']          = $item['title'];
        $args['pool_item_artist']        = $item['artist'];
        $args['pool_item_description']   = $item['description'];
        $args['pool_item_approved']      = $pool['pool_auto_approve'];
        $args['pool_item_timestamp']     = $item['date_timestamp'];

        $extra = array( 'guid'   => $item['guid'],
                        'length' => $item['enclosure']['length'],
                        'type'   => $item['enclosure']['type'],
                        'tags'   => $item['category'] );

        $args['pool_item_extra']         = serialize($extra);

        $pool_items->Insert($args);

        $args['pool_item_extra']         = $extra;

        return( $args );
    }

    function _dig_out_license(&$pool,&$item)
    {
        $error_msg = '';

        if( !empty($item['license_url']) )
            return('');

        $link = $item['link'];
        if( empty($link) )
        {
            $error_msg = "Could not determine remix license without license metadata";
        }

        if( empty($error_msg) )
        {
            // scrape around the song page for rdf meta info

            require_once('cclib/snoopy/Snoopy.class.php');
            $snoopy = new Snoopy();
            $snoopy->accept = 'text/*, application/xml'; // heavan forbid this points to a media file
            $snoopy->_httpmethod = 'HEAD';
            if( !$snoopy->fetch($link) )
            {
                $error_msg = "Call to determine page type of ($link) failed";
            }
        }

        if( empty($error_msg) )
        {
            $hkeys = array_keys($snoopy->headers);
            $count = count($hkeys);
            $found = false;
            for( $i = 0; $i < $count; $i++ )
            {
                if( preg_match('#Content-type.*(text|xml)#i', $snoopy->headers[$keys[$i]]) )
                {
                    $found = true;
                    break;
                }
            }
            
            if( !$found )
            {
                $error_msg = "$link is not pointing to a text resource";
            }
        }


        if( empty($error_msg) )
        {
            $snoopy->_httpmethod = 'GET';
            $snoopy->fetch($link);
            if( empty($snoopy) )
            {
                $error_msg = "Results for $link is empty";
            }
        }

        if( empty($error_msg) )
        {
            $regex =  '#<([a-z]+:)?license\s+[^">]+"(http://creativecommons.org/licenses/[^"]*)"#s';
            if( !preg_match( $regex, $snoopy->results, $match ) )
            {
                $error_msg = "Count not find license meta info in $link";
            }
            else
            {
                $item['license_url'] = $match[2];
            }
        }

        if( !empty($error_msg) )
        {
            if( !empty($pool['pool_default_license']) )
            {
                $item['license_url'] = $pool['pool_default_license'];
                $error_msg = '';
            }
        }

        return($error_msg);
    }

    function InstallPools()
    {
        // 
        // Remote pools of CC licensed material
        //
        $drops [] = 'cc_tbl_pools';

        $sql[] = <<<END

    CREATE TABLE cc_tbl_pools
        (
          pool_id          int(5) unsigned  NOT NULL auto_increment,
          pool_name        varchar(255),
          pool_short_name  varchar(50),
          pool_description mediumtext NOT NULL default '',
          pool_api_url     mediumtext NOT NULL default '',
          pool_site_url    mediumtext NOT NULL default '',
          pool_ip          varchar(10) NOT NULL default '',
          pool_banned      tinyint(1) NOT NULL default 0,
          pool_search      tinyint(1) NOT NULL default 0,
          pool_auto_approve tinyint(1) NOT NULL default 0,
          pool_default_license  varchar(50) NOT NULL default '',

          PRIMARY KEY pool_id (pool_id)
        )
END;

        // 
        // Pool CC licensed material works
        //
        // These items share samples with this site
        //
        $drops [] = 'cc_tbl_pool_item';

        $sql[] = <<<END

    CREATE TABLE cc_tbl_pool_item
        (
          pool_item_id           int(5) unsigned  NOT NULL auto_increment,
          pool_item_pool         int(5),
          pool_item_url          mediumtext   NOT NULL default '',
          pool_item_download_url mediumtext   NOT NULL default '',
          pool_item_description  mediumtext   NOT NULL default '',
          pool_item_extra        mediumtext   NOT NULL default '',
          pool_item_license      varchar(255) NOT NULL default '',
          pool_item_name         varchar(255) NOT NULL default '',
          pool_item_artist       varchar(255) NOT NULL default '',
          pool_item_approved     tinyint(0)   NOT NULL default 0,
          pool_item_num_remixes  int(6)       NOT NULL default 0,
          pool_item_num_sources  int(6)       NOT NULL default 0,
          pool_item_timestamp    int(30),

          PRIMARY KEY pool_item_id (pool_item_id)
        )
END;

        // 
        // Pool remix tree
        //
        $drops [] = 'cc_tbl_pool_tree';

        $sql[] = <<<END

    CREATE TABLE cc_tbl_pool_tree 
        (
          pool_tree_id              int(5) unsigned NOT NULL auto_increment,
          pool_tree_parent          int(5) unsigned,
          pool_tree_child           int(5) unsigned,
          pool_tree_pool_parent   int(5) unsigned,
          pool_tree_pool_child    int(5) unsigned,

          PRIMARY KEY pool_tree_id (pool_tree_id)
        )

END;

        $tables = CCDatabase::ShowTables();
        $drop_sql = array();
        $drops = array_intersect( $drops, $tables );
        if( !empty($drops) )
        {
            foreach( $drops as $drop )
            {
                $drop_sql[] = "DROP TABLE $drop";
            }

            CCDatabase::Query($drop_sql);
        }
        CCDatabase::Query($sql);

        // don't overwrite intallers settings

        // $vals['allow-pool-ui']       = false;
        // $vals['allow-pool-search']   = false;
        // $vals['allow-pool-register'] = false;
        
        $vals['pool-push-hub']       = '';
        $vals['pool-pull-hub']       = '';
        $vals['pool-remix-throttle'] = 10;

        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig( 'config', $vals, CC_GLOBAL_SCOPE, true );
    }
    
}

/**
* @package cchost
* @access private
*/
function cc_trim_array(&$a)
{
    $keys = array_keys($a);
    $count = count($keys);
    for( $i = 0; $i < $count; $i++ )
    {
        $t =& $a[ $keys[$i] ];
        if( is_array($t) )
            cc_trim_array($t);
        else
            $t = trim($t);
    }
}
?>