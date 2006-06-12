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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCMailerAPI' , 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCMailerAPI' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCMailerAPI' , 'OnGetConfigFields') );

/**
*/
class CCMailer
{
    var $_to;
    var $_from;
    var $_cc;
    var $_bcc;
    var $_subject;
    var $_message;

    function CCMailer()
    {
        $this->_to          = "";
        $this->_from        = "";
        $this->_cc          = array();
        $this->_bcc         = array();
        $this->_subject     = "";
        $this->_message     = "";
    }

    function Message($msg)
    {
        $this->_message = $msg;
    }

    function Body($msg)
    {
        $this->_message = $msg;
    }

    function To($to)
    {
        $this->_to = $to;
    }

    function CC($cc)
    {
        $this->_cc[] = $cc;
    }

    function BCC($bcc)
    {
        if( empty($bcc) )
            $this->_bcc = array();
        else
            $this->_bcc[] = $bcc;
    }

    function Subject($subject)
    {
        $this->_subject = $subject;
    }

    function From($from)
    {
        $this->_from = $from;
    }

    function DefaultFrom()
    {
        global $CC_GLOBALS;

        return( $CC_GLOBALS['mail_sender'] );
    }

    function Send()
    {
        global $CC_GLOBALS;

        if( empty($this->_from) && empty($CC_GLOBALS['mail_sender']) )
        {
            CCPage::SystemError("Mail has not been properly configured on the this system. Contact your administrator.");
            return;
        }

        $default_from = $this->DefaultFrom();

        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');

        $subject = '[' . $ttags['site-title'] . '] ' . $this->_subject;
        $from    = empty($this->_from) ? $default_from : $this->_from;
        $bcc     = empty($this->_bcc) ? '' : implode( ', ', $this->_bcc );
        $cc      = empty($this->_cc)  ? '' : implode( ', ', $this->_cc  );
        $to      = $this->_to;
        $message = $this->_message; // do doubt this needs to be messaged...

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=us-ascii\r\n";
        $headers .= "From: $from\r\n";

        /*
        stuff to consider:

        "Return-Path: " . $board_config['board_email'] . 
        "\nMessage-ID: <" . md5(uniqid(time())) . "@" . $board_config['server_name'] . ">".
        "\nContent-transfer-encoding: 8bit" .
        "\nDate: " . date('r', time()) . 
        "\nX-Priority: 3" .
        "\nX-MSMail-Priority: Normal" .
        "\nX-Mailer: PHP" .
        "\nX-MimeOLE: Produced By ccHost\n" . 
        */

        if (!empty($cc) )
            $headers .= "Cc: $cc\r\n";

        if (!empty($bcc)) 
            $headers .= "Bcc: $bcc\r\n";

        return mail( $to, $subject, $message, $headers);
    }
} 

