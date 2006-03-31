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

if( CCDebug::IsEnabled() )
    define('MAGPIE_DEBUG',1);

define('MAGPIE_CACHE_ON',0);
define('MAGPIE_CACHE_DIR', 'cclib/phptal/phptal_cache' );

$MAGPIE_PARSER = null;

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCFeeds',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCFeeds',  'OnUploadDelete'));
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,    array( 'CCFeeds',  'OnFileDelete'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCFeeds',  'OnUploadDone'));

/**
* XML Feed generator and reader for site
*
*/
class CCFeeds
{
    /**
    * Handler for feed/rss - returns rss xml feed for given tags
    *
    * @param string $tagstr Space (or '+') delimited tags to use as basis of xml feed
    */
    function GenerateRSSFromTags($tagstr='')
    {
        if( empty($tagstr) )
        {
            if( !empty($_REQUEST['remixesof']) )
            {
                $username = CCUtil::StripText($_REQUEST['remixesof']);
                if( !empty($username) )
                {
                    $user_id = CCUser::IDFromName($username);
                    if( !empty($user_id) )
                    {
                        $remixes =& CCRemixSources::GetTable();
                        $records =& $remixes->GetRemixesOf($user_id);
                        if( !empty($records) )
                            $this->PrepRecords($records);
                        $qstring = '?remixesof=' . $username;
                        $tagstr = cct('Remixes of ') . $username;
                    }
                }
            }
            elseif( !empty($_REQUEST['remixedby']) )
            {
                $username = CCUtil::StripText($_REQUEST['remixedby']);
                if( !empty($username) )
                {
                    $user_id = CCUser::IDFromName($username);
                    if( !empty($user_id) )
                    {
                        $remixes =& CCRemixes::GetTable();
                        $records =& $remixes->GetRemixedBy($user_id);
                        if( !empty($records) )
                            $this->PrepRecords($records);
                        $qstring = '?remixedby=' . $username;
                        $tagstr = cct('Uploads remixed by ') . $username;
                    }
                }
            }
        }
        else
        {
            $tagstr = str_replace(' ',',',urldecode($tagstr));
            $this->_check_cache('rss',$tagstr);
            $qstring = '';
        }

        if( empty($records) )
            $records =& $this->_get_tag_data($tagstr);

        CCFeeds::GenerateRSSFromRecords($records,$tagstr,ccl('tags',$tagstr) . $qstring);
    }


    function PodcastPage()
    {
        $uploads =& CCUploads::GetTable();

        //
        //
        // if you're making changes here, you probably want to make
        // the same ones in cc-renderaudio.php StreamPage() as well
        //
        //
        $sort_order = array();

        if( !empty($_REQUEST['ids']) )
        {
            $ids = $_REQUEST['ids'];
            $ids = explode(';',$ids);
            $ids = array_unique($ids);
            $where_id = array();
            $i = 0;
            foreach($ids as $id)
            {
                $sort_order[$id] = $i++;
                $where_id[] = " (upload_id = $id) ";
            }
            $where = implode('OR',$where_id);
            if( empty($_REQUEST['nosort']) )
                $sort_order = array();
        }
        elseif( !empty($_REQUEST['tags']) )
        {
            $tagstr = CCUtil::StripText($_REQUEST['tags']);
            $tagstr = str_replace(' ',',',urldecode($tagstr));
            if( empty($tagstr) )
                return;
            $uploads->SetTagFilter($tagstr,'all');
            $where = '';
        }
        else
        {
            return;
        }

        if( empty($_REQUEST['nosort']) )
            $uploads->SetOrder('upload_date','DESC');
        $records = $uploads->GetRecords($where);
        $this->_resort_records($records,$sort_order);
        $this->PrepRecords($records);
        $this->GenerateRSSFromRecords($records,"Podcast this page",ccl('podcast','page'),'podcast');
    }

    function _resort_records(&$records,&$sort_order)
    {
        if( !empty($sort_order) )
        {
            $sorted = array();
            $count = count($records);
            for( $i = 0; $i < $count; $i++ )
            {
                $sorted[ $sort_order[ $records[$i]['upload_id'] ] ] = $records[$i];
            }
            $records = $sorted;
            $sorted = null;
            ksort($records);
        }
    }

    function PodcastUser($username='')
    {
        if( empty($username) )
            return;

        $uploads =& CCUploads::GetTable();
        $where['user_name'] = $username;
        $records = $uploads->GetRecords($where);
        $this->PrepRecords($records);
        $this->GenerateRSSFromRecords($records,"Podcast for $username",ccl('podcast','page',$username),'podcast');
    }
    /**
    * Handler for feed/rss - returns rss xml feed for given records
    *
    * @param array $records Results of some kind of uploads query
    * @param string $tagstr  Search string to display as part of description
    * @param string $feed_url The URL that represents this result set 
    */
    function GenerateRSSFromRecords(&$records,$tagstr,$feed_url,$cache_type='rss')
    {
        global $CC_GLOBALS;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = utf8_encode($this->_cct($template_tags['site-title']));

        $args = $CC_GLOBALS;
        $args += $template_tags;

        if( empty($tagstr) )
        {
            $args['feed_url']            = cc_get_root_url();
            $args['channel_title']       = $site_title;
            $args['feed_subject']        = $site_title;
        }
        else
        {
            $args['feed_url'] = $feed_url;
            $args['channel_title'] = "$site_title ($tagstr)";
            $args['feed_subject'] = "$site_title ($tagstr)";
        }

        $args['channel_description'] = utf8_encode($this->_cct($template_tags['site-description']));

        if( empty($records) )
        {
            $date = CCUtil::FormatDate(RFC822_FORMAT,time());
        }
        else
        {
            $args['feed_items'] = $records;
            $date = $records[0]['rss_pubdate'];
        }

		$args['rfc822-build-date'] = 
		$args['rfc822-pub-date'] = $date;


        $template = new CCTemplate( $CC_GLOBALS['template-root'] . 'rss_20.xml', false ); // false means xml mode
        header("Content-type: text/xml"); // this should enforce a utf-8
        $xml = $template->SetAllAndParse( $args );
        if( !empty($records) && !empty($tagstr) )
            $this->_cache($xml,$cache_type,$tagstr);
        print($xml);
        exit;
    }

    function ReadFeed($feed_url)
    {
        require_once('cclib/cc-magpie.php');
        global $CC_MAGPIE_PARSER;

        $CC_MAGPIE_PARSER = 'CCMagpieParser';

        $rss = fetch_rss($feed_url);

        // CCDebug::PrintVar($rss);

        return( $rss );
    }

    /**
    * Adds the cute orange buttons to the page
    *
    * @param string $tagstr Tags to add to hrefs of links
    */
    function AddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        if( !empty($tagstr) )
        {
            $tags = CCTag::TagSplit($tagstr);
            $utags = urlencode(implode(' ',$tags));
            $feed_url = ccl('feed','rss',$utags);
        }
        else
        {
            $feed_url = url_args( ccl('feed','rss'), $qstring );
        }
        $title = 'XML';
        $link_text = 'XML';
        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml', $feed_url, $title );
        CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', $feed_url, $title, $link_text,$help_text );
    }
  
    /**
    * Event hander to clear the feed cache
    * 
    * @param array $record Upload database record
    */
    function OnUploadDelete($record)
    {
        $this->_clear_cache($record);
    }

    /**
    * Event hander to clear the feed cache
    * 
    * @param integer $fileid Database ID of file
    */
    function OnFileDelete($fileid)
    {
        $this->_clear_cache($fileid);
    }

    /**
    * Event hander to clear the feed cache
    * 
    * @param integer $fileid Database ID of file
    */
    function OnUploadDone($fileid)
    {
        $this->_clear_cache($fileid);
    }

    /**
    * Cleans out the feed cache
    *
    * @param mixed $record_or_id Database record of ID of changed file (unused)
    */
    function _clear_cache($record_or_id)
    {
        $cache = new CCTable('cc_tbl_feedcache','feedcache_id');
        $cache->DeleteWhere('1');
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'feed/rss',  array( 'CCFeeds', 'GenerateRSSFromTags'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'podcast/page',  array( 'CCFeeds', 'PodcastPage'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'podcast/artist',  array( 'CCFeeds', 'PodcastUser'), CC_DONT_CARE_LOGGED_IN);
    }

    /**
    * Internal: Cache a feed into the database
    *
    * @param string $xml Actual feed text
    * @param string $type Feed format
    * @param string $tagstr Tags represented by this feed.
    */
    function _cache(&$xml,$type,$tagstr)
    {
        $args['feedcache_type'] = $type;
        $args['feedcache_tags'] = $tagstr;
        $args['feedcache_text'] = $xml;
        $cache = new CCTable('cc_tbl_feedcache','feedcache_id');
        $cache->Insert($args);
    }

    /**
    * Intrnal check the cache for a given type of feed for specific query
    *
    * @param string $type Feed format
    * @param string $tagstr Tag query
    */
    function _check_cache($type,$tagstr)
    {
        return;

        $where['feedcache_type'] = $type;
        $where['feedcache_tags'] = $tagstr;
        $cache = new CCTable('cc_tbl_feedcache','feedcache_id');
        $row = $cache->QueryRow($where);
        if( !empty($row) )
        {
            header("Content-type: text/xml");
            print($row['feedcache_text']);
            exit;
        }
    }

    /**
    * Generate and return a feed-ready set of records for a given tag query
    * 
    * @param string $tagstr Tag query
    * @returns string $xml Feed text
    */
    function & _get_tag_data($tagstr)
    {
        // sometimes (like for REST API) we just want the channel info

        if( empty($tagstr) )
            return( array() );

        $users =& CCUsers::GetTable();
        $username = '';
        $tags = CCTag::TagSplit($tagstr);
        foreach( $tags as $tag )
        {
            $where['user_name'] = $tag;
            if( $users->CountRows($where) == 1 )
            {
                $username = $tag;
                $tags = array_diff( $tags, array($username) );
                break;
            }
        }

        $uploads =& CCUploads::GetTable();
        
        $uploads->SetOffsetAndLimit(0,15);  // nice and arbitrary and hard
                                            // coded -- just like I like
                                            // my men

        $uploads->SetOrder('upload_date','DESC');

        if( !empty($tags) )
        {
            $tagstr = implode(',',$tags);
            $uploads->SetTagFilter($tagstr,'all');
        }

        if( empty($username) )
            $where = array();

        $records = $uploads->GetRecords($where);

        //CCDebug::PrintVar($tags);

        CCFeeds::PrepRecords($records);
        return($records);
    }

    function _cct($str)
    {
        return( preg_replace('&[^a-zA-Z0-9()!@#$%^*-_=+\[\];:\'\"\\.,/?~ ]&','',$str ) );
    }

    function PrepRecords(&$records)
    {
        $remix_api = new CCRemix();

        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $row =& $records[$i];
            if( array_key_exists('upload_date',$row) )
            {
                $time = strtotime($row['upload_date']);
                $row['rdf_pubdate'] = CCUtil::FormatDate(W3CDTF_FORMAT,$time);
                $row['rss_pubdate'] = CCUtil::FormatDate(RFC822_FORMAT,$time);
            }
            
            if( empty($row['stream_link']) )
            {
                $row['stream_link_html'] = '';
            }
            else
            {
                $url = $row['stream_link']['url'];
                $text = $row['stream_link']['text'];
                $row['stream_link_html'] = "<a href=\"$url\">$text</a>";
            }

            $row['upload_description'] = utf8_encode($this->_cct($row['upload_description']));
            $row['upload_name']        = utf8_encode($this->_cct($row['upload_name']));
            $row['user_real_name']     = utf8_encode($this->_cct($row['user_real_name']));

            $remix_api->OnUploadListing( $row );
        }

    }
}



?>