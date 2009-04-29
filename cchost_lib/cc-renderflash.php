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

require_once('cchost_lib/cc-render.php');

/**
* @package cchost
* @subpackage video
*/
class CCRenderFlash extends CCRender
{
    function Play($username='',$upload_id='') {
        $this->Show($username,$upload_id);
    }

    function Show($username='',$upload_id='')
    {
        $username = CCUtil::StripText($username);
        $upload_id = sprintf('%d',$upload_id);
        if( empty($username) || empty($upload_id) )
            CCUtil::Send404();

        require_once('cchost_lib/cc-query.php');
        $q = 'dataview=files&f=php&ids=' . $upload_id;
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs($q);
        list( $records, $m ) = $query->Query($args);
        if( empty($records) )
            CCUtil::Send404();
        $record =& $records[0];
        $dim = CCUploads::GetFormatInfo($record,'dim');
        if( empty($dim) )
        {
            // er, ungraceful yo'
            die('this upload does not support dimensions');
        }
        list( $w, $h ) = $dim;
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
