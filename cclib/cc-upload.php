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
        CCPage::SetTitle(sprintf(_("Administrator Functions for '%s'"), $name));
        require_once('cclib/cc-upload-forms.php');
        $form = new CCAdminUploadForm($record);
        if( empty($_POST['adminupload']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            
            require_once('cclib/cc-uploadapi.php');

            CCUploadAPI::UpdateCCUD($upload_id,$values['ccud'],$record['upload_extra']['ccud']);
            $url = $record['file_page_url'];
            $link1 = "<a href=\"$url\">";
            CCPage::Prompt(sprintf(_("Changes saved to '%s'. Click %shere%s to see results"), 
                        $name, $link1, '</a>'));
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

    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        extract($args);

        if( strtolower($format) != 'page' )
            return;

        if( !empty($title) )
            CCPage::SetTitle($title);

        if( !empty($tmacro) )
        {
            $dochop = isset($chop) && $chop > 0;
            $chop   = isset($chop) ? $chop : 25;

            CCPage::PageArg('chop',$chop);
            CCPage::PageArg('dochop',$dochop);
            CCPage::PageArg( '_query_macro', $tmacro );
            $macro = '_query_macro';
        }

        if( !empty($template_args) )
        {
            foreach( $template_argas as $K => $V )
                CCPage::PageArg($K,$V);
        }

        $menu  = empty($nomenu);
        $srcs  = empty($nosrc);
        $macro = empty($macro) ? '' : $macro;

        if( !empty($macro) && !empty($template) )
            CCPage::PageArg($macro,$template . '/' . $macro);

        // we don't know WHAT shape the global table is in but
        // we have the latest where used so we use that for
        // paging (as it happens the query url will recognize offset
        // in it)

        $temp_up = new CCUploads();
        
        if( !empty($playlist) )  /// HHHHHHAAAAACK
        {
            // aaaaah!!!
            require_once('ccextras/cc-cart-table.inc');
            $temp_up->AddJoin( new CCPlaylistItems(), 'upload_id', 'LEFT OUTER', 'cart_item_upload' );
        }

        global $CC_GLOBALS;
        $CC_GLOBALS['fplay_args'][] = $qstring;

        CCPage::AddPagingLinks($temp_up,$last_where);

        $this->ListRecords($records, $macro, $menu, $srcs );

        if( !empty($feed) )
        {
            // Let folks know they can subscribe to this query

            $feed = strlen($feed) > 10 ? substr($feed,0,8) . '...' : $feed;
            $tags = empty($tags) ? '' : $tags;
            $qstring = empty($qstring) ? '' : $qstring;
            CCFeed::AddFeedLinks( $tags, $qstring, $feed);
        }

        $result = true;
    }


    function ListRecords( &$records, $macro = '', $menu = true, $srcs = true )
    {
        if( empty($macro) )
            $macro = 'list_files';

        $count = count($records);

        for( $i = 0; $i < $count; $i++ )
        {
            if( $menu )
                $records[$i]['local_menu'] = CCUpload::GetRecordLocalMenu($records[$i]);
            if( $srcs )
                CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$records[$i]));
        }

        CCPage::PageArg( 'file_records', $records, $macro);

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
        CCPage::SetTitle(_("Deleting File"));
        if( empty($_POST['confirmdelete']) )
        {
            $pretty_name = $uploads->QueryItemFromKey('upload_name',$upload_id);
            require_once('cclib/cc-upload-forms.php');
            $form = new CCConfirmDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            require_once('cclib/cc-uploadapi.php');
            CCUploadAPI::DeleteUpload($upload_id);
            CCPage::Prompt(_("Upload has been deleted."));
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
        CCEvents::Invoke(CC_EVENT_INIT_VALIDATOR);

        global $CC_UPLOAD_VALIDATOR;

        $types = array();
        if( isset($CC_UPLOAD_VALIDATOR) )
            $CC_UPLOAD_VALIDATOR->GetValidFileTypes($types);

        if( empty($types) )
        {
            $form_tip = _('Specify the file to upload.');
        }
        else
        {
            $types = implode(', ',$types);
            $form_tip = _("Valid file types") . ": " . $types;
        }

        $fields[$field_name] = 
                           array(  'label'      => _('File'),
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

        require_once('cclib/cc-uploadapi.php');

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

        require_once('cclib/cc-uploadapi.php');

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
