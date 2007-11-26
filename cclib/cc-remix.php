<?php
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
* Module for handling Remix UI
*
* @package cchost
* @subpackage upload
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * Remix API
 *
 */
class CCRemix
{
    /**
    * Display UI for managing remix ('I Sampled This') list
    *
    * @param integer $upload_id Uplaod ID to edit remixes for
    */
    function EditRemixes($upload_id)
    {
        global $CC_GLOBALS;

        require_once('cclib/cc-pools.php');
        require_once('cclib/cc-upload.php');
        require_once('cclib/cc-remix-forms.php');

        CCUpload::CheckFileAccess(CCUser::CurrentUserName(),$upload_id);

        $uploads =& CCUploads::GetTable();
        $name = $uploads->QueryItemFromKey('upload_name',$upload_id);
        $msg = sprintf(_("Editing Remixes for '%s'"),$name);
        CCPage::SetTitle($msg);

        $form = new CCEditRemixesForm($upload_id);
        $show = false;
        if( empty($_REQUEST['editremixes']) )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            // this will do a AddForm if it has to
            $this->OnPostRemixForm($form, '', '', $upload_id);
        }
    }

    /**
    * Function called in repsonse to submit on a remix form
    *
    * @param object &$form CCForm object
    * @param string $relative_dir Target directory of upload
    * @param string $ccud System tag to attach to upload
    * @param integer $remixid Upload id of remix editing
    */
    function OnPostRemixForm(&$form, $relative_dir, $ccud = CCUD_REMIX, $remixid = '')
    {
        require_once('cclib/cc-pools.php');
        require_once('cclib/cc-sync.php');

        $is_update  = !empty($remixid);
        $uploads    =& CCUploads::GetTable();
        $pool_items =& CCPoolItems::GetTable();

        $remix_sources = array();
        $pool_sources  = array();

        $have_sources =  CCRemix::_check_for_sources( 'remix_sources', $uploads,    $form, $remix_sources );
        $have_sources |= CCRemix::_check_for_sources( 'pool_sources',  $pool_items, $form, $pool_sources );

        if( ($is_update || $have_sources) && $form->ValidateFields() )
        {
            $remixes =& CCRemixes::GetTable();
            $pool_tree =& CCPoolRemixes::GetTable();

            if( $is_update )
            {
                CCSync::RemixDetach($remixid);
                $where1['tree_child'] = $remixid;
                $remixes->DeleteWhere($where1);
                $where2['pool_tree_child'] = $remixid;
                $pool_tree->DeleteWhere($where2);
            }
            else
            {
                $remixid = CCUpload::PostProcessNewUploadForm($form,
                                                               $ccud,
                                                               $relative_dir,
                                                               $remix_sources);
            }


            if( $remixid )
            {
                CCRemix::_update_remix_tree('remix_sources', $remixid, 'tree_parent', 
                                        'tree_child', $remixes);

                CCRemix::_update_remix_tree('pool_sources', $remixid, 
                                        'pool_tree_pool_parent', 'pool_tree_child', $pool_tree);

                if( $is_update )
                {
                    // license might have changed
                    if( $have_sources )
                    {
                        $upargs['upload_license'] = $form->GetFormValue('upload_license');
                        $upargs['upload_id'] = $remixid;
                        $uploads->Update($upargs);
                    }

                    // ccud might have changed...
                    // for both license and ccud let's just recalc all the tags...

                    $current_tags = $uploads->QueryItemFromKey('upload_tags',$remixid);

                    if( CCTag::InTag( CCUD_ORIGINAL . ',' . CCUD_REMIX, $current_tags ) )
                    {
                        require_once('cclib/cc-uploadapi.php');

                        $ccuda = array( CCUD_ORIGINAL, CCUD_REMIX  );

                        CCUploadAPI::UpdateCCUD( $remixid, $ccuda[$have_sources], $ccuda[!$have_sources] );
                    }
                }

                // dig out the page url
                $uploads->SetTagFilter(''); // this shouldn't be needed -- but it is
                $record = $uploads->GetRecordFromID($remixid);
                $url = $record['file_page_url'];

                if( !empty($pool_sources) )
                {
                    CCSync::PoolSourceRemix($pool_sources);
                    CCPool::NotifyPoolsOfRemix($pool_sources,$url);
                }

                CCSync::Remix($remixid,$remix_sources);

                CCEvents::Invoke(CC_EVENT_SOURCES_CHANGED, array( $remixid, &$remix_sources) );

                $msg = $is_update ? _('update') : _('upload');
                $link1 = "<a href=\"$url\">";
                $link2 = '</a>';
                $prompt = sprintf(_("Remix %s succeeded (click %shere%s to see the results)."),
                    $msg, $link1, $link2);
                CCPage::Prompt($prompt);
                return(true);
            }
        }

        CCPage::AddForm( $form->GenerateForm() );

        return( false );
    }


    /**
    * @access private
    */
    function _update_remix_tree($field, $remixid, $parentf, $childf, &$table)
    {
        if( empty($_POST[$field]) )
            return;

        $sourceids = array_keys($_POST[$field]);
        if( empty($sourceids) )
            return;

        $all_fields = array();
        foreach( $sourceids as $sourceid )
        {
            $fields = array();
            $fields[$parentf] = CCUtil::StripText($sourceid);
            $fields[$childf]  = $remixid;
            $all_fields[] = $fields;
        }

        $table->InsertBatch( array($parentf, $childf), $all_fields );
        return($sourceids);
    }

    /**
    * @access private
    */
    function _check_for_sources( $field, &$table, &$form, &$remix_sources )
    {
        if( !empty($_POST[$field]) )
        {
            //
            // This means the user has actually identified and 
            // checked off some remix sources
            //
            $remix_check_boxes = array_keys($_POST[$field]);
            $remix_sources = $table->GetRecordsFromKeys($remix_check_boxes,true);
            if( !empty($remix_sources) )
            {
                $form->SetTemplateVar( $field, $remix_sources );
                return(true);
            }
        }
        return( false );
    }

    /**
    * Calculate the strictest license given a set of uploads
    *
    * @param object &$form CCForm object
    * @param array &$rows Array of upload rows
    */
    function RemixLicenses()
    {
        $pool_sources = $_GET['pool_sources'];
        $rows_r = $rows_p = array();
        if( !empty($_GET['remix_sources']) )
        {
            $sql = 'SELECT DISTINCT upload_license FROM cc_tbl_uploads WHERE upload_id IN (' . $_GET['remix_sources'] . ')';
            $rows_r = CCDatabase::QueryItems($sql);
        }
        if( !empty($_GET['pool_sources']) )
        {
            $sql = 'SELECT DISTINCT pool_item_license FROM cc_tbl_pool_item WHERE pool_item_id IN (' . $_GET['pool_sources'] . ')';
            $rows_p = CCDatabase::QueryItems($sql);
        }
        $rows = array_unique(array_merge($rows_r,$rows_p));
        require_once('cclib/cc-lics-chart.inc');
        $license = '';
        foreach( $rows as $L )
        {
            $license = $license ? cc_stricter_license( $L, $license ) : $L;
        }
        $lics = new CCTable('cc_tbl_licenses','license_id');
        $row = $lics->QueryKeyRow($license);
        require_once('cclib/zend/json-encoder.php');
        $text = CCZend_Json_Encoder::encode($row);
        header( "X-JSON: $text");
        header( 'Content-type: text/plain');
        print($text);
        exit;
    }


    /**
    * Event hander for {@link CC_EVENT_DELETE_UPLOAD}
    * 
    * @param array $record Upload database record
    */
    function OnUploadDelete( &$row )
    {
        $id = $row['upload_id'];
        $where = "(tree_parent = $id) OR (tree_child = $id)";
        $tree = new CCRemixTree('tree_parent','tree_child');
        $tree->DeleteWhere($where);
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('file','remixes'), array( 'CCRemix', 'EditRemixes'), 
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{upload_id}', _("Displays 'Manage Remixes' for upload"), CC_AG_UPLOAD );
        CCEvents::MapUrl( ccp('remixlicenses'), array( 'CCRemix', 'RemixLicenses'), 
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{upload_id}', _("Ajax callback to calculate licenses"), CC_AG_UPLOAD );
    }


}


?>
