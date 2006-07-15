<?

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

if( class_exists('CCEditConfigForm') || class_exists('cceditconfigform') )
{
    /**
    * Form for admin ui for ftp remoting
    */
    class CCRemoteAdminForm extends CCEditConfigForm
    {
        /**
        * Constructor
        */
        function CCRemoteAdminForm()
        {
            $this->CCEditConfigForm('remote_files',CC_GLOBAL_SCOPE);
    /*
        'username' => 'mixter',
        'server'   => 'bellflower.dreamhost.com',
        'dir'      => 'ccmixtermedia.org/userfiles',
        'password' => '',
        'cmdurl'   => 'http://ccmixtermedia.org/userfiles/cmd.php',
        'urlbase'  => 'http://ccmixtermedia.org/userfiles/',
    */

            $fields = array( 
                        'enable_streaming' =>  
                           array(  'label'      => 'Enable FTP Remote Streaming',
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_POPULATE ),
                /*
                        'enable_download' =>  
                           array(  'label'      => 'Enable FTP Remote Downloading',
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_POPULATE ),
                        'delete_local' =>  
                           array(  'label'      => 'Delete Local File After Transfer',
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_POPULATE ),
                */
                        'ftp_server' =>  
                           array(  'label'      => 'FTP Server',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                        'ftp_username' =>  
                           array(  'label'      => 'FTP Username',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                        'ftp_password' =>  
                           array(  'label'      => 'FTP Password',
                                   'formatter'  => 'password',
                                   'nomd5'      => true,
                                   'flags'      => CCFF_POPULATE ),
                        'ftp_dir' =>  
                           array(  'label'      => 'Remote directory',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                        'ftp_cmdurl' =>  
                           array(  'label'      => 'URL to Server Script',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                        'ftp_baseurl' =>  
                           array(  'label'      => 'Base remote URL',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                );

            $this->AddFormFields($fields);

            $queue = cc_remote_get_queue();

            $msg = '';
            if( !empty($queue) )
            {
                $caption = _('Run Transfer Queue Now');
                $url     = ccl('admin','remoting','run');
                $button  =<<<END
    <div style="text-align: center; width: 50%">
      <a href="$url" class="cc_gen_button"><span>$caption</span></a>
    </div>
END;
                $msg = sprintf(_('There are %d files in the queue'), count($queue)) . $button;
            }

            if( empty($msg) )
            {
                $msg = _('There are no files in the transfer queue');
            }

            $this->SetHiddenField('_file', 'mixter-lib/cc-remote-client.inc', CCFF_HIDDEN);
            
            $this->SetHelpText($msg);
        }
    }
}

/* ------------------------------------------------------------------- */

define( 'CC_FTP_BLOCKSIZE', 64 * 1024 );

function cc_remote_send_file($fname,$local_path,$mixter_ftp_settings)
{
    if( !file_exists($local_path) )
    {
        CCDebug::Log("remote_send: '$local_path' doesn't exists");
        return '';
    }

    // $handle = fopen("ftp://user:password@example.com/somefile.txt", "w");

    $u = $mixter_ftp_settings['ftp_username'];
    $s = $mixter_ftp_settings['ftp_server'];
    $d = $mixter_ftp_settings['ftp_dir'];
    $p = $mixter_ftp_settings['ftp_password'];

    $retval = cc_remote_delete($fname,$mixter_ftp_settings);

    if( $retval < 0 )
    {
        //die("Error on remote cmd: $retval");
        return '';
    }

    $remote_open_path = "ftp://{$u}:{$p}@{$s}/{$d}/{$fname}"; 
    $hremote = fopen($remote_open_path, 'wb');
    if( $hremote !== false )
    {
        $hlocal  = fopen($local_path, 'rb' );

        CCDebug::Chronometer($timer1);

        if( $hlocal !== false )
        {

            while (!feof($hlocal)) {
              $block = fread($hlocal, CC_FTP_BLOCKSIZE);
              fwrite($hremote,$block);
            }

            fclose($hlocal);
        }

        fclose($hremote);

        CCDebug::Log('transfer: ' . CCDebug::Chronometer($timer1));

        $url = $mixter_ftp_settings['ftp_baseurl'] . '/' . $fname;

        return $url;
    }

    return '';
}


function cc_remote_delete($fname,$mixter_ftp_settings)
{
    return cc_remote_exec( "cmd=softdel&f=$fname", $mixter_ftp_settings );
}

function cc_remote_exec($query,$mixter_ftp_settings)
{
    require_once('cclib/snoopy/Snoopy.class.php');

    $snoopy = new Snoopy();
    $snoopy->read_timeout = 10;
    $url = $mixter_ftp_settings['ftp_cmdurl'] . '?' . $query;
    $ok = @$snoopy->fetch($url );

    if( $ok )
        return $snoopy->results;

    return -100;
}

function cc_remote_get_settings()
{
    global $CC_GLOBALS;

    $config =& CCConfigs::GetTable();
    $mixter_ftp_settings = $config->GetConfig('remote_files');
    $queue_file_name = $CC_GLOBALS['logfile-dir'] . 'remote_queue.txt';
    return array( $mixter_ftp_settings, $queue_file_name );

}

function cc_remote_queue_file($file_id,$queue_file_name)
{
    $queue = cc_remote_get_queue();
    if( !empty($queue) && in_array( $file_id, $queue ) )
        return;
    
    $file = fopen($queue_file_name,'a');
    fwrite( $file, $file_id . ',');
    fclose($file);
    chmod($queue_file_name, 0777 );
}

function cc_remote_get_queue($queue_file_name='')
{
    if( empty($queue_file_name) )
        list( , $queue_file_name ) = cc_remote_get_settings();

    if( file_exists($queue_file_name) )
    {
        $queue = file_get_contents($queue_file_name);
        $queue = trim($queue, ' ,');
        if( !empty($queue) )
        {
            $queue = split( ',', $queue );
            return $queue;
        }
    }
}

function cc_remote_update_queue($arr,$queue_file_name='')
{
    if( empty($queue_file_name) )
        list( , $queue_file_name ) = cc_remote_get_settings();

    if( empty($arr) )
    {
        if( file_exists($queue_file_name) )
            unlink($queue_file_name);
    }
    else
    {
        $str = join(',',$arr);
        $f = fopen($queue_file_name, 'w');
        fwrite($f,$str);
        fclose($f);
        chmod($queue_file_name, 0777);
    }
}

function cc_remote_transfer_queue()
{
    list( $mixter_ftp_settings, $queue_file_name ) = cc_remote_get_settings();

    if( empty($mixter_ftp_settings['enable_streaming']) &&
        empty($mixter_ftp_settings['enable_download'])
      )
    {
        return;
    }

    $file_ids = cc_remote_get_queue();
    if( empty($file_ids) )
        return;

    $uploads  =& CCUploads::GetTable();
    $files    =& CCFiles::GetTable();
    $done     = array();
    $count    = count($file_ids);

    for( $i = 0; $i < $count; $i++ )
    {
        $file_id = array_shift($file_ids);

        $ok = !empty($file_id);

        if( $ok && !in_array( $file_id, $done ) )
        {
            $upload_id = $files->QueryItemFromKey( 'file_upload', $file_id );
            $record =& $uploads->GetRecordFromKey( $upload_id );
            $ok = !empty($record); // upload might have been deleted

            if( $ok )
            {
                //print('upload: ' . $record['upload_name'] . "\n");
                foreach( $record['files'] as $F )
                {
                    // print_r($F); exit;

                    if( $F['file_id'] != $file_id ) 
                    {
                        continue;
                    }

                    $url = cc_remote_send_file( $F['file_name'],
                                         $F['local_path'],
                                         $mixter_ftp_settings);

                    if( $url )
                    {
                        $args['file_id'] = $file_id;
                        $ext = $F['file_extra'];
                        $ext['remote_url'] = $url;
                        $args['file_extra'] = serialize($ext);
                        $files->Update($args);
                    }
                    $done[] = $file_id;
                    break;
                }
            }
        }

        cc_remote_update_queue($file_ids,$cc_remote_update_queue);
    }

}

function cc_remote_populate_queue($last_n_files=25)
{
    //  FOR TESTING: prepopulate queue file...

    $sql = 'SELECT file_id FROM cc_tbl_files ORDER BY file_id DESC LIMIT ' . $last_n_files ;
    $ids = CCDatabase::QueryItems($sql);
    cc_remote_update_queue($ids);
}

?>