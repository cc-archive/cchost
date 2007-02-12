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
 * Base module for generating feeds and handling generic feed events
 *
 * @package cchost
 * @subpackage api
 *
 * TODO: Finish abstracting the feed classes into this one.
 */

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * The number of items in a feed.
 */
define ( 'CC_FEED_NUM_ITEMS', 15 );

/**
 * The folder where data is dumped.
 */
define ( 'CC_DUMP_DIR', 'dump' );

/**
 * The name of the file where audio is dumped.
 */
define ( 'CC_FEED_DUMP_FILE', 'dump.xml' );

/**
 * The default tag if there is no tag. CURRENTLY NOT USED!
 */
define ( 'CC_FEED_DEFAULT_TAG', 'audio' );

require_once('cclib/cc-query.php');

/**
 */
//define('CC_FEED_CACHE_ON', true);

/**
 * Bass class to be used for generating feeds.
 *
 * @package cchost
 * @subpackage api
 */
class CCFeed
{
    /**
     * This has a string identifier listing the feed type.
     * @var		string
     * @access	private
     */
    var $_feed_type;

    /**
     * @var boolean true if is full dump and false otherwise
     * @var boolean
     * @access private
     */
    var $_is_dump;

    /**
     * This is the file where all audio is dumped.
     * @var	string
     * @access	private
     */
    var $_dump_file_name; 

    // No default constructor

