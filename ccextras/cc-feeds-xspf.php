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
* $Id: cc-feeds-atom.php 3503 2006-06-18 07:09:30Z kidproto $
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


CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCFeedsXSPF', 'OnMapUrls'));

define('CC_FEED_XSPF', 'xspf');

/**
* Atom Feed generator and reader for site
*
* @package cchost
* @subpackage api
*/
class CCFeedsXSPF extends CCFeed
{
    var $_feed_type = CC_FEED_XSPF;

    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type= CC_FEED_XSPF)
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
            $date = CCUtil::FormatDate(CC_RFC822_FORMAT,time());
        }
        else
        {
            $args['feed_items'] = $records;
            $date = $records[0]['rss_pubdate'];
        }

        $args['rss-build-date'] = 
        $args['rss-pub-date'] = $date;

        // to build a registration link
        $args['home_registration'] = $args['home-url'] . 'register';

        for ($i=0; $i < count($args['feed_items']); $i++)
        {
            // make up a release date YYYYMMDD
            if ( $args['feed_items'][$i]['upload_date'] )
                $args['feed_items'][$i]['upload_date_fmt'] =
                    date("Ymd", 
                        strtotime($args['feed_items'][$i]['upload_date']));


            for ($j=0; $j < count($args['feed_items'][$i]['files']); $j++)
            {
                $file = &$args['feed_items'][$i]['files'][$j];
                $format_info = 
                 &$args['feed_items'][$i]['files'][$j]['file_format_info'];

                if( empty($format_info['ch']) )
                {
                    $format_info['ch_num'] = 0;
                }
                else
                {
                    switch ( $format_info['ch'] )
                    {
                        case 'mono':
                            $format_info['ch_num'] = 1;
                            break;
                        case 'stereo':
                            $format_info['ch_num'] = 2;
                            break;
                        default:
                            $format_info['ch_num'] = 
                                $format_info['ch'];
                    }
                }

                if ( !empty($format_info['sr']) )
                    $format_info['sr_num'] = 
                        str_replace('k', '', $format_info['sr']);
            }
        }

        // CCDebug::PrintVar( $args );

        $template = new CCTemplate( 'xspf_10.xml', false ); // false means xml mode

        $xml = $template->SetAllAndParse( $args );

        if( $this->_is_caching_on() && empty($records) && !empty($tagstr) )
            $this->_cache($xml,$cache_type,$tagstr);

        $this->_output_xml($xml);
    	exit;


        // $this->_gen_feed_from_records('xspf_10.xml',$records,$tagstr,
        //                              $feed_url,$cache_type);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('feed','xspf'),  
                          array( 'CCFeedsXSPF', 'GenerateFeed'), 
			         CC_DONT_CARE_LOGGED_IN);
    }

} // end of class CCFeeds


?>
