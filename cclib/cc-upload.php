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
* @subpackage upload
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * Base class for forms that uplaod media files.
 * 
 */
class CCUploadMediaForm extends CCUploadForm 
{
    /**
     * Constructor.
     * 
     * Sets up basic editing fields for name, tags, description and the
     * file upload itself. Invokes the CC_UPLOAD_VALIDATOR 
     * to get a list of valid file types allowed for upload.
     *
     * @access public
     * @param integer $user_id This id represents the 'owner' of the media
     */
    function CCUploadMediaForm($user_id,$file_field = true)
    {
        global $CC_CFG_ROOT;

        $this->CCUploadForm();
        $this->SetSubmitText(cct('Upload'));
        $this->SetHiddenField('upload_user', $user_id);
        $this->SetHiddenField('upload_config', $CC_CFG_ROOT);

        $fields['upload_name'] =
                        array( 'label'      => cct('Name'),
                               'formatter'  => 'textedit',
                               'form_tip'   => cct('Display name for file'),
                               'flags'      => CCFF_POPULATE );

        if( $file_field )
        {
            CCUpload::GetUploadField($fields,'upload_file_name');
        }

        $tags =& CCTags::GetTable();
        $where['tags_type'] = CCTT_USER;
        $tags->SetOffsetAndLimit(0,'25');
        $tags->SetOrder('tags_count','DESC');
        $pop_tags = $tags->QueryKeys($where);

        $fields['upload_tags'] =
                        array( 'label'      => cct('Tags'),
                               'formatter'  => 'tagsedit',
                               'form_tip'   => cct('Comma separated list of terms'),
                               'flags'      => CCFF_NONE );

        $fields['popular_tags'] =
                        array( 'label'      => cct('Popular Tags'),
                               'target'     => 'upload_tags',
                               'tags'       => $pop_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => cct('Click on these to automatically add to your upload.'),
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE );

        $fields['upload_description'] =
                        array( 'label'      => cct('Description'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE );
        
        $this->AddFormFields( $fields );

        $this->_extra = array();

        CCPage::AddScriptBlock('popular_tags_script');
    }

    function AddSuggestedTags($suggested_tags)
    {
        if( empty($suggested_tags) )
            return;

        if( !is_array($suggested_tags) )
            $suggested_tags = CCTag::TagSplit($suggested_tags);

        $fields['suggested_tags'] =
                        array( 'label'      => cct('Suggested Tags'),
                               'target'     => 'upload_tags',
                               'tags'       => $suggested_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => cct('Click on these to automatically add to your upload.'),
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE );

        $this->InsertFormFields( $fields, 'before', 'popular_tags' );
    }

}

/**
 * Extend this class for forms that upload new media to the system.
 *
 */
class CCNewUploadForm extends CCUploadMediaForm
{
    /**
     * Constructor.
     *
     * Tweaks the bass class state to be in line with
     * new uploads, original or remixes.
     *
     * @access public
     * @param integer $userid The upload will be 'owned' by this user
     * @param integer $show_lic Set this to display license choices
     */
    function CCNewUploadForm($userid, $show_lic = true)
    {
        $this->CCUploadMediaForm($userid);

        $this->SetHiddenField('upload_date', date( 'Y-m-d H:i:s' ) );

        if( $show_lic )
        {
            $licenses =& CCLicenses::GetTable();
            $lics     = $licenses->GetEnabled();
            $count    = count($lics);
            if( $count == 1 )
            {
                $this->SetHiddenField('upload_license',$lics[0]['license_id']);
            }
            elseif( $count > 1 )
            {
                $fields = array( 
                    'upload_license' =>
                                array( 'label'      => cct('License'),
                                       'formatter'  => 'metalmacro',
                                       'flags'      => CCFF_POPULATE,
                                       'macro'      => 'license_choice',
                                       'license_choice' => $lics
                                )
                            );
                
                $this->AddFormFields( $fields );
            }
        }
        
    }

}

class CCConfirmDeleteForm extends CCForm
{
    function CCConfirmDeleteForm($pretty_name)
    {
        $this->CCForm();
        $this->SetHelpText(cct("This action can not be reversed..."));
        $this->SetSubmitText(sprintf(cct("Delete \"%s\" ?"),$pretty_name));
    }
}

/**
* @package cchost
* @subpackage admin
*/
class CCAdminUploadForm extends CCForm
{
    function CCAdminUploadForm(&$record)
    {
        $this->CCForm();

        $tags =& CCTags::GetTable();
        $where['tags_type'] = CCTT_SYSTEM;
        $tags->SetOrder('tags_tag','ASC');
        $sys_tags = $tags->QueryKeys($where);

        $fields = array(
            'ccud' => array(
                'label' => 'Internal Tags',
                'form_tip' => 'Be careful when editing these, it\'s easy to confuse the system',
                'value' => $record['upload_extra']['ccud'],
                'formatter' => 'textedit',
                'flags' => CCFF_REQUIRED | CCFF_POPULATE
                ),
            'popular_tags' =>
                        array( 'label'      => 'System Tags',
                               'target'     => 'ccud',
                               'tags'       => $sys_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => 'Click on these to automatically add',
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE 
                ),
            );

        $this->AddFormFields($fields);
        CCPage::AddScriptBlock('popular_tags_script');

    }
}

// -----------------------------
//  Upload UI
// -----------------------------
class CCUpload
{

    function EnsureFiles(&$record,$fetch_if_missing)
    {
        if( empty($record['files']) )
        {
            if( !$fetch_if_missing )
                return;

            $files =& CCFiles::GetTable();
            $record['files'] = $files->FilesForUpload($record);
        }
    }

