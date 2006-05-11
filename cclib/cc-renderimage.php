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
* @subpackage image
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,       array( 'CCRenderImage', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCRenderImage', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,        array( 'CCRenderImage', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS, array( 'CCRenderImage' , 'OnGetConfigFields' ));

/**
* @package cchost
* @subpackage image
*/
class CCRenderImage
{

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('media','showimage'), array('CCRenderImage','Show'), CC_DONT_CARE_LOGGED_IN );
    }

    function Show($username,$upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        CCUpload::EnsureFiles($record,true);
        $url = $record['files'][0]['download_url'];
        $html =<<< END
<html>
<body>
<img src="$url" />
</body>
</html>
END;
        print($html);
        exit;
    }

    /**
    * Event handler for building local menus for contest rows
    *
    * @see CCMenu::AddItems()
    */
    function OnContestMenu(&$menu,&$record)
    {
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
//      if( empty($record['upload_banned']) && CCUploads::IsMediaType($record,'image') )
        if( empty($record['upload_banned']) && CCUploads::InTags('image',$record) )
        {
            $link = ccl('media','showimage', $record['user_name'],
                                             $record['upload_id']);
            list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
            $w += 10;
            $h += 10;
            $action =<<<END
      window.open('$link','showimage','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=$w, height=$h');
END;
            $menu['stream'] = 
                         array(  'menu_text'  => cct('Show'),
                                 'weight'     => 1,
                                 'group_name' => 'play',
                                 'id'         => 'showimage',
                                 'access'     => CC_DONT_CARE_LOGGED_IN,
                                 'scriptaction'     =>  $action );

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
        $image_index = CCRenderImage::_any_image($record);
        if( $image_index == -1 )
            return;
    
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');

        $maxx = empty($settings['thumbnail-x']) ? '60px' : $settings['thumbnail-x'];
        $maxy = empty($settings['thumbnail-y']) ? '60px' : $settings['thumbnail-y'];

        CCUpload::EnsureFiles($record,true);
        $record['file_macros'][]   = 'render_image';
        $record['thumbnail_url']   = $record['files'][$image_index]['download_url'];
        $record['thumbnail_style'] = "height:$maxy;width:$maxx;";

    }

    function _any_image($record)
    {
        $ok = CCUploads::InTags('image',$record);
        return  $ok ? true : -1;
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['thumbnail-x'] = 
               array( 'label'       => 'Max Thumb X',
                       'formatter'  => 'textedit',
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

            $fields['thumbnail-y'] =
               array( 'label'       => 'Max Thumb Y',
                       'formatter'  => 'textedit',
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}


?>