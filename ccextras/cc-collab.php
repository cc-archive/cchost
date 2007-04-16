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
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCCollab',  'OnMapUrls')         , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCCollab', 'OnFormFields')      , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,        array( 'CCCollab', 'OnUploadDone')      , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCCollab',  'OnUploadDelete')    , 'ccextras/cc-collab.inc' );

/**
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCCollabsHV',  'OnBuildUploadMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCCollabsHV',  'OnUploadMenu')       );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCCollabsHV',  'OnUploadRow')     );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCCollabsHV',  'OnUserRow')      );
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCCollabsHV',  'OnUserProfileTabs')      );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCCollab' , 'OnGetConfigFields') , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_TOPIC_ROW,          array( 'CCCollab' , 'OnTopicRow')        , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_TOPIC_DELETE,       array( 'CCCollab' , 'OnTopicDelete')     , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_DO_SEARCH,          array( 'CCCollabFormAPI',  'OnDoSearch')        , 'ccextras/cc-collab-forms.inc' );
*/

class CCCollabs extends CCTable
{
    function CCCollabs()
    {
        $this->CCTable('cc_tbl_collabs','collab_id');
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
            $_table = new CCCollabs();
        return( $_table );
    }
}

class CCCollabUploads extends CCTable
{
    function CCCollabUploads()
    {
        $this->CCTable('cc_tbl_collab_uploads','collab_upload_upload');
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
            $_table = new CCCollabUploads();
        return( $_table );
    }
}

class CCCollabUsers extends CCTable
{
    function CCCollabUsers()
    {
        $this->CCTable('cc_tbl_collab_users','collab_user_collab');
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
            $_table = new CCCollabUsers();
        return( $_table );
    }
}

require_once('ccextras/cc-topics.inc');

class CCCollabTopics extends CCTopics
{
    function CCCollabTopics()
    {
        $this->CCTopics();
        $this->LimitType('collab');
        $this->SetOrder('topic_date','ASC');
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
        static $table;
        if( !isset($table) )
            $table = new CCCollabTopics();
        return $table;
    }
}


/**
*/
require_once('cclib/cc-form.php');


class CCCollabForm extends CCForm
{
    function CCCollabForm()
    {
        $this->CCForm();

        $fields = array( 
                    'collab_name' =>
                       array( 'label'       => _('Name of collaboration project'),
                               'formatter'  => 'textedit',
                               'form_tip'   => '',
                               'flags'      => CCFF_REQUIRED | CCFF_POPULATE),
                          
                    'collab_desc' =>
                       array( 'label'       => _('Description'),
                               'formatter'  => 'textarea',
                               'want_formatting' => true,
                               'flags'      => CCFF_POPULATE),
                    );

        $this->AddFormFields($fields);
    }
}


require_once('ccextras/cc-topics-forms.inc');


class CCCollabTopicForm extends CCTopicForm
{
    function CCCollabTopicForm()
    {
        $this->CCTopicForm(_('Collobration Discussion'),'Submit Topic');
    }
}


/**
* Event listener for collaboration projects
*/
class CCCollab
{

