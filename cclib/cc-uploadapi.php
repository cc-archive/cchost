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
* @subpackage io
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


require_once('cclib/cc-upload-table.php');

/**
* Non-UI Method for manipulating uploads and associated files
*
* This class is designed (i.e. supposed to) work from command line as well as
* in GUI
*
*/
class CCUploadAPI
{
    function UpdateCCUD($upload_id,$new_ccud,$replaces_ccud)
    {
        CCUploadAPI::_recalc_upload_tags($upload_id,$new_ccud,$replaces_ccud);
    }

    function DeleteUpload($upload_id)
    {
        require_once('cclib/cc-sync.php');
        require_once('cclib/cc-remix-tree.php');
        require_once('cclib/cc-tags.inc');

        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);    

        // we get the tree so for sync'ing later

        $remix_sources =& CCRemixSources::GetTable();
        $parents = $remix_sources->GetSources($record,false);
        if( !empty($parents) )
            $record['remix_parents'] = $parents;
        $remixes =& CCRemixes::GetTable();
        $children = $remixes->GetRemixes($record,false);
        if( !empty($children) )
            $record['remix_children'] = $children;
        
        CCEvents::Invoke(CC_EVENT_DELETE_UPLOAD, array( &$record ));

        $relative_dir = $record['upload_extra']['relative_dir'];
        $files =& CCFiles::GetTable();
        foreach( $record['files'] as $file )
        {
            $path = realpath($relative_dir . '/' . $file['file_name']);
            if( file_exists($path) )
                @unlink($path);
            $where['file_id'] = $file['file_id'];
            $files->DeleteWhere($where);
        }
        $where = array();
        $where['upload_id'] = $upload_id;
        $uploads->DeleteWhere($where);

        $tags =& CCTags::GetTable();
        $tags->TagDelete($record['upload_tags']);

