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
* @subpackage video
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCRenderFlash', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRenderFlash', 'OnMapUrls'));

/**
* @package cchost
* @subpackage video
*/
class CCRenderFlash extends CCRender
{
    function Play($username,$upload_id) {
        Show($username,$upload_id);
    }

    function Show($username,$upload_id)
    {
        /*
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        CCUpload::EnsureFiles($record,true);
        $url = $record['files'][0]['download_url']; */
        parent::Show();
        list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
        $html =<<<END
<html>
<body style="margin:0">
<object width="$w" height="$h" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0">
<param name="movie" value="$url">
<embed src="$url" width="$w" height="$h" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" >
</embed>
</object></body>
</html>
END;
        print($html);
        exit;
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow(&$record)
    {
        if( empty($record['script_link']) )
        {
            $link = $this->_get_stream_link($record);
            if( !empty($link) )
                $record['script_link'] = $link;
        }
    }

    function _get_stream_link(&$record)
    {
      //if( empty($record['upload_banned']) && CCUploads::IsMediaType($record,'video','swf')  )
        if( empty($record['upload_banned']) && CCUploads::InTags('swf',$record) )
        {
            $ilink = ccl('files','playflash', $record['user_name'],
                                             $record['upload_id']);
            list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
            $w += 30;
            $h += 30;
            $action =<<<END
      javascript:window.open('$ilink','flashplay',
                'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=$w, height=$h');
END;
            $link['url'] = $action;
            $link['text'] = _('Play');
            return($link);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('files','playflash'),   array('CCRenderFlash', 'Play'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{user_name}/{upload_id}', _('Display Flash'), CC_AG_RENDER );
    }

}


?>