    function OnUploadDelete($record)
    {
        $collab_uploads = new CCCollabUploads();
        $w['collab_upload_upload'] = $record['upload_id'];
        $collab_uploads->DeleteWhere($w);
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * Called when the form is building. Add and modify fields here
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        /************************************************************
           N.B. This can be called more than once for the same form
        ***************************************************************/

        global $CC_GLOBALS;

        if( is_subclass_of($form,'CCUploadMediaForm') ||
                    is_subclass_of($form,'ccuploadmediaform') )
        {
            $collab_users = new CCCollabUsers();
            $collab_users->AddJoin( new CCCollabs(), 'collab_user_collab' );
            $w['collab_user_user'] = CCUser::CurrentUser();
            $collabs = $collab_users->QueryRows($w);
            if( !empty($collabs) )
            {
                $F = array();

                $options = array( 0 => _('(none)') );
                foreach( $collabs as $collab )
                    $options[ $collab['collab_id'] ] = $collab['collab_name'];

                $F['collab'] = 
                    array( 'label' => _('Collaboration'),
                           'formatter' => 'select',
                           'form_tip'  => 'Assign this file to a collaboration project',
                           'options'   => $options,
                           'value'     => 0, // todo: 
                           'flags'     => CCFF_NOUPDATE,
                            );

                $form->InsertFormFields( $F, 'before', 'upload_tags' );
            }
        }
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    *
    * Upload is complete and otherwise successful. Mark the upload with tags and 
    * extra fields here.
    *
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'}
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id, $op)
    {
        if( ($op == CC_UF_NEW_UPLOAD || $op == CC_UF_PROPERTIES_EDIT) &&
            array_key_exists('collab',$_POST)
          )
        {
            if( !empty($_POST['collab']) )
            {
                $collab_id = $_POST['collab'];
                $this->_add_upload($collab_id,$upload_id);
                $files = new CCCollabUploads();
                $w['collab_upload_collab'] = $collab_id;
                $collab_ids = $files->QueryItems('collab_upload_upload',$w);
                $this->_sync_collab_sources($collab_ids);
            }
        }
    }

    function _add_upload($collab_id,$upload_id)
    {
        $collab_uploads = new CCCollabUploads();
        $w['collab_upload_upload'] = $upload_id;
        $w['collab_upload_collab'] = $collab_id;
        $rows = $collab_uploads->QueryRows($w);
        if( empty($rows) )
        {
            $collab_uploads->Insert($w);
            $url = ccl( 'collab', $collab_id );
            $text = _('A new file has been posted to your collaboration project') . "\n\n$url\n\n" . _('Thanks');
            $this->_mail_out( $collab_id, _('New Upload'), $text); 
        }
    }

    function Create()
    {
        $form =  new CCCollabForm();
        if( empty($_POST['collab']) || !$form->ValidateFields() )
        {
            CCPage::SetTitle(_('Create New Collaboration Project'));
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $collabs = new CCCollabs();
            $form->GetFormValues($values);
            $values['collab_date'] = date('Y-m-d H:i:s',time());
            $values['collab_user'] = CCUser::CurrentUser();
            $values['collab_id'] = $collabs->NextID();
            $collabs->Insert($values);

            $collab_users = new CCCollabUsers();
            $uvalues['collab_user_collab'] = $values['collab_id'];
            $uvalues['collab_user_user'] = CCUser::CurrentUser();
            $uvalues['collab_user_role'] = 'owner';
            $collab_users->Insert($uvalues);

            $url = ccl('collab',$values['collab_id']);
            CCUtil::SendBrowserTo($url);
        }
    }

    function Edit($collab_id='')
    {
        if( empty($collab_id) )
            CCUtil::Send404();

        $form =  new CCCollabForm();
        $is_post = !empty($_POST['collab']);
        $collabs = new CCCollabs();
        if( !$is_post || !$form->ValidateFields() )
        {
            $collab_row = $collabs->QueryKeyRow($collab_id);
            CCPage::SetTitle(sprintf( _('Collaboration Project: "%s"'), $collab_row['collab_name'] ));
            if( !$is_post )
                $form->PopulateValues($collab_row);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $values['collab_id'] = $collab_id;
            $collabs->Update($values);
            $url = ccl('collab',$values['collab_id']);
            CCUtil::SendBrowserTo($url);
        }
    }

    function _collab_row($collab_id)
    {
        if( empty($collab_id) )
            CCUtil::Send404();

        $collabs = new CCCollabs();
        $collabs->AddJoin( new CCUsers(), 'collab_user' );
        $collab_row = $collabs->QueryKeyRow($collab_id);

        if( empty($collab_row) )
            CCUtil::Send404();

        return $collab_row;
    }

