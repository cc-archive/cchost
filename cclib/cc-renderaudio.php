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
* @package cchost
* @subpackage audio
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Some linux players prevent us from passing this 
* number in as part of the GET argument so we
* hard wire it here.
*/
define('RADIO_PROMO_INTERVAL', 4); 

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,     array( 'CCRenderAudio', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,      array( 'CCRenderAudio', 'OnUploadRow'));
//CCEvents::AddHandler(CC_EVENT_CONTEST_ROW,     array( 'CCRenderAudio', 'OnContestRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,        array( 'CCRenderAudio', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_LISTING_RECORDS, array( 'CCRenderAudio', 'OnListingRecords')); 

/**
*/
class CCRenderAudio extends CCRender
{
    /**
    * Handler for {@link CC_EVENT_LISTING_RECORDS}
    *
    * @param array $records Array of records being displayed
    */
    function OnListingRecords(&$records)
    {
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            //if( CCUploads::IsMediaType($records[$i],'audio') )
            if( CCUploads::InTags('audio',$records[$i]) )
            {
                CCPage::PageArg('can_stream_page',true);
                return;
            }
        }
    }

    function StreamPage()
    {
        $sort_order = array();

        if( !empty($_REQUEST['ids']) )
        {
            $ids = $_REQUEST['ids'];
            $ids = explode(';',$ids);
            $ids = array_unique($ids);
            $where_id = array();
            foreach($ids as $id)
                $where_id[] = " (upload_id = $id) ";
            $where = implode('OR',$where_id);
            $i = 0;
            foreach($ids as $id)
            {
                $sort_order[$id] = $i++;
                $where_id[] = " (upload_id = $id) ";
            }
            $where = implode('OR',$where_id);
            if( empty($_REQUEST['nosort']) )
                $sort_order = array();
            $tags = '';
        }
        elseif( !empty($_REQUEST['tags']) )
        {
            $tags = CCUtil::StripText($_REQUEST['tags']);
            $tags = str_replace(' ',',',urldecode($tags));
            if( empty($tags) )
                return;
            $where = '';
        }
        else
        {
            return;
        }

        $this->_stream_files($where,$tags,'',$sort_order);
    }

    function StreamRadio()
    {
        if( !empty($_REQUEST['tags']) )
        {
            $tags = CCUtil::StripText($_REQUEST['tags']);
            $tags = str_replace(' ',',',urldecode($tags));
        }

        if( empty($tags) )
            return;

        $this->_stream_files('',$tags,'',null,true);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'contest/streamsource', array('CCRenderAudio', 'StreamContestSource'),  CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/stream',       array('CCRenderAudio', 'StreamContest'),        CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'files/stream',         array('CCRenderAudio', 'StreamFiles'),          CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'stream/page',          array('CCRenderAudio', 'StreamPage'),           CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'stream/radio',         array('CCRenderAudio', 'StreamRadio'),          CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Event handler for {@link CC_EVENT_CONTEST_ROW}
    *
    * @param array &$record Contest row to massage before display
    */
    function OnContestRow(&$record)
    {
        $contest = $record['contest_short_name'];

        if( $this->_contest_has_audio($record['contest_id'],CCUD_CONTEST_ALL_SOURCES) )
		{
			$record['render_source_link'] = 
					 array(  'href'     => ccl( 'contest', 'streamsource', $contest . '.m3u' ),
							 'title'    => _('Stream Sources'),
							 'id'       => 'cc_streamfile' );
		}

        if( $this->_contest_has_audio($record['contest_id'],CCUD_CONTEST_ENTRY ) )
		{
			if( CCUser::IsAdmin() || $record['contest_can_browse_entries'] )
			{
				$record['render_entries_link'] = 
					 array(  'title'  => _('Stream Entries'),
							 'id'         => 'cc_streamfile',
							 'href'     => ccl( 'contest', 'stream', $contest. '.m3u' ) );
			}
		}
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow(&$record)
    {
        if( empty($record['stream_link']) )
        {
            $link = $this->_get_stream_link($record);
            if( !empty($link) )
                $record['stream_link'] = $link;
        }
    }

    function _get_stream_link(&$record)
    {
        if( !CCUploads::InTags('audio',$record) )
            return(null);

        //if( !CCUploads::IsMediaType($record,'audio') )
        //    return(null);

        if( empty($record['contest_id']) )
            $fakename = $record['user_name'];
        else
            $fakename = $record['contest_short_name'];

        $link['url'] = ccl( 'files', 'stream', 
                                    $fakename, 
                                    $record['upload_id']. '.m3u' );
        $link['text'] = _('Stream');

        return($link);
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu()
    */
    function OnUploadMenu(&$menu,&$record)
    {
        $link = $this->_get_stream_link($record);
        if( empty($link) || !empty($record['upload_banned']) )
            return;

        $menu['stream'] = 
                 array(  'menu_text'  => $link['text'],
                         'weight'     => -1,
                         'group_name' => 'play',
                         'id'         => 'cc_streamfile',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => $link['url'] );
    }


    function StreamContestSource($contest_with_m3u)
    {
        list( $name ) = explode('.',$contest_with_m3u);
        $this->_stream_contest_files($name,CCUD_CONTEST_ALL_SOURCES);
    }

    function StreamContest($contest_with_m3u)
    {
        list( $contest_short_name ) = explode('.',$contest_with_m3u);
        $contests =& CCContests::GetTable();
        $record =& $contests->GetRecordFromShortName($contest_short_name);
        if( CCUser::IsAdmin() || $record['contest_can_browse_entries'] )
            $this->_stream_contest_files($contest_short_name,CCUD_CONTEST_ENTRY);
        else
            CCUtil::AccessError(__FILE__,__LINE__);
    }

    function _stream_contest_files($contest_short_name,$systags)
    {
        $contests =& CCContests::GetTable();
        $contest_id = $contests->GetIDFromShortName($contest_short_name);
        $where['upload_contest'] = $contest_id;
        $this->_stream_files($where,$systags);
    }

    function StreamFiles($user,$upload_id_with_m3u)
    {
        list( $upload_id ) = explode('.',$upload_id_with_m3u);
        $where['upload_id'] = $upload_id;
        $this->_stream_files($where,'');
    }

    function _contest_has_audio($contest_id,$tag)
    {
        $uploads =& CCUploads::GetTable();
        if( empty($tag) )
            $tag = 'audio';
        else
            $tag .= ',audio';
        $uploads->SetTagFilter($tag);
        $where['upload_contest'] = $contest_id;
        $records =& $uploads->GetRecords($where);
        $uploads->SetTagFilter('');
        return count($records) > 0 ;
    }

    function _stream_files($where,$tags,$type='',$sort_order=array(),$isRadio = false)
    {
        if( empty($type) )
            $type = 'all';

        $uploads =& CCUploads::GetTable();
        
        if( $isRadio )
        {
            $uploads->SetOrder('RAND()');
            $uploads->SetTagFilter('site_promo');
            $promos = $uploads->GetRecords('');
            if( empty($promos) )
            {
                $isRadio = false;
            }
            else
            {
                $promo_count = count($promos);
                $promo = 0;
                $pgap = RADIO_PROMO_INTERVAL;
            }
        }
        elseif( empty($_REQUEST['nosort']) )
        {
            $uploads->SetOrder('upload_date','DESC');
        }

        if( $tags )
            $tags .= ',audio';
        else
            $tags = 'audio';

        $uploads->SetTagFilter($tags,$type);
        $records =& $uploads->GetRecords($where);
        CCFeeds::_resort_records($records,$sort_order);
        $streamfile = '';
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $isRadio && ( !$i || ($i % $pgap == 0) ) )
            {
                $p = $promos[ $promo++ % $promo_count ];
                CCUpload::EnsureFiles($p,true);
                $url = $this->_get_streamable_url($p,0);
                $streamfile .=  $url. "\n";
            }
            CCUpload::EnsureFiles($records[$i],true);
            $fcount = count($records[$i]['files']);
            $files =& $records[$i]['files'];
            for( $n = 0; $n < $fcount; $n++)
                if( $files[$n]['file_format_info']['media-type'] == 'audio' )
                    break;
            if( $n == $fcount )
                continue; // this really never should happen
            $surl = $this->_get_streamable_url($records[$i],$n);
            $url = str_replace(' ', '%20', $surl );
            $streamfile .=  $url . "\n";
        }

        header("Content-type: audio/x-mpegurl");
        print($streamfile);
        exit;
    }

    function _get_streamable_url(&$R,$n)
    {
        if( !empty($R['files'][$n]['file_extra']['remote_url']) )
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('remote_files');
            if( !empty($settings['enable_streaming']) )
            {
                $url = $R['files'][$n]['file_extra']['remote_url'];
                return $url;
            }
        }
        
        return $R['files'][$n]['download_url'];
    }

}


?>
