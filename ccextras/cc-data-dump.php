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
* @package cchost
* @subpackage api
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS, 
    array( 'CCDataDump', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,
    array( 'CCDataDump',  'OnUploadDelete'));
CCEvents::AddHandler(CC_EVENT_DELETE_FILE,
    array( 'CCDataDump',  'OnFileDelete'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,
    array( 'CCDataDump',  'OnUploadDone'));

/**
*/
define('CCDATADUMP_CACHE_ON', true);

/**
* XML Feed generator for xml format for audio
*
* NOTE: Kill the cache for the menu if you are adding new menu items:
* http://cchost.localhost/?ccm=/media/admin/menu/killcache
*/
class CCDataDump extends CCFeed
{
    /**
    * Handler for feed/rss - returns rss xml feed for given records
    *
    * @param array $records Results of some kind of uploads query
    * @param string $tagstr  Search string to display as part of description
    * @param string $feed_url The URL that represents this result set 
    */
    function GenerateFeedFromRecords(&$records,$tagstr,$feed_url,
                                     $cache_type='datadump')
    {
        global $CC_GLOBALS;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = utf8_encode($this->__($template_tags['site-title']));

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

        $args['channel_description'] = utf8_encode($this->__($template_tags['site-description']));

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

                if ( $format_info['sr'] )
                    $format_info['sr_num'] = 
                        str_replace('k', '', $format_info['sr']);
            }
        }

        // CCDebug::PrintVar( $args );

        $template = new CCTemplate( $CC_GLOBALS['template-root'] . 'datadump.xml', false ); // false means xml mode

        $xml = $template->SetAllAndParse( $args );

        if( CCDATADUMP_CACHE_ON && empty($records) && !empty($tagstr) )
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
        CCEvents::MapUrl( 'feed/datadump',
                          array( 'CCDataDump', 'GenerateFeed'),
                          CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Internal: Cache an rss feed into the database
    *
    * @see   CCFeed::_cache()
    * @param string $xml Actual feed text
    * @param string $type Feed format
    * @param string $tagstr Tags represented by this feed.
    */
    function _cache(&$xml,$type,$tagstr)
    {
        if( $type != 'datadump' )
            return;
        parent::_cache($xml, $type, $tagstr);
    }

}

?>
