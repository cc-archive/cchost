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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Wrapper for cc_tbl_uploads SQL table
*
* There are two tables that manage uploads, the CCUploads table manages 
* the meta data for the upload, CCFiles handles the actual physical
* files. (There can be multiple physical files for one upload record.)
* 
* <code> 
//  Rules for when an upload is visible:
//  
//   These apply to uploads not part of a contest:
//   ---------------------------------------------
//         If the admin said auto-publish:
//             SHOW if : the user said ok to publish
//         If the admin wants to vet uploads:
//             SHOW if : the admin checked the publish bit
//             
//   These apply to a contest entry:
//   --------------------------------------------
//         If the admin said auto-publish:
//             SHOW
//         If the admin withholds entries until deadline:
//             SHOW if : after contest deadline
// 
//   These two override all above states:
//   ----------------------------------
//   SHOW if : an admin is logged in
//   SHOW if : the registered user is the same as uploader
// 
//       If we get into an override state:
//       ---------------------------------
//        Display a notice to user that file is 'unpublished'
// 
//           If the current user is Admin:
//           -----------------------------
//             Display a command link to publish the work
</code>
* 
*
* @see CCUploads::CCUploads 
*/
class CCUploads extends CCTable
{
    var $_tags;
    var $_tag_filter_type; // 'any' or 'all'
    var $_filter;

    /**
     * Constructor
     *
     * 
     * <code> 
 //    This is what we're aiming for in the constructor:
 // 
 // SELECT *, DATE_FORMAT(upload_date, '$CC_SQL_DATE') as upload_date_format,
 //           DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format  
 //    FROM cc_tbl_uploads
 //    LEFT OUTER JOIN cc_tbl_contests e ON upload_contest  = e.contest_id
 //    LEFT OUTER JOIN cc_tbl_user     u ON upload_user     = u.user_id
 //    LEFT OUTER JOIN cc_tbl_license  c ON upload_license  = c.license_id
     * </code> 
     * 
    */
    function CCUploads($anon_user=false)
    {
        global $CC_SQL_DATE, $CC_CFG_ROOT;

        $this->CCTable('cc_tbl_uploads','upload_id');

        $juser = $this->AddJoin( new CCUsers(),    'upload_user');
        $this->AddJoin( new CCLicenses(), 'upload_license');
        $this->AddJoin( new CCContests(), 'upload_contest');
        
        $this->AddExtraColumn("DATE_FORMAT(upload_date, '$CC_SQL_DATE') as upload_date_format");
        $this->AddExtraColumn("DATE_FORMAT($juser.user_registered, '$CC_SQL_DATE') as user_date_format");
        $this->AddExtraColumn("0 as works_page, upload_banned as skip_remixes, 0 as ratings_score, 0 as reviews_link");

        $this->SetDefaultFilter(true,$anon_user);
    }

    /*
     * Typically this class is sensitive to the current user, showing files
     * they own even if it's banned or unpublished, etc. (Admins can see 
     * everything) Use the 'anon_user' flag on the constructor to create
     * an instance assumes anonymous user which is safe to use when 
     * creating lists that might be seen by someone other than the owner
     * or admin.
    */
    function SetDefaultFilter($set,$anon_user=false)
    {
        $this->_filter = '';

        if( !$set )
            return;

        // if the current user is admin, don't put 
        // any filters on the listings

        if( $anon_user || !CCUtil::IsHTTP() || !CCUser::IsAdmin() )
        {
            $this->_filter .= " ( ";

            if( !$anon_user && CCUser::IsLoggedIn() )
            {
                $userid = CCUser::CurrentUser();

                // let the current user see all their
                // files no matter what

                $this->_filter .=<<<END
                    (
                        upload_user = $userid
                    )
                    OR
END;
            }

            // todo: remove any notion of contest from here

            $entrybit    = CCUD_CONTEST_ENTRY;
            $sourcesbits = preg_replace('/[ ]?,[ ]?/','|',CCUD_CONTEST_ALL_SOURCES);
            // if not part of a contest, then check the published bit
            // otherwise, show entries in an auto-publish contest or the source parts
            // failing that, show entries after the deadline has passed

            $this->_filter .=<<<END
                (
                    (
                        upload_banned < 1
                    )
                    AND
                    (
                        upload_published

                        OR
                        (
                            upload_contest AND contest_publish 

                            AND 
                            
                            (
                                (
                                    contest_auto_publish OR
                                    (upload_tags REGEXP '(^| |,)($sourcesbits)(,|\$)' )
                                )
                                OR
                                (
                                   (upload_tags REGEXP '(^| |,)($entrybit)(,|\$)' ) AND
                                   ( NOW() > contest_deadline)
                                )
                            )
                        )
                    )
                )
END;

        $this->_filter .= " )";  //

        } // endif for non-admin users filters

    }


