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
//CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCCollabFormAPI',  'OnFormFields')      , 'ccextras/cc-collab-forms.inc' );

/**
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCCollabsHV',  'OnBuildUploadMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCCollabsHV',  'OnUploadMenu')       );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCCollabsHV',  'OnUploadRow')     );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCCollabsHV',  'OnUserRow')      );
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCCollabsHV',  'OnUserProfileTabs')      );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCCollab' , 'OnGetConfigFields') , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCCollab',  'OnUploadDelete')    , 'ccextras/cc-collab.inc' );
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
                $collab_uploads = new CCCollabUploads();
                $w['collab_upload_upload'] = $upload_id;
                $rows = $collab_uploads->QueryRows($w);
                $collab = $_POST['collab'];
                $found = false;
                foreach( $rows as $row )
                {
                    if( $found = ($row['collab_upload_collab'] == $collab) )
                        break;
                }
                if( !$found )
                {
                    $w['collab_upload_collab'] = $collab;
                    $collab_uploads->Insert($w);
                }
            }
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

    function View($collab_id='')
    {
        if( empty($collab_id) )
            CCUtil::Send404();

        $collabs = new CCCollabs();
        $collabs->AddJoin( new CCUsers(), 'collab_user' );
        $collab_row = $collabs->QueryKeyRow($collab_id);

        if( empty($collab_row) )
            CCUtil::Send404();

        CCPage::SetTitle(sprintf( _('Collaboration Project: "%s"'), $collab_row['collab_name'] ));

        $collab_row['collab_desc'] = _cc_format_format($collab_row['collab_desc']);

        $users = new CCCollabUsers();
        $users->AddJoin( new CCUsers(), 'collab_user_user' );
        $w1['collab_user_collab'] = $collab_id;
        $collab_users = $users->QueryRows($w1);

        $curr_user = CCUser::CurrentUser();

        if( CCUser::IsAdmin() )
        {
            $is_owner = true;
        }
        else
        {
            $is_owner = $curr_user == $collab_row['collab_user'];

            if( !$is_owner )
            {
                $found = false;
                foreach( $collab_users as $CU )
                    if( $found = ($curr_user == $CU['user_id']) )
                        break;

                if( !$found ) 
                {
                    CCPage::Prompt(_("You are not a member of this collaboration project"));
                    CCUtil::Send404();
                }
            }
        }

        $uploads = new CCUploads();
        $uploads->AddJoin( new CCCollabUploads(), 'upload_id' );
        $uploads->SetDefaultFilter(false); // allow unpublished collab files through
        $w2['collab_upload_collab'] = $collab_id;
        $collab_uploads = $uploads->GetRecords($w2);

        $topics = new CCCollabTopics();
        $w3['topic_upload'] = $collab_id;
        $collab_topics = $topics->QueryRows($w3);
        for( $i = 0; $i < count($collab_topics); $i++ )
            $collab_topics[$i]['commands'] = array(); // for now...
        $users =& CCUsers::GetTable();
        $collab_topics =& $users->GetRecordsFromRows($collab_topics);

        $args = array(
                'is_owner' => $is_owner,
                'collab'   => $collab_row,
                'users'    => $collab_users,
                'uploads'  => $collab_uploads,
                'topics'   => $collab_topics );

        CCPage::PageArg( 'show_collab', 'collab.xml/show_collab' );
        CCPage::PageArg( 'collab', $args, 'show_collab' );

    }

    function Upload($collab_id='', $upload_id='',$cmd='')
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
                        require_once('ccextras/cc-mail.inc');
                        $mailer = new CCMailer();
                        $mailer->To($user_email);
                        $mailer->From( $CC_GLOBALS['user_email'] );
                        $mailer->Subject( _('Collaboration Message') );
                        $mailer->Body( $text );
                        $mailer->Send();
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

    function _output($args) {
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

            //CCEvents::Invoke( CC_EVENT_REVIEW, array( &$row ) );

            $url = ccl('collab',$collab_id);
            CCUtil::SendBrowserTo($url);
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
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{collab_id}' );

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
