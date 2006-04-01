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
* Base class for contest creating/editing form
*
*/
class CCContestForm extends CCUploadForm
{
    /**
    * Constructor
    *
    */
    function CCContestForm()
    {
        $this->CCUploadForm();

        $username = CCUser::CurrentUserName();

        $fields = array(

            'contest_friendly_name' => array (
                        'label'      => 'Friendly Name',
                        'form_tip'   => 'This is the one people actually see',
                        'formatter'  => 'textedit',
                        'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

            'contest_description' => array (
                        'label'      => 'Description',
                        'form_tip'   => '',
                        'formatter'  => 'textarea',
                        'flags'      => CCFF_POPULATE),

            'contest_bitmap' => array (
                        'label'      => 'Logo',
                        'form_tip'   => 'Image file',
                        'formatter'  => 'avatar',
                        'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL ),

            'contest_publish' => array (
                        'label'      => 'Contest is Online',
                        'form_tip'   => 'Uncheck this to hide contest',
                        'formatter'  => 'checkbox',
                        'value'      => '1',
                        'flags'      => CCFF_POPULATE),
            
            'contest_open' => array (
                        'label'      => 'Contest Opens',
                        'form_tip'   => 'Entries are accepted after this date/time',
                        'formatter'  => 'date',
                        'value'      => 'now',
                        'flags'      => CCFF_POPULATE),
                
            'contest_deadline' => array (
                        'label'      => 'Contest Deadline',
                        'form_tip'   => 'Entries are not accepted after this date/time',
                        'formatter'  => 'date',
                        'value'      => 'now +2 weeks',
                        'flags'      => CCFF_POPULATE),
                
            'contest_auto_publish' => array (
                        'label'      => 'Auto-Publish Entries',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'options'      => array( 
                                            '0' => 'Only admins can see entries until after the deadline',
                                            '1' => 'Entries are made public upon upload' ),
                        'value'      => '0',
                        'flags'      => CCFF_POPULATE),

            'contest_vote_online' => array (
                        'label'      => 'Vote Online',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'options'      => array( 
                                            '0' => 'Winner is determined offline',
                                            '1' => 'Display a poll after deadline for entries has passed' ),
                        'value'      => '1',
                        'flags'      => CCFF_POPULATE),

            'contest_vote_deadline' => array (
                        'label'      => 'Voting Deadline',
                        'form_tip'   => 'When do polls close? (Only applies if you chose online voting above)',
                        'formatter'  => 'date',
                        'value'      => 'now +4 weeks',
                        'flags'      => CCFF_POPULATE),

            '_server_time' => array (
                        'label'      => 'Current Server Time',
                        'formatter'  => 'statictext',
                        'value'      => date('F d, Y h:i a'),
                        'flags'      => CCFF_NOUPDATE | CCFF_STATIC),

            );

        $this->AddFormFields( $fields );
        $this->SetHiddenField( 'contest_user', CCUser::CurrentUser() );
        $this->EnableSubmitMessage(false);
    }
}

/**
* Form used for creating contests
*
*/
class CCCreateContestForm extends CCContestForm
{
    /**
    * Constructor
    *
    */
    function CCCreateContestForm()
    {
        $this->CCContestForm();

        $fields = array(
                'contest_short_name' => array (
                        'label'      => 'Internal Name',
                        'form_tip'   => 'Letters and numbers only, 25 or less',
                        'formatter'  => 'shortcontestname',
                        'class'      => 'cc_form_input_short',
                        'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
            );

        $this->AddFormFields( $fields );

    }

    /**
    * Special HTML generator for short (internal) contest names
    *
    * This method is called from CCForm, don't call it.
    *
    * @see CCForm::GenerateForm
    * @param string $varname Name of form field
    * @param string $value Value (if any) to populate into form field
    * @param string $class CSS class to use for this field
    * @returns string HTML that represents form field
    */
    function generator_shortcontestname($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }
    
    /**
    * Special POST validator for short (internal) contest name.
    *
    * Validates the short (internal) contest name given by users making
    * it adheres to very strict rules about what the internal name will
    * look like. This is basically done because the name will be used
    * as a directory name and in URLs so we want to keep the name
    * very, very simple and small
    *
    * This method is called from CCForm, don't call it
    * 
    * @see CCForm::ValidateFields
    * @param string $fieldname Name of form field
    * @returns bool $ok true if field validates ok
    */
    function validator_shortcontestname($fieldname)
    {
        if( $this->GetFormFieldItem($fieldname,'flags') & CCFF_HIDDEN )
            return(true);

        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);
            if( preg_match('/[^a-z0-9]/i',$value) || (strlen($value) > 25) )
            {
                $this->SetFieldError($fieldname, cct('Must be characters and numbers, no more than 25'));
                return(false);
            }
            $contests =& CCContests::GetTable();
            if( $contests->GetIDFromShortName($value) )
            {
                $this->SetFieldError($fieldname, 'Name already exists');
                return(false);
            }

            return(true);
        }

        return(false);
    }
}

/**
* Form used for editing contest information
*
*/
class CCEditContestForm extends CCContestForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest 
    * @param string $upload_dir Directory where avatars will be put
    */
    function CCEditContestForm($R,$upload_dir)
    {
        $this->CCContestForm();
        $this->SetSubmitText('Submit Contest Changes');
        $this->SetFormFieldItem( 'contest_bitmap',    'upload_dir', $upload_dir );
        $this->SetHiddenField('contest_id',$R['contest_id']);
    }
}

