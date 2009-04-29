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

require_once('cchost_lib/cc-contest-table.inc');
require_once('cchost_lib/cc-remix-forms.php');
require_once('cchost_lib/cc-contest.php');

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
        $this->SetHiddenField('upload_published',1);
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
        require_once('cchost_lib/cc-contest-admin.inc');
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
        require_once('cchost_lib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->Admin($this,$contest_short_name);
    }


    /**
    * Handler for contest/publicize_sources
    */
    function PublishSources($contest_short_name)
    {
        require_once('cchost_lib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->PublishSources($contest_short_name);
    }


    /**
    * Handles contest/[name]/edit and POST results from form
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function EditContest($contest_short_name)
    {
        require_once('cchost_lib/cc-contest-admin.inc');
        $admin_api = new CCContestAdmin();
        $admin_api->EditContest($this,$contest_short_name);
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
        $C = $contests->GetRecordFromShortName($contest_short_name) ;
        if( !$C['contest_taking_submissions'] )
            return; // someone is hacking in

        CCPage::SetTitle(sprintf(_("Submit to: '%s'"),$C['contest_friendly_name']));

        $records   = $this->_contest_uploads($C['contest_id'],CCUD_CONTEST_ALL_SOURCES);
        $do_prompt = false;
        $ccud      = array( CCUD_CONTEST_ENTRY, $contest_short_name );
        $username  = CCUser::CurrentUserName();
        $uid       = CCUser::CurrentUser();

        if( empty($records) )
        {
            // this contest doesn't have 'sources' (like a sample upload contest)
            $ccud[] = CCUD_ORIGINAL;
            $form = new CCNewUploadForm($uid);
            $form->SetHiddenField('upload_contest',$C['contest_id']);
            $form->SetHiddenField('upload_published',1);
            if( !empty($formhelp) )
                $form->SetHelpText($formhelp);
            if( empty($_POST['newupload']) )
            {
                CCPage::AddForm( $form->GenerateForm() );
            }
            else
            {
                $upload_dir = CCContestHV::_get_user_upload_dir( $C, $username );

                if( $form->ValidateFields() )
                {
                    $upload_id = CCUpload::PostProcessNewUploadForm( $form, $ccud, $upload_dir );
                    if( $upload_id )
                        CCUpload::ShowAfterSubmit($upload_id);
                }
            }
        }
        else
        {   
            $ccud[] = CCUD_REMIX;
            $form = new CCSubmitContestEntryForm($C);
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
                        require_once('cchost_lib/cc-tags.php');
                        $R['no_check'] = CCTag::InTag($inherit,$R['upload_tags']);
                    }
                }
                $form->SetTemplateVar( 'remix_sources', $records );
                CCPage::AddForm( $form->GenerateForm() );
            }
            else
            {
                $upload_dir = CCContestHV::_get_user_upload_dir( $C, $username);
                CCRemix::OnPostRemixForm( $form, $upload_dir, $ccud);
            }
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
        require_once('cchost_lib/cc-contest-admin.inc');
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
    * Internal helper for getting the upload page's URL
    *
    * @param array $record Upload database record (may be incomplete)
    * @param string $contest_short_name Short (internal) contest name
    */
    function _get_file_page_url(&$record,$contest_short_name)
    {
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
        // don't think we support contests anymore
        /*
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
        */
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

        if( !empty($file['file_id']) )
            $patterns['%unique_id%'] = $file['file_id'];

        if( empty($record['upload_contest']) )
        {
            if( !empty($file['file_upload']) )
            {
                $uploads =& CCUploads::GetTable();
                $contest_id = $uploads->QueryItemFromKey('upload_contest',$file['file_upload']);
            }
        }
        else
        {
            $contest_id = $record['upload_contest'];
        }

        if( empty($contest_id) )
        {
            // things are pretty horked here...
            return;
        }


        $contests =& CCContests::GetTable();
        $contest = $contests->QueryKeyRow($contest_id);

        if( !empty($record['upload_id']) )
        {
            if( empty($record['user_name']) )
            {
                $user_name = CCDatabase::QueryItem('SELECT user_name FROM cc_tbl_user WHERE user_id='.$record['upload_user']);
            }
            else
            {
                $user_name = $record['user_name'];
            }
            $patterns['%song_page%'] = ccl('files',$user_name,$record['upload_id']);
        }

        $patterns['%contest%']          = $contest['contest_short_name'];
        $patterns['%contest_fullname%'] = $contest['contest_friendly_name'];
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        // contests are deprecated

        CCEvents::MapUrl( 'contest/submit',        array( 'CCContest', 'SubmitEntry'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '', '', CC_AG_deprecated );
            // , '{contestname}', _('Display contest entry form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/submitsource',  array( 'CCContest', 'SubmitSource'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '', '', CC_AG_deprecated);
            // '{contestname}', _('Display contest source upload form'), CC_AG_CONTESTS );

        CCEvents::MapUrl( 'contest/publish_sources',  array( 'CCContest', 'PublishSources'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '', '', CC_AG_deprecated);
            // '{contestname}', _('Publish contest sources'), CC_AG_CONTESTS );

        CCEvents::MapUrl( 'contest/create',        array( 'CCContest', 'CreateContest'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', '', CC_AG_deprecated) ;
            // '', _('Display new contest form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'contest/edit',          array( 'CCContest', 'EditContest'),   
            CC_ADMIN_ONLY, ccs(__FILE__), '', '', CC_AG_deprecated);
            // '{contestname}', _('Display contest properties form'), CC_AG_CONTESTS );
        CCEvents::MapUrl( 'admin/contest',          array( 'CCContest', 'Admin'),        
            CC_ADMIN_ONLY, ccs(__FILE__), '', '', CC_AG_deprecated);
            // '{contestname}', _('Display admin contest forms'), CC_AG_CONTESTS );
    }

}


?>
