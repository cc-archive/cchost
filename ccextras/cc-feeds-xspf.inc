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
* XSPF Module feed generator
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


define('CC_FEED_XSPF', 'xspf');

require_once('cclib/cc-feed.php');

/**
* Atom Feed generator and reader for site
*
* @package cchost
* @subpackage api
*/
class CCFeedsXSPF extends CCFeed
{
    var $_feed_type = CC_FEED_XSPF;

    function GetMaxItems()
    {
        return 100; // eh, why not
    }

    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type= CC_FEED_XSPF,$sub_title='')
    {
        global $CC_GLOBALS;

        if( empty($sub_title) )  // backwords compat
            $sub_title = $tagstr;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = utf8_encode($this->_cct($template_tags['site-title']));

        $args = $CC_GLOBALS;
        $args += $template_tags;

        $args['root_url'] = cc_get_root_url();
        $args['raw_feed_url'] = htmlentities(cc_get_root_url() . $_SERVER['REQUEST_URI']);
        $args['sub_title'] = $sub_title;


        if( empty($feed_url) )
        {
            $args['feed_url'] = cc_get_root_url();
        }
        else
        {
            $args['feed_url'] = $feed_url;
        }

        if( empty($sub_title) )
        {
            $args['channel_title']       = $site_title;
            $args['feed_subject']        = $site_title;
        }
        else
        {
            $args['channel_title'] = "$site_title ($sub_title)";
            $args['feed_subject'] = "$site_title ($sub_title)";
        }

        $args['feed_url'] = htmlentities($args['feed_url']);

        $args['channel_description'] = utf8_encode($this->_cct($template_tags['site-description']));

        if( empty($records) )
        {
            $date = CCUtil::FormatDate(CC_RFC822_FORMAT,time());
        }
        else
        {
            $keys = array_keys($records);
            $c = count($keys);
            for( $i = 0; $i < $c; $i++ )
            {
                $R =& $records[ $keys[$i] ];
                $R['download_url'] = empty($R['fplay_url']) ? $R['files'][0]['download_url'] : $R['fplay_url'];
            }
            $args['feed_items'] = $records;
            $date = $records[0]['rss_pubdate'];
        }

        $args['rss-build-date'] = 
        $args['rss-pub-date'] = $date;

        // to build a registration link
        $args['home_registration'] = $args['home-url'] . 'register';

        $template = new CCTemplate( 'xspf_10.xml', false ); // false means xml mode

        $xml = $template->SetAllAndParse( $args );

        if( $this->_is_caching_on() && empty($records) && !empty($tagstr) )
            $this->_cache($xml,$cache_type,$tagstr);

        $this->_output_xml($xml);
    	exit;
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('feed','xspf'),  array( 'CCFeedsXSPF', 'GenerateFeed'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[tags]?query_param', _('Generate XSPF playlist'),
            CC_AG_FEEDS );
    }

} // end of class CCFeeds


?>
