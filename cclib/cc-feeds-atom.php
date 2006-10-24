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
* Atom Module feed generator
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCFeedsAtom', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADD_FEED_LINKS,     array( 'CCFeedsAtom',  'OnAddFeedLinks')); 

// this is in the base class
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCFeedsAtom', 'OnApiQueryFormat')); 

define('CC_FEED_ATOM', 'atom');

/**
* Atom Feed generator and reader for site
*
* @package cchost
* @subpackage api
*/
class CCFeedsAtom extends CCFeed
{
    var $_feed_type = CC_FEED_ATOM;


    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type= CC_FEED_ATOM, $sub_title='')
    {
        $this->_gen_feed_from_records('atom_10.xml',$records,$tagstr,
                                      $feed_url,$cache_type,$sub_title);
    }

    function OnAddFeedLinks($tagstr,$qstring='',$help_text='')
    {
        if( !empty($tagstr) )
        {
            $tags = CCTag::TagSplit($tagstr);
            $utags = urlencode(implode(' ',$tags));
            $atom_feed_url = ccl('feed',CC_FEED_ATOM,$utags);
        }
        else
        {
            $atom_feed_url = url_args( ccl('feed',CC_FEED_ATOM), $qstring );
        }

        CCPage::AddLink( 'head_links', 'alternate', 'application/atom+xml', 
                         $atom_feed_url, "ATOM 1.0");
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('feed',CC_FEED_ATOM),  array( 'CCFeedsAtom', 'GenerateFeed'), 
			         CC_DONT_CARE_LOGGED_IN,ccs(__FILE__), '', 
            _('Feed generator RSS Podcast'), CC_AG_FEEDS );
    }

} // end of class CCFeeds


?>
