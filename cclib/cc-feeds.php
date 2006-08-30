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
* RSS Module feed generator
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCFeeds',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCFeeds',  'OnUploadDelete'));
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,    array( 'CCFeeds',  'OnFileDelete'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCFeeds',  'OnUploadDone'));
CCEvents::AddHandler(CC_EVENT_RENDER_PAGE,    array( 'CCFeeds',  'OnRenderPage'));

define('CC_FEED_RSS', 'rss');

/**
* RSS Feed generator and reader for site
*
* NOTE: Kill the cache for the menu if you are adding new menu items:
* http://cchost.localhost/?ccm=/media/admin/menu/killcache
* @package cchost
* @subpackage api
*
* TODO: Rename this to CCFeedsRSS20
* TODO: Rename the file cc-feeds-rss20.php
* TODO: extract the atom stuff that is tainting this to another class
* TODO: probably should abstract the interface parts of this as well.
*/
class CCFeeds extends CCFeed
{

    var $_feed_type = CC_FEED_RSS;

    /**
     * Handler for feed/rss - returns rss xml feed for given tags
     *
     * @param string $tagstr Space (or '+') delimited tags to use as basis of 
     * xml feed
     */
    function GenerateFeedFromTags($tagstr = '')
    {
        $this->_gen_feed_from_tags('rss_20.xml',$tagstr,CC_FEED_RSS);
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

            $sub_title = date(' (Y-d-m h:i:s a)');
        }
        elseif( !empty($_REQUEST['tags']) )
        {
            $tagstr = CCUtil::StripText($_REQUEST['tags']);
            $tagstr = str_replace(' ',',',urldecode($tagstr));
            if( empty($tagstr) )
                return;
            $uploads->SetTagFilter($tagstr,'all');
            $where = '';
            $sub_title = ' (' . $tagstr . ') ';
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
        $this->GenerateFeedFromRecords( $records, 
                                       _("Podcast this page") . $sub_title,
                                       ccl('podcast','page'),
                                       'podcast');
    }

    function PodcastUser($username='')
    {
        if( empty($username) )
            return;

        $uploads =& CCUploads::GetTable();
        $where['user_name'] = $username;
        $records = $uploads->GetRecords($where);
        $this->PrepRecords($records);
        $this->GenerateFeedFromRecords($records, 
	                               sprintf(_("Podcast for %s"), $username),
                                       ccl('podcast','page',$username), 
                                       'podcast');
    }

    /**
    * Handler for feed/rss - returns rss xml feed for given records
    *
    * @param array $records Results of some kind of uploads query
    * @param string $tagstr  Search string to display as part of description
    * @param string $feed_url The URL that represents this result set 
    */
    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type= CC_FEED_RSS)
    {
        $this->_gen_feed_from_records('rss_20.xml',$records,$tagstr,$feed_url,
                                      $cache_type);
    }

    /**
    * Adds the cute orange buttons to the page
    *
    * @param string $tagstr Tags to add to hrefs of links
    */
    function AddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        CCFeeds::_inner_add_feed_links($tagstr,$qstring,$help_text);
        global $CC_GLOBALS;
        $CC_GLOBALS['page-has-feed-links'] = 1;
    }

    function _inner_add_feed_links($tagstr,$qstring='',$help_text='')
    {
        if( !empty($tagstr) )
        {
            $tags = CCTag::TagSplit($tagstr);
            $utags = urlencode(implode(' ',$tags));
            $rss_feed_url  = ccl('feed','rss', $utags);
            $atom_feed_url = ccl('feed','atom',$utags);
        }
        else
        {
            $rss_feed_url = url_args( ccl('feed','rss'), $qstring );
            $atom_feed_url = url_args( ccl('feed','atom'), $qstring );
        }

        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml',
                         $rss_feed_url, "RSS 2.0");
        CCPage::AddLink( 'head_links', 'alternate', 'application/atom+xml', 
                         $atom_feed_url, "ATOM 1.0");

        CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', 
                         $rss_feed_url, "RSS 2.0", "xml",$help_text );
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

        if( $skip )
            return;

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        if( !empty($settings['default-feed-tags']) )
        {
            CCFeeds::_inner_add_feed_links($settings['default-feed-tags'],'',
                                           'Syndicate');
        }

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'feed/rss',  array( 'CCFeeds',
                          'GenerateFeedFromTags'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'podcast/page',  array( 'CCFeeds', 'PodcastPage'),
                          CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'podcast/artist',  array( 'CCFeeds', 'PodcastUser'),
                          CC_DONT_CARE_LOGGED_IN);
    }

} // end of class CCFeeds


?>
