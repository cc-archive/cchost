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
* Implementation of the ccHost RESTful API (e.g. Sample Pool API)
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
require_once('cclib/cc-feedreader.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS, array('CCRestAPI', 'OnMapUrls'));


/**
* @package cchost
* @subpackage api
*/
class CCRestAPI
{
    function MakeUrl( $base, $cmd, $args = '' )
    {
        $url = CCUtil::CheckTrailingSlash($base,true);
        if( !preg_match("#$cmd?$#", $url ) )
            $url .= $cmd;
        if( !empty($args) )
        {
            if( strchr($url,'?') === false )
                $args = '?' . $args;
            else
                $args = '&' . $args;
        }
        return( $url . $args );
    }

    function Info($feeds = null)
    {
        if( !isset($feeds) )
            $feeds = new CCFeedsRss();
        $feeds->GenerateFeed('');
    }

    function Search()
    {
        $feeds = new CCFeeds();

        if( empty( $_REQUEST['query'] ) )
            $this->Info($feeds);

        $query = CCUtil::StripText( urldecode($_REQUEST['query']) );
        if( empty($query) )
            $this->Info($feeds);

        $feeds->_check_cache('search',$query);

        if( !empty($_REQUEST['type']) )
            $type = CCUtil::StripText($_REQUEST['type']);
        if( empty($type) || !in_array( $type, array( 'any', 'all', 'phrase')) )
            $type = 'any';

        $results = array();
        CCSearch::DoSearch( $query, $type, CC_SEARCH_UPLOADS, $results  );

        $feeds->PrepRecords($results[CC_SEARCH_UPLOADS]);

        $feeds->GenerateFeedFromRecords(
                        $results[CC_SEARCH_UPLOADS],
                        $query,
                        ccl( 'api', 'search', $query ),
                        'search'
                        );
    }

    function _get_upload_id_from_guid($guid)
    {
        if( intval($guid) > 0 )
            return $guid;

        if( is_string($guid) )
        {
            $guid = urldecode(CCUtil::StripText($guid));
            if( preg_match( '#/([0-9]*)$#', $guid, $m ) )
            {
                return($m[1]);
            }
        }
        
        return null;
    }

    function File($guid='')
    {
        if( empty($guid) )
            $guid = urldecode($_REQUEST['guid']);

        $feeds = new CCFeedsRSS();

        $upload_id = CCRestAPI::_get_upload_id_from_guid($guid);

        if( empty($upload_id) )
            $this->Info($feeds);

        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);

        $records = array( $record );
        $feeds->PrepRecords($records);
        $feeds->GenerateFeedFromRecords(
                        $records,
                        '',
                        ccl('api','file',$upload_id),
                        ''
                        );
    }

    //
    // 1. guid=[song_page_url]
    // 2. remixid=[remixguid] 
    // 3. poolsite=[poolsite_api_url]
    //    
    //
    function UBeenRemixed()
    {
        global $CC_GLOBALS;

        $guid        = $_REQUEST['guid'];
        $remixguid   = $_REQUEST['remixguid'];
        $poolsiteurl = $_REQUEST['poolsite'];

        $upload_id   = $this->_get_upload_id_from_guid($guid);
        if( empty($upload_id) )
        {
            $this->error_exit("Missing or invalid parameter: guid=$guid");
        }
        $remixguid   = urldecode(CCUtil::StripText($remixguid));
        if( empty($remixguid) )
        {
            $this->error_exit("Missing or invalid parameter: (remixguid=$remixguid)");
        }
        $poolsiteurl = urldecode(CCUtil::StripText($poolsiteurl));
        if( empty($poolsiteurl) )
        {
            $this->error_exit("Missing or invalid parameter: (poolsiteurl=$poolsiteurl)");
        }

        $uploads =& CCUploads::GetTable();
        $uploadargs['upload_id'] = $upload_id;

        if( $uploads->CountRows($uploadargs) != 1 )
        {
            $this->error_exit("That source identifier is not valid (it might have been deleted by original artist).");
        }

        // Check for spam throttle
        $ip = CCUtil::EncodeIP( $_SERVER['REMOTE_ADDR'] );
        $where = "(pool_ip = '$ip' OR pool_api_url = '$poolsiteurl')";
        $pools =& CCPools::GetTable();
        $matching_pools = $pools->QueryRows($where);
        $pool_items =& CCPoolItems::GetTable();
        $total_unapproved_entries = 0;
        $count = count($matching_pools);
        for( $i = 0; $i < $count; $i++ )
        {
            $where = array();
            $where['pool_item_pool'] = $matching_pools[$i]['pool_id'];
            $where['pool_item_approved']   = '0';
            $total_unapproved_entries += $pool_items->CountRows($where);
        }

        if( $total_unapproved_entries >= $CC_GLOBALS['pool-remix-throttle'] )
        {
            $this->error_exit("Maximum remix limit reached.");
        }

        // poolsite not in pools table? add it
        //
        $where = array();
        $where['pool_api_url'] = $poolsiteurl;
        $pool = $pools->QueryRow($where);
        if( empty($pool) )
        {
            $pool = CCPool::AddPool($poolsiteurl);
        }

        if( is_string($pool) )
        {
            $this->error_exit('Could not verify calling remix site: '. $pool);
        }

        $guid_url = $this->MakeUrl( $poolsiteurl, 'file', 'guid=' . urlencode($remixguid) );

        $rss = CCFeeds::ReadFeed( $guid_url );

        $fr = new CCFeedReader();
        $rss = $fr->cc_parse_url($guid_url);

        if( !empty($rss->ERROR) )
        {
            $this->error_exit("Could not retrieve remix information: {$rss->ERROR}");
        }

        if( empty($rss->items) )
        {
            $this->error_exit("Remix information was not returned on request");
        }

        // remember where this pool is calling from in case we need to throttle
    
        $poolargs['pool_id'] = $pool['pool_id'];
        $poolargs['pool_ip'] = $ip;
        $pools->Update($poolargs);

        $item =& $rss->items[0];

        if( empty($item['link']) )
        {
            $this->error_exit( "Missing remix link from return information" );
        }

        $link = $item['link'];

        $pool_items =& CCPoolItems::GetTable();

        $workswhere['pool_item_url'] = $link;
        $pool_item = $pool_items->QueryRow($workswhere);

        if( empty($pool_item) )
        {
            $pool_item =& CCPool::AddItemtoPool( $pool, $item );
            if( is_string($pool_item) )
                $this->error_exit($pool_item);
        }

        $pool_tree =&  CCPoolRemixes::GetTable();
        $remtreeargs['pool_tree_parent'] = $upload_id;
        $remtreeargs['pool_tree_pool_child'] = $pool_item['pool_item_id'];
        $pool_tree->Insert($remtreeargs);

        $this->success_exit();
    }

    function success_exit($status='ok', $msg = 'operation succeeded')
    {
        header('Content-type: text/xml');
        print('<?xml version="1.0" encoding="utf-8" ?>' . "\n<results><status>$status</status><detail>$msg</detail></results>");
        exit;
    }

    function error_exit($msg='')
    {
        $this->success_exit('error',$msg);
    }

    function Pools()
    {
        global $CC_GLOBALS;

        // not really done here...
        $tfile = CCTemplate::GetTemplate('api.xml');
        $template = new CCTemplate( $tfile, false ); // false means xml mode
        $configs =& CCConfigs::GetTable();
        $args = array_merge($CC_CONFIGS,$configs->GetConfig('ttags'));
        $pools =& CCPools::GetTable();
        $args['feed_items'] = $pools->QueryRows('');
        $xml = $template->SetAllAndParse($args);
        header("Content-type: text/xml");
        print($xml);
        exit;
    }

    function PoolRegister()
    {
        if( empty($CC_GLOBALS['allow-pool-register']) )
            $this->error_exit("remote registration not allowed at this site");

        $pool_api_url = urldecode( $_REQUEST['poolsite'] );
        if( empty($pool_api_url) )
            $this->error_exit("missing parameter: poolsite");

        $pools =& CCPools::GetTable();
        $where['pool_api_url'] = $pool_api_url;
        if( $pools->CountRows($where) == 0 )
        {
            $api = new CCPool();
            $pool = $api->AddPool($pool_api_url);
            if( is_string($pool) )
                $this->error_exit("Error adding pool: $pool_api_url");
        }

        $this->success_exit();
    }

    function ISampledThis($works_page)
    {
        
    }

    function Version()
    {
        header("Content-type: text/plain");
        print '2.0';
        exit;
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('api','info'),                 array( 'CCRestAPI', 'Info'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','search'),               array( 'CCRestAPI', 'Search'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','file'),                 array( 'CCRestAPI', 'File'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','ubeensampled'),         array( 'CCRestAPI', 'UBeenRemixed'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','isampledthis'),         array( 'CCRestAPI', 'ISampledThis'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','pools'),                array( 'CCRestAPI', 'Pools'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','poolregister'),         array( 'CCRestAPI', 'PoolRegister'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('api','version'),         array( 'CCRestAPI', 'Version'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
    }
    
}



?>
