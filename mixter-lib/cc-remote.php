<?
 

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/*
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,   array( 'CCRemote', 'OnUploadDone'), 'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD, array( 'CCRemote', 'OnDeleteUpload'), 'mixter-lib/cc-remote.inc'  );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,    array( 'CCRemote', 'OnAdminMenu'), 'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRemote', 'OnMapUrls'), 'mixter-lib/cc-remote.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCRemoteHV', 'OnUploadRow'));
*/

class CCRemoteHV
{
    function OnUploadRow(&$row)
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('remote_files');
        if( empty($settings['enable_streaming']) && empty($settings['enable_download']))
            return;

        $K = array_keys($row['files']);
        $c = count($K);
        for( $i = 0; $i < $c; $i++ )
        {
            $F =& $row['files'][$K[$i]];

            if( empty($F['file_extra']['remote_url']) )
                continue;

            $F['file_extra']['remote_url'] = $settings['ftp_baseurl'] . '/' . $F['file_extra']['remote_file_name'];

            if( $i == 0 && !empty($row['fplay_url']) )
                $row['fplay_url'] = $F['file_extra']['remote_url'];

        }
    }
}


?>