    function View($collab_id='')
    {
        $collab_row = $this->_collab_row($collab_id);

        CCPage::SetTitle(sprintf( _('Collaboration Project: "%s"'), $collab_row['collab_name'] ));

        $collab_row['collab_desc'] = _cc_format_format($collab_row['collab_desc']);

        $collab_users = $this->_get_collab_users($collab_id);

        list( $is_owner, $is_member ) = $this->_get_user_acccess($collab_row,$collab_users);

        $topics = new CCCollabTopics();
        $w3['topic_upload'] = $collab_id;
        $collab_topics = $topics->QueryRows($w3);
        for( $i = 0; $i < count($collab_topics); $i++ )
            $collab_topics[$i]['commands'] = array(); // for now...
        $users =& CCUsers::GetTable();
        $collab_topics =& $users->GetRecordsFromRows($collab_topics);

        require_once('cclib/cc-license.php');
        $licenses =& CCLicenses::GetTable();
        $lics     = $licenses->GetEnabled();

        $args = array(
                'is_owner'  => $is_owner,
                'is_member' => $is_member,
                'collab'    => $collab_row,
                'users'     => $collab_users,
                'lics'      => $lics,
                'topics'    => $collab_topics );

        CCPage::PageArg( 'show_collab', 'collab.xml/show_collab' );
        CCPage::PageArg( 'collab', $args, 'show_collab' );

    }

    function _get_collab_users($collab_id)
    {
        $users = new CCCollabUsers();
        $users->AddJoin( new CCUsers(), 'collab_user_user' );
        $w1['collab_user_collab'] = $collab_id;
        return $users->QueryRows($w1);
    }

    function _get_user_acccess($collab_row,$collab_users=array())
    {
        $curr_user = CCUser::CurrentUser();
        $is_member = false;

        if( CCUser::IsAdmin() )
        {
            $is_owner = true;
            $is_member = true;
        }
        else
        {
            $is_owner = $curr_user == $collab_row['collab_user'];

            if( $is_owner )
            {
                $is_member = true;
            }
            else
            {
                if( empty($collab_users) )
                    $collab_users = $this->_get_collab_users($collab_row['collab_id']);

                foreach( $collab_users as $CU )
                    if( $is_member = ($curr_user == $CU['user_id']) )
                        break;
            }
        }

        return array( $is_owner, $is_member );
    }

    function GetUploads( $collab_id='' )
    {
        $collab_row = $this->_collab_row($collab_id);
        list( $is_owner, $is_member ) = $this->_get_user_acccess($collab_row);
        $uploads = new CCUploads();
        $uploads->SetOrder('upload_date','DESC');
        $uploads->AddJoin( new CCCollabUploads(), 'upload_id' );
        if( $is_member )
            $uploads->SetDefaultFilter(false); // allow unpublished collab files through
        $w2['collab_upload_collab'] = $collab_id;
        $collab_uploads = $uploads->GetRecords($w2);
        if( empty($collab_uploads) )
        {
            print(_('There are no visible uploads right now'));
        }
        else
        {
            $me = CCUser::CurrentUser();
            $n = count($collab_uploads);
            $upkeys = array_keys($collab_uploads);
            for( $i = 0; $i < $n; $i++ )
            {
                $R =& $collab_uploads[$upkeys[$i]];
                $R['is_collab_owner'] = $is_owner || ($R['upload_user'] == $me);
                $R['collab_type'] = preg_match( '/(^|,| )remix(,| $)/',$R['upload_extra']['ccud']) ? 'remix' : 'sample';
                $R['collab_tags'] = $R['collab_type'] . ' ' . preg_replace( '/[, ]+/', ' ', $R['upload_extra']['usertags']);
            }
            $template = new CCTemplateMacro( 'collab.xml', 'show_collab_files' );
            $args['uploads'] = $collab_uploads;
            $args['is_owner'] = $is_owner;
            $args['is_member'] = $is_member;
            $template->SetAllAndPrint($args,'uploads');
        }
        exit;
    }

