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
* User interface for managing physical files
*
* @package cchost
* @subpackage io
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-upload-forms.php');

/**
 * This class is used to edit the values of media already in the system
 *
 */
class CCEditFileForm extends CCUploadMediaForm 
{
    /**
     * Sets up the upload media form base class to act like a property form.
     *
     * Derived from UploadMediaForm just to share the fields.
     *
     * @param integer $userid Owner of the media being edited
     * @param integer $record Database record being edited
     */
    function CCEditFileForm($userid,&$record)
    {
        $this->CCUploadMediaForm($userid,false);
        $this->SetHiddenField('upload_id' , $record['upload_id']);
        $this->SetFormValue('upload_tags', $record['upload_extra']['usertags']);
        $this->SetSubmitText('str_file_save_properties');


        $url = ccl('file','manage',$record['upload_id'] );


        $fields['upload_man_files'] =
                array( 'label'              => '',
                       'form_tip'           => 'str_files_update_the_list',
                       'value'              => "<a class=\"cc_file_command\" href=\"$url\">" .
                                                    _('Manage Files') . "</a>",
                       'formatter'          => 'statictext',
                       'flags'              => CCFF_STATIC | CCFF_NOUPDATE );


        $url = ccl('file','remixes',$record['upload_id'] );

        $fields['upload_remixes'] =
                array( 'label'              => '',
                       'form_tip'           => 'str_files_update_sources',
                       'value'              => "<a class=\"cc_file_command\" href=\"$url\">" .
                                                   _('Manage Remixes') . "</a>",
                       'formatter'          => 'statictext',
                       'flags'              => CCFF_STATIC | CCFF_NOUPDATE );

        $this->InsertFormFields( $fields, 'top' );
        $this->EnableSubmitMessage(false);
    }

    function _looser_child_lics($record)
    {
        $id = $record['upload_id'];
        $strict = $record['license_strict'];

        $sql =<<<END
            SELECT DISTINCT lic.*
            FROM 
                cc_tbl_tree t, 
                cc_tbl_uploads u1, 
                cc_tbl_uploads u2,
                cc_tbl_licenses lic
            WHERE 
                t.tree_child = $id                 AND 
                t.tree_parent = u2.upload_id       AND
                u2.upload_license = lic.license_id AND
                lic.license_strict <= $strict
END;

        $lics1 = CCDatabase::QueryRows($sql);

        $sql =<<<END
         SELECT DISTINCT lic.*
            FROM 
                cc_tbl_pool_tree t, 
                cc_tbl_uploads u1, 
                cc_tbl_pool_item p,
                cc_tbl_licenses lic
            WHERE 
                t.pool_tree_child =       $id            AND 
                t.pool_tree_pool_parent = p.pool_item_id AND
                p.pool_item_license     = lic.license_id AND
                lic.license_strict <= $strict
END;

        $lics2 = CCDatabase::QueryRows($sql);

        $lics = array();
        foreach($lics1 as $lic)
            $lics[ $lic['license_id'] ] = $lic;
        foreach($lics2 as $lic)
            $lics[ $lic['license_id'] ] = $lic;

        return( $lics  );
    }
}

/**
* Form used for add file formats to an upload record
*
*/
class CCFileAddForm extends CCUploadForm
{
    /**
    * Constructor
    *
    */
    function CCFileAddForm()
    {
        $this->CCUploadForm();
        $fields = array();
        CCUpload::GetUploadField($fields);
        $fields['file_nicname'] = 
                array( 'label'              => 'str_files_nickname',
                       'form_tip'           => 'str_files_lofi_hires',
                       'formatter'          => 'textedit',
                       'flags'              => CCFF_POPULATE );
        $this->AddFormFields($fields);
    }
}

/**
* Form used for replacing individual file formats
*
*/
class CCFilePropsForm extends CCFileAddForm
{
    /**
    * Constructor
    *
    * @param string $oldnic Current nicname for upload
    * @param bool $do_upload true means show the upload file input field
    */
    function CCFilePropsForm($oldnic)
    {
        $this->CCFileAddForm();
        $this->SetFormValue( 'file_nicname', $oldnic );
    }
}

