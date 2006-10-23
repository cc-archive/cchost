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
class CCRenderImage extends CCRender
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
        /* 
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        CCUpload::EnsureFiles($record,true);
        $url = $record['files'][0]['download_url'];
        */
        parent::Show();
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
                         array(  'menu_text'  => _('Show'),
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
        $has_image = CCUploads::InTags('image',$record);
        if( !$has_image )
            return;
    
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');

        if( empty($settings['thumbnail-on']) )
            return;

        if( !empty($settings['thumbnail-x']) && !empty($settings['thumbnail-y']) )
        {
            // set as a default
            $maxx =  $settings['thumbnail-x'];

            if ( $settings['thumbnail-constrain-y'] )
            {
                $thumbnailx = str_replace('px', '', $settings['thumbnail-x']);
                $thumbnaily = str_replace('px', '', $settings['thumbnail-y']);

                // right now assuming first record is image...which is bad
                // should really not assume and show thumbnails for all
                list($orig_width, $orig_height) = 
                   getimagesize($record['files'][0]['local_path']);
                // echo "$orig_width X $orig_height <br />"; 
                if ( $orig_height > 0 )
                {
                    $zoom_factor = $thumbnaily / $orig_height ;
                    $maxx = round($zoom_factor * $orig_width);
                    /*
                    echo $thumbnaily . " / " . $orig_height;
                    echo "<br />";
                    echo $zoom_factor . " * " . $orig_width . " + " . $orig_width . "<br />";
                    echo $zoom_factor . " * " . $orig_width;
                    echo "<br />";
                    print_r($maxx);
                    */
                }
            }

            if( strpos($maxx,'px') === false )
                $maxx .= 'px';

            $maxy =  $settings['thumbnail-y'];
            if( strpos($maxy,'px') === false )
                $maxy .= 'px';
            $record['thumbnail_style'] = "height:$maxy;width:$maxx;";
        }
        else
        {
            $record['thumbnail_style'] = '';
        }

        CCUpload::EnsureFiles($record,true);
        $image_index = 0;
        $count = count($record['files']);
        for( $image_index = 0; $image_index < $count; $image_index++ )
        {
            if( $record['files'][$image_index]['file_format_info']['media-type'] == 'image' )
                break;
        }
        $record['file_macros'][]   = 'render_image';
        $record['thumbnail_url']   = $record['files'][$image_index]['download_url'];
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
            $fields['thumbnail-on'] = 
               array( 'label'       => _('Display Thumbnails'),
                       'formatter'  => 'checkbox',
                       'form_tip'   => _('Display thumbnails for image uploads'),
                       'flags'      => CCFF_POPULATE);

            $fields['thumbnail-constrain-y'] = 
               array( 'label'       => _('Constrain Thumbnail Proportion'),
                       'formatter'  => 'checkbox',
                       'form_tip'   => _('Constrain proportion of image to the original image\'s height (y value)'),
                       'flags'      => CCFF_POPULATE);

            $fields['thumbnail-x'] = 
               array( 'label'       => _('Max Thumb X'),
                       'formatter'  => 'textedit',
                       'form_tip'   => _('Leave this blank or 0 (zero) to use the image\'s natural size'),
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE);

            $fields['thumbnail-y'] =
               array( 'label'       => _('Max Thumb Y'),
                       'formatter'  => 'textedit',
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE );

        }
    }
}


?>
