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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCContest' , 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCContest',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,    array( 'CCContest' , 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCContest' , 'OnGetConfigFields' ));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCContest', 'OnAdminMenu'));


/**
* Form used for uploading a contest entry
*
*/
class CCSubmitContestEntryForm extends CCPostRemixForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest this entry is for
    */
    function CCSubmitContestEntryForm($R)
    {
        // $R['contest_user']
        $this->CCPostRemixForm( CCUser::CurrentUser() );
        $this->SetHiddenField('upload_contest',$R['contest_id']);
        // er, and I don't think line does anything...
        $this->SetTemplateVar('remix_search', true);
   }
}


//-------------------------------------------------------------------

/**
* Wrapper for database Contest table
*
*/
class CCContests extends CCTable
{
    var $_publish_filter;

    /**
    * Constructor -- don't use new, use GetTable() instead
    *
    * @see GetTable
    */
    function CCContests()
    {
        $this->CCTable('cc_tbl_contests','contest_id');
        $this->AddJoin(new CCUsers(),'contest_user');

        if( !CCUser::IsAdmin() )
            $_publish_filter = '(contest_publish > 0)';
    }

    /**
    * Returns static singleton of table wrapper.
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
            $_table = new CCContests();
        return($_table);
    }

    /**
    * Returns the display name for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return string $long_name Display (friendly) name of contest
    */
    function GetFriendlyNameFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_friendly_name', $where ) );
    }

    /**
    * Returns the database ID for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return integer $id Database ID of contest
    */
    function GetIDFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_id', $where ) );
    }

    /**
    * Returns the display ready record for a contest given the short (internal) name
    *
    * This will return a full record (as opposed to raw database row) for a contest
    *
    * @param string $name Short (internal) name of contest
    * @return array $record Full record (as opposed to raw db row) for contest
    */
    function & GetRecordFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        $row = $this->QueryRow($where);
        return( $this->GetRecordFromRow($row) );
    }

    /**
    * Returns a series of full display ready records of contests
    *
    * @see CCTable::GetRecordFromRow()
    * @see CCTable::Query()
    * @param mixed $where array or string specifying row filter
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $offset Offset into database
    * @param integer $limit Number of records to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetRecords($where='',$expand=true, $offset=0, $limit=0)
    {
        $this->SetOffsetAndLimit($offset,$limit);
        $qr = $this->Query($where);
        $this->SetOffsetAndLimit(0,0);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
        {
            $record = $this->GetRecordFromRow($row,$expand);
            $records[] = $record;
        }

        $ret =& $records;
        return $ret;
    }

    /**
    * Returns the display ready records for the currently open contests
    *

    * @see CCTable::GetRecordForRow()
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $limit Number of contests to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetOpenContests($expand=false,$limit=0)
    {
        $where =<<<EOF
         contest_publish 
           AND
         NOW() > contest_open
           AND 
         (
           (NOW() < contest_deadline) OR 
           (NOW() < contest_vote_deadline)
         )
EOF;
        $r = $this->GetRecords($where,$expand,0,$limit);
        return $r;
    }

    /**
    * Returns the display ready records for contests no longer open
    *
    * @see CCTable::GetRecordForRow()
    * @param bool  $expand true if you need the local command menus for each row
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetPastContests($expand=false)
    {
        $where = '(contest_deadline < NOW()) AND (contest_vote_deadline < NOW())';
        return( $this->GetRecords($where,$expand) );
    }

    /**
    * Populate a database row for a contest with specific state flags
    *
    * Upon return there will be several boolean flags regarding the
    * current state of this contest. 
    *
    * <code>
    * $a['contest_taking_submissions']  // true if NOW is before deadline
    * $a['contest_voting_open']         // true if voting is allowed and NOW is after deadline but before voting deadline
    * $a['contest_show_results']        // true if voting is allowed and NOW is after voting deadline
    * $a['contest_can_browse_entries']  // true if browing is always allowed or NOW is after contest deadline
    * $a['contest_over']                // true is NOW after all submissions and voting
    *  </code>
    *
    * @param array $row Reference to contest database row
    */
    function GetOpenStatus(&$row)
    {
        $row['contest_taking_submissions'] = false;
        $row['contest_voting_open']        = false;
        $row['contest_show_results']       = false;
        $row['contest_can_browse_entries'] = false;
        $row['contest_over']               = false;
        if( $row['contest_publish'] > 0 )
        {
            $open     = strtotime($row['contest_open']);
            $deadline = strtotime($row['contest_deadline']);
            $now      = time();
            if( ($now > $open) && ($now < $deadline) )
            {
                $row['contest_taking_submissions'] = true;

                if( $row['contest_auto_publish'] )
                    $row['contest_can_browse_entries'] = true;
            }
            else
            {
                if( $now > $open )
                {
                    $row['contest_can_browse_entries'] = true;

                    if( $row['contest_vote_online'] )
                    {
                        $deadline = strtotime($row['contest_vote_deadline']);
                        $row['contest_show_results'] = true;
                        if( $now < $deadline )
                            $row['contest_voting_open'] = true;
                        else
                            $row['contest_over'] = true;
                    }
                }
                else
                {
                    $row['contest_over'] = true;
                }
            }
        }
    }

    /**
    *  Converts a raw database row to a semantically rich (display ready) record
    *
    * @param array $row Reference to database row
    * @param bool  $expand true if you want to include local menu commands for each record
    */
    function & GetRecordFromRow(&$row,$expand = true)
    {
        if( !$row['contest_id'] )
            return;

        $this->GetOpenStatus($row);

        $row['contest_url']               = ccc( $row['contest_short_name'] );
        $row['contest-homepage']          = false;

        $row['contest_description_html'] = CCUtil::TextToHTML($row['contest_description']);

        if( $row['contest_bitmap'] )
        {
            $relative = CCContest::_get_upload_dir($row);
            $row['contest_bitmap_url'] =  ccd( $relative, $row['contest_bitmap'] );
        }

        $row['contest_deadline_fmt']      = date(' l F jS, Y \a\t g:ia',
                                              strtotime($row['contest_deadline']));
        $row['contest_vote_deadline_fmt'] = date(' l F jS, Y \a\t g:ia',
                                              strtotime($row['contest_vote_deadline']));
        if( $row['contest_taking_submissions'] )
        {
            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_open',
                       'text'      => $row['contest_friendly_name'] . 
                                      _(' is currently open and taking submissions.') );

            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_open',
                       'text'      => _('Submissions allowed until: '));

			$row['contest_states'][] = 
                array( 'css_class' => '',
                       'text'      => $row['contest_deadline_fmt'] );

            if( !CCUser::IsLoggedIn() )
            {
				$row['contest_states'][] = 
					array( 'css_class' => 'cc_contest_open',
						   'text'      => _('(Only logged in users can submit entries.)') );
            }
        }
        else
        {
            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_closed',
                       'text'      => $row['contest_friendly_name'] . 
                                      _(' is not taking submissions any more.') );

            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_closed',
                       'text'      => _('Submissions stopped after: ') );

            $row['contest_states'][] = 
                array( 'css_class' => '',
                       'text'      => $row['contest_deadline_fmt'] );
        }

        if( $row['contest_voting_open'] )
        {
            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_voting_status',
                       'text'      => _('Voting is open until ') .
                                      $row['contest_vote_deadline_fmt'] ) ;

        }

        if( $row['contest_vote_online'] )
            $row['contest_vote_url'] = ccl('contest', 'vote', $row['contest_short_name'] );

        if( $expand )
        {
            CCEvents::Invoke(CC_EVENT_CONTEST_ROW,array(&$row));
        }

        return( $row );
    }

    /**
    * Verifies that the current user is allowed to vote in the current contest
    *
    * @param array $record Contest record to check
    */ 
    function OKToVote(&$record)
    {
        return( empty($_REQUEST['polls']) && CCUser::IsLoggedIn() && 
                   $record['contest_voting_open'] && 
                  !CCPoll::AlreadyVoted($record['contest_short_name']) );

    }

    /**
    * Overwrites base class to add specific publishing and other filters
    * 
    * @param mixed $where string or array representing WHERE clause
    * @param string $columns SELECT will be limited to these columns
    * @return string $select Fully formed SELECT statement
    */
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( !empty($this->_publish_filter) )
        {
            if( empty($where) )
                $where = $this->_publish_filter;
            else
                $where = '($where) AND ({$this->_publish_filter})';
        }
        $sql = parent::_get_select($where,$columns);
        return($sql);

    }
}

