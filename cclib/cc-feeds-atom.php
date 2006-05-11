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

/**
* Atom Feed generator and reader for site
*
* @package cchost
* @subpackage api
*/
class CCFeedsAtom extends CCFeed
{
    function GenerateAtomFromRecords(&$records,$tagstr,$feed_url,$cache_type='atom')
    {
        $this->_gen_feed_from_records('atom_10.xml',$records,$tagstr,$feed_url,$cache_type);
    }

    function GenerateAtomFromTags($tagstr='')
    {
        $this->_gen_feed_from_tags('atom_10.xml',$tagstr,'rss');
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('feed','atom'),  
                          array( 'CCFeedsAtom', 'GenerateAtomFromTags'), CC_DONT_CARE_LOGGED_IN);
    }

} // end of class CCFeeds


?>