    /**
    * Returns static singleton of configs table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCUploads();
        return $_table;
    }

    function SetTagFilter($tags, $type='any')
    {
        if( is_array($tags) )
            $tags = implode(', ',$tags);

        $this->_tags = $tags;
        $this->_tag_filter_type = $type;
    }

    function & GetRecordFromRow($row)
    {
        if( is_string( $row['upload_extra'] ) )
            $row['upload_extra'] = unserialize($row['upload_extra']);

        $row['upload_description_html'] = CCUtil::TextToHTML($row['upload_description']);

        $row['upload_short_name'] = strlen($row['upload_name']) > 18 ? 
                                        substr($row['upload_name'],0,17) . '...' : 
                                        $row['upload_name'];

        $files =& CCFiles::GetTable();
        $row['files'] = $files->FilesForUpload($row);
        
        $tags =& CCTags::GetTable();

        $baseurl = ccl('tags');
        $utags = CCTag::TagSplit($row['upload_tags']);
        foreach($utags as $tag)
        {
            $row['upload_taglinks'][] = array( 'tagurl' => $baseurl . '/' . $tag,
                                               'tag' => $tag );
        }  

        $utags = $row['upload_extra']['ccud'];
        if( !empty($row['upload_extra']['usertags']) )
        {
            $ut = trim( $row['upload_extra']['usertags'] );
            if( !empty($ut) )
                $utags .= ',' . $ut;
        }

        $utags = CCTag::TagSplit($utags);
        foreach($utags as $tag)
        {
            $row['usertag_links'][] = array( 'tagurl' => $baseurl . '/' . $tag,
                                             'tag' => $tag );
        }  

        CCEvents::Invoke(CC_EVENT_UPLOAD_ROW, array( &$row ));

        if( $row['upload_banned'] )
        {
            if( strpos($row['upload_name'],'(moderated)') !== 0 )
                $row['upload_name'] = '(moderated) ' . $row['upload_name'];
        }
        elseif( empty($row['upload_contest']) && !$row['upload_published'] )
        {
            if( strpos($row['upload_name'],'(hidden)') !== 0 )
                $row['upload_name'] = '(hidden) ' . $row['upload_name'];
        }

        return $row;
    }

    function IsMediaType(&$record,$looking_for_media,$looking_for_ext='')
    {
        if( empty($record['files']) )
        {
            CCDebug::StackTrace();
            trigger_error('Invalid call to IsMediaType');
        }
        $file = $record['files'][0];
        if( empty($file['file_format_info']['media-type']) )
            return(false);
        $mt = $file['file_format_info']['media-type'];
        $ok = ($mt == $looking_for_media);
        if( $ok && $looking_for_ext )
            $ok =  $file['file_format_info']['default-ext'] == $looking_for_ext;
        return($ok);
    }

    function GetFormatInfo(&$record,$field='')
    {
        if( empty($record['files']) )
        {
            CCDebug::StackTrace();
            trigger_error('Invalid call to GetFormatInfo');
        }
        $file = $record['files'][0];
        if( empty($file['file_format_info']) )
            return(null);
        $F = $file['file_format_info'];
        if( $field && empty($F[$field]) )
            return( null );
        if( $field )
            return( $F[$field] );
        return( $F );
    }

    function SetExtraField( $id_or_row, $fieldname, $value)
    {
        if( is_array($id_or_row) )
        {
            $extra = $id_or_row['upload_extra'];
            $id = $id_or_row['upload_id'];
            if( is_string($extra) )
                $extra = unserialize($extra);
        }
        else
        {
            $id = $id_or_row;
            $row = $this->QueryKeyRow($id_or_row);
            $extra = unserialize($row['upload_extra']);
        }

        $extra[$fieldname] = $value;
        $args['upload_extra'] = serialize($extra);
        $args['upload_id'] = $id;
        $this->Update($args);
    }

    function GetExtraField( &$id_or_row, $fieldname )
    {
        if( is_array($id_or_row) )
        {
            $extra = $id_or_row['upload_extra'];
            if( is_string($extra) )
                $extra = unserialize($extra);
        }
        else
        {
            $row = $this->QueryKeyRow($id_or_row);
            $extra = unserialize($row['upload_extra']);
        }
        if( !empty($extra[$fieldname]) )
            return( $extra[$fieldname] );

        return( null );
    }

    function InTags($tags,&$record)
    {
        return( CCTag::InTag($tags,$record['upload_tags']));
    }

    function SplitTags(&$record)
    {
        return( CCTag::TagSplit($record['upload_tags']) );
    }

    // overwrite parent's version to add descriptor
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( !empty($this->_tags) )
        {
            if( $this->_tag_filter_type == 'any' )
            {
                $tagors = preg_replace('/[ ]?,[ ]?/','|',$this->_tags);
                $filter = " upload_tags REGEXP '(^| |,)($tagors)(,|\$)' ";
            }
            else
            {
                $tagands = array();
                $tagsarr = CCTag::TagSplit($this->_tags);
                foreach( $tagsarr as $tag )
                {
                    $tagands[] = "(upload_tags REGEXP '(^| |,)($tag)(,|\$)' )";
                }
                $filter = implode( ' AND ', $tagands );
            }
            if( empty($where) )
                $where = $filter;
            else
                $where = "$where AND ($filter)";
        }

        if( !empty($this->_filter) )
        {
            if( empty($where) )
                $where = $this->_filter;
            else
                $where = "$where AND \n ({$this->_filter})";
        }

        return( parent::_get_select($where,$columns) );
    }
}

/**
* Wrapper for cc_tbl_files SQL table
*
* There are two tables that manage uploads, the CCUploads table manages 
* the meta data for the upload, CCFiles handles the actual physical
* files. (There can be multiple physical files for one upload record.)
*
* @see CCUploads::CCUploads 
*/
class CCFiles extends CCTable
{
    /**
    * Constructor -- don't use new, use GetTable() instead
    *
    * @see CCTable::GetTable
    */
    function CCFiles()
    {
        $this->CCTable('cc_tbl_files','file_id');
        $this->SetOrder('file_order');
    }