/**
* Form used for uploading Contest source files
*
*/
class CCUploadContestSourceForm extends CCNewUploadForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest this source is for
    */
    function CCUploadContestSourceForm( $R )
    {
        // $R['contest_user']
        $this->CCNewUploadForm( CCUser::CurrentUser() );
        $fields = array(
                'ccud_tags' => array(
                        'label'      => cct('Source Type:'),
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'value'      => CCUD_CONTEST_MAIN_SOURCE,
                        'options'    => array( 
                                           CCUD_CONTEST_MAIN_SOURCE
                                                           => cct('Main Remix Material'),
                                           CCUD_CONTEST_SAMPLE_SOURCE
                                                           => cct('Sample (loop, layer, fragment, etc.)')),
                        'flags'      => CCFF_NOUPDATE),
            );
        $this->AddFormFields($fields);
        $this->SetHiddenField('upload_contest',$R['contest_id']);
    }

}


//-------------------------------------------------------------------

/**
* Contest Admin API 
*
*/
class CCContestAdmin
{
    /**
    * Handler for contest/create
    *
    * Show a contest create form and handles POST
    */
    function CreateContest($contest_api)
    {
        CCPage::SetTitle('Create Contest');

        $ok = false;
        $form = new CCCreateContestForm();
        if( empty($_POST['createcontest']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $contest_short_name = $form->GetFormValue('contest_short_name');

            $upload_dir = $contest_api->_get_upload_dir($contest_short_name);
            $form->FinalizeAvatarUpload('contest_bitmap', $upload_dir);

            $form->SetHiddenField('contest_created', date('Y-m-d H:i') );
            $form->GetFormValues($fields);
            $contests =& CCContests::GetTable();
            $contests->Insert($fields);

            $long_name = $fields['contest_friendly_name'];

            $configs =& CCConfigs::GetTable();

            // create a tabs page for this contest
            $pages['contest'] = $this->_get_contest_tabs($contest_short_name, $long_name );
            $configs->SaveConfig('tab_pages',$pages,$contest_short_name,false);

            // create a new virtual config, point it's home page it at the tabs page
            $settings = $configs->GetConfig('settings');
            $settings['homepage'] = 'view';
            $configs->SaveConfig('settings',$settings,$contest_short_name,false);

            // set the title of the vconfig to the contest friendly name
            $template_tags   = $configs->GetConfig('ttag');
            $template_tags['banner-html'] = $template_tags['site-title'] . ' - ' . $long_name;
            $configs->SaveConfig('ttag',$template_tags,$contest_short_name,false);

            CCUtil::SendBrowserTo( ccr($contest_short_name) );
        }

    }

    /**
    * Internal helper for getting default contest tabs
    *
    * @param string $short_name Contest short name
    * @param string $full_name Contest friendly name
    */
    function _get_contest_tabs($short_name, $full_name )
    {
        $a = array(
             'handler' => array(
                                 'method' => array( 'CCContest', 'OnTabDisplay' ),
                                 'args' => array( 'contest' => $short_name ),
                                 'access' => 0 ),

             'home'   =>    array(  'text'   => cct('About'),
                                   'help'   => cct('Home for ') . $full_name,
                                   'tags'   => '/contest/' . $short_name,
                                   'limit'  => 1,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'url' ),

             'winners'   =>  array(  'text'   => cct('Winners'),
                                   'help'   => cct('...and the winner is...'),
                                   'tags'   => 'winner,' . $short_name,
                                   'limit'  => 1,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'all' ),

             'sources' =>    array(  'text'   => cct('Sources'),
                                   'help'   => cct('Download Source Material for ') . $full_name,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'limit'  => 1,
                                   'tags'   => CCUD_CONTEST_ALL_SOURCES,
                                   'function' => 'any' ),

             'entries' =>     array(  'text'   => cct('Entries'),
                                   'help'   => cct('See recently uploaded originals'),
                                   'limit'  => 1,
                                   'tags'   => CCUD_CONTEST_ENTRY,
                                   'access' => CC_DONT_CARE_LOGGED_IN,
                                   'function' => 'any' ),

             'submit'   =>    array(  'text'   => cct('Submit'),
                                   'help'   => cct('Submit an entry in the ') . $full_name . ' contest',
                                   'tags'   => '/contest/submit/' . $short_name,
                                   'limit'  => 1,
                                   'access' => CC_MUST_BE_LOGGED_IN,
                                   'function' => 'url' ),

             'submitsources'   =>    array(  'text'   => cct('Submit Sources'),
                                   'help'   => cct('Submit sources for the ') . $full_name . ' contest',
                                   'tags'   => '/contest/submitsource/' . $short_name,
                                   'limit'  => 1,
                                   'access' => CC_ADMIN_ONLY,
                                   'function' => 'url' ),

             'edit'   =>    array(  'text'   => cct('Properties'),
                                   'help'   => cct('Edit the properties (dates, rules, etc.) for ') . $full_name . ' contest',
                                   'tags'   => '/contest/edit/' . $short_name,
                                   'limit'  => 1,
                                   'access' => CC_ADMIN_ONLY,
                                   'function' => 'url' ),
             );

        return( $a );
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
    function EditContest($contest_api, $contest_short_name)
    {
        if( !CCUser::IsAdmin() )
            return;

        $contests =& CCContests::GetTable();

        $record = $contests->GetRecordFromShortName($contest_short_name) ;
        CCPage::SetTitle(cct('Edit ') . $record['contest_friendly_name']);

        $upload_dir = $contest_api->_get_upload_dir($contest_short_name);
        $form = new CCEditContestForm($record,$upload_dir);
        if( empty($_POST['editcontest']) || !$form->ValidateFields() )
        {
            $form->PopulateValues($record);
        }
        else
        {
            $upload_dir = $contest_api->_get_upload_dir($contest_short_name);
            $form->FinalizeAvatarUpload('contest_bitmap', $upload_dir);
            $form->GetFormValues($fields);
            $contests =& CCContests::GetTable();
            $contests->Update($fields);
            CCPage::Prompt(cct('Changes Saved'));
        }

        CCPage::AddForm( $form->GenerateForm() );
    }


    /**
    * Handles contest/[contest_short_name]/entry/submit
    *
    * This will display a form to submit sources to the contest.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function SubmitSource($contest_api, $contest_short_name)
    {
        if( !CCUser::IsAdmin() )
            return;

        CCPage::SetTitle(cct('Submit Contest Sources'));
        $contests =& CCContests::GetTable();
        $record =& $contests->GetRecordFromShortName($contest_short_name);
        $form = new CCUploadContestSourceForm($record);
        if( !empty($_POST['uploadcontestsource']) )
        {
            CCUser::CheckCredentials($_POST['upload_user']);

            if( $form->ValidateFields() )
            {
                $ccud_tags = array( $record['contest_short_name'],
                                    CCUD_ORIGINAL,
                                   $form->GetFormValue('ccud_tags') );                                

                $relative_dir = $contest_api->_get_upload_dir($record);

                $id = CCUpload::PostProcessNewUploadForm(  $form,
                                                           $ccud_tags,
                                                           $relative_dir );

                if( $id )
                {
                    CCPage::Prompt(cct('Upload succeeded'));
                    $form = new CCUploadContestSourceForm($record); // this clears the form
                }
            }
        }
        

        CCPage::AddForm( $form->GenerateForm() );
    }

}


?>