<?

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

if( class_exists('CCPage') || class_exists('ccpage') )
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

            $queue =& cc_remote_get_records();
        
            _cc_show_remoting_menu($queue);

            $this->SetHiddenField('_file', 'mixter-lib/cc-remote-client.inc', CCFF_HIDDEN);
            
        }
    }

    function _cc_show_remoting_menu(&$queue)
    {
        if( empty($queue) )
        {
            $caption_clear = 
            $url_clear     = 
            $class_clear   = 
            $caption_run = 
            $url_run     = 
            $class_run   = 
            $caption_view = 
            $url_view     = 
            $class_view   = '';

            $msg = _('There are no published or unbanned uploads queued for upload');
        }
        else
        {
            $caption_clear = _('Clear Transfer Queue');
            $url_clear     = ccl('admin','remoting','manage', 'clear');
            $class_clear   = 'cc_gen_button';

            $caption_run = _('Run Transfer Queue Now');
            $url_run     = ccl('admin','remoting','run');
            $class_run   = 'cc_gen_button';

            $caption_view = _('View Transfer Queue');
            $url_view     = ccl('admin','remoting','view');
            $class_view   = 'cc_gen_button';
            
            $msg = sprintf(_('There are %d files in the queue'), count($queue));
        }

        $caption_manage = _('Manage Transfer Queue');
        $url_manage     = ccl('admin','remoting','manage');

        $caption_props = _('Transfer Queue Settings');
        $url_props     = ccl('admin','remoting' );

        $buttons  =<<<END
<div style="text-align: center; width: 20%; float:right;padding-left: 15px;">
 <table>
    <tr><td><a href="$url_props"  class="cc_gen_button"><span>$caption_props</span></a></td></tr>
    <tr><td><a href="$url_run"    class="$class_run"><span>$caption_run</span></a></td></tr>
    <tr><td><a href="$url_view"   class="$class_view"><span>$caption_view</span></a></td></tr>
    <tr><td><a href="$url_clear"  class="$class_clear"><span>$caption_clear</span></a></td></tr>
    <tr><td><a href="$url_manage" class="cc_gen_button"><span>$caption_manage</span></a></td></tr>
    <tr><td>$msg</td></tr>
 </tr></table>
</div>
END;

        CCPage::AddPrompt('body_html',$buttons);

        return count($queue);
    }

    function cc_remote_remove($id)
    {
        $queue = cc_remote_get_queue();
        if( empty($queue) )
            return;
        $arr = array_diff( $queue, array( $id ) );
        cc_remote_update_queue($arr);
    }

    function cc_remote_add($id)
    {
        $queue = cc_remote_get_queue();
        if( empty($queue) )
            $queue = array( $id );
        else
            $queue[] = $id;
        cc_remote_update_queue($queue);
    }

    function cc_remote_unremote($id)
    {
        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($id);
        if( is_string( $row['file_extra'] ) )
            $row['file_extra'] = unserialize($row['file_extra']);
        unset($row['file_extra']['remote_url']);
        $args['file_extra'] = serialize($row['file_extra']);
        $args['file_id'] = $id;
        $files->Update($args);
    }

    function _cc_get_remove_link($fid)
    {
        return _cc_get_arlink($fid,'remove','Remove');
    }

    function _cc_get_add_link($fid)
    {
        return _cc_get_arlink($fid,'add','Add');
    }

    function _cc_get_arlink($fid,$upart,$text)
    {
        $curl = ccl( 'admin', 'remoting', 'manage', $upart, $fid );
        $spname = _cc_get_arlink_name($fid);
        $jurl = "cc_get_data('$curl', '$spname', '...'); return false;";
        return "<a onclick=\"$jurl\" href='#' style=\"color:green;\">[ $text ]</a>";
    }

    function _cc_get_arlink_name($fid)
    {
        return 'arspan_' . $fid;
    }

    function cc_remote_manage($cmd='',$id='')
    {
        $tagfilter = '';

        switch( $cmd )
        {
            case 'remove':
                cc_remote_remove($id);
                print( _cc_get_add_link($id) );
                exit;

           case 'add':
                cc_remote_add($id);
                print( _cc_get_remove_link($id) );
                exit;

           case 'unremote':
                cc_remote_unremote($id);
                $msg = _('file no longer remoted');
                break;
           
           case 'clear':
                cc_remote_clear_queue();
                $msg = _('transfer queue cleared');
                break;

           case 'filter':
                $tagfilter = $_REQUEST['filter'];
                $type = $_REQUEST['type'];
                break;
        }

        $qrecs = cc_remote_get_records();
        _cc_show_remoting_menu($qrecs);

        $uploads =& CCUploads::GetTable();
        $uploads->SetDefaultFilter(false,false);
        $uploads->SetOrder('upload_date','DESC');
        if( !empty($tagfilter) )
            $uploads->SetTagFilter($tagfilter,$type);
        $where = '';
        CCPage::AddPagingLinks($uploads,$where,30);
        $records =& $uploads->GetRecords($where);
        $count = count($records);
        $queue = cc_remote_get_queue();
        $furl = ccl('admin','remoting','manage','filter');
        $html =<<<END
            <form method="GET" action="$furl">Filter by tags: <input style="font-family: Verdana;font-size: 11px;width: 250px;" name="filter" id="filter" value="$tagfilter" /><select name="type" style="font-family: Verdana;font-size: 11px;" id="type"><option value="all" selected="selected">all</option><option value="any">any</option></select><input style="font-family: Verdana;font-size: 11px;" type="submit" value="go" /></form>
            <table style="font-size: 11px; font-family: Verdana;white-space:nowrap;">
END;
        if( !empty($msg) )
            $html .= "<tr><td colspan=\"3\"><span style=\"display:inline;margin: 4px;\"
                         class=\"cc_system_prompt\">$msg</div></td></tr>";

        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $records[ $i ];
            $html .=<<<END
<tr>
  <td colspan="3"><br />
    <b><a href="{$R['file_page_url']}">{$R['upload_name']}</a></b> <a href="{$R['artist_page_url']}">{$R['user_real_name']}</a>
  </td>
</tr>
END;
            $fcount = count($R['files']);
            for( $n = 0; $n < $fcount; $n++ )
            {
                $F =& $R['files'][ $n ];
                $fid = $F['file_id'];
                $spname = _cc_get_arlink_name($fid);
                $html .= "<tr>\n  <td><span id=\"$spname\">"; 
                if( in_array($fid,$queue) )
                    $ar_link = _cc_get_remove_link($fid);
                else
                    $ar_link = _cc_get_add_link($fid);
                $html .= "$ar_link</span></td>\n   <td>";
                if( !empty($F['file_extra']['remote_url']) )
                {
                    if( !empty($R['stream_link']) )
                    {
                        $img = ccd('cctemplates/ccmixter/hear-button-bg.gif');
                        $html .= "<a href=\"{$R['stream_link']['url']}\"><img src=\"$img\" /></a> ";
                    }

                    $curl = ccl( 'admin', 'remoting', 'manage', 'unremote', $F['file_id'] );
                    $html .= "<a href=\"$curl\"  style=\"color:green;\">[ Unremote ]</a>";
                }
                $furl = 'http://ccmixter.org/people/' . $R['user_name'] . '/' . $F['file_name'];
                $html .= "</td>\n   <td><a href='$furl' style='color: #777;'>{$F['file_name']}</a></td>\n   <td></tr>\n";
            }
        }
        $html .= '</table>';
        CCPage::AddPrompt('body_html', $html);
        CCPage::PageArg('dummy1', '0', 'prev_next_links');
    }

    function cc_remote_dump_queue()
    {
        CCPage::SetTitle(_('View Remoting Queue'));

        $records =& cc_remote_get_records();

        _cc_show_remoting_menu($records);

        if( empty($records) )
        {
            return;
        }

        $html = '<table>';
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $records[ $i ];
            $t =<<<END
                <tr><td>
                     <a href="{$R['file_page_url']}">{$R['upload_name']}</a> by 
                     <a href="{$R['artist_page_url']}">{$R['user_real_name']}</a>
                     </td></tr>
END;
            $html .= $t;
        }

        $html .= '</table>';

        CCPage::AddPrompt('body_html',$html);
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

    $remote_open_path = "ftp://{$u}:{$p}@{$s}/{$d}/{$fname}"; 
    $hremote = @fopen($remote_open_path, 'wb');
    if( $hremote === false )
    {
        CCDebug::Log("Warning: remote open failed $remote_open_path");
    }
    else
    {
        $hlocal  = @fopen($local_path, 'rb' );

        CCDebug::Chronometer($timer1);

        if( $hlocal === false )
        {
            CCDebug::Log("Warning: local open of $local_path failed");
        }
        else
        {

            while (!feof($hlocal)) {
              $block = fread($hlocal, CC_FTP_BLOCKSIZE);
              fwrite($hremote,$block);
            }

            fclose($hlocal);
        }

        fclose($hremote);

        CCDebug::Log("Info transfer: $fname (" . CCDebug::Chronometer($timer1) . ')' );

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
    CCDebug::Log("Info: Transfer exec $url");
    $ok = @$snoopy->fetch($url );

    if( $ok )
    {
        CCDebug::Log("Info: transfer exec results: {$snoopy->results}");
        return $snoopy->results;
    }
    else
    {
        CCDebug::Log("Warning: transfer exec seems to have failed");
        CCDebug::LogVar('SnoopyResults',$snoopy);
    }

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

function cc_remote_clear_queue()
{
    list( , $queue_file_name ) = cc_remote_get_settings();
    if( file_exists($queue_file_name) )
        unlink($queue_file_name);
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

    return array();
}

function & cc_remote_get_records()
{
    $q = cc_remote_get_queue();
    if( !empty($q) )
    {
        $files =& CCFiles::GetTable();
        $where = 'file_id IN (' . join( ',', $q ) . ')';
        $rows = $files->QueryRows($where,'file_upload');
        if( !empty($rows) )
        {
            $up_ids = array();
            foreach( $rows as $row )
                $up_ids[] = $row['file_upload'];
            $where = 'upload_id IN (' . join( ',', $up_ids ) . ')';
            $uploads =& CCUploads::GetTable();
            $uploads->SetDefaultFilter(true,true);
            $records =& $uploads->GetRecords($where);
            return $records;
        }
    }

    $e = array();
    return $e;
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

    $results = array();

    if( empty($mixter_ftp_settings['enable_streaming']) &&
        empty($mixter_ftp_settings['enable_download'])
      )
    {
        return $results;
    }

    $file_ids = cc_remote_get_queue();
    if( empty($file_ids) )
        return $results;

    $uploads  =& CCUploads::GetTable();
    $uploads->SetDefaultFilter(true,true);
    $files    =& CCFiles::GetTable();
    $done     = array();
    $count    = count($file_ids);

    for( $i = 0; $i < $count; $i++ )
    {
        $file_id = array_shift($file_ids);

        CCDebug::Log("Info: Staring transfer file $file_id");

        if( empty($file_id) )
        {
            CCDebug::Log("Warning: empty file_id in transfer queue");
            continue;
        }

        if( in_array( $file_id, $done ) )
        {
            CCDebug::Log("Warning: $file_id already transfered in this session");
            continue;
        }

        $upload_id = $files->QueryItemFromKey( 'file_upload', $file_id );
        $record =& $uploads->GetRecordFromKey( $upload_id );

        if( empty($record) )
        {
            CCDebug::Log("Warning: $file_id is in queue but won't be transfered (may be banned or deleted)");
            continue;
        }

        foreach( $record['files'] as $F )
        {
            if( $F['file_id'] != $file_id ) 
            {
                continue;
            }

            $ext = $F['file_extra'];

            if( !empty($ext['remote_file_name']) )
            {
                $retval = cc_remote_delete($ext['remote_file_name'],$mixter_ftp_settings);

                if( $retval < 0 )
                {
                    //die("Error on remote cmd: $retval");
                    CCDebug::Log("Warning: transfer exec failed. Returned: $retval");
                    return '';
                }
            }

            $url = cc_remote_send_file( $F['file_name'],
                                 $F['local_path'],
                                 $mixter_ftp_settings);

            if( $url )
            {
                CCDebug::Log("Info: {$F['file_name']} is at $url");
                $args['file_id'] = $file_id;
                $ext['remote_url'] = $url;
                $ext['remote_file_name'] = $F['file_name'];
                $args['file_extra'] = serialize($ext);
                $files->Update($args);
            }
            else
            {
                CCDebug::Log("Warning: Transfer seems to have failed");
            }

            $done[] = $file_id;
            break;
        }

        cc_remote_update_queue($file_ids,$queue_file_name );

    }

    return $done;
}

?>