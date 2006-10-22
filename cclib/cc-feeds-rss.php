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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCFeedsRSS',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADD_FEED_LINKS,     array( 'CCFeedsRSS',  'OnAddFeedLinks')); 

// this is in the base class
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCFeedsRSS',  'OnApiQueryFormat')); 

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
class CCFeedsRSS extends CCFeed
{
    var $_feed_type = CC_FEED_RSS;

    function PodcastPage()
    {
        $query = new CCQuery();

        $args['format']       = CC_FEED_RSS;
        $args['sub_title']   = _("Podcast this page");
        $args['feed_url']     = ccl('podcast','page');
        
        $args = $query->ProcessUriArgs($args);

        if( !empty($args['ids']) )
        {
            $args['limit']  = 200; // @todo yea, yea, for now... 
        }
        elseif( !empty($args['tags']) )
        {
            $args['limit']  = CC_FEED_NUM_ITEMS;
        }
        else
        {
            CCUtil::Send404();
        }

        $query->Query($args);
    }

    function PodcastUser($username='')
    {
        if( empty($username) )
            return;

        $query = new CCQuery();
        
        $args['user']   = CCUtil::Strip($username);
        $args['format'] = 'rss';
        $args['limit']  = 0;
        $args['sub_title'] = sprintf(_("Podcast for %s"), $username);
        $args['feed_url'] = ccl('podcast','page',$username);

        $args = $query->ProcessUriArgs($args);
        $query->Query($args);
    }

    /**
    * Handler for feed/rss - returns rss xml feed for given records
    *
    * @param array $records Results of some kind of uploads query
    * @param string $tagstr  Search string to display as part of description
    * @param string $feed_url The URL that represents this result set 
    */
    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type= CC_FEED_RSS, $sub_title='')
    {
        $this->_gen_feed_from_records('rss_20.xml',$records,$tagstr,$feed_url,
                                      $cache_type, $sub_title);
    }

    function OnAddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        if( !empty($tagstr) )
        {
            $tags = CCTag::TagSplit($tagstr);
            $utags = urlencode(implode(' ',$tags));
            $rss_feed_url  = ccl('feed','rss', $utags);
        }
        else
        {
            $rss_feed_url = url_args( ccl('feed','rss'), $qstring );
        }

        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml',
                         $rss_feed_url, "RSS 2.0");

        CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', 
                         $rss_feed_url, "RSS 2.0", "xml",$help_text );
    }

    /**
    * @deprecated Use CCFeed::AddFeedLinks instead
    */
    function AddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        return parent::AddFeedLinks($tagstr,$qstring,$help_text);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('feed',CC_FEED_RSS),  array( 'CCFeedsRSS',
                          'GenerateFeed'), CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('podcast','page'),  array( 'CCFeedsRSS', 'PodcastPage'),
                          CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('podcast','artist'),  array( 'CCFeedsRSS', 'PodcastUser'),
                          CC_DONT_CARE_LOGGED_IN);
    }

} // end of class CCFeedsRSS


?>