class CCFileNicknameForm extends CCForm
{
    /**
    * Constructor
    *
    * @param string $oldnic Current nicname for upload
    */
    function CCFileNicknameForm($oldnic)
    {
        $this->CCForm();
        $fields['file_nicname'] = 
                array( 'label'              => 'str_files_nickname',
                       'form_tip'           => 'str_files_lofi_hires',
                       'formatter'          => 'textedit',
                       'value'              => $oldnic,
                       'flags'              => CCFF_POPULATE );
        $this->AddFormFields($fields);
    }
}


/**
* API and system event handler class for handling files
*
*/
class CCPhysicalFile
{
    function Manage($upload_id)
    {
        $upload_id = CCUtil::StripText($upload_id);
        if( empty($upload_id) || !intval($upload_id) )
            return;

        $record = CCDatabase::QueryRow('SELECT upload_id, upload_name, user_name, upload_contest FROM cc_tbl_uploads JOIN cc_tbl_user ' .
                                                      'ON upload_user = user_id WHERE upload_id = ' . $upload_id);
        $record['file_page_url'] = ccl('files',$record['user_name'],$upload_id);

        $dv = new CCDataView();
        $e = array( 'e' => array( CC_EVENT_FILTER_FILES ) ); // requires nested arrays
        $recs = array( &$record );
        $dv->FilterRecords( $recs, $e );

        require_once('cclib/cc-page.php');

        $this->_build_bread_crumb_trail($upload_id,true,false,'str_file_manage');
        CCPage::SetTitle( 'str_files_manage' );

        $args['upload_id'] = $upload_id;
        $args['files'] = &$record['files'];
        $args['urls'] = array( 
                        'upload_new_url'    => ccl('file','add',$upload_id),
                       'upload_replace_url' => ccl('file','replace'),
                       'upload_delete_url'  => ccl('file','delete'),
                       'upload_jockey_url'  => ccl('file','jockey',$upload_id),
                       'upload_nicname_url' => ccl('file','nickname'),
                    );
        
        CCPage::PageArg('field', $args, 'edit_files_links' );

    }

