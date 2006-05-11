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
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
require_once('ccextras/cc-topics.php');

CCEvents::AddHandler(CC_EVENT_TOPIC_ROW,         array( 'CCFlag', 'OnTopicRow'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,        array( 'CCFlag', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS, array( 'CCFlag' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,        array( 'CCFlag' , 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCFlag' , 'OnMapUrls') );


/**
*/
class CCFlagContentForm extends CCSecurityVerifierForm
{
    function CCFlagContentForm($type,$id,$name,$user_from)
    {
        global $CC_GLOBALS;

        $this->CCSecurityVerifierForm();

        $text = cct("This $type contains material that may violate the terms of the site");

        $fields = array( 
                    'mail_from' => array(
                            'label'       => cct('From'),
                            'formatter'   => 'textedit',
                            'value'       => $user_from,
                            'form_tip'    => cct('Your email address (optional)'),
                            'flags'      => CCFF_NONE ),
                    'mail_subject' => array(
                            'label'       => cct('Subject'),
                            'formatter'   => 'statictext',
                            'value'      => sprintf( cct('Flag %s "%s"'), $type, $name ),
                            'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                    'mail_body' => array(
                            'label'       => cct('Message'),
                            'formatter'   => 'textarea',
                             'value'      => $text,
                            'flags'      => CCFF_REQUIRED ),
                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => cct('Security Key'),
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => cct('Type in characters above'),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE)
            );

        if( !empty($CC_GLOBALS['flag_msg']) )
            $this->SetHelpText($CC_GLOBALS['flag_msg']);

        $this->AddFormFields($fields);
    }
}

/**
*
*
*/
class CCFlag
{
    function _is_flagging_on()
    {
        global $CC_GLOBALS;
        return( !empty($CC_GLOBALS['flagging']) );
    }

    function OnTopicRow(&$row)
    {
        if( $this->_is_flagging_on() )
            $row['flag_url'] = ccl('flag','topic',$row['topic_id']);
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        if( $this->_is_flagging_on() )
            $record['flag_url'] = ccl( 'flag', 'upload', $record['upload_id'] );
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('flag'), array('CCFlag','Flag'), CC_DONT_CARE_LOGGED_IN);
    }

    function Flag($type,$id)
    {
        global $CC_GLOBALS;

        CCPage::SetTitle(cct('Flag Content'));

        if( $type == 'upload' )
        {
            $uploads =& CCUploads::GetTable();
            $name = $uploads->QueryItemFromKey('upload_name',$id);
        }
        elseif( $type == 'topic' )
        {
            $topics =& CCTopics::GetTable();
            $name = $topics->QueryItemFromKey('topic_name',$id);
        }

        $user_from = empty($CC_GLOBALS['user_email']) ? '' : $CC_GLOBALS['user_email'];
        
        $form = new CCFlagContentForm($type,$id,$name,$user_from);

        if( empty($_POST['flagcontent']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            
            if( $type == 'upload' )
            {
                $record = $uploads->GetRecordFromKey($id);
                $url = $record['file_page_url'];
                $title = $record['upload_name'];
                $type = cct('Upload');

            }
            elseif( $type == 'topic' )
            {
                $record = $topics->GetRecordFromKey($id);
                $url = ccl('topics','view',$id);
                $title = $record['topic_name'];
                $type = cct('Topic');
            }

            $from = empty($values['mail_from']) ? $CC_GLOBALS['mail_sender'] : $values['mail_from'];
            $user_text = $values['mail_body'];
            $flag_lang = cct('Content has been flagged');
            $ip = $_SERVER['REMOTE_ADDR'];
            $user = CCUser::IsLoggedIn() ? CCUser::CurrentUserName() : 'anon';
            $text =<<<END
$flag_lang
$type: $title
URL: $url
IP: $ip
Flagger: $user
-------------------------------------
$user_text
-------------------------------------                
END;
            $mailer = new CCMailer();
            $mailer->To($CC_GLOBALS['mail_sender']);
            $mailer->Subject( $type . ' Flagged' );
            $mailer->From($from);
            $mailer->Body($text);
            $ok = $mailer->Send();
            if( !$ok )
            {
                CCPage::Prompt(cct("An error occurred trying to contact the site"));
            }
            else
            {
                CCPage::Prompt(cct("Thank you, your message has been sent"));                
            }
        }
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
            $fields['flagging'] =
               array(  'label'      => 'Flagging',
                       'form_tip'   => 'Allow users to directly flag something as a possible violation of terms of this site',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
            $fields['flag_msg'] =
               array(  'label'      => 'Flag form message',
                       'form_tip'   => 'This text will appear on the mail form to users who flag content',
                       'value'      => 0,
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        /*
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array( 
                'flagadmin'   => array( 'menu_text'  => 'Flagging',
                                 'menu_group' => 'configure',
                                 'help'      => 'See newly flagged uploads and topics',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 90,
                                 'action' =>  ccl('admin','flag') ),
                );
        }
        */
    }
}



?>