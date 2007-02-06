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
* Implements the user interface and database management for contests
*
* @package cchost
* @subpackage contest
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

//-------------------------------------------------------------------

/**
* Contest High Volume API and event callbacks
*
*/
class CCContestHV
{
    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        if( empty($record['upload_contest']) )
            return;

        $systags = CCUploads::SplitTags($record);

        $isentry  = in_array( CCUD_CONTEST_ENTRY, $systags);
        $issource = !$isentry && (in_array( CCUD_CONTEST_MAIN_SOURCE, $systags ) ||
                                 in_array( CCUD_CONTEST_SAMPLE_SOURCE, $systags) );

        if( $issource  )
            $relative = $this->_get_upload_dir($record);
        elseif( $isentry )
            $relative = $this->_get_user_upload_dir($record);
        else
            return;
        
        $record['relative_dir']  = $relative;
        for( $i = 0; $i < count($record['files']); $i++ )
        {
            $record['files'][$i]['download_url']  = ccd( $relative, $record['files'][$i]['file_name'] );
            $record['files'][$i]['local_path']    = cca( $relative, $record['files'][$i]['file_name']);
        }

        $record['file_page_url'] = $this->_get_file_page_url($record);
    }


    /**
    * Internal helper for getting the upload page's URL
    *
    * @param array $record Upload database record (may be incomplete)
    */
    function _get_file_page_url(&$record)
    {
        return( ccl('files',$record['user_name'],$record['upload_id']) );
    }

    /**
    * Internal helper method
    *
    * Returns the upload directory associated with the username (or one
    * found in the record
    *
    * @param array $record Database record with contest name in it
    * @param string $username For this user (if blank, $record is assumed to have a username in it)
    * @returns string $dir User's upload directory for this contest
    */
    function _get_user_upload_dir( $record, $username='' )
    {
        $basedir = CCContestHV::_get_upload_dir($record);
        if( empty($username) )
            $username = $record['user_name'];

        return( $basedir . '/' . $username );
    }

    /**
    * Internal helper method
    *
    * Returns the base upload directory associated with a contest
    *
    * @param mixter $name_or_row Either short contest name or database record with contest name in it
    */
    function _get_upload_dir($name_or_row)
    {
        global $CC_GLOBALS;

        $name = is_string($name_or_row) ? $name_or_row : CCContestHV::GetContestNameFromRecord($name_or_row);
        $base_dir = empty($CC_GLOBALS['contest-upload-root']) ? 'contests' : 
                            $CC_GLOBALS['contest-upload-root'];

        return( $base_dir . '/' . $name );
    }

    function GetContestNameFromRecord($row) 
    {
        global $CC_GLOBALS;

        if( !empty($row['contest_short_name']) )
            return $row['contest_short_name'];

        if( empty($CC_GLOBALS['contests']) )
        {
            // this will happen one time when upgrading to 4

            require_once('cclib/cc-contest-admin.inc');
            $CC_GLOBALS['contests'] = CCContestAdmin::UpgradeGlobals();
        }

        $name = '';

        if( empty($row['contest_short_name']) )
        {
            if( !empty($row['upload_contest']) )
            {
                $name = $CC_GLOBALS['contests'][ $row['upload_contest'] ];
            }
        }
        else
        {
            $name = $name['contest_short_name'];
        }

        return $name;
    }

    /**
    * Callback for Navigation tab display
    *
    * @see CCNavigator::View()
    * @param array $page Array of tabs to be manipulated before display
    */
    function OnTabDisplay( &$page )
    {
        require_once('cclib/cc-contest-table.inc');
        $contests =& CCContests::GetTable();
        $short_name = $page['handler']['args']['contest'];
        $contest = $contests->GetRecordFromShortName($short_name);

        if( !empty($page['winners']) )
        {
            if( $page['winners']['function'] != 'url' )
            {
                $uploads =& CCUploads::GetTable();
                $uploads->SetTagFilter('winner,' . $short_name,'all');
                $num_winners = $uploads->CountRows();
                $uploads->SetTagFilter('');

                if( !$num_winners )
                {
                    unset($page['winners']);
                }
            }
        }

        if( !$contest['contest_taking_submissions'] )
        {
            unset($page['submit']);
        }

        if( !CCUser::IsAdmin() && !$contest['contest_can_browse_entries'] )
        {
            unset($page['entries']);
        }
    }