    /**
    * Handler for /file/edit URL
    *
    * Shows and processes form for editing upload properties
    *
    * @param string $username Owner of file record
    * @param integer $upload_id Upload record id number
    */
    function Edit($username,$upload_id)
    {
        require_once('cclib/cc-upload.php');
        require_once('cclib/cc-page.php');
        require_once('cclib/cc-dataview.php');
        CCUpload::CheckFileAccess($username,$upload_id);

        $this->_build_bread_crumb_trail($upload_id,false,false,'str_file_edit');

        $userid = CCUser::IDFromName($username);

        CCPage::SetTitle('str_edit_properties');

        $info = array(
            'sql' => 'SELECT *, user_name FROM cc_tbl_uploads JOIN cc_tbl_user ON upload_user=user_id WHERE upload_id='.$upload_id,
            'e'   => array( CC_EVENT_FILTER_FILES, CC_EVENT_FILTER_EXTRA )
            );
        $dv = new CCDataView();
        $record = $dv->PerformInfo( $info, array(), CCDV_RET_RECORD);
        $form = new CCEditFileForm($userid,$record);
        $show = true;
        if( empty($_POST['editfile']) )
        {
            $form->PopulateValues($record);
        }
        else
        {
            if( $form->ValidateFields() )
            {
                CCUpload::PostProcessEditUploadForm($form, $record, $record['upload_extra']['relative_dir'] );
                $url = url_args( ccd('files',CCUser::CurrentUserName(),$upload_id), 'prompt=str_file_changed' );
                CCUtil::SendBrowserTo($url);
                $show = false;
            }
        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handler for reordering files within an upload record
    *

    * @param integer $file_id The file_id field in the CCFiles database record
    */
    function Jockey($upload_id)
    {
        $this->CheckFileAccess(0,$upload_id);
        $new_order = CCUtil::Strip($_GET['file_order']);
        if( empty($new_order) )
            die('no file order?');
        $i = 0;
        for( $i = 0; $i < count($new_order); $i++ )
        {
            $n = $new_order[$i] - 1;
            $sql = "UPDATE cc_tbl_files SET file_order = $i WHERE file_upload = $upload_id AND file_order = $n";
            CCDatabase::Query($sql);
        }

        CCUtil::ReturnAjaxMessage('str_files_have_been_reordered');
    }

    /**
    * @access private
    */
    function _build_bread_crumb_trail($upload_id,$edit,$manage,$cmd)
    {
        $trail[] = array( 'url' => ccl(), 
                          'text' => 'str_home');
        
        $trail[] = array( 'url' => ccl('people'), 
                          'text' => 'str_people' );

        $sql = 'SELECT user_real_name, upload_name, user_name FROM cc_tbl_uploads ' .
                'JOIN cc_tbl_user ON upload_user=user_id WHERE upload_id='.$upload_id;

        list( $user_real_name, $upload_name, $user_name ) = CCDatabase::QueryRow($sql, false);

        $trail[] = array( 'url' => ccl('people',$user_name), 
                          'text' => $user_real_name );

        $trail[] = array( 'url' => ccl('files',$user_name, $upload_id), 
                          'text' => '"' . $upload_name . '"' );

        if( $edit )
        {
            $trail[] = array( 'url' => ccl('files','edit', $user_name, $upload_id), 
                               'text' => 'str_file_edit');

            if( $manage )
            {
                $trail[] = array( 'url' => ccl('file','manage', $upload_id), 
                                   'text' => 'str_file_manage');
            }
        }

        $trail[] = array( 'url' => '', 'text' => $cmd );

        CCPage::AddBreadCrumbs($trail);
    }

    /**
    * Internal helper
    */
    function _title_and_prompt($upload_id,$is_manage=false)
    {
        list( $pretty_name, $user_name ) = CCDatabase::QueryRow(
            'SELECT upload_name,user_name FROM cc_tbl_uploads JOIN cc_tbl_user ON upload_user=user_id WHERE upload_id='.$upload_id, false );

        CCPage::SetTitle('str_edit_properties');
        $path = ccl('files',$user_name,$upload_id);
        $msg= sprintf(_("Changes saved, see %s."), '<a href="' . $path . '">' . $pretty_name . ' ' . _('page') . '</a>');
        if( $is_manage )
        {
            $url = ccl('file','manage',$upload_id);
            $msg .= sprintf(_("Or, go back to <a href=\"%s\">Manage Files</a>."),$url);
        }
        CCPage::Prompt($msg);
    }

    /**
    * Handler for file/delete URL
    *
    * Shows confirmation dialog and then processes delete request
    *
    * @param integer $file_id file_id of CCFiles record to delete
    */
    function Delete($file_id)
    {
        $this->CheckFileAccess($file_id);
        list( $upload_id, $pretty_name ) = CCDatabase::QueryRow(
                'SELECT file_upload, file_name FROM cc_tbl_files WHERE file_id ='.$file_id, false );
        require_once('cclib/cc-page.php');
        $this->_build_bread_crumb_trail($upload_id,true,true,'str_file_delete_one');
        $files =& CCFiles::GetTable();
        if( empty($_POST['confirmdelete']) )
        {
            CCPage::SetTitle('str_files_delete_s',$pretty_name);
            $form = new CCConfirmDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            require_once('cclib/cc-uploadapi.php');
            CCUploadAPI::PostProcessFileDelete( $file_id, $upload_id );
            CCUtil::SendBrowserTo( ccl('file','manage',$upload_id) );
        }
    }

    /**
    * Handlers file/replace URL, shows and processes properties form for a file
    *
    * @param integer $file_id file_id of CCFiles record to edit
    */
    function Replace($file_id)
    {
        $this->CheckFileAccess($file_id);
        require_once('cclib/cc-page.php');
        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($file_id);
        $this->_build_bread_crumb_trail($row['file_upload'],true,true,'str_file_replace');
        CCPage::SetTitle('str_file_replace_s',$row['file_name']);
        $form = new CCFilePropsForm($row['file_nicname']);
        $show = true;
        if( !empty($_POST['fileprops']) && $form->ValidateFields() )
        {
            $form->GetFormValues($values);
            $current_path = $values['upload_file_name']['tmp_name'];
            $new_name     = $values['upload_file_name']['name'];
            $nicname      = $values['file_nicname'];

            $ret = CCUploadAPI::PostProcessFileReplace( $file_id,
                                                     $nicname,
                                                     $current_path,
                                                     $new_name);

            if( is_string($ret) )
            {
                $form->SetFieldError('upload_file_name',$ret);
            }
            else
            {
                CCUtil::SendBrowserTo( ccl('file','manage',$row['file_upload']) );
            }

        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );

    }

    /**
    * Handles file/nicname URL, shows and process form for changing the 'nicname' of an upload
    *
    * The 'nicname' is used in the download command of a given format.
    *
    * @param integer $file_id The file_id of the CCFiles record to edit
    */
    function Nicname($file_id)
    {
        $this->CheckFileAccess($file_id);
        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($file_id);
        $this->_build_bread_crumb_trail($row['file_upload'],true,true,'str_file_nicname');
        CCPage::SetTitle('str_files_nickname_for_s',$row['file_name']);
        $form = new CCFileNicknameForm($row['file_nicname']);
        $show = true;
        if( !empty($_POST['filenickname']) && $form->ValidateFields() )
        {
            $form->GetFormValues($values);
            if( empty($values['file_nicname']) )
            {
                $fi = unserialize($row['file_format_info']);
                $values['file_nicname'] = $fi['default-ext'];
            }
            $values['file_id'] = $file_id;
            $files->Update($values);
            CCUtil::SendBrowserTo( ccl('file','manage',$row['file_upload']) );
        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handles file/add URL, shows and process form for adding a file to an upload record
    *
    * @param integer $upload_id The upload_id of the CCUploads table to add this file to
    */
    function Add($upload_id)
    {
        $this->CheckFileAccess(0,$upload_id);
        require_once('cclib/cc-page.php');
        $upload_name = CCDatabase::QueryItem('SELECT upload_name FROM cc_tbl_uploads WHERE upload_id='.$upload_id);
        $this->_build_bread_crumb_trail($upload_id,true,true,'str_file_add_one');
        CCPage::SetTitle('str_files_add_to_s',$upload_name);
        $form = new CCFileAddForm();
        $show = true;
        if( !empty($_POST['fileadd']) && $form->ValidateFields() )
        {
            $dv = new CCDataView();
            $record = $dv->PerformFile('default',array( 'where'=> 'upload_id='.$upload_id),CCDV_RET_RECORD);

            $form->GetFormValues($values);
            $current_path = $values['upload_file_name']['tmp_name'];
            $new_name     = $values['upload_file_name']['name'];
            $relative_dir = $record['upload_extra']['relative_dir'];
            $nicname      = $values['file_nicname'];

            require_once('cclib/cc-uploadapi.php');
            $ret = CCUploadAPI::PostProcessFileAdd( $record,
                                                 $nicname,
                                                 $current_path,
                                                 $new_name,
                                                 $relative_dir);

            if( is_string($ret) )
            {
                $form->SetFieldError('upload_file_name',$ret);
            }
            else
            {
                CCUtil::SendBrowserTo( ccl('file','manage',$upload_id) );
            }

        }

        if( $show )
        {
            require_once('cclib/cc-page.php');
            CCPage::AddForm( $form->GenerateForm() );
        }

    }

    /**
    * Confirm this user has the right to edit the records
    *
    * You can pass either the file_id for a record from CCFiles table
    * or an upload_id for a record from the CCUploads table.
    * 
    * This method will NOT return if access fails.
    *
    * @param integer $file_id The file_id from the CCFiles table
    * @param integer $upload_id The upload_id from the CCUploads table
    */
    function CheckFileAccess($file_id, $upload_id=0)
    {
        if( !$upload_id )
        {
            require_once('cclib/cc-uploadapi.php');
            $files =& CCFiles::GetTable();
            $upload_id = $files->QueryItemFromKey('file_upload',$file_id);
        }
        require_once('cclib/cc-upload.php');
        CCUpload::CheckFileAccess(CCUser::CurrentUser(),$upload_id);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'file/add',     array('CCPhysicalFile','Add'),     CC_MUST_BE_LOGGED_IN , ccs(__FILE__) );
        CCEvents::MapUrl( 'file/replace', array('CCPhysicalFile','Replace'), CC_MUST_BE_LOGGED_IN , ccs(__FILE__) );
        CCEvents::MapUrl( 'file/jockey',  array('CCPhysicalFile','Jockey'),  CC_MUST_BE_LOGGED_IN , ccs(__FILE__) );
        CCEvents::MapUrl( 'file/delete',  array('CCPhysicalFile','Delete'),  CC_MUST_BE_LOGGED_IN , ccs(__FILE__) );
        CCEvents::MapUrl( 'file/nickname',array('CCPhysicalFile','Nicname'), CC_MUST_BE_LOGGED_IN , ccs(__FILE__) );
        CCEvents::MapUrl( 'file/manage',  array('CCPhysicalFile','Manage'), 
            CC_MUST_BE_LOGGED_IN , ccs(__FILE__), '', _('Show "Manage Files" form'), CC_AG_UPLOADS );
    }

}


?>