    //---------------------------
    // EVENT HANDLERS 
    //---------------------------

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
               array(  'label'      => _('Feed Caching'),
                       'form_tip'   =>
                          _('Feed caching can optimize replies for feed requests'),
                       'value'      => '',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE);
        }
    }

    //
    // Derived class call here, otherwise we'd lose the 'this' pointer
    //
    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        if( $args['format'] != $this->_feed_type )
            return;

        if( empty($args['feed_url']) )
        {
            $qstring = '';
            $amp = '';
            foreach( $args as $K => $V )
            {
                if( !empty($V) )
                    $qstring .= $amp . $K . '=' . $V;
                $amp = '&';
            }
            $args['feed_url'] = url_args( ccl('api','query'), $qstring );
        }

        if( !empty($args['ids']) )
        {
            $ids = $args['ids'];
            $st[] = $args['sub_title'];
            $st[] = dechex(crc32(is_array( $ids ) ? join(';',$ids) : $ids));
            $sub_title = join(' ', $st);
        }
        else if( empty($args['sub_title']) )
        {
            if( $args['remixesof'] )
                $sub_title = sprintf( _('Remixes of %s'), $args['remixesof'] );
            elseif( $args['remixedby'] )
                $sub_title = sprintf( _('Remixed by %s'), $args['remixedby'] );
            elseif( $args['tags'] )
                $sub_title = $args['tags'];
            else
                $sub_title = '';
        }

        if( !empty($sub_title) )
            $args['sub_title'] = $sub_title;

        if( !empty($args['caller_feed']) )
        {
            $this->SetIsDump( $args['caller_feed']->GetIsDump() );
            $this->SetDumpFileName( $args['caller_feed']->GetDumpFileName() );
        }

        $this->PrepRecords($records);
        // this call exits the session:
        $this->GenerateFeedFromRecords( $records, 
                                        $args['tags'], 
                                        $args['feed_url'],
                                        $this->_feed_type,
                                        empty($args['sub_title']) ? '' : $args['sub_title'] );
    }


    /**
     * Internal: Checks to see if the cache is on or off.
     *
     * @returns boolean <code>true</code> if on or <code>false</code> otherwise
     */
    function _is_caching_on()
    {
        global $CC_GLOBALS;

        return !$this->_is_full_dump() && 
                !empty($CC_GLOBALS['feed-cache-flag']) 
              ;
    }

    /**
     * Internal: Cleans out the feed cache
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
     * Internal: check the cache for a given type of feed for specific query
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
     * Returns true is user is requesting an entire dump
     *
     * If logged in as admin and the url has ?all=1 in it
     *
     * @return boolean $is_full_dump
     */
    function _is_full_dump()
    {
        return $this->_is_dump;
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
     * Internal helper to generate XML from a set of records
     *
     * This method with print XML to the browser.
     *
     * @param string $template Relative path to template file to merge
     * @param array &$records Array of records to merge with template
     * @param string $records Array of records to merge with template
     * @param string $feed_url URL that represents this feed
     * @param string $cache_type e.g. 'rss', 'atom', etc.
     */
    function _gen_feed_from_records( $template, 
                                     &$records,
                                      $tagstr,
                                      $feed_url,
                                      $cache_type,
                                      $feed_sub_title='')
    {
        global $CC_GLOBALS;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = utf8_encode($this->_cct($template_tags['site-title']));

        $args = $CC_GLOBALS;
        $args += $template_tags;

        $args['root_url'] = cc_get_root_url();
        $args['raw_feed_url'] = CCUtil::HTMLEncode(cc_get_root_url() . $_SERVER['REQUEST_URI']);

        if( empty($feed_sub_title) )
        {
            $args['channel_title'] = 
            $args['feed_subject']  = $site_title;
        }
        else
        {
            $args['channel_title'] = 
            $args['feed_subject'] = "$site_title ($feed_sub_title)";
        }

        if( empty($feed_url) )
        {
            $args['feed_url'] = $args['root_url'];
        }
        else
        {
            $args['feed_url'] = $feed_url;
        }
        
        $args['feed_url'] = htmlentities($args['feed_url']);

        $args['channel_description'] = utf8_encode($this->_cct($template_tags['site-description']));

        if( empty($records) )
        {
            $rssdate = CCUtil::FormatDate(CC_RFC822_FORMAT,time());
            $atomdate = CCUtil::FormatDate(CC_RFC3339_FORMAT,time());
        }
        else
        {
            $args['feed_items'] = $records;
            $first_index = key($records);
            reset($records);
            $rssdate    = $records[$first_index]['rss_pubdate'];
            $atomdate   = $records[$first_index]['atom_pubdate'];
        }

		$args['rss-build-date'] = 
		$args['rss-pub-date'] = $rssdate;
		$args['atom-build-date'] = 
		$args['atom-pub-date'] = $atomdate;

        $tfile = CCTemplate::GetTemplate($template);
        $template = new CCTemplate( $tfile, false ); // false means xml mode
        $xml = $template->SetAllAndParse( $args );
        if( !empty($records) && !empty($tagstr) )
        {
            // this will exit the session if feed is 
            // in the cache.
            $this->_cache($xml,$cache_type,$tagstr);
        }

        # forces xml declaration line if not the first 5 characters
        if( substr($xml,0,5) != '<?xml' )
            $xml = '<?xml version="1.0" encoding="utf-8" ?>' . "\n" . $xml;

        $this->_output_xml($xml);
        exit(0);
    }

    /**
     * Either outputs xml to screen or to a file from the class.
     * @returns boolean
     */
    function _output_xml (&$xml)
    {

        if( $this->_is_full_dump() )
        {
            if ( ! is_dir(CC_DUMP_DIR) ) {
                if ( ! mkdir(CC_DUMP_DIR, cc_default_dir_perm()) ) {
                    echo sprintf(_('Could not open folder "%s"'), CC_DUMP_DIR);
                    return false;
                }
            }
            $dump_file_path = ccp(CC_DUMP_DIR, $this->GetDumpFileName() );

            $f = fopen($dump_file_path,'w');
            if( !$f )
            {
                echo sprintf(_('Could not open "%s"'), $dump_file_path);
                return false;
            }
            else
            {
                fwrite($f,$xml);
                fclose($f);
                chmod($dump_file_path,cc_default_file_perms());
                return true;
            }
        }
        else
        {
            // this should enforce a utf-8
            header("Content-type: text/xml; charset=" . CC_ENCODING); 
            print($xml);
        }
    }

    /**
    * Format a set of records for UTF-8 feed output
    *
    * @param array &$records Array of records to massage
    */
    function PrepRecords(&$records)
    {
        global $CC_GLOBALS;

        // this is a hack for upload listing
        $CC_GLOBALS['works_page'] = true;

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

            if( !empty($row['upload_description_html']) )
            {
                $row['upload_description_html'] = utf8_encode($this->_clean_urls($row['upload_description_html'])) ;
            }

            $row['upload_description'] = utf8_encode($this->_cct($row['upload_description']));
            $row['upload_name']        = utf8_encode($this->_cct($row['upload_name']));
            $row['user_real_name']     = utf8_encode($this->_cct($row['user_real_name']));

            CCEvents::Invoke( CC_EVENT_UPLOAD_LISTING, array( &$row ) );
        }

    }

    function SetQueryOptions($options)
    {
        $this->_query_opts = $options;
    }

    function GetQueryOptions()
    {
        return isset($this->_query_opts) ? $this->_query_opts : array();
    }

    function GetLimit()
    {
        return CC_FEED_NUM_ITEMS;
    }

    /** 
     * Generates a feed from tags generically.
     *
     * @param string $tags Tags used for generating the feed.
     * @return mixed Either true or false, or big xml dump.
     */
    function GenerateFeed ($tags='')
    {
        $type = $this->GetFeedType();

        $is_remix_feed = !empty($_GET['remixesof']) || !empty($_GET['remixedby']);
        
        if( !empty($tags) )
            $args['tags'] = $tags;
        $args['format']   = $type;
        $args['feed_url'] = ccl('feed',$type,$tags);
        if( !$is_remix_feed )
        {
            $args['limit']  = $this->GetLimit();
            if( !empty($_GET['limit']) )
            {
                $g_limit = CCUtil::Strip($_GET['limit']);
                if( intval($g_limit) > 0 && $g_limit < $args['limit'] )
                    $args['limit'] = $g_limit;
            }
        }

        $query = new CCQuery();
        $args = $query->ProcessUriArgs($args);

        // check_cache already checks to see if it is on
        // this will exit session if found
        $this->_check_cache($type,$args['tags']);
        
        $args = array_merge( $args, $this->GetQueryOptions() );

        if( !$is_remix_feed && empty($args['tags']) && !$this->GetIsDump() )
        {
            // this is for backwards compat with <3.1 in which
            // the sample pool api would call this method with
            // an empty tagstr on purpose to generate an empty
            // RSS header with channel info
            // 
            $fake_recs = array();

            // So we pretend like we've been called back from the
            // query engine...

            // this method will exit the session
            $this->OnApiQueryFormat( $fake_recs, $args, $result, $result_mime );
        }

        $args['caller_feed'] = $this;

        $query->Query($args);
    }

    /**
    * @deprecated Use GenerateFeed instead
    */
    function GenerateFeedFromTags($tags='')
    {
        $this->GenerateFeed($tags);
    }

    /**
     * Returns if this object is a dump or not.
     */
    function GetIsDump ()
    {
        return $this->_is_dump;
    }

    /**
     * This sets the variable $_is_dump.
     * @param boolean $is_dump Is this feed a dump or the incremental type.
     */
    function SetIsDump ($is_dump = false)
    {
        $this->_is_dump = $is_dump;
    }

    /**
     * @returns string A string that is the dump filename internally.
     */
    function GetDumpFileName ()
    {
        if ( empty($this->_dump_file_name) )
            $this->SetDumpFileName();
        return $this->_dump_file_name;
    }

    /**
     * This sets the variable $_dump_file_name.
     * @param string $dump_file_name The filename for data to be dumped to.
     */
    function SetDumpFileName ($dump_file_name = '')
    {
        if (empty($dump_file_name)) {
            $dump_file_name = $this->_feed_type . ".xml";
        }
        $this->_dump_file_name = $dump_file_name;
    }

    /**
     * Gets the feed type of this feed.
     * @returns string A string that is the dump filename internally.
     */
    function GetFeedType ()
    {
        return $this->_feed_type;
    }

    /**
    * Adds the cute orange buttons to the page
    *
    * DO NOT CALL THIS from a CC_EVENT_ADD_FEED_LINKS event handler
    * That will call recursion. Feeds should call CCPage::AddLink
    * to add their links. Everybody else calls this.
    *
    * @param string $tagstr Tags to add to hrefs of links
    */
    function AddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        CCFeed::_inner_add_feed_links($tagstr,$qstring,$help_text);
        global $CC_GLOBALS;
        $CC_GLOBALS['page-has-feed-links'] = 1;
    }

    function _inner_add_feed_links($tagstr,$qstring='',$help_text='')
    {
        CCEvents::Invoke( CC_EVENT_ADD_FEED_LINKS, array( $tagstr, $qstring, $help_text) );
    }

    /**
    * Event handler for {@link CC_EVENT_RENDER_PAGE}
    *
    */
    function OnRenderPage()
    {
        global $CC_GLOBALS;
        $skip = array_key_exists('page-has-feed-links',$CC_GLOBALS) &&
                    $CC_GLOBALS['page-has-feed-links'] == 1;

        // If our AddLinks was already called directly (perfectly
        // reasonable) then we don't want this to happen

        if( $skip )
            return;

        // If our AddLinks was NOT already called directly then
        // if the admin has a 'default feed' they to display

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        if( !empty($settings['default-feed-tags']) )
        {
            CCFeed::_inner_add_feed_links($settings['default-feed-tags'],'',
                                           _('Syndicate'));
        }

    }

    function _clean_urls($text)
    {
        return preg_replace_callback("/(?:href|title)\s?=['\"][^'\"]+\?([^'\"]+)['\"]/U",'_cc_encode_feed_url', $text);
    }

    /**
     * This sets the feed type as a string which is used in various places
     * like identifying the cache and other necessary strings for referencing
     * this feed type.
     *
     * @param string $feed_type This is the type of feed
     */
/*
    function SetFeedType ($feed_type)
    {
        $this->_feed_type = $feed_type;
    }
*/

} // end of primarily abstract class CCFeed

function _cc_encode_feed_url($m) 
{
    return str_replace($m[1],urlencode($m[1]),$m[0]);
}

?>
