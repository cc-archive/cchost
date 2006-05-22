<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file COPYING.
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
* Bass module for generating feeds
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCFeed', 'OnGetConfigFields') );

/**
*/
define ( 'NUM_FEED_ITEMS', 15 );

/**
* Abstract class to be used for generating feeds.
*
* @package cchost
* @subpackage api
*/
class CCFeed
{
    /**
     * @var boolean true if is full dump and false
     */
    var $is_dump;
    
    // No default constructor

  
    /**
    * Event hander for {@link CC_EVENT_DELETE_UPLOAD}
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
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    * 
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'} 
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id,$op)
    {
        $this->_clear_cache($upload_id);
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['feed-cache-flag'] =
               array(  'label'      => 'Feed Caching',
                       'form_tip'   => 'Feed caching can optimize replies for feed requests',
                       'value'      => '',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE);
        }
    }

    function _is_caching_on()
    {
        global $CC_GLOBALS;
        return( !empty($CC_GLOBALS['feed-cache-flag']) );
    }

    /**
    * Cleans out the feed cache
    *
    * @param mixed $record_or_id Database record of ID of changed file (unused)
    */
    function _clear_cache($record_or_id)
    {
        if( $this->_is_caching_on() )
        {
            $cache = new CCTable('cc_tbl_feedcache','feedcache_id');
            $cache->DeleteWhere('1');
        }
    }

    /**
    * Internal: Cache a generic feed into the database
    *
    * @param string $xml Actual feed text
    * @param string $type Feed format
    * @param string $tagstr Tags represented by this feed.
    */
    function _cache(&$xml,$type,$tagstr)
    {
        if( $this->_is_caching_on() )
        {
            $args['feedcache_type'] = $type;
            $args['feedcache_tags'] = $tagstr;
            $args['feedcache_text'] = $xml;
            $cache = new CCTable('cc_tbl_feedcache','feedcache_id');
            $cache->Insert($args);
        }
    }

    /**
    * Intrnal check the cache for a given type of feed for specific query
    *
    * @param string $type Feed format
    * @param string $tagstr Tag query
    */
    function _check_cache($type,$tagstr)
    {
        if( $this->_is_caching_on() )
        {
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
        
        if ( !$this->_is_full_dump() )
            $uploads->SetOffsetAndLimit(0,NUM_FEED_ITEMS);  

        $uploads->SetOrder('upload_date','DESC');

        if( !empty($tags) )
        {
            $tagstr = implode(',',$tags);
            $uploads->SetTagFilter($tagstr,'all');
        }

        if( empty($username) )
            $where = array();

        $records =& $uploads->GetRecords($where);

        //CCDebug::PrintVar($tags);

        CCFeed::PrepRecords($records);

        return $records;
    }

    /**
    * Returns true is user is requesting an entire dump
    *
    * If logged in as admin and the url has ?all=1 in it
    *
    * @return boolean $is_full_dump
    */
    function _is_full_dump()
    {
        return $this->is_dump || (CCUser::IsAdmin() && !empty($_REQUEST['all']) && ($_REQUEST['all'] == 1));
    }

    /**
    * Internal: strip string of any non-ascii characters
    * 
    * @param string $str String to clean
    */
    function _cct($str)
    {
        return( preg_replace('&[^a-zA-Z0-9()!@#$%^*-_=+\[\];:\'\"\\.,/?~ ]&','',$str ) );
    }

    /**
    * @access private
    */
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

    /**
    * Internal helper to generate XML from tags
    *
    * This method with print XML to the browser.
    *
    * @param string $template Relative path to template file to merge
    * @param string $tagstr Comma seperated list tags 
    * @param string $cache_type e.g. 'rss', 'atom', etc.
    * @see _gen_feed_from_records
    */
    function _gen_feed_from_tags($template, $tagstr, $cache_type)
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

        $this->_gen_feed_from_records( $template,
                                       $records,
                                       $tagstr,
                                       ccl('tags',$tagstr) . $qstring,
                                       $cache_type);
    }


    /**
    * Internal helper to generate XML from a set of records
    *
    * This method with print XML to the browser.
    *
    * @param string $template Relative path to template file to merge
    * @param array &$records Array of records to merge with template
    * @param string $feed_url URL that represents this feed
    * @param string $cache_type e.g. 'rss', 'atom', etc.
    */
    function _gen_feed_from_records($template,&$records,$tagstr,$feed_url,$cache_type)
    {
        global $CC_GLOBALS;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = utf8_encode($this->_cct($template_tags['site-title']));

        $args = $CC_GLOBALS;
        $args += $template_tags;

        $args['root_url'] = cc_get_root_url();
        $args['raw_feed_url'] = cc_get_root_url() . $_SERVER['REQUEST_URI'];

        if( empty($tagstr) )
        {
            $args['feed_url']            = $args['root_url'];
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
            $rssdate = CCUtil::FormatDate(CC_RFC822_FORMAT,time());
            $atomdate = CCUtil::FormatDate(CC_RFC3339_FORMAT,time());
        }
        else
        {
            $args['feed_items'] = $records;
            $rssdate    = $records[0]['rss_pubdate'];
            $atomdate   = $records[0]['atom_pubdate'];
        }

		$args['rss-build-date'] = 
		$args['rss-pub-date'] = $rssdate;
		$args['atom-build-date'] = 
		$args['atom-pub-date'] = $atomdate;


        $template = new CCTemplate( $CC_GLOBALS['template-root'] . $template, false ); // false means xml mode
        $xml = $template->SetAllAndParse( $args );
        if( !empty($records) && !empty($tagstr) )
            $this->_cache($xml,$cache_type,$tagstr);

        if( $this->_is_full_dump() )
        {
            header("Content-type: text/plain"); 
            $f = fopen('all_audio.xml','w');
            if( !$f )
            {
                print('could not open "all_audio.xml"');
            }
            else
            {
            fwrite($f,$xml);
            fclose($f);
                chmod('all_audio.xml',CC_DEFAULT_FILE_PERMS);
            print('all_audio.xml written to server');
        }
        }
        else
        {
            header("Content-type: text/xml"); // this should enforce a utf-8
            print($xml);
        }

        exit;
    }

    /**
    * Format a set of records for UTF-8 feed output
    *
    * @param array &$records Array of records to massage
    */
    function PrepRecords(&$records)
    {
        $remix_api = new CCRemix();

        $keys = array_keys($records);
        $count = count($keys);
        for( $i = 0; $i < $count; $i++ )
        {
            $key = $keys[$i];
            $row =& $records[$key];
            if( array_key_exists('upload_date',$row) )
            {
                $time = strtotime($row['upload_date']);
                $row['atom_pubdate'] = CCUtil::FormatDate(CC_RFC3339_FORMAT,$time);
                $row['rss_pubdate']  = CCUtil::FormatDate(CC_RFC822_FORMAT,$time);
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

            // a bit of a hack but it could have been worse I guess:
            // if there is a formatter installed, it was supposed to
            // put a 'clean' version of the description in the '_text'
            // field...

            if( !empty($row['upload_description_text']) )
                $row['upload_description'] =  $row['upload_description_text'];

            $row['upload_description'] = utf8_encode($this->_cct($row['upload_description']));
            $row['upload_name']        = utf8_encode($this->_cct($row['upload_name']));
            $row['user_real_name']     = utf8_encode($this->_cct($row['user_real_name']));

            $remix_api->OnUploadListing( $row );
        }

    }
}



?>
