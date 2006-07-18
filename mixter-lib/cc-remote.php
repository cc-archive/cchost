<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,   array( 'CCRemote', 'OnUploadDone') );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD, array( 'CCRemote', 'OnDeleteUpload') );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,    array( 'CCRemote', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRemote', 'OnMapUrls'));


/**
   How remoting works in chronological order:

   1. We catch UPLOAD_DONE events here -- all kinds new file,
      replace, add file -- and do the same actions for all:

      - For file in the upload's record:
      -   If there is an remote url in the file record:
      -     Ping the remote server to remove the file
      -     Unset the remote url
      -   Add the file_id to the QUEUE file

      The QUEUE file is a text file, comma separated 
      file_ids

   ...some time later...

   2. The cron script cc-remote.php-cron is run
   3. That script open the QUEUE file and for each ID:

      - Sends the file to the remote server (bellflower at
        dreamhosters) 
      - Puts the file 'userfiles' directory with the file_id
        as the name and 'mp3' (or 'au' or whatever) as the
        extension
      - Remove the id from the queue file
      - Mark the file_id's record with the remote URL

  ...even later...

  4. User ABC requests a stream of XYZ's file
  5. The render audio routine finds a remote url in the 
     record and uses that.

  ...still later...

  6. Remixer deletes file
  7. Our file delete handler:

     - Look for remote url in the record
     - If found, ping the remote server to remove the file

There's some question about whether we need to watch for file properties
events because most media players do not pay attention to id3 tags when
streaming from an m3u file, they just use the file name.

*/
class CCRemote
{

    function Run()
    {
        CCPage::SetTitle(_('Transfering files'));
        require_once('mixter-lib/cc-remote-client.inc');
        $results = cc_remote_transfer_queue();
        if( empty($results) )
        {
            CCPage::Prompt( _('No files were transfered') );
        }
        else
        {
            $set = join(',', $results);
            $where = "file_id in ($set)";
            $files =& CCFiles::GetTable();
            $rows = $files->QueryRows($where);
            $html = _('Files transfered:<br />');
            foreach( $rows as $row )
                $html .= $row['file_name'] . '<br />';
            CCPage::Prompt( _('Files transfer complete') );
            CCPage::AddPrompt('body_text',$html);
        }
    }

    function View()
    {
        require_once('mixter-lib/cc-remote-client.inc');
        cc_remote_dump_queue();
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin', 'remoting'),           array('CCRemote', 'Admin'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin', 'remoting', 'run'),    array('CCRemote', 'Run'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin', 'remoting', 'view'),   array('CCRemote', 'View'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin', 'remoting', 'manage'), array('CCRemote', 'Manage'),   CC_ADMIN_ONLY );
    }

    function Admin()
    {
        require_once('mixter-lib/cc-remote-client.inc');
        CCPage::SetTitle('Configure Remote Transfers');
        $form = new CCRemoteAdminForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function Manage($cmd='',$arg='')
    {
        require_once('mixter-lib/cc-remote-client.inc');
        CCPage::SetTitle('Manage Remote Transfers');
        cc_remote_manage($cmd,$arg);
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array(
                'remote_files' => array( 
                                 'menu_text'  => _('Remote Transfers'),
                                 'menu_group' => 'configure',
                                 'help' => 'Configure an ftp server for remote streaming and downloading',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 40,
                                 'action' =>  ccl('admin','remoting')
                                 ),
                );
        }
    }

    function OnUploadDone( $upload_id )
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('remote_files');

        if( empty($settings['enable_streaming']) && empty($settings['enable_download']))
            return;

        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);
        
        $files =& CCFiles::GetTable();

        require_once('mixter-lib/cc-remote-client.inc');

        list( , $queue_file_name ) = cc_remote_get_settings();

        foreach( $record['files'] as $F )
        {
            $this->_nuke_remote($F,$settings);

            if( $F['file_format_info']['media-type'] != 'audio' )
                return;

            $args['file_id'] = $F['file_id'];
            unset($F['file_extra']['remote_url']);
            $args['file_extra'] = serialize($F['file_extra']);
            $files->Update($args);

            cc_remote_queue_file($F['file_id'], $queue_file_name);
        }
    }

    function OnDeleteUpload( &$record )
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('remote_files');
        if( empty($settings['enable_streaming']) )
            return;

        foreach( $record['files'] as $F )
            $this->_nuke_remote($F,$settings);
    }


    function _nuke_remote(&$F, $settings)
    {
        if( empty($F['file_extra']['remote_url']) )
            return;

        require_once( 'mixter-lib/cc-remote-client.inc' );

        cc_remote_delete($F['file_name'],$settings);

    }
}


?>