    function AdminUpload($upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);
        if( empty($record) )
            return;
        $name = $record['upload_name'];
        CCPage::SetTitle("Administrator Functions for '$name'");
        $form = new CCAdminUploadForm($record);
        if( empty($_POST['adminupload']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            CCUploadAPI::UpdateCCUD($upload_id,$values['ccud'],$record['upload_extra']['ccud']);
            $url = $record['file_page_url'];
            CCPage::Prompt("Changes saved to '$name', click <a href=\"$url\">here</a> to see results");
        }
    }


    function ListMultipleFiles( $sql_where = '', 
                                $ccud = '', // CCUD_MEDIA_BLOG_UPLOAD, 
                                $search_type = '', 
                                $macro = '' )
    {
        if( empty($search_type) )
            $search_type = 'all';

        $uploads =& CCUploads::GetTable();

        $uploads->SetTagFilter($ccud,$search_type);
        CCPage::AddPagingLinks($uploads,$sql_where);
       
        $uploads->SetSort( 'upload_date', 'DESC' );

        $records =& $uploads->GetRecords($sql_where);

        CCUpload::ListRecords($records,$macro);
    }

    function ListRecords( &$records, $macro = '' )
    {
        if( empty($macro) )
            $macro = 'list_files';

        $count = count($records);

        for( $i = 0; $i < $count; $i++ )
        {
            $records[$i]['local_menu'] = CCUpload::GetRecordLocalMenu($records[$i]);
            CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$records[$i]));
        }

        CCPage::PageArg( 'file_records', $records, $macro);
        CCPage::AddScriptBlock('ajax_block');

        if( CCUser::IsAdmin() && !empty($_REQUEST['dump_rec']) )
            CCDebug::PrintVar($records[0],false);

        CCEvents::Invoke( CC_EVENT_LISTING_RECORDS, array( &$records ) );
    }

    function GetRecordLocalMenu(&$record)
    {
        $menu = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$record),CC_EVENT_BUILD_UPLOAD_MENU);
        $grouped_menu = array();
        foreach( $menu as $key => $item )
        {
            if( !isset($item['type']) )
                $item['type'] = '';
            $grouped_menu[$item['group_name']][$key] = $item;
        }
        return( $grouped_menu );
    }

    function Delete($upload_id)
    {
        $this->CheckFileAccess(CCUser::CurrentUser(),$upload_id);
        $uploads =& CCUploads::GetTable();
        CCPage::SetTitle(cct("Deleting File"));
        if( empty($_POST['confirmdelete']) )
        {
            $pretty_name = $uploads->QueryItemFromKey('upload_name',$upload_id);
            $form = new CCConfirmDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCUploadAPI::DeleteUpload($upload_id);
            CCPage::Prompt(cct("Upload has been deleted"));
        }
    }

    function AddMacroRef(&$row,$macro_group, $macro)
    {
        if( empty($row[$macro_group]) || !in_array( $macro, $row[$macro_group] ) )
        {
            $row[$macro_group][] = $macro;
        }
    }

    function CheckFileAccess($usernameorid,$upload_id)
    {
        if( CCUser::IsAdmin() )
            return(true);
        if( !CCUser::IsLoggedIn() )
            CCUtil::AccessError();
        CCUser::CheckCredentials($usernameorid);
        $uploads =& CCUploads::GetTable();
        if( !intval($usernameorid) )
            $usernameorid = CCUser::IDFromName($usernameorid);
        $fileowner = $uploads->QueryItemFromKey('upload_user',$upload_id);
        // $s = "arg: $usernameorid / owner: $fileowner";
        if(  $fileowner != $usernameorid )
            CCUtil::AccessError();
    }

    function GetUploadField(&$fields,$field_name = 'upload_file_name')
    {
        global $CC_UPLOAD_VALIDATOR;

        $types = array();
        if( isset($CC_UPLOAD_VALIDATOR) )
            $CC_UPLOAD_VALIDATOR->GetValidFileTypes($types);

        if( empty($types) )
        {
            $form_tip = cct('Specify file to upload');
        }
        else
        {
            $types = implode(', ',$types);
            $form_tip = cct("Valid file types: ") . $types;
        }

        $fields[$field_name] = 
                           array(  'label'      => cct('File'),
                                   'formatter'  => 'upload',
                                   'form_tip'   => $form_tip,
                                   'flags'      => CCFF_REQUIRED  );
    }

    function PostProcessNewUploadForm( &$form, $ccud_tags, $relative_dir, $parents = null)
    {
        $form->GetFormValues($values);
        $current_path = $values['upload_file_name']['tmp_name'];
        $new_name     = $values['upload_file_name']['name'];
        $user_tags    = $values['upload_tags'];

        // All fields here that start with 'upload_' are 
        // considered to be fields in the CCUploads table
        // so....
        // Destroy the $_FILES object so it doesn't get
        // confused with that 

        unset($values['upload_file_name']);

        $ret = CCUploadAPI::PostProcessNewUpload(   $values, 
                                                    $current_path,
                                                    $new_name,
                                                    $ccud_tags,
                                                    $user_tags,
                                                    $relative_dir,
                                                    $parents );

        if( is_string($ret) )
        {
            $form->SetFieldError('upload_file_name',$ret);
            return(0);
        }

        return($ret);
    }

    function PostProcessEditUploadForm($form, $record, $relative_dir)
    {
        $form->GetFormValues($upload_args);

        $ret = CCUploadAPI::PostProcessEditUpload( $upload_args, $record, $relative_dir );

        if( is_string($ret) )
        {
            $form->SetFieldError('upload_file_name',$ret);
            return(0);
        }

        return( intval($record['upload_id']) );
    }

}


?>