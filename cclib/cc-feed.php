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
 * Base module for generating feeds
 *
 * @package cchost
 * @subpackage api
 *
 * TODO: Finish abstracting the feed classes into this one.
 */

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCFeed', 'OnGetConfigFields') );

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


/**
 */
//define('CC_FEED_CACHE_ON', true);

/**
 * Abstract class to be used for generating feeds.
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

    /**
     * Internal: Checks to see if the cache is on or off.
     *
     * @returns boolean <code>true</code> if on or <code>false</code> otherwise
     */
    function _is_caching_on()
    {
        global $CC_GLOBALS;
        return( !empty($CC_GLOBALS['feed-cache-flag']) );
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
     * Internal: Generate and return a feed-ready set of records for a given 
     * tag query
     *
     * @param string $tagstr Tag query
     * @returns string $xml Feed text
     */
    function & _get_tag_data($tagstr)
    {
        // sometimes (like for REST API) we just want the channel info

        if( empty($tagstr) )
        {
            $a = array();
            return $a;
        }

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
            $uploads->SetOffsetAndLimit(0,CC_FEED_NUM_ITEMS);  

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
        return $this->_is_dump || (CCUser::IsAdmin() && !empty($_REQUEST['all']) && ($_REQUEST['all'] == 1));
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
    function _gen_feed_from_tags($template, $tagstr, $cache_type = 'rss')
    {
        $qstring = '';

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
                        $tagstr = sprintf(_('Remixes of %s'), $username);
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
                        $tagstr = sprintf(_('Uploads remixed by %s'), $username);
                    }
                }
            }
        }
        else
        {
            $tagstr = str_replace(' ',',',urldecode($tagstr));
            $this->_check_cache($cache_type,$tagstr);
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

        $this->_output_xml($xml);
        // testing against user agent tests if we are through web browser
        if ( isset($_SERVER["HTTP_USER_AGENT"]) )
            exit(0);
        // }
    }

    /**
     * Either outputs xml to screen or to a file from the class.
     * @returns boolean
     */
    function _output_xml (&$xml)
    {
        if( $this->_is_full_dump() )
        {
            header("Content-type: text/plain");

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
                if (isset($_REQUEST['all']))
                    echo sprintf(_('%s written to server'), 
                                 $dump_file_path);
                return true;
            }
        }
        else
        {
            header("Content-type: text/xml"); // this should enforce a utf-8
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

    /** 
     * Generates a feed from tags generically.
     *
     * @param string $tags Tags used for generating the feed.
     * @return mixed Either true or false, or big xml dump.
     */
    function GenerateFeed ($tags='')
    {
        $tags = str_replace(' ',',',urldecode($tags));
        // check_cache already checks to see if it is on
        $this->_check_cache($this->GetFeedType(),$tags);
        $qstring = '';

        /*
        if( empty($tags) )
            $tags = CC_FEED_DEFAULT_TAG;
        else
            $tags .= ',' . CC_FEED_DEFAULT_TAG;
        */
        $records =& $this->_get_tag_data($tags);

        return ( $this->GenerateFeedFromRecords( $records,
                                                 $tags,
                                                 ccl('tags',$tags) . $qstring));
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



?>