    function Upload( $collab_id='', $upload_id='', $cmd='' )
    {
        $ok = !empty($collab_id) && !empty($upload_id) && !empty($cmd);
        if( $ok )
        {
            $collab_id = CCUtil::Strip($collab_id);
            $upload_id = CCUtil::Strip($upload_id);
            $cmd = CCUtil::Strip($cmd);
            $ok = !empty($collab_id) && !empty($upload_id) && !empty($cmd);
        }
        else
        {
            $args['error'] = _('Invalid arguments');
        }

        if( $ok )
        {
            $args['upload_id'] = $upload_id;
            switch($cmd)
            {
                case 'remove':
                    $collab_uploads = new CCCollabUploads();
                    $w1['collab_upload_upload'] = $upload_id;
                    $w1['collab_upload_collab'] = $collab_id;
                    $collab_uploads->DeleteWhere($w1);
                    $args['msg'] = _('File removed from project');
                    break;

                case 'publish':
                    $uploads = new CCUploads();
                    $uploads->SetDefaultFilter(false); // allow unpublished collab files through
                    $row = $uploads->QueryKeyRow($upload_id);
                    if( empty($row) )
                    {
                        $args['error'] = _('Can not find that file');
                    }
                    else
                    {
                        $w2['upload_id'] = $upload_id;
                        $w2['upload_published'] = !$row['upload_published'];
                        $uploads->Update($w2);
                        if( $w2['upload_published'] )
                        {
                            $args['published'] = 1;
                            $args['msg'] = _('Upload is now visible to everybody');
                        }
                        else
                        {
                            $args['published'] = 0;
                            $args['msg'] = _('Upload is now only visible to members of the project');
                        }
                    }
                    break;

                default:
                    $args['error'] = _('Unknown command: ' . $cmd);
                    break;
            }
        }

        $this->_output($args);
    }

    function User($collab_id='',$username='',$cmd='')
    {
        global $CC_GLOBALS;

        $ok = !empty($collab_id) && !empty($username) && !empty($cmd);
        if( $ok )
        {
            $collab_id = CCUtil::Strip($collab_id);
            $username = CCUtil::Strip($username);
            $cmd = CCUtil::Strip($cmd);
            $ok = !empty($collab_id) && !empty($username) && !empty($cmd);
        }
        else
        {
            $args['error'] = _('Invalid arguments');
        }

        $args = array();

        if( $ok )
        {
            $user_id = CCUser::IDFromName($username);

            switch( $cmd )
            {
                case 'add':
                    $users =& CCUsers::GetTable();
                    $user_real_name = $users->QueryItemFromKey('user_real_name',$user_id);
                    $users = new CCCollabUsers();
                    $w1['collab_user_user'] = $user_id;
                    $w1['collab_user_collab'] = $collab_id;
                    $collab_user = $users->QueryRow($w1);
                    if( !empty($collab_user) )
                    {
                        $args['error'] = sprintf(_('%s is already a member'),$user_real_name);
                    }
                    else
                    {
                        $w1['collab_user_role'] = 'member';
                        $users->Insert($w1);
                        $args['user_name'] = $username;
                        $args['user_real_name'] = $user_real_name;
                        $args['msg'] = sprintf(_('%s added to project'),$user_real_name);
                    }
                    break;

                case 'remove':
                    $users =& CCUsers::GetTable();
                    $user_real_name = $users->QueryItemFromKey('user_real_name',$user_id);
                    $users = new CCCollabUsers();
                    $w1['collab_user_user'] = $user_id;
                    $w1['collab_user_collab'] = $collab_id;
                    $collab_user = $users->QueryRow($w1);
                    if( empty($collab_user) )
                    {
                        $args['error'] = sprintf(_('%s is not member of this project'),$user_real_name);
                    }
                    else
                    {
                        $w1['collab_user_role'] = 'member';
                        $users->DeleteWhere($w1);
                        $args['user_name'] = $username;
                        $args['msg'] = sprintf(_('%s removed from project'),$user_real_name);
                    }
                    break;

                case 'credit':
                    if( ($ok = !empty($_GET['credit'])) == true )
                    {
                        $credit = CCUtil::Strip($_GET['credit']);
                        $ok = !empty($credit);
                    }

                    if( !$ok )
                    {
                        $args['error'] = _('Missing argument');
                    }
                    else
                    {
                        $users = new CCCollabUsers();
                        $w1['collab_user_user'] = $user_id;
                        $w1['collab_user_collab'] = $collab_id;
                        $w2['collab_user_credit'] = $credit;
                        $users->UpdateWhere($w2,$w1);
                        $args['user_name'] = $username;
                        $args['credit'] = $credit;
                        $args['msg'] = _('User credit updated');
                    }
                    break;

                case 'contact':
                    if( ($ok = !empty($_POST['text'])) == true )
                    {
                        $text = CCUtil::Strip($_POST['text']);
                        $ok = !empty($text);
                    }
                    if( !$ok )
                    {
                        $args['error'] = _('Missing argument');
                    }
                    else
                    {
                        $users =& CCUsers::GetTable();
                        $user_email = $users->QueryItemFromKey('user_email',$user_id);
                        $url = ccl('collab',$collab_id);
                        $collabs = new CCCollabs();
                        $collab_name = $collabs->QueryItemFromKey('collab_name',$collab_id);
                        $text .= "\n\nProject: \"$collab_name\"\n$url\n";
                        $this->_mail_out( $collab_id, _('Private message'), $text, $user_email );
                        $args['msg'] = _('Message sent');
                    }
                    break;

                default:
                    $args['error'] = _('Invalid command');
                    $ok = false;
                    break;
            }
        }
        else
        {
            $args['error'] = _('Invalid arguments');
        }

        $this->_output($args);
    }

