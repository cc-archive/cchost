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


CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCPhysicalFile', 
                                                   'OnMapUrls'));

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
        $this->SetSubmitText(_('Save File Properties'));

        $licenses =& CCLicenses::GetTable();

        // Get the licenses that are looser than this one
        $lics = $this->_looser_child_lics($record);
        
        if( empty($lics) )
            $lics = $licenses->GetEnabled($record['license_strict']);

        $lics[ $record['upload_license'] ] = $record;

        if( count($lics) > 1 )
        {
            $select_fields = array();
            foreach( $lics as $lic )
                $select_fields[ $lic['license_id'] ] = $lic['license_name'];
            $fields['upload_license'] = 
                array( 'label' => _('License'),
                   'form_tip' => _('NOTE: You can only pick less restrictive license. Once you have done that you can not re-license under a stricter license (on this site). '),
                   'formatter' => 'select',
                   'value'     => $record['license_id'],
                   'options'   => $select_fields,
                   'flags'     => CCFF_NONE );
        }

        $url = ccl('file','manage',$record['upload_id'] );


        $fields['upload_man_files'] =
                array( 'label'              => _("Manage Files"),
                       'form_tip'           => _('Update the list of files used by this upload'),
                       'value'              => "<a class=\"cc_file_command\" href=\"$url\">" .
                                                    _('Manage Files') . "</a>",
                       'formatter'          => 'statictext',
                       'flags'              => CCFF_STATIC | CCFF_NOUPDATE );


        $url = ccl('file','remixes',$record['upload_id'] );

        $fields['upload_remixes'] =
                array( 'label'              => _("Manage the 'I Sampled This' List"),
                       'form_tip'           => _('Update the list of sources used by this upload'),
                       'value'              => "<a class=\"cc_file_command\" href=\"$url\">" .
                                                   _('Manage Remixes') . "</a>",
                       'formatter'          => 'statictext',
                       'flags'              => CCFF_STATIC | CCFF_NOUPDATE );

        $this->AddFormFields( $fields );

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
                array( 'label'              => _('Nickname'),
                       'form_tip'           => _("(e.g. 'lofi', 'hires') Leave blank to use default"),
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
                array( 'label'              => _('Nickname'),
                       'form_tip'           => _("(e.g. 'lofi', 'hires') Leave blank to use default"),
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
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);
        if( empty($record) )
            return;
        CCPage::SetTitle( _("Manage files for: ") . $record['upload_name'] );

        $args['files'] = &$record['files'];
        $args['urls'] = array( 
                        'upload_new_url'          => ccl('file','add',$record['upload_id']),
                       'upload_replace_url'      => ccl('file','replace'),
                       'upload_delete_url'       => ccl('file','delete'),
                       'upload_jockey_up_url'    => ccl('file','jockey','up'),
                       'upload_jockey_down_url'  => ccl('file','jockey','down'),
                       'upload_nicname_url'      => ccl('file','nickname'),
                    );
        
        $help = '<p>' . sprintf(_('This is where you add or replace files associated with \'%s\''), '<a href="%s">%s</a>.') . "</p>\n" . 
        '<p>' . _('Use this screen to upload associated files. Common reasons to upload multiple files:') . "</p>" . 

            '<ol><li>' . _('There are multiple resolutions of the main file (different bit rates, aspect ratios, etc.).') . '</li>' . 
            '<li>' . _('There are multiple formats of the same file (e.g. mp3, ogg, wma, etc. for audio).') . '</li>' .
            '<li>' . _('There are samples associated with the upload such as solo tracks or layers.') . '</li>' . 
            '<li>' . _('There are streamable audio or image previews for an archive (e.g. ZIP) upload.') . '</li>' . 
            '</ol>' . 
        
        '<p>' . _('HINT: Use the \'Nickname\' to distinguish between different uploads (e.g. \'LoRes\', \'Hires\', etc.) The default value is based on the file format (extension).') . '</p>' .
'<p>' . _('HINT: The file at the top of the list is used for all default streaming and podcasting commands.') . '</p>';
            
        $args['form_about'] = sprintf(_($help),$record['file_page_url'],$record['upload_name']);
        
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
        CCUpload::CheckFileAccess($username,$upload_id);

        $userid = CCUser::IDFromName($username);
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        $pretty_name = $record['upload_name'];
        CCPage::SetTitle(_("Edit Properties: ") . $pretty_name);
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
                $msg = sprintf(_("Changes saved (see %s)"), '<a href="' . $record['file_page_url'] . '">' . _('results') . '</a>');
                CCPage::Prompt($msg);
                $show = false;
            }
        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handler for file/jockey/up and file/jockey/down URLs
    *
    * This method will shift the position of a file record, moving
    * record up or down. If it makes it into the first slot then
    * it will be treated as the 'default' upload used by render code
    *
    * @param string $dir 'up' or 'down'
    * @param integer $file_id The file_id field in the CCFiles database record
    * @param bool    $with_ui true means show titles and prompt, false means do 'silently'
    */
    function Jockey($dir,$file_id,$with_ui=true)
    {
        $this->CheckFileAccess($file_id);
        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($file_id);
        $where['file_upload'] = $row['file_upload'];
        $rows = $files->QueryRows($where);
        $count = count($rows);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $rows[$i]['file_id'] == $file_id )
            {
                $swap_src = $i;
                if($dir == 'down')
                    $swap_dest = $i + 1;
                else
                    $swap_dest = $i - 1;
                break;
            }
        }
        $db_args = array();
        $db_args['file_id']    = $rows[$swap_src]['file_id'];
        $db_args['file_order'] = $rows[$swap_dest]['file_order'];
        $files->Update($db_args);
        $db_args['file_id']    = $rows[$swap_dest]['file_id'];
        $db_args['file_order'] = $rows[$swap_src]['file_order'];
        $files->Update($db_args);

        if( $with_ui )
            $this->_title_and_prompt($row['file_upload'],true);
    }

    /**
    * Internal helper
    */
    function _title_and_prompt($upload_id,$is_manage=false)
    {
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        $pretty_name = $record['upload_name'];
        CCPage::SetTitle(_("Edit Properties: ") . $pretty_name);
        $path = $record['file_page_url'];
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
        $files =& CCFiles::GetTable();
        if( empty($_POST['confirmdelete']) )
        {
            $pretty_name = $files->QueryItemFromKey('file_name',$file_id);
            CCPage::SetTitle(sprintf(_("Delete File '%s'"),$pretty_name));
            $form = new CCConfirmDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCUploadAPI::PostProcessFileDelete( $file_id, $upload_id );
            $this->_title_and_prompt($upload_id,true);
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
        $files =& CCFiles::GetTable();
        $row = $files->QueryKeyRow($file_id);
        CCPage::SetTitle(sprintf(_("Replace '%s'"),$row['file_name']));
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
                $show = false;
                $this->_title_and_prompt($row['file_upload'],true);
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
        CCPage::SetTitle(sprintf(_("Nickname for '%s'"),$row['file_name']));
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
            $show = false;
            $this->_title_and_prompt($row['file_upload'],true);
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
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        CCPage::SetTitle(sprintf(_("Add File to '%s'"),$record['upload_name']));
        $form = new CCFileAddForm();
        $show = true;
        if( !empty($_POST['fileadd']) && $form->ValidateFields() )
        {
            $form->GetFormValues($values);
            $current_path = $values['upload_file_name']['tmp_name'];
            $new_name     = $values['upload_file_name']['name'];
            $relative_dir = $record['upload_extra']['relative_dir'];
            $nicname      = $values['file_nicname'];

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
                $show = false;
                $this->_title_and_prompt($upload_id,true);
            }

        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );

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
            $files =& CCFiles::GetTable();
            $upload_id = $files->QueryItemFromKey('file_upload',$file_id);
        }
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
