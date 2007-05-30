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

require_once('cclib/zip/zip.lib.php');
require_once('ccextras/cc-licensefiles.inc');
require_once('ccextras/cc-checksum.inc');

class CCDownload
{

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('download'), array('CCDownload','Download'), CC_DONT_CARE_LOGGED_IN,
                ccs(__FILE__), '{user_name}/{upload_id}', _('Download files with other files.'),
                CC_AG_UPLOADS );
    }

    function Download($name,$id)
    {

        global $CC_GLOBALS;

        $files =& CCFiles::GetTable();
        $file_name = $files->QueryItemFromKey('file_name',$id);

        if(!empty($CC_GLOBALS['counter']))
        {
            $uargs['file_num_download'] = $files->QueryItemFromKey('file_num_download',$id) + 1;
            $uargs['file_id'] = $id;
            $upload = new CCTable('cc_tbl_files','file_id');
            $upload->Update($uargs);
        }

        if(!empty($CC_GLOBALS['license']) || !empty($CC_GLOBALS['checksum']))
        {
            $people_dir = empty($CC_GLOBALS['user-upload-root']) ? 'people' :
                               $CC_GLOBALS['user-upload-root'];

            $file_dir = $people_dir . '/' . $name . '/' . $file_name;
            $zip = new zipfile();
            $zip->addFile($file_dir,$file_name);

            if (!empty($CC_GLOBALS['license']))
                $zip->addFile(CCLicensefiles::Licensefiles($name,$id),'license.rdf');

            if (!empty($CC_GLOBALS['checksum']))
                $zip->addFile(CCChecksum::Checksum($file_dir),'checksum.md5');

            echo $zip->file();
            header('Content-Type: application/x-zip; name=' . substr($file_name, 0, strrpos($file_name, ".")) . '.zip');
            header('Content-Disposition: attachment; filename=' . substr($file_name, 0, strrpos($file_name, ".")) . '.zip');
        }
        else
        {
            $upload_root = CCUser::GetPeopleDir();
            $relative = $upload_root . '/' . $name;

            CCUtil::SendBrowserTo( ccd( $relative, $file_name ) );
        }

    }

    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['counter'] =
               array(  'label'      => _('Download counter'),
                       'form_tip'   => _('File download counter.'),
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
        }
    }

}
?>
