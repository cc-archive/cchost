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
*
*/
define('CC_REMIX_SEARCH_LIMIT', 30 );

CCEvents::AddHandler(CC_EVENT_UPLOAD_LISTING, array( 'CCRemix', 'OnUploadListing'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCRemix', 'OnUploadDelete'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCRemix' ,'OnMapUrls') );

/**
 * Base class for uploading remixes form
 *
 * Note: derived classes must call SetHandler()
 * @access public
 */
class CCPostRemixForm extends CCNewUploadForm
{
    /**
     * Constructor
     *
     * Sets up form as a remix form. Initializes 'remix search' box.
     
     * @access public
     * @param integer $userid The remix will be 'owned' by owned by this user
     */
    function CCPostRemixForm($userid,$show_pools=false)
    {
        $this->CCNewUploadForm($userid,false);

        if( $show_pools )
            CCRemix::_add_pool_to_form($this);

        CCRemix::_setup_search_fields($this);
    }

    /**
     * Overrides the base class and only displays fields if search results is not empty.
     *
     */
    function GenerateForm()
    {
        if( $this->TemplateVarExists('remix_sources') || $this->TemplateVarExists('pool_sources')  )
        {
            parent::GenerateForm(false);
        }
        else
        {
            $this->EnableSubmitMessage(false);
            $this->SetSubmitText(null);
            parent::GenerateForm(true); // hiddenonly = true
        }

        return( $this );
    }

}


class CCEditRemixesForm extends CCForm
{
    /**
     * Constructor
     *
     * Sets up form as a remix editing form. Initializes 'remix search' box.
     *
     * @param bool $show_pools (reserved)
     */
    function CCEditRemixesForm($show_pools=false)
    {
        $this->CCForm();

        if( $show_pools )
            CCRemix::_add_pool_to_form($this);

        CCRemix::_setup_search_fields($this);

        $this->SetSubmitText(cct('Done Editing'));
    }
}


/**
 * Remix API
 *
 */
class CCRemix
{
    /**
    * @access private
    */
    function _setup_search_fields(&$form)
    {
        if( !empty($_POST['remix_search_type']) )
            $type = CCUtil::StripText($_POST['remix_search_type']);
        else
            $type = 'artist';

        $searchtypes[] = array( 'value' => 'artist',
                                'label' => cct('Artist'),
                                'selected' => $type == 'artist');
        $searchtypes[] = array( 'value' => 'title',
                                'label' => cct('Title'),
                                'selected' => $type == 'title' );
        $searchtypes[] = array( 'value' => 'full',
                                'label' => cct('Full search'),
                                'selected' => $type == 'full'  );

        $form->SetTemplateVar('remix_search_types', $searchtypes );
        $form->SetTemplateVar( 'remix_search_query', '' );

        $form->CallFormMacro('remix_search', 'show_remix_search');

        $this->SetTemplateVar('inputcheck', 'checked="checked"' );
    }

    /**
    * @access private
    */
    function _add_pool_to_form(&$form)
    {
        $pool_table =& CCPools::GetTable(); // tada!!!
        $where = "pool_api_url > '' AND pool_search > 0 AND pool_banned < 1";
        $pools = $pool_table->QueryRows($where);
        if( !empty($pools) )
        {
            array_unshift( $pools, array( 'pool_id' => -1,
                                          'pool_name' => 'This site' ));
            $sel_pool = empty($_POST['pool']) ? -1 : $_POST['pool'];
            $count = count($pools);
            for( $i = 0; $i < $count; $i++ )
            {
                $pools[$i]['selected'] = $pools[$i]['pool_id'] == $sel_pool;
            }

            $form->SetTemplateVar('pools', $pools );
        }
    }

