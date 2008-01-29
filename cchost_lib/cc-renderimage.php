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

require_once('cchost_lib/cc-render.php');

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
        CCEvents::MapUrl( ccp('media','showimage'), array('CCRenderImage','Show'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{user_name}/{upload_id}', _('Display bitmap'), CC_AG_RENDER );
        CCEvents::MapUrl( ccp('admin','thumbnail'), array('CCRenderImage','Admin'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _('Display thumbnail admin form'), CC_AG_RENDER );
    }

    function Admin()
    {
        require_once('cchost_lib/cc-renderimage-form.php');
        CCPage::SetTitle(_('Admin Thumbnail'));
        $form = new CCAdminThumbnailForm();
        CCPage::AddForm( $form->GenerateForm() );
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
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    */
    function OnUploadMenu(&$menu,&$record) 
    { 
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


        CCUpload::EnsureFiles($record,true);
        $image_index = 0;
        $count = count($record['files']);
        // finds out where the thumbnail is...
        for( $image_index = 0; $image_index < $count; $image_index++ )
        {
            if( $record['files'][$image_index]['file_format_info']['media-type'] == 'image' )
                break;
        }

        // Basically, make sure that there is something here to get 
        // dimensions with
        if( empty( $record['files'][$image_index]['download_url'] ) &&
            empty( $record['files'][$image_index]['local_path'] ) )
            return;
        
        if( !empty($settings['thumbnail-x']) && !empty($settings['thumbnail-y']) )
        {
            // set as a default
            $maxx =  $settings['thumbnail-x'];

            if ( $settings['thumbnail-constrain-y'] )
            {
                $thumbnailx = str_replace('px', '', $settings['thumbnail-x']);
                $thumbnaily = str_replace('px', '', $settings['thumbnail-y']);

                // CCDebug::PrintVar($record);
                
                if ( $record['files'][$image_index]['local_path'] )  
                    list($orig_width, $orig_height) = 
                        getimagesize($record['files'][$image_index]['local_path']);
                else
                    list($orig_width, $orig_height) = 
                        getimagesize($record['files'][$image_index]['download_url']);

                // echo "$orig_width X $orig_height <br />"; 
                if ( $orig_height > 0 && $orig_width > 0 )
                {
                    $zoom_factor = $thumbnaily / $orig_height ;
                    $maxx = round($zoom_factor * $orig_width);
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

        
        $record['file_macros'][]   = 'render_image';
        $record['thumbnail_url']   = $record['files'][$image_index]['download_url'];
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items, $scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'thumbnails'=> array( 'menu_text'  => _('Thumbnails'),
                                'menu_group' => 'configure',
                                'access'     => CC_ADMIN_ONLY,
                                'help'       => _('Configure thumbnails handling (for image uploads)'),
                                'weight'     => 160,
                                'action'     => ccl('admin','thumbnail') )
                        );
    }

}


?>