    /**
    * Returns static singleton of configs table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCFiles();
        return( $_table );
    }

    /**
    * Return array of files records given an upload record
    *
    * @param mixed $row a row from CCUploads table
    * @returns array $rows Returns array of file records
    */
    function FilesForUpload($row)
    {
        $upload_id = is_array($row) ? $row['upload_id'] : $row;

        $where['file_upload'] = $upload_id;
        $rows = $this->QueryRows($where);
        for( $i = 0; $i < count($rows); $i++ )
        {
            $rows[$i]['file_format_info'] = unserialize($rows[$i]['file_format_info']);
            $rows[$i]['file_extra']       = unserialize($rows[$i]['file_extra']);
            $this->_format_file_size($rows[$i]);
        }

        CCEvents::Invoke(CC_EVENT_UPLOAD_FILES, array(&$row,&$rows) );

        return($rows);
    }

    /**
    * Internal goodie for formatting file sizes
    */
    function _format_file_size(&$row)
    {
        $fs = $row['file_filesize'];
        if( $fs )
        {
            $row['file_rawsize'] = $fs;
            if( $fs > CC_1MG )
                $fs = number_format($fs/CC_1MG,2) . 'MB';
            else
                $fs = number_format($fs/1024) . 'KB';
            $row['file_filesize'] = " ($fs)";
        }
        else
        {
            $row['file_filesize'] = '';
        }
    }


}

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
        $errs = CCUploadAPI::_do_verify_file_format($current_path,$file_args);
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
        CCUploadAPI::_do_get_systags(&$upload_args, $a_files, $ccud_tags, $user_tags);


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

        CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, array( $upload_args['upload_id'], CC_UF_NEW_UPLOAD ) );

        if( !$parents ) // sync'ing for remixes happens elsewhere
            CCSync::NewUpload($db_args['file_upload']);

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
        $errs = CCUploadAPI::_do_verify_file_format( $current_path, &$file_args );
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
        $errs = CCUploadAPI::_do_verify_file_format($current_path,&$file_args);
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
        CCUploadAPI::_do_get_systags(&$new_args, $new_args['files'], $ccud_tags, $user_tags);
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

    function _do_verify_file_format($current_path,&$file_args)
    {
        global $CC_UPLOAD_VALIDATOR;
        if( isset($CC_UPLOAD_VALIDATOR) )
        {
            $format_info =  new CCFileFormatInfo($current_path);

            $CC_UPLOAD_VALIDATOR->FileValidate( $format_info );
            $errors = $format_info->GetErrors();
            if( !empty($errors) )
            {
                $msg = cct("There was error in the file format<br />") . implode("<br />", $errors );
                CCDebug::Log($msg);
                return( $msg );
            }
        }

        $file_args['file_format_info'] = $format_info->GetData();
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
        global $CC_RENAMER;
        $newname = '';
        if( isset($CC_RENAMER) )
        {
            if( $CC_RENAMER->Rename($upload_args,$file_args,$newname) )
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
                $msg = "Rename to $new_path failed ($is_up)";
            }
            elseif( !file_exists($new_path) )
            {
                $msg = "Move to $new_path failed ($is_up)";
                $ok = false;
            }

            if( $ok )
            {
               // this seems to be failing on ccMixter ported
               // files only (!) when doing a property edit
               // hack fix: quiet the error...
               @chmod( $new_path, CC_DEFAULT_FILE_PERMS );
            }

        }

        return( $msg );
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
        $errs = CCUploadAPI::_do_rename( $record, $file_args, $current_path, $relative_dir );
        if( $errs )
            return($errs);
    
        // What follows is a little bit of cheat because instead
        // of calling GetRecordFromRow (which we deam too dangerous
        // because we don't have an actual record) we stuff these
        // values into the record ourselves. If in the future
        // some OnRecordRow event handler needs this step to ID3 tagging
        // work better this step will have to be rethought)
        //
        $file_args['local_path']   = cca($relative_dir,$file_args['file_name']);
        $file_args['download_url'] = ccd($relative_dir,$file_args['file_name']);


        if( defined('IN_MIXTER_PORT') )
            return;

        // Run the file through the ID3 tagger
        //
        global $CC_ID3_TAGGER;
        if( isset($CC_ID3_TAGGER) )
            $CC_ID3_TAGGER->TagFile( $record, $file_args['local_path'] );

        return(null);
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
        $upload_root = empty($CC_GLOBALS['user-upload-root']) ? 'people' : 
                               $CC_GLOBALS['user-upload-root'];
        $upload_root = realpath($upload_root);
        CCUtil::MakeSubdirs($upload_root);
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