//-------------------------------------------------------------------

/**
* Contest API and event callbacks
*
*/
class CCContest
{
    /**
    * Handler for contest/create
    *
    * Show a contest create form and handles POST
    */
    function CreateContest()
    {
        require_once('cclib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->CreateContest($this);
    }

    /**
    * Handler for admin/contest
    *
    * Show a contest create form and handles POST
    */
    function Admin($contest_short_name)
    {
        require_once('cclib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->Admin($this,$contest_short_name);
    }

    /**
    * Callback for Navigation tab display
    *
    * @see CCNavigator::View()
    * @param array $page Array of tabs to be manipulated before display
    */
    function OnTabDisplay( &$page )
    {
        $contests =& CCContests::GetTable();
        $record = $contests->GetRecordFromShortName($page['handler']['args']['contest']);

        if( !$record['contest_over'] )
        {
            unset($page['winners']);
        }

        if( !$record['contest_taking_submissions'] )
        {
            unset($page['submit']);
        }

        if( !CCUser::IsAdmin() && !$record['contest_can_browse_entries'] )
        {
            unset($page['entries']);
        }
    }


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
            print(_('<h3>This contest does not support online voting</h3>'));
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
                print(_('<h3>Voting will open after submission period has ended</h3>'));
            }
            elseif( !$record['contest_voting_open'] )
            {
                $data = $polls->GetPollingData($contest_short_name,'poll_numvotes');
                $data = array_merge($data,$record);
                print(_('<h3>Poll Results</h3>'));
                $args['poll_data'] = $data;
                $args['auto_execute'][] = 'polling_data';
                $template = new CCTemplate($CC_GLOBALS['skin-map'] );
                $template->SetAllAndParse($args,true);
            }
            else
            {
                if( !CCUser::IsLoggedIn() )
                    print(_('<h3>Voting is only open to registered users</h3>'));
                else
                    print(_('<h3>Results will be shown here after the voting period is closed</h3>'));
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
        CCEvents::MapUrl( 'contest',               array( 'CCContest', 'Contests'),      CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contests',              array( 'CCContest', 'Contests'),      CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/submit',        array( 'CCContest', 'SubmitEntry'),   CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/submitsource',  array( 'CCContest', 'SubmitSource'),  CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'contest/create',        array( 'CCContest', 'CreateContest'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'contest/edit',          array( 'CCContest', 'EditContest'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'contest/vote',          array( 'CCContest', 'Vote'),          CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'admin/contest',          array( 'CCContest', 'Admin'),        CC_ADMIN_ONLY);

        //CCEvents::MapUrl( 'contest/poll/results', array( 'CCContest', 'PollResults'),     CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['contest-upload-root'] =
               array( 'label'       => 'Contest Upload Directory',
                       'form_tip'   => 'Contest files will be uploaded/downloaded based from here.(This must accessable from the Web.)',
                       'value'      => 'contests',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}


?>