    function _mail_out( $collab_id, $sub_head, $body, $emails='')
    {
        global $CC_GLOBALS;

        $from = $CC_GLOBALS['user_email'];
        if( empty($emails) )
        {
            $collab_users = $this->_get_collab_users($collab_id);
            $emails = array();
            foreach( $collab_users as $CU )
                $emails[] = $CU['user_email'];
            $emails = array_diff( array_unique($emails), array( $from) );
            if( empty($emails) )
                return;
        }
        else
        {
            if( !is_array($emails) )
                $emails = array($emails);
        }

        $collabs = new CCCollabs();
        $collab_row = $collabs->QueryKeyRow($collab_id);
        require_once('ccextras/cc-mail.inc');
        $mailer = new CCMailer();
        $mailer->From( $from );
        $mailer->Subject( _('Project') . ' "' . $collab_row['collab_name'] .'" ' . $sub_head );
        $mailer->Body( $body );
        foreach( $emails as $email )
        {
            $mailer->To( $email );
            $ok = @$mailer->Send();
            if( !$ok )
                CCDebug::Log("Trying to send notify mail to '$email': '$ok'");
        }
    }

    function _output($args) 
    {
        require_once('cclib/zend/json-encoder.php');
        $text = CCZend_Json_Encoder::encode($args);
        header( "X-JSON: $text");
        header( 'Content-type: text/plain');
        print($text);
        exit;
    }

    function PostTopic($collab_id)
    {
        global $CC_GLOBALS, $CC_CFG_ROOT;

        $form = new CCCollabTopicForm();

        if( empty($_POST['collabtopic']) || !$form->ValidateFields() )
        {
            CCPage::SetTitle(_('Collaboration message'));
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $topics =& CCTopics::GetTable();
            $values['topic_id'] = $topics->NextID();
            $values['topic_upload'] = $collab_id;
            $values['topic_date'] = date('Y-m-d H:i:s',time());
            $values['topic_user'] = CCUser::CurrentUser();
            $values['topic_type'] = 'collab';
            $topics->Insert($values);

            $url = ccl( 'collab', $collab_id );
            $text = _('A new topic has been posted to your collaboration project') . "\n\n$url\n\n" . _('Thanks');
            $this->_mail_out( $collab_id, _('Topic Post'), $text); 

            //CCEvents::Invoke( CC_EVENT_REVIEW, array( &$row ) );

            $url = ccl('collab',$collab_id);
            CCUtil::SendBrowserTo($url);
        }
    }