    /**
    * Display UI for managing remix ('I Sampled This') list
    *
    * @param integer $upload_id Uplaod ID to edit remixes for
    */
    function EditRemixes($upload_id)
    {
        global $CC_GLOBALS;

        CCUpload::CheckFileAccess(CCUser::CurrentUserName(),$upload_id);

        $uploads =& CCUploads::GetTable();
        $name = $uploads->QueryItemFromKey('upload_name',$upload_id);
        $msg = sprintf(cct("Editing Remixes for '%s'"),$name);
        CCPage::SetTitle($msg);
        $pools    = empty($CC_GLOBALS['allow-pool-search']) ? false : $CC_GLOBALS['allow-pool-search'];
        $form = new CCEditRemixesForm($pools);
        $show = false;
        if( empty($_REQUEST['editremixes']) )
        {
            $record =& $uploads->GetRecordFromID($upload_id);
            $record['works_page'] = true;
            CCPage::PageArg( 'chop', false );
            
            $remix_sources =& CCRemixSources::GetTable();
            $sources = $remix_sources->GetSources($record,false);
            $form->SetTemplateVar( 'remix_sources', $sources );

            $pool_sources =& CCPoolSources::GetTable();
            $psources = $pool_sources->GetSources($record,false);
            $form->SetTemplateVar( 'pool_sources', $psources );

            $show = true;
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
        $is_update  = !empty($remixid);
        $uploads    =& CCUploads::GetTable();
        $pool_items =& CCPoolItems::GetTable();

        $remix_sources = array();
        $pool_sources  = array();

        $have_sources =  CCRemix::_check_for_sources( 'remix_sources', $uploads,    $form, $remix_sources );
        $have_sources |= CCRemix::_check_for_sources( 'pool_sources',  $pool_items, $form, $pool_sources );

        if( $have_sources )
        {
            $tmerged = array_merge($remix_sources,$pool_sources);
            CCRemix::StrictestLicense($form, $tmerged);
            unset($tmerged);
        }

        if( !empty($_POST['search']) || !empty($_POST['accept_remixes']) )
        {
            CCRemix::_perform_search($form);
        }
        elseif( !empty($_POST['form_submit']) )
        {

            //
            // this means the user hit submit:
            //
            // they have selected sources along the way
            //
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

                        // this can't be right... (what if it's an a cappella?)

                        $ccuda = array( CCUD_ORIGINAL, CCUD_REMIX  );

                        CCUploadAPI::UpdateCCUD( $remixid, $ccuda[$have_sources], $ccuda[!$have_sources] );
                    }

                    // dig out the page url
                    $uploads->SetTagFilter(''); // this shouldn't be needed -- but it is
                    $record = $uploads->GetRecordFromID($remixid);
                    $url = $record['file_page_url'];

                    if( !empty($pool_sources) )
                    {
                        CCPool::NotifyPoolsOfRemix($pool_sources,$url);
                    }

                    CCSync::Remix($remixid,$remix_sources);

                    $msg = $is_update ? 'update' : 'upload';
                    $prompt = sprintf(cct("Remix %s succeeded (click <a href=\"%s\">here</a> to see results)"),
                        $msg, $url);
                    CCPage::Prompt($prompt);
                    return(true);
                }
            }
        }

        CCPage::AddForm( $form->GenerateForm() );

        return( false );
    }

    /**
    * @access private
    */
    function _perform_search(&$form)
    {
        $query = CCUtil::StripText($_POST['remix_search_query']);
        $form->SetTemplateVar( 'remix_search_query', $query );

        if( !empty($query) && empty($_POST['accept_remixes']) )
        {
            $type = CCUtil::StripText($_POST['remix_search_type']);

            //
            // User hit the 'search' key
            //
            $pool_id = empty($_POST['pool']) ? 0 : intval(CCUtil::StripText($_POST['pool']));

            if( $pool_id > 0 )
            {
                list( $type, $pool_results ) = CCPool::PoolQuery($pool_id, $query, $type);

                if( !empty($pool_results) )
                {
                    if( $type == 'rss' )
                    {
                        $pools =& CCPools::GetTable();
                        $pool = $pools->QueryKeyRow($pool_id);
            
                        $items = array();
                        foreach( $pool_results as $pool_result )
                        {
                            $item = CCPool::AddItemToPool( $pool, $pool_result );
                            if( is_array($item) )
                            {
                                $items[] = array_merge( $item, $pool );
                            }
                        }
                    }
                    else
                    {
                        $items = $pool_results;
                    }

                    if( !empty($items) )
                    {
                        $form->SetTemplateVar( 'pool_search_result', $items);
                        $form->SetTemplateVar( 'got_search_result', true);
                    }
                }
            }
            else
            {
                switch( $type )
                {
                    case 'artist':
                        $fields = array( 'user_name', 'user_real_name' );
                        $limit = 1000; // let's see teru hit this number
                        break;

                    case 'title':
                        $fields = array( 'upload_name ');
                        $limit = CC_REMIX_SEARCH_LIMIT;
                        break;

                    default:
                        $limit = CC_REMIX_SEARCH_LIMIT;
                        $fields = array();
                }

                CCSearch::DoSearch( $query, 'all', CC_SEARCH_UPLOADS, $results, $limit, $fields );
                $count = 0;
                if( !empty($results[CC_SEARCH_UPLOADS]) )
                {
                    $sremixes = array();
                    $count = count($results[CC_SEARCH_UPLOADS]);
                    $keys = array_keys($results[CC_SEARCH_UPLOADS]);
                    $me = CCUser::CurrentUser();
                    for( $i = 0; $i < $count; $i++ )
                    {
                        $rm =& $results[CC_SEARCH_UPLOADS][ $keys[$i] ];
                        if( $rm['upload_user'] != $me )
                            $sremixes[] = $rm;
                    }

                    if( !empty($sremixes) )
                    {
                        $form->SetTemplateVar( 'remix_search_result', $sremixes );
                        $form->SetTemplateVar( 'got_search_result', true);
                    }
                }

                $form->SetTemplateVar( 'remix_search_limit_hit', $count >= $limit );
            }
        }

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
            $remix_sources = $table->GetRecordsFromKeys($remix_check_boxes);
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
    function StrictestLicense( &$form, &$rows )
    {
        $license = '';
        $strict = 0;
        foreach( $rows as $row )
        {
            if( !$license || ($strict < $row['license_strict'] ) )
            {
                $strict  = $row['license_strict'];
                $license = $row['license_id'];
            }
        }

        $form->CallFormMacro( 'remix_license', 'show_remix_license' );
        $form->SetHiddenField( 'upload_license', $license, CCFF_HIDDEN | CCFF_STATIC );
        $lics =& CCLicenses::GetTable();
        $licenserow = $lics->QueryKeyRow($license);
        $form->AddTemplateVars( $licenserow  );
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
    * Event handler for {@link CC_EVENT_UPLOAD_LISTING}
    *
    * Final chance to massage a record before being displayed in a list
    * 
    * @param array &$row Record to massage with extra display information
    */
    function OnUploadListing( &$row )
    {
        global $CC_GLOBALS;

        $fhome = ccl() . 'files/';
        $phome = ccl() . 'people/';
        $upload_id = $row['upload_id'];
        $limit = empty($CC_GLOBALS['works_page']) ? 'LIMIT ' . CC_MAX_SHORT_REMIX_DISPLAY : '';

        $select =<<<END
                SELECT upload_name, user_real_name,
                       CONCAT( '$fhome', user_name, '/', upload_id ) as file_page_url,
                       CONCAT( '$phome', user_name ) as artist_page_url
                FROM cc_tbl_uploads
                LEFT OUTER JOIN cc_tbl_user j1 ON upload_user = user_id
END;

        $extra_where = '';

        if( !empty($row['upload_num_sources']) )
        {
            // fast version:

            $sql =<<<END
                $select
                LEFT OUTER JOIN cc_tbl_tree j4 ON upload_id = j4.tree_parent  
                WHERE tree_child = '$upload_id'
                $extra_where
                $limit
END;

            // slow version:

            //$remix_sources =& CCRemixSources::GetTable();
            //$parents = $remix_sources->GetSources($row);

            $parents = CCDatabase::QueryRows($sql);
            $this->_mark_row($row,'has_parents','remix_parents', $parents, 'more_parents_link');
        }


        if( !empty($row['upload_num_remixes']) )
        {
            // fast version:

            $sql =<<<END
                $select
                LEFT OUTER JOIN cc_tbl_tree j4 ON upload_id = j4.tree_child
                WHERE tree_parent = '$upload_id'
                $extra_where
                $limit
END;

            // slow version:

            //$remixes =& CCRemixes::GetTable();
            //$children = $remixes->GetRemixes($row);

            $children = CCDatabase::QueryRows($sql);
            if( !$this->_mark_row($row,'has_children','remix_children', $children,'more_children_link') )
            {
                if( !CCUploads::InTags('remix',$row) )
                    $row['is_orphan_original'] = true;
            }
        }

    }

    /**
    * @access private
    */
    function _mark_row(&$row,$hasflag,$elemname,&$branches,$more_name,$add_macro=true)
    {
        if( empty($branches) ) 
            return false;

        $row[$hasflag] = true;
        if( empty($row[$elemname]) )
            $row[$elemname] = $branches;
        else
            $row[$elemname] = array_merge( $row[$elemname], $branches );

        if( empty($row['works_page']) && (count($branches) == CC_MAX_SHORT_REMIX_DISPLAY) )
            $row[$more_name] = $row['file_page_url'];

        /*
        if( $add_macro )
            CCUpload::AddMacroRef($row, 'file_macros', "show_$elemname");
        */
        
        return(true);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('file','remixes'), array( 'CCRemix', 'EditRemixes'), CC_MUST_BE_LOGGED_IN);
    }


}


?>
