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

require_once('ccextras/cc-topics.inc');
require_once('cclib/cc-user.inc');

/**
*/
class CCFlagContentForm extends CCSecurityVerifierForm
{
    function CCFlagContentForm($type,$id,$name,$user_from)
    {
        global $CC_GLOBALS;

        $this->CCSecurityVerifierForm();

        $text = sprintf(_("This %s contains material that may violate the terms of the site"), $type);

        $fields = array( 
                    'mail_from' => array(
                            'label_str'       => 'str_email_from',
                            'formatter'   => 'textedit',
                            'value'       => $user_from,
                            'form_tip_str'    => 'str_email_your_address_opt',
                            'flags'      => CCFF_NONE ),
                    'mail_subject' => array(
                            'label_str'       => 'str_email_subject',
                            'formatter'   => 'statictext',
                            'value'      => sprintf( _('Flag %s "%s"'), $type, $name ),
                            'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                    'mail_body' => array(
                            'label_str'       => 'str_email_message',
                            'formatter'   => 'textarea',
                             'value'      => $text,
                            'flags'      => CCFF_REQUIRED ),
                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array(  'label_str'       => 'str_security_key',
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip_str'   => CCSecurityVerifierForm::GetSecurityTipStr(),
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
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('flag'), array('CCFlag','Flag'), CC_DONT_CARE_LOGGED_IN,
                ccs(__FILE__), '(upload|topic)/{upload_id}', _('Users flag an upload or topic.'),
                CC_AG_UPLOADS );
    }

    function Flag($type,$id)
    {
        global $CC_GLOBALS;

        CCPage::SetTitle(_('Flag Content'));

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
                $type = _('Upload');

            }
            elseif( $type == 'topic' )
            {
                $record = $topics->GetRecordFromKey($id);
                $url = ccl('topics','view',$id);
                $title = $record['topic_name'];
                $type = _('Topic');
            }

            $from = empty($values['mail_from']) ? $CC_GLOBALS['mail_sender'] : $values['mail_from'];
            $user_text = $values['mail_body'];
            $flag_lang = _('Content has been flagged');
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
            require_once('ccextras/cc-mail.inc');
            $mailer = new CCMailer();
            $mailer->To($CC_GLOBALS['mail_sender']);
            $mailer->Subject( $type . ' Flagged' );
            $mailer->From($from);
            $mailer->Body($text);
            $ok = $mailer->Send();
            if( !$ok )
            {
                CCPage::Prompt(_("An error occurred trying to contact the site"));
            }
            else
            {
                CCPage::Prompt(_("Thank you, your message has been sent"));                
            }
        }
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        if( (strtolower(get_class($form)) == 'ccbanadminform') && empty($fields['flagging']) )
        {
            $fields['flagging'] =
               array(  'label'      => _('Flagging'),
                       'form_tip'   => _('This allows users to directly flag something as a possible violation of terms of this site.'),
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
            $fields['flag_msg'] =
               array(  'label'      => _('Flag form message'),
                       'form_tip'   => _('This text will appear on the mail form to users who flag content.'),
                       'value'      => 0,
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE );
        }
    }
}

?>