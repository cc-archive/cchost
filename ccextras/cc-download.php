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
* $Id: cc-mail.inc 5180 2007-02-06 01:16:33Z fourstones $
*
*/

/**
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,       array( 'CCDownloadHV', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCDownload' , 'OnMapUrls') , 'ccextras/cc-download.inc' );
//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS, array( 'CCDownload' , 'OnGetConfigFields') , 'ccextras/cc-download.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCDownload', 'OnAdminMenu'), 'ccextras/cc-download.inc');

class CCDownloadHV
{

    function OnUploadMenu(&$menu,&$record)
    {

        global $CC_GLOBALS;
        
        if(!empty($CC_GLOBALS['counter']) || !empty($CC_GLOBALS['license']) || !empty($CC_GLOBALS['checksum']))
        {
            $weight = 0;
            foreach( $record['files'] as $file )
            {
                $weight++;
                $menu[$weight]['action'] = ccl( 'download', $record['user_name'], $file['file_id'], $file['file_name'] );
                if(!empty($CC_GLOBALS['counter']))
                {
                    $menu[$weight]['menu_text'] .= " " . _('downloads') . ": " . $file['file_num_download'];
                }
            }
        }
    }

}
?>