        CCSync::Delete($record);
    }

    function PostProcessNewUpload(  $upload_args, 
                                    $current_path,
                                    $new_name,
                                    $ccud_tags,
                                    $user_tags,
                                    $relative_dir,
                                    $parents)
    {
        CCUploadAPI::_move_upload_file($current_path,$new_name,$is_temp);

        global $CC_CFG_ROOT;

        // make a JOIN so $upload_args can get all kinds of stuff
        // (user record, license info, etc.) so renaming (which needs 
        // user contest fields) and ID3 tagging (which needs licensing 
        // info, user and contst fields, etc.) will work properly.
        //
        $uploads =& CCUploads::GetTable();
        $uploads->FakeJoin($upload_args); 

        // We need to get an upload_id so that paths works
        // (like the song page for the CC License ID3 tag)
        //
        $upload_args['upload_id'] = $uploads->NextID();

        // Get remix children (if any) 
        //
        // This has to happen before renaming and ID3 tagging
        // so for example, the remix naming can have the parent
        // 
        if( $parents )
            $upload_args['remix_sources'] =& $parents;

        // Run the file through the verifier (is it allowed? it is valid?)
        // (this will update $file_args['file_format_info'])
        //
        $file_args = array();
        $file_args['file_extra'] = array();
        $errs = CCUploadAPI::_do_verify_file_size($current_path);
	$errs .= CCUploadAPI::_do_verify_file_format($current_path,$file_args);
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // get_systags and rename like array of files
        $a_files = array( &$file_args );

        // Get folksonomy tagging out of the way 
        // (this will update $upload_args['upload_tags'] and 
        //  $upload_args['upload_extra'])
        //
        CCUploadAPI::_do_get_systags($upload_args, $a_files, $ccud_tags, $user_tags);


        // Sometimes the user might leave the upload name field
        // empty so we tear off the file name and use that
        //
        if( empty( $upload_args['upload_name'] ) )
            $upload_args['upload_name'] = CCUtil::BaseFile($new_name);

        // The renamer wants these fields pre-filled in
        //
        $files =& CCFiles::GetTable();
        $file_args['file_name']  = $new_name;
        $file_args['file_id']    = $files->NextID();

        // Run everything through the renamer
        // (this method upldates $file_args['file_name']
        //
        $errs = CCUploadAPI::_do_rename_and_tag($upload_args, $file_args, $current_path, $relative_dir);
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // copy the new data and use that to create a new CCUpload record
        //
        $db_args = array();
        foreach( $upload_args as $field_name => $value )
        {
            if( strpos($field_name,'upload_') === 0 )
                $db_args[$field_name] = $value;
        }

        if( empty($db_args['upload_config']) )
        {
            $db_args['upload_config'] = $CC_CFG_ROOT;
        }

        $db_args['upload_extra']['relative_dir'] = $relative_dir;
        $db_args['upload_extra'] = serialize($db_args['upload_extra']);
        $db_args['upload_date'] = date( 'Y-m-d H:i:s' );
        $uploads->Insert($db_args);


        $tags =& CCTags::GetTable();
        $tags->Update($db_args['upload_tags']);

        // Do sh1, magnet link and other post upload stuff
        //
        CCEvents::Invoke( CC_EVENT_FILE_DONE, array( &$file_args ) );

        $db_args = array();
        $db_args['file_id']          = $file_args['file_id'];
        $db_args['file_upload']      = $upload_args['upload_id'];
        $db_args['file_name']        = $file_args['file_name'];
        $db_args['file_nicname']     = $file_args['file_format_info']['default-ext'];
        $db_args['file_extra']       = serialize($file_args['file_extra']);
        $db_args['file_format_info'] = serialize($file_args['file_format_info']);
        $db_args['file_filesize']    = filesize($file_args['local_path']);
        $files->Insert($db_args);

        CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, array( $upload_args['upload_id'], CC_UF_NEW_UPLOAD, &$parents ) );

        if( !$parents ) // sync'ing for remixes happens elsewhere
        {
            require_once('cclib/cc-sync.php');
            CCSync::NewUpload($db_args['file_upload']);
        }

        CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);

        return( intval($db_args['file_upload']) );
    }

    function PostProcessFileAdd( $record,
                                 $nicname,
                                 $current_path,
                                 $new_name,
                                 $relative_dir)
    {
        CCUploadAPI::_move_upload_file($current_path,$new_name,$is_temp);

        //CCDebug::StackTrace(false);

        // Run the file through the verifier (is it allowed? it is valid?)
        // (this will update $file_args['file_format_info'])
        //
        $file_args = array();
        $file_args['file_extra'] = array();
        $errs = CCUploadAPI::_do_verify_file_format( $current_path, $file_args );
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // The renaming code wants these fields pre-filled in...
        $files =& CCFiles::GetTable();
        $file_args['file_name']  = $new_name;
        $file_args['file_id']    = $files->NextID();

        // (_do_rename_and_tag will update $file_args['file_name'])
        //
        $errs = CCUploadAPI::_do_rename_and_tag( $record, $file_args, $current_path, $relative_dir );
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // Do sh1, magnet link and other post upload stuff
        //
        CCEvents::Invoke( CC_EVENT_FILE_DONE, array( &$file_args ) );

        // Make a new record in the CCFiles table
        //
        $db_args = array();
        $db_args['file_id']          = $file_args['file_id'];
        $db_args['file_upload']      = $record['upload_id'];
        $db_args['file_name']        = $file_args['file_name'];
        $db_args['file_nicname']     = empty($nicname) ? $file_args['file_format_info']['default-ext'] : $nicname;
        $db_args['file_extra']       = serialize($file_args['file_extra']);
        $db_args['file_format_info'] = serialize($file_args['file_format_info']);
        $db_args['file_filesize']    = filesize($file_args['local_path']);
        $db_args['file_order']       = 1 + $record['files'][ count($record['files']) - 1 ]['file_order'];

        $files->Insert($db_args);

        CCUploadAPI::_recalc_upload_tags($record['upload_id']);

        CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, array( $record['upload_id'], CC_UF_FILE_ADD ) );

        CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);

        return intval($record['upload_id']);
    }

    function PostProcessFileDelete( $file_id, &$upload_id )
    {
        CCEvents::Invoke( CC_EVENT_DELETE_FILE, array( $file_id ) );

        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($file_id);
        $upload_id = $row['file_upload'];

        $uploads =& CCUploads::GetTable();
        $relative_dir = $uploads->GetExtraField($upload_id,'relative_dir');
        $path = realpath( $relative_dir . '/' . $row['file_name'] );
        if( file_exists($path) )
            @unlink($path);

        $where['file_id'] = $file_id;
        $files->DeleteWhere($where);

        CCUploadAPI::_recalc_upload_tags($upload_id);
    }

    function PostProcessFileReplace( $overwrite_this,
                                     $nicname,
                                     $current_path,
                                     $new_name )
    {
        CCUploadAPI::_move_upload_file($current_path,$new_name,$is_temp);

        $files =& CCFiles::GetTable();
        $existing_row = $files->QueryKeyRow($overwrite_this);

        $upload_id = $existing_row['file_upload'];
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        $relative_dir = $record['upload_extra']['relative_dir'];

        // Run the file through the verifier (is it allowed? it is valid?)
        // (this will update $file_args['file_format_info'])
        //
        $file_args = array();
        $file_args['file_extra'] = array();
        $errs = CCUploadAPI::_do_verify_file_format($current_path,$file_args);
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // We have to nuke the existing one now in case the
        // new one has a different name.
        //
        $existing_path = cca($relative_dir,$existing_row['file_name']);
        if( file_exists($existing_path) )
            unlink($existing_path);

    
        // if the file exists (like in the case if hires/lores of the same format)
        //  _do_rename_and_tag needs a unique id
        // 
        $file_args['file_id']  = $existing_row['file_id'];

        // _do_rename_and_tag will update $file_args['file_name']
        // 
        $file_args['file_name']  = $new_name;
        $errs = CCUploadAPI::_do_rename_and_tag( $record, $file_args, $current_path, $relative_dir );
        if( $errs )
        {
            CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);
            return($errs);
        }

        // Do sh1, magnet link and other post upload stuff
        //
        CCEvents::Invoke( CC_EVENT_FILE_DONE, array( &$file_args ) );

        // Update the CCFiles table
        //
        $db_args = array();
        $db_args['file_id']          = $overwrite_this;
        $db_args['file_upload']      = $upload_id;
        $db_args['file_name']        = $file_args['file_name'];
        $db_args['file_nicname']     = empty($nicname) ? $file_args['file_format_info']['default-ext'] : $nicname;
        $db_args['file_extra']       = serialize($file_args['file_extra']);
        $db_args['file_format_info'] = serialize($file_args['file_format_info']);
        $db_args['file_filesize']    = filesize($file_args['local_path']);

        $files->Update($db_args);

        CCUploadAPI::_recalc_upload_tags($upload_id);

        CCUploadAPI::_cleanup_upload_file($current_path,$is_temp);

        CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, array( $upload_id, CC_UF_FILE_REPLACE ) );
    }

    function PostProcessEditUpload(  $upload_args, 
                                     $old_record,
                                     $relative_dir) 
    {
        // ---------
        // N.B. The following code assumes JOINed information
        // (user, license, contest, etc.) did NOT change for
        // this upload record
        // ----------

        // update: I'm not sure what the comment above means,
        // it is now possible to re-license an upload on
        // the way to this function and doesn't look
        // distruptive.

        // Save off old tags, we'll need this to replace 
        // folksonomy tags with new ones
        $old_tags = $old_record['upload_tags'];

        // Merge records for now. Duplicate are not
        // overwritten in + (union) so new values
        // are preserved
        //
        $new_args = $upload_args + $old_record;

        $ccud_tags = $new_args['upload_extra']['ccud'];
        $user_tags = $new_args['upload_tags'];

        // Get folksonomy tagging out of the way 
        // (this will update $upload_args['upload_tags'] and 
        //  $upload_args['upload_extra'])
        //
        CCUploadAPI::_do_get_systags($new_args, $new_args['files'], $ccud_tags, $user_tags);
        $upload_args['upload_tags']  = $new_args['upload_tags'];
        
        // Sometimes the user might leave the upload name field
        // empty so we tear off the file name of the first file
        // and use that
        //
        if( empty( $new_args['upload_name'] ) )
        {
            $upload_args['upload_name'] = 
            $new_args['upload_name']    = CCUtil::BaseFile($new_args['files'][0]['file_name']);
        }

        // Run each file through the renamer and ID3 tagger
        //
        $file_count = count($new_args['files']);
        for( $i = 0; $i < $file_count; $i++ )
        {
            $file_args =& $new_args['files'][$i];

            // (_do_rename_and_tag will update $file_args['file_name'])
            //
            $errs = CCUploadAPI::_do_rename_and_tag( $new_args, 
                                                  $file_args, 
                                                  $file_args['local_path'], 
                                                  $relative_dir );
            if( $errs )
                return($errs);
        }

        // copy the new data and use that to update the 
        // CCUpload record 
        //
        $upload_args['upload_extra'] = serialize($new_args['upload_extra']);
        $db_args = $upload_args;
        
        $uploads =& CCUploads::GetTable();
        $uploads->Update($db_args);

        $tags =& CCTags::GetTable();
        $tags->Replace($old_tags,$db_args['upload_tags']);

        // It's quite possible that all the formats of this
        // upload were renamed or their format_info changed
        // by plugin modules in which case it doesn't cost
        // that much to rifle through them and update the
        // records just in case
        //
        $files =& CCFiles::GetTable();
        for( $i = 0; $i < $file_count; $i++ )
        {
            $file_args =& $new_args['files'][$i];

            // Do sh1, magnet link and other post upload stuff
            //
            CCEvents::Invoke( CC_EVENT_FILE_DONE, array( &$file_args ) );

            $db_args = array();
            $db_args['file_id']          = $file_args['file_id'];
            $db_args['file_upload']      = $file_args['file_upload'];
            $db_args['file_name']        = $file_args['file_name'];
            $db_args['file_nicname']     = $file_args['file_nicname'];
            $db_args['file_format_info'] = serialize($file_args['file_format_info']);
            $db_args['file_extra']       = serialize($file_args['file_extra']);
            if( !defined('IN_MIXTER_PORT') )
            {
                $db_args['file_filesize']    = filesize($file_args['local_path']);
            }

            $files->Update($db_args);
        }

        CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, array( $new_args['upload_id'], CC_UF_PROPERTIES_EDIT, array(&$old_record) ) );
    }

    function _do_verify_file_size($current_path)
    {
        global $CC_GLOBALS;
        if( empty($CC_GLOBALS['enable_quota']) )
            return( null );
        $users = new CCUsers();
        $record = $users->QueryKeyRow($CC_GLOBALS['user_id']);
        $quota = empty($record['user_quota']) ? $CC_GLOBALS['default_quota'] : $record['user_quota'];
        $size = filesize($current_path);
        $upload_root = CCUser::GetPeopleDir();
        $upload_root = realpath($upload_root) . '/' . $CC_GLOBALS['user_name'];

        $total = $size;
        if( is_dir($upload_root) )
        {
            $handle = opendir($upload_root);
            while( $entry = readdir( $handle ) )
            {
                if ($entry == '.' || $entry == '..')
                    continue;
                $total += filesize($upload_root . '/' . $entry);
            }
        }

        if( $total > ( $quota * 1024000 ) && $quota > 0 )
        {
            $msg = _("You have not enough space") . "<br />";
            return ( $msg );
        }

        return( null );
    }

    function _do_verify_file_format($current_path,&$file_args)
    {
        require_once('cclib/cc-formatinfo.php');

        CCEvents::Invoke( CC_EVENT_INIT_VALIDATOR );

        global $CC_UPLOAD_VALIDATOR;

        $format_info =  new CCFileFormatInfo($current_path);
        
        if( isset($CC_UPLOAD_VALIDATOR) )
        {

            $CC_UPLOAD_VALIDATOR->FileValidate( $format_info );
            $errors = $format_info->GetErrors();
            if( !empty($errors) )
            {
                $msg = _("There was error in the file format") . "<br />" . implode("<br />", $errors );
                CCDebug::Log($msg);
                return( $msg );
            }
        
            $file_args['file_format_info'] = $format_info->GetData();
        }
        else
        {
            $data = $format_info->GetData();

            if( preg_match('/\.([^\.]+)$/',$current_path,$m) )
                $ext = $m[1];
            else
                $ext = 'tmp';

            $data['default-ext'] = $ext;

            $file_args['file_format_info'] = $data;
        }
        
        return( null );
    }

    function _do_get_systags(&$record,&$a_files, $ccud_tags,$user_tags)
    {
        $systags = array();
        $eargs = array( &$record, null, &$systags );
        CCEvents::Invoke( CC_EVENT_GET_SYSTAGS, $eargs );
        for( $i = 0; $i < count($a_files); $i++ )
        {
            $eargs2 = array( null, &$a_files[$i], &$systags );
            CCEvents::Invoke( CC_EVENT_GET_SYSTAGS, $eargs2 );
        }

        require_once('cclib/cc-tags.inc');
        $tags  =& CCTags::GetTable();
        $user_tags = $tags->CheckAliases($user_tags);
        $user_tags = $tags->CleanSystemTags($user_tags);

        // we keep these sperate in case they change later in the lifetype of the record
        // (like the user add/removes tags)

        $record['upload_extra']['usertags']    = CCUploadAPI::_concat_tags( $user_tags );
        $record['upload_extra']['ccud']        = CCUploadAPI::_concat_tags( $ccud_tags );
        $record['upload_extra']['systags']     = CCUploadAPI::_concat_tags( $systags );
        $all_tags                              = CCUploadAPI::_concat_tags( $ccud_tags, $systags, $user_tags );

        $tags->Insert($record['upload_extra']['ccud'],     CCTT_SYSTEM );
        $tags->Insert($record['upload_extra']['systags'],  CCTT_SYSTEM );
        $tags->Insert($record['upload_extra']['usertags'], CCTT_USER );

        // multiple formats can share tags, we reduced them down however

        $all_tags_arr = array_unique(CCTag::TagSplit($all_tags));

        $record['upload_tags'] = implode(',',$all_tags_arr);
    }

    function _do_rename( &$upload_args, &$file_args, $current_path, $relative_dir )
    {
        $renamer = null;
        CCEvents::Invoke( CC_EVENT_UPLOAD_RENAMER, array( &$renamer ) );
        $newname = '';
        if( isset($renamer) )
        {
            if( $renamer->Rename($upload_args,$file_args,$newname) )
            {
                $file_args['file_name'] = CCUtil::LegalFileName($newname);
            }
        }

        CCUtil::MakeSubdirs( $relative_dir ); // you have to make the dir for realpath() to work

        $current_path  = str_replace('\\', '/', $current_path);
        $new_path      = str_replace( '\\', '/', realpath($relative_dir) . '/' . $file_args['file_name'] );

        $msg = null;

        if( $new_path != $current_path )
        {
            $file_num = 1; 
            while( file_exists($new_path) )
            {
                $f = $file_args['file_name'];
                if( preg_match( '#[^_]*_([0-9]*)\.[^\.]+$#', $f ) )
                {
                    $newf = preg_replace( '#([^_]*_)([0-9]*)(\.[^\.]+)$#', '${1}' . $file_num . '${3}', $f );

                }
                else
                {
                    $newf = preg_replace( '#(.*)(\.[^\.]+)$#', '${1}_' . $file_num . '${2}', $f );
                }
                $file_args['file_name'] = $newf;
                $new_path = realpath($relative_dir) . '/' . $newf;
                ++$file_num;
            }

            $is_up = is_uploaded_file($current_path);

            if( $is_up )
                $ok = @move_uploaded_file($current_path,$new_path);
            else
                $ok = @rename($current_path,$new_path);

            if( !$ok )
            {
                $msg = sprintf(_("Rename to new path, %s, failed (%s)"), 
                                 $new_path, $is_up);
            }
            elseif( !file_exists($new_path) )
            {
                $msg = sprintf(_("Move to new path, %s, failed (%s)"), 
                                 $new_path, $is_up);
                $ok = false;
            }

            if( $ok )
            {
               // this seems to be failing on ccMixter ported
               // files only (!) when doing a property edit
               // hack fix: quiet the error...
               @chmod( $new_path, cc_default_file_perms() );
            }

        }

        return $msg;
    }
    
    function _concat_tags()
    {
        $ts = func_get_args();
        $result = '';
        foreach($ts as $t)
        {
            if( is_array($t) )
                $t = implode(',',$t);
            $t = trim($t);
            if( !$t )
                continue;
            if( empty($result) )
                $result = $t;
            else
                $result .= ',' . $t;
        }

        return( $result);
    }

    function _recalc_upload_tags($upload_id,$new_ccud = '',$replaces_ccud='')
    {
        // Just get the record again with the new 'files' 
        // array filled out (yes, heavy weight but safe)
        //
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);


        $old_tags = $record['upload_tags'];

        $ccud_tags = CCTag::TagSplit($record['upload_extra']['ccud']);

        if( $replaces_ccud )
        {
            if( is_string($replaces_ccud) )
                $replaces_ccud = CCTag::TagSplit($replaces_ccud);
            $ccud_tags = array_diff($ccud_tags,$replaces_ccud);
        }

        if( $new_ccud )
        {
            if( is_string($new_ccud) )
                $new_ccud = CCTag::TagSplit($new_ccud);
            $ccud_tags = array_merge( $ccud_tags, $new_ccud );
            $ccud_tags = array_unique( $ccud_tags );
        }

        $user_tags = $record['upload_extra']['usertags'];
        
        // (this will update $record['upload_tags']
        //
        CCUploadAPI::_do_get_systags( $record, $record['files'], $ccud_tags, $user_tags);

        // All we need to update is the one field
        //
        $db_args = array();
        $db_args['upload_id']    = $record['upload_id'];
        $db_args['upload_tags']  = $record['upload_tags'];
        $db_args['upload_extra'] = serialize($record['upload_extra']);
        $uploads->Update($db_args);

        $tags =& CCTags::GetTable();
        $tags->Replace($old_tags,$db_args['upload_tags']);
    }

    function _do_rename_and_tag( &$record, &$file_args, $current_path, $relative_dir )
    {
        // Run the file through the renamer 
        // (this will update $file_args['file_name'])
        //
        $msg = CCUploadAPI::_do_rename( $record, $file_args, $current_path, $relative_dir );
        if( !empty($msg) )
            return $msg;
    
        // What follows is a little bit of cheat because instead
        // of calling GetRecordFromRow (which we deam too dangerous
        // because we don't have an actual record) we stuff these
        // values into the record ourselves. If in the future
        // some OnRecordRow event handler needs this step to ID3 tagging
        // work better this step will have to be rethought)
        //
        $file_args['local_path']   = cca($relative_dir,$file_args['file_name']);
        $file_args['download_url'] = ccd($relative_dir,$file_args['file_name']);

        // Run the file through the ID3 tagger
        //
        $tagger = null;
        CCEvents::Invoke( CC_EVENT_UPLOAD_ID3TAGGER, array( &$tagger ) );
        if( isset($tagger) )
            $tagger->TagFile( $record, $file_args['local_path'] );

        return $msg;
    }

    function _move_upload_file(&$current_path,$new_name,&$is_temp)
    {
        // sigh
        //
        // getid3 requires that a file have an extension that
        // relates to the format. (hey, don't be so quick to judge)
        // since shared hosting environments are unlikely to allow
        // direct manipulation of files in /tmp we move the file
        // to a temp location (root of /people) with a unique name
        // 
        // if the upload worked then a rename() above will move
        // the temp file out of people. if the upload fails, 
        // the code in _cleanup_upload_file() will nuke it
        //

        $is_temp = false;

        if( !is_uploaded_file($current_path) )
            return;
        
        global $CC_GLOBALS;
        $upload_root = CCUser::GetPeopleDir();
        CCUtil::MakeSubdirs($upload_root);
        $upload_root = realpath($upload_root);
        if( preg_match('/\.([^\.]+)$/',$new_name,$m) )
            $ext = $m[1];
        else
            $ext = 'tmp';
        $root_name = substr( md5(uniqid(rand(),true)), rand() & 0x1F, 8 );
        $temp_name = $upload_root . '/cch_'. $CC_GLOBALS['user_name'] . '_' . $root_name . '.' . $ext;
        move_uploaded_file($current_path,$temp_name);
        $current_path = $temp_name;
        $is_temp = true;
        return $current_path;
    }

    function _cleanup_upload_file(&$current_path,$is_temp)
    {
        if( $is_temp && file_exists($current_path) )
            @unlink($current_path);
    }

}

?>