    function UpdateTags($collab_id,$upload_id)
    {
        $bad = empty($upload_id) || empty($_GET['tags']); 

        if( !$bad )
        {
            $tags = CCUtil::Strip($_GET['tags']);
            $bad = empty($tags);
            if( $bad )
                $args['error'] = _('Missing arguments');
        }

        if( !$bad )
        {
            $uploads = new CCUploads();
            $uploads->SetDefaultFilter(false); // allow unpublished collab files through
            $record = $uploads->GetRecordFromID($upload_id);
            $bad != empty($record);
            if( $bad )
                $args['error'] = _('Upload record not found');
        }

        if( !$bad )
        {
            require_once('cclib/cc-tags.php');
            require_once('cclib/cc-tags.inc');
            $old_user_tags = CCTag::TagSplit($record['upload_extra']['usertags']);
            $old_tags = CCTag::TagSplit($record['upload_tags']);
            $new_user_tags = CCTag::TagSplit($_GET['tags']);
            $tags = new CCTags();
            $new_user_tags = $tags->CheckAliases($tags->CleanSystemTags($new_user_tags));
            $record['upload_extra']['usertags'] = $new_user_tags;
            $up['upload_extra'] = serialize($record['upload_extra']);
            $up['upload_tags'] = join( ', ', array_merge( array_diff( $old_tags, $old_user_tags ), CCTag::TagSplit($new_user_tags) ));
            $up['upload_id'] = $upload_id;
            $uploads->Update($up);
            $args['msg'] = _('Tags updated');
            $args['user_tags'] = $new_user_tags;
            $args['upload_id'] = $upload_id;
        }

        $this->_output($args);
    }

    function UploadFile($collab_id)
    {
        global $CC_GLOBALS;

        $collabs = new CCCollabs();
        $collabs->AddJoin( new CCUsers(), 'collab_user' );
        $collab_row = $collabs->QueryKeyRow($collab_id);

        CCUtil::Strip($_POST);

        $ret = 0;
        $msg = '';
        $bad = empty($collab_row)  || empty($_FILES) || empty($_FILES['upfile']) || ($_FILES['upfile']['error'] != 0); 

        if( !$bad )
        {

            $fname = empty($_POST['upname']) ? $_FILES['upfile']['name'] : $_POST['upname'];
            $values['upload_name']        = $fname;
            $values['upload_published']   = 0;
            $values['upload_description'] = sprintf( _('This is part of the %s collaboration project.'),
                                                 '[url=' . ccl('collab',$collab_id) . '"]' 
                                                  . $collab_row['collab_name'] . '[/url]' );

            require_once('cclib/cc-mediahost.php');
            $media_host = new CCMediaHost();
            $new_path = $media_host->_get_upload_dir($CC_GLOBALS['user_name']);

            $files = new CCCollabUploads();
            $w['collab_upload_collab'] = $collab_id;
            $collab_ids = $files->QueryItems('collab_upload_upload',$w);

            $ccud = $_POST['uptype'];
            $uploads = new CCuploads();
            if( $ccud == 'remix' && !empty($collab_ids) )
            {
                $uploads->SetTagFilter('-remix','all');
                $sources = $uploads->QueryKeyRows($collab_ids);
                $uploads->SetTagFilter('');
            }
            else
            {
                $sources = array();
            }

            $values['upload_license'] = $_POST['lic'];
            $values['upload_user'] = CCUser::CurrentUser();

            require_once('cclib/cc-uploadapi.php');
            $ret = CCUploadAPI::PostProcessNewUpload(   $values, 
                                                        $_FILES['upfile']['tmp_name'],
                                                        $values['upload_name'],
                                                        array( $ccud, 'media'),
                                                        '', // $user_tags,
                                                        $new_path,
                                                        $sources );

            if( intval($ret) > 0 )
            {
                $this->_add_upload($collab_id,$ret);
                $collab_ids[] = $ret;
                $this->_sync_collab_sources($collab_ids);
            }
            else
            {
                $msg = $ret;
            }
        }

        $html =<<<EOF
        <html>
        <body>
        <script>
            if( window.parent.upload_done )
                window.parent.upload_done('$ret','$msg');
            else
                alert('can not see it');
        </script>
        </body>
        </html>
EOF;
        print($html);
        exit;
    }