class CCContactMailerForm extends CCSecurityVerifierForm
{
    function CCContactMailerForm($user_to,$user_from)
    {
        $this->CCSecurityVerifierForm();

        $to = $user_to['user_real_name'] . ' (' . $user_to['user_name'] . ')';

        $fields['mail_to'] = array(
                'label'       => cct('To'),
                 'value'      => $to,
                 'flags'      => CCFF_STATIC | CCFF_NOUPDATE,
                 'formatter'  => 'statictext' );

        if( empty($user_from) )
        {
            $fields['mail_from'] = array(
                        'label'       => cct('From'),
                        'formatter'   => 'email',
                        'form_tip'    => cct('Your email address'),
                        'flags'      => CCFF_REQUIRED );
        }
        else
        {
            $from = $user_from['user_real_name'] . ' (' . $user_from['user_name'] . ')';

            $fields['mail_from_STATIC'] = array(
                            'label'       => cct('From'),
                            'value'      => $from,
                            'flags'      => CCFF_STATIC | CCFF_NOUPDATE,
                            'formatter'  => 'statictext' );

            $this->SetHiddenField('mail_from_id',$user_from['user_id']);
        }


        $fields += array( 
                    'mail_subject' => array(
                            'label'       => cct('Subject'),
                            'formatter'   => 'textedit',
                            'flags'      => CCFF_NONE ),
                    'mail_body' => array(
                            'label'       => cct('Message'),
                            'formatter'   => 'textarea',
                            'maxlength'   => 1000,
                            'form_tip'    => 'Message is limited to 1,000 characters',
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

        

        $this->AddFormFields($fields);
    }

}

class CCMailerAPI
{

    function _ok_to_contact($userto)
    {
        global $CC_GLOBALS;

        $curr_time = time();
        if( !empty($CC_GLOBALS['user_extra']['last_email_send']) )
        {
            $last_email = $CC_GLOBALS['user_extra']['last_email_send'];
            if( ($curr_time - $last_email) < (60 * 10) )
            {
                CCPage::Prompt('You have exceeded the temporary quota of emails allowed.' );
                return false;
            }            
        }   

        return true;
    }

    function _mark_user_send()
    {
        global $CC_GLOBALS;

        $users =& CCUsers::GetTable();
        $row['user_extra'] = $CC_GLOBALS['user_extra'];
        $row['user_extra']['last_email_send'] = time();
        $row['user_extra'] = serialize($row['user_extra']);
        $row['user_id'] = CCUser::CurrentUser();
        $users->Update($row);
    }

    function Contact($userto='')
    {
        global $CC_GLOBALS;

        if( !CCUser::IsLoggedIn() )
        {
            CCPage::Prompt('Due to spamming issues we have to temporarily restrict email contacts ' . 
                           'to registered users only.');
            return;
        }

        if( empty($CC_GLOBALS['mail_sender']) )
        {
            CCPage::SystemError("Mail has not been properly configured on the this system. Contact your administrator.");
            return;
        }

        if( empty($userto) )
            return;

        CCPage::SetTitle("Send Mail to $userto");

        global $CC_GLOBALS;

        $users =& CCUsers::GetTable();
        $where['user_name'] = $userto;
        $user_to = $users->QueryRow($where);
        $user_from = CCUser::IsLoggedIn() ? $CC_GLOBALS : '';

        $form = new CCContactMailerForm($user_to,$user_from);

        $is_post = !empty( $_REQUEST['contactmailer'] );

        if( !$is_post && !$this->_ok_to_contact($userto) )
            return;

        if( !$is_post || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->_mark_user_send();

            $form->GetFormValues($fields);
            
            global $CC_MAILER;

            if( empty($CC_MAILER) )
                $mailer = new CCMailer();
            else
                $mailer = $CC_MAILER;

            $from_email = empty($user_from) ? $fields['mail_from'] : $user_from['user_email'];
            $mailer->From( $from_email );
            $mailer->To( $user_to['user_email'] );
            $mailer->Subject( $fields['mail_subject'] );
            $mailer->Body( $fields['mail_body'] );
            $ok = $mailer->Send();

            $msg = cct('Mail sent');
            if( CCUser::IsAdmin() )
                $msg .= " ($ok)";

            CCPage::Prompt($msg);

            CCDebug::Enable(true);
            $from = empty($user_from) ? $fields['mail_from'] : $user_from['user_name'];
            CCDebug::Log("Mail sent from $from -- to $userto");

        }
    }

    function MassMail()
    {
        if( !CCUser::IsAdmin() )
            exit;

        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['mail_sender']) )
        {
            $url = ccl('admin','setup');
            CCPage::SystemError("Mail has not been properly configured on the this system. Set a return mail address in <a href=\"$url\">Global Settings</a>");
            return;
        }
        CCPage::SetTitle("Send Mail to Everybody");

        global $CC_GLOBALS;

        $user_to['user_name'] = 'really';
        $user_to['user_real_name'] = 'Everyone';
        $user_from['user_name'] = 'really';
        $user_from['user_real_name'] = 'You';
        $user_from['user_id'] = CCUser::CurrentUser();

        $form = new CCContactMailerForm($user_to,$user_from);


        if( empty( $_REQUEST['contactmailer'] ) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($fields);
            
            global $CC_MAILER;

            if( empty($CC_MAILER) )
                $mailer = new CCMailer();
            else
                $mailer = $CC_MAILER;
            
            $MAX_GET = 100;
            $users = new CCTable('cc_tbl_user', 'user_email');
            $users->GroupOnKey();
            $total = $users->CountRows();

            $from = $mailer->DefaultFrom();
            $mailer->From( $from );
            $mailer->To( $from );
            $mailer->Subject( $fields['mail_subject'] );
            $mailer->Body( $fields['mail_body'] );

            $sent = 0;
            for( $offset = 0; $offset < $total; $offset += $MAX_GET )
            {
                $users->SetOffsetAndLimit(  $offset, $MAX_GET );
                $rows = $users->QueryRows('1','user_email');
                $count = count($rows);
                for( $i = 0; $i < $count; $i++ )
                {
                    $addr = $rows[$i]['user_email'];
                    $mailer->BCC( $addr );
                }
                $ok = $mailer->Send();
                if( !$ok )
                    break;
                $sent += $count;
                $mailer->BCC('');
            }

            $msg = "Mass mail: Sent $sent Messages";
            if( CCUser::IsAdmin() )
                $msg .= " ($ok)";

            CCPage::Prompt($msg);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('people','contact'),  array('CCMailerAPI', 'Contact'), CC_DONT_CARE_LOGGED_IN ); // CC_MUST_BE_LOGGED );
        CCEvents::MapUrl( ccp('admin', 'massmail'),  array('CCMailerAPI', 'MassMail'), CC_ADMIN_ONLY); // CC_MUST_BE_LOGGED );
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
            $fields['mail_sender'] = array(
                'label'       => 'Admin email address',
                 'flags'      => CCFF_POPULATE,
                 'formatter'  => 'textedit' );
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
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array( 
            'massmail'   => array( 'menu_text'  => 'Mass Mailing',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'help' => 'Send mail to all users',
                             'weight' => 10001,
                             'action' =>  ccl('admin','massmail')
                             ),
                );

        }
    }


}




?>