<<<<<<< .mine
=======


    /**
    * Displays contest listing into the current page
    *
    * @param string $contest_short_name Optional: if this parameter is not null only one contest will be displayed, otherwise all contests.
    */
    function ViewContests($contest_short_name='')
    {
        $contests =& CCContests::GetTable();

        if( empty($contest_short_name) )
        {
            $contests->SetOrder('contest_deadline','DESC');
            $records =& $contests->GetRecords();
        }
        else
        {
            $contests =& CCContests::GetTable();
            $record = $contests->GetRecordFromShortName($contest_short_name);
            CCPage::SetTitle($record['contest_friendly_name']);
            $record['contest-homepage'] = true;
            $records = array( &$record );
            if( $record['contest_vote_online'] )
                CCPage::AddScriptBlock('ajax_block');
        }

        CCPage::PageArg( 'contest_record', $records, 'contest_listing' );
    }

    /**
    * List out contests
    *
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function Contests($contest_short_name='')
    {
        CCPage::SetTitle(_('Browse Contests'));
        $this->ViewContests($contest_short_name);
    }

    /**
    * Delete a contest
    * 
    * (not implemented)
    * 
    * @param string $contest_short_name Internal contest name
    */
    function ContestDelete($contest_short_name)
    {
        if( !CCUser::IsAdmin() )
            return;

        CCPage::Prompt('no implemento');
    }

    /**
    * Handles contest/[name]/edit and POST results from form
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function EditContest($contest_short_name)
    {
        require_once('cclib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->EditContest($this,$contest_short_name);
    }
    /**
    * Handles contest/vote (this method exits the session)
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function Vote($contest_short_name)
    {
		global $CC_GLOBALS;

        // this is meant to be shown in an IFRAME (or ajax div) so we just
        // return raw html

        // just make sure we're even supposed to be here
        $contests =& CCContests::GetTable();
        $record = $contests->GetRecordFromShortName($contest_short_name);

        if( !$record['contest_vote_online'] )
        {
            print('<h3>' . 
	          _('This contest does not support online voting') . '</h3>');
            cc_exit();
        }

        if( !empty($_POST['polls']) )
        {
            // user voted, count it and return

            CCPoll::Vote($contest_short_name);
            CCUtil::SendBrowserTo( ccl('contest',$contest_short_name) );
        }

        $polls =& CCPolls::GetTable();

        if( CCContests::OKToVote($record) )
        {
            if( !$polls->PollExists($contest_short_name) )
            {
                $entries =& $this->_contest_uploads($contest_short_name,CCUD_CONTEST_ENTRY);
                $pollinsert = array();
                foreach( $entries as $entry )
                {
                    $pollinsert[] = array( $contest_short_name,
                                           $entry['user_real_name'] . '/' . $entry['upload_name'] );
                }
                $columns = array( 'poll_id', 'poll_value' );
                $polls->InsertBatch($columns, $pollinsert);
            }

            $where['contest_short_name'] = $contest_short_name;
            $vote_expires = $contests->QueryItem('contest_vote_deadline',$where);
            $form = new CCPollsForm($contest_short_name,strtotime($vote_expires));
            //$form->SetHandler( ccl('contest',$contest_short_name) );
            print( $form->GenerateHTML() );
        }
        else
        {
            if( $record['contest_taking_submissions'] )
            {
                print('<h3>' . 
		_('Voting will open after submission period has ended') . 
		'</h3>');
            }
            elseif( !$record['contest_voting_open'] )
            {
                $data = $polls->GetPollingData($contest_short_name,'poll_numvotes');
                $data = array_merge($data,$record);
                print('<h3>' . _('Poll Results') . '</h3>');
                $args['poll_data'] = $data;
                $args['auto_execute'][] = 'polling_data';
                $template = new CCTemplate($CC_GLOBALS['skin-map'] );
                $template->SetAllAndParse($args,true);
            }
            else
            {
                if( !CCUser::IsLoggedIn() )
                    print('<h3>' . 
		          _('Voting is only open to registered users') . 
			  '</h3>');
                else
                    print('<h3>' . _('Results will be shown here after the voting period is closed') . '</h3>');
            }
        }

        cc_exit();
    }

    /**
    * Handles contest/[conetst_short_name]/entry/submit
    *
    * This will display a form to submit an entry to the contest.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function SubmitEntry($contest_short_name)
    {
        global $CC_GLOBALS;

        $contests =& CCContests::GetTable();
        $record = $contests->GetRecordFromShortName($contest_short_name) ;
        if( !$record['contest_taking_submissions'] )
            return; // someone is hacking in

        CCPage::SetTitle(sprintf(_("Submit to: '%s'"),$record['contest_friendly_name']));

        $records   = $this->_contest_uploads($record['contest_id'],CCUD_CONTEST_ALL_SOURCES);
        $do_prompt = false;
        $ccud      = array( CCUD_CONTEST_ENTRY, $contest_short_name );
        $username  = CCUser::CurrentUserName();
        $uid       = CCUser::CurrentUser();

        if( $CC_GLOBALS['cc_mixter_installed'] )
        {
            $formhelp =<<<END
   Having upload problems? Click <a 
            href="http://ccmixter.org/media/viewfile/isitlegal.xml#upload_problems"><b>HERE</b></a>.
END;
        }

        if( empty($records) )
        {
            // this contest doesn't have 'sources' (like a sample upload contest)
            $ccud[] = CCUD_ORIGINAL;
            $form = new CCNewUploadForm($uid);
            $form->SetHiddenField('upload_contest',$record['contest_id']);
            if( !empty($formhelp) )
                $form->SetHelpText($formhelp);
            if( empty($_POST['newupload']) )
            {
                CCPage::AddForm( $form->GenerateForm() );
            }
            else
            {
                $upload_dir = $this->_get_user_upload_dir( $record, $username );

                if( $form->ValidateFields() )
                {
                    $upload_id = CCUpload::PostProcessNewUploadForm( $form, $ccud, $upload_dir );
                    
                    $do_prompt = !empty($upload_id);
                }
            }
        }
        else
        {   
            $ccud[] = CCUD_REMIX;
            $form = new CCSubmitContestEntryForm($record);
            if( !empty($formhelp) )
                $form->SetHelpText($formhelp);
            if( empty($_POST['submitcontestentry']) )
            {
                // here's a whacky unnatural side-effect for ya:
                // inherited tags are considered evil (like 'digital_distortion')
                // so we don't want to encourage the use of them so we tell
                // the template to uncheck the box by default

                if( !empty($CC_GLOBALS['tags-inherit']) )
                {
                    $keys = array_keys($records);
                    $inherit = join(',',$CC_GLOBALS['tags-inherit']);
                    foreach( $keys as $key )
                    {
                        $R =& $records[$key];
                        $R['no_check'] = CCTag::InTag($inherit,$R['upload_tags']);
                    }
                }

                $form->SetTemplateVar( 'remix_sources', $records );
                CCPage::AddForm( $form->GenerateForm() );
            }
            else
            {
                $upload_dir = $this->_get_user_upload_dir( $record, $username);
                
                $do_prompt = CCRemix::OnPostRemixForm( $form, $upload_dir, $ccud);
            }
        }

        if( $do_prompt )
        {
            CCPage::Prompt(sprintf(_("Your upload has been entered in '%s'"),$record['contest_friendly_name']));
        }
    }

    /**
    * Handles contest/[contest_short_name]/entry/submit
    *
    * This will display a form to submit sources to the contest.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function SubmitSource($contest_short_name)
    {
        require_once('cclib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->SubmitSource($this,$contest_short_name);
    }

    /* ------------------------------
        Class helpers
       ------------------------------ */
    /**
    * Internal helper method
    *
    * Returns the all the uploads for a given contest, filtered by type
    *
    * @param mixed $contest_name_or_id Short (internal) name of contest or the ID
    * @param string $systags System tag to filter on
    * @returns array $records Records based on parameter requests
    */
    function & _contest_uploads($contest_name_or_id,$systags)
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetSort( 'upload_date', 'DESC' );
        if( intval($contest_name_or_id) == 0 )
        {
            $uploads->SetTagFilter($systags . ',' . $contest_short_name, 'all');
            $where = '';
        }
        else
        {
            $uploads->SetTagFilter($systags,'any');
            $where['upload_contest'] = $contest_name_or_id;
        }
        $records =& $uploads->GetRecords($where);
        $uploads->SetTagFilter('');
        $uploads->SetSort('');
        return $records;
    }

    /**
    * Internal helper method
    *
    * Returns the upload directory associated with the username (or one
    * found in the record
    *
    * @param array $record Database record with contest name in it
    * @param string $username For this user (if blank, $record is assumed to have a username in it)
    * @returns string $dir User's upload directory for this contest
    */
    function _get_user_upload_dir( $record, $username='' )
    {
        $basedir = $this->_get_upload_dir($record);
        if( empty($username) )
            $username = $record['user_name'];

        return( $basedir . '/' . $username );
    }

    /**
    * Internal helper method
    *
    * Returns the base upload directory associated with a contest
    *
    * @param mixter $name_or_row Either short contest name or database record with contest name in it
    */
    function _get_upload_dir($name_or_row)
    {
        global $CC_GLOBALS;

        if( is_array($name_or_row) )
            $name_or_row = $name_or_row['contest_short_name'];

        $base_dir = empty($CC_GLOBALS['contest-upload-root']) ? 'contests' : 
                            $CC_GLOBALS['contest-upload-root'];

        return( $base_dir . '/' . $name_or_row );
    }

    /**
    * Internal helper for getting the upload page's URL
    *
    * @param array $record Upload database record (may be incomplete)
    * @param string $contest_short_name Short (internal) contest name
    */
    function _get_file_page_url(&$record,$contest_short_name)
    {
        // return( ccc($contest_short_name,'files',$record['user_name'],$record['upload_id']) );
        return( ccl('files',$record['user_name'],$record['upload_id']) );
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'addcontest'  => array( 'menu_text'  => 'Create Contest',
                             'menu_group' => 'configure',
                             'weight' => 100,
                             'help' => 'Create a new contest, set its start time, rules, etc.',
                             'action' =>  ccl('contest', 'create'),
                             'access' => CC_ADMIN_ONLY
                              ),
             );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        if( empty($record['upload_contest']) )
            return;

        $systags = CCUploads::SplitTags($record);

        $isentry  = in_array( CCUD_CONTEST_ENTRY, $systags);
        $issource = !$isentry && (in_array( CCUD_CONTEST_MAIN_SOURCE, $systags ) ||
                                 in_array( CCUD_CONTEST_SAMPLE_SOURCE, $systags) );

        if( $issource  )
            $relative = $this->_get_upload_dir($record);
        elseif( $isentry )
            $relative = $this->_get_user_upload_dir($record);
        else
            return;
        
        if( $isentry )
        {
            CCContests::GetOpenStatus($record);
            if( !$record['contest_can_browse_entries'] )
            {
                $msg = _('This contest entry is only visible to the owner and admins.');
                $record['publish_message'] = $msg;
                $record['file_macros'][] = 'upload_not_published';
            }
        }

        $record['relative_dir']  = $relative;
        for( $i = 0; $i < count($record['files']); $i++ )
        {
            $record['files'][$i]['download_url']  = ccd( $relative, $record['files'][$i]['file_name'] );
            $record['files'][$i]['local_path']    = cca( $relative, $record['files'][$i]['file_name']);
        }

        global $CC_CFG_ROOT; 
        $root =  empty($record['contest_short_name']) ? $CC_CFG_ROOT : $record['contest_short_name'];
        $record['file_page_url'] = $this->_get_file_page_url($record, $root);
    }

    /**
    * Event handler for {@link CC_EVENT_GET_MACROS}
    *
    * @param array &$record Upload record we're getting macros for (if null returns documentation)
    * @param array &$file File record we're getting macros for
    * @param array &$patterns Substituion pattern to be used when renaming/tagging
    * @param array &$mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros( &$record, &$file, &$patterns, &$mask )
    {
        if( empty($record) )
        {
            $patterns['%contest%']            = 'Contest (Internal Name)';
            $patterns['%contest_fullname%']   = 'Contest (Full Name)';
            $patterns['%url%']                = 'Download URL';
            $patterns['%song_page%']          = 'File page URL';
            $patterns['%unique_id%']     = 'Guaranteed to be unique number';
            $mask['contest']        = 'Pattern to use for contest entries';
            $mask['contest-source'] = 'Pattern to use for contest sources';
            return;
        }

        $isentry  = CCUploads::InTags( CCUD_CONTEST_ENTRY, $record );
        $issource = !$isentry && CCUploads::InTags( CCUD_CONTEST_ALL_SOURCES, $record );

        if( !($isentry || $issource)  )
            return;

        $configs =& CCConfigs::GetTable();
        $mask_configs = $configs->GetConfig('name-masks');

        if( $isentry )
        {
            if( array_key_exists('contest',$mask_configs) )
                $mask = $mask_configs['contest'];
        }
        elseif( $issource )
        {
            if( array_key_exists('contest-source',$mask_configs) )
                $mask = $mask_configs['contest-source'];
        }

        if( !empty($record['download_url']) )
            $patterns['%url%']              = $record['download_url'];

        if( !empty($record['upload_id']) )
            $patterns['%song_page%'] = $this->_get_file_page_url($record,$record['contest_short_name']);

        if( !empty($file['file_id']) )
            $patterns['%unique_id%'] = $file['file_id'];

        $patterns['%contest%']          = $record['contest_short_name'];
        $patterns['%contest_fullname%'] = $record['contest_friendly_name'];
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'contest',               array( 'CCContest', 'Contests'),      
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[contestname]', _('Display Contests home page'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contests',              array( 'CCContest', 'Contests'),      
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[contestname]', _('Alias for /contest'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/submit',        array( 'CCContest', 'SubmitEntry'),   
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{contestname}', _('Display contest entry form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/submitsource',  array( 'CCContest', 'SubmitSource'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '{contestname}', _('Display contest source upload form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/create',        array( 'CCContest', 'CreateContest'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Display new contest form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/edit',          array( 'CCContest', 'EditContest'),   
            CC_ADMIN_ONLY, ccs(__FILE__), '{contestname}', _('Display contest properties form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/vote',          array( 'CCContest', 'Vote'),          
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( 'admin/contest',          array( 'CCContest', 'Admin'),        
            CC_ADMIN_ONLY, ccs(__FILE__), '{contestname}', _('Display admin contest forms'), CC_AG_CONTESTS );

        //CCEvents::MapUrl( 'contest/poll/results', array( 'CCContest', 'PollResults'),     CC_DONT_CARE_LOGGED_IN );
    }

>>>>>>> .r5178
}


?>