    function _sync_collab_sources($collab_ids)
    {
        // attach all collab samples and pells to remixes...

        if( count($collab_ids) < 2 )
            return; // there's only one (or none) uploads, nothing to attach

        $uploads = new CCuploads();
        $uploads->SetDefaultFilter(false); // allow hidden files;
        $keys = join(',',$collab_ids);
        $where = "upload_id IN ({$keys})";

        $uploads->SetTagFilter('-remix','all');
        $source_ids = $uploads->QueryKeys($where);
        $uploads->SetTagFilter('remix','any');
        $remix_ids = $uploads->QueryKeys($where);

        $uploads->SetTagFilter('');
        
        if( count($source_ids) == 0 || count($remix_ids) == 0 )
            return; // there are only sources (or remixes), nothing to attach

        // check for existing remix sources, we want to preserve
        // non-collab sources and add any new ones we found here...

        require_once('cclib/cc-sync.php');
        $remix_tree = new CCTable('cc_tbl_tree','tree_parent');
        foreach( $remix_ids as $remix_id )
        {
            $w['tree_child'] = $remix_id;
            $tree_source_ids = $remix_tree->QueryItems('tree_parent',$w);
            $new_sources = array_diff( $source_ids, $tree_source_ids);
            if( $new_sources )
            {
                $source_rows = $uploads->QueryKeyRows($new_sources);
                $remixer = $uploads->QueryItemFromKey('upload_user',$remix_id);
                $parents = array();
                foreach( $source_rows as $source )
                {
                    if( $remixer == $source['upload_user'] )
                        continue; // still can't remix yourself

                    $parents[] = $source;
                    $insert = array( 'tree_child' => $remix_id,
                                     'tree_parent' => $source['upload_id'] );
                    $remix_tree->Insert($insert);
                }
                if( !empty($parents) )
                    CCSync::Remix($remix_id,$parents);
            }
        }

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('collab'),        array( 'CCCollab', 'View'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{collab_id}' );

        CCEvents::MapUrl( ccp('collab','create'), array( 'CCCollab', 'Create'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('collab','edit'), array( 'CCCollab', 'Edit'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id}'  );

        CCEvents::MapUrl( ccp('collab','topic','add'), array( 'CCCollab', 'PostTopic'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id}'  );

        // useradd, removeuser, editcredit, contact
        CCEvents::MapUrl( ccp('collab','user'), array( 'CCCollab', 'User'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id},{username},{cmd}'  );

        // remove, publish
        CCEvents::MapUrl( ccp('collab','upload'), array( 'CCCollab', 'Upload'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id},{upload_id},{cmd}'  );

        CCEvents::MapUrl( ccp('collab','upload','update'), array( 'CCCollab', 'GetUploads'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{collab_id},{upload_id}'  );

        CCEvents::MapUrl( ccp('collab','upload','file'), array( 'CCCollab', 'UploadFile'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id},{upload_id}'  );

        CCEvents::MapUrl( ccp('collab','upload','tags'), array( 'CCCollab', 'UpdateTags'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id},{upload_id}'  );
    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$record)
    {
        if( empty($record['artist_page']) )
            return;

        /*
        $record['user_fields'][] = array( 'label'   => _('Reviews'), 
                                          'value'   => $text,
                                          'id'      => 'user_review_stats' );
        */
    }

}


?>
