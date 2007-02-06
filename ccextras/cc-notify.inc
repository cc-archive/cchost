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


/**
*/
define('CC_NTFY_IVE_BEEN_REVIEWED', 0x0001 );
define('CC_NTFY_THEY_BEEN_REMIXED', 0x0002 );
define('CC_NTFY_REVIEW_REPLY',      0x0004 );
define('CC_NTFY_ED_PICK',           0x0008 );
define('CC_NTFY_THEY_UPLOADED',     0x0010 );
define('CC_NTFY_IVE_BEEN_REMIXED',  0x0020 );
define('CC_NTFY_IVE_BEEN_RATED',    0x0040 );

define('CC_NTFY_MAX',    CC_NTFY_IVE_BEEN_RATED );

class CCNotifications extends CCTable
{
    function CCNotifications()
    {
        $this->CCTable('cc_tbl_notifications','notify_id');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCNotifications();
        return $_table;
    }
}



/**
*
*
*/
class CCNotifyAPI
{

    function EditMyNotifications($other_user_name='')
    {
        CCPage::SetTitle(_('My Notifications'));

        // figure out what fields we are editing

        if( $other_user_name )
        {
            // user is at another user's profile
            // and wants to monitor their activity

            $users =& CCUsers::GetTable();
            $where['user_name'] = $other_user_name;
            $user_happy_name = $users->QueryItem('user_real_name',$where);
            $fields = array(
                array( 'label' => sprintf(_('%s has been remixed'),$user_happy_name),
                       'flag' => CC_NTFY_THEY_BEEN_REMIXED, ),
                array( 'label' => sprintf(_('%s uploads something new'),$user_happy_name),
                       'flag' => CC_NTFY_THEY_UPLOADED, ),
                );
        }
        else
        {
            // user is at their own profile and wants
            // to get notified when people affect her work

            $fields = array(
                array( 'label' => _("I've been remixed"),
                       'flag' => CC_NTFY_IVE_BEEN_REMIXED, ),
                array( 'label' => _("I've been reviewed"),
                       'flag' => CC_NTFY_IVE_BEEN_REVIEWED, ),
                array( 'label' => _("I've been replied to"),
                       'flag' => CC_NTFY_REVIEW_REPLY, ),
                array( 'label' => _("I've been rated"),
                       'flag' => CC_NTFY_IVE_BEEN_RATED, ),
/*
                array( 'label' => _('Any upload has been editor picked'),
                       'flag' => CC_NTFY_ED_PICK, ),
*/
                );
        }

        // let's see if there's already a record
        // for this user in this context...

        $notifications =& CCNotifications::GetTable();
        $args['notify_user'] = CCUser::CurrentUser();
        if( $other_user_name )
        {
            $w2['user_name'] = $other_user_name;
            $args['notify_other_user'] = $users->QueryKey($w2);
        }
        else
        {
            $args['notify_other_user'] = 0;
        }
        $current_notifications = $notifications->QueryRow($args);
        
        require_once('ccextras/cc-notify-forms.php');

        $form = new CCEditUserNotificationForm($fields);

        if( !empty($_POST['editusernotification']) )
        {
            // user hit submit...

            $form->ValidateFields();
            $form->GetFormValues($values);
            $mask = 0;
            foreach( $values as $K => $V )
            {
                if( $V )
                    $mask |= intval(substr($K,2));
            }
            $args['notify_mask'] = $mask;
            if( $current_notifications )
            {
                $args['notify_id'] = $current_notifications['notify_id'];
                $notifications->Update($args);
            }
            else
            {
                $notifications->Insert($args);
            }
            
            CCPage::Prompt(_('Notifications have been updated'));
        }
        else
        {
            // we're displaying the form...

            if( $current_notifications )
            {
                $mask = $current_notifications['notify_mask'];
                if( $mask )
                {
                    // we need to populate the current
                    // settings for this user in this
                    // context

                    $values = array();
                    $bit = 1;
                    for( $bit = 1; $bit <= CC_NTFY_MAX; $bit <<= 1 )
                    {
                        if( $mask & $bit )
                            $values['f_' . $bit] = 'on';
                    }
                    $form->PopulateValues($values);
                }
            }

            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function OnRated($rating_rec,$rating,&$record)
    {
        $uploader = $record['upload_user'];
        $ibr = CC_NTFY_IVE_BEEN_RATED;
        $where = "(notify_user = $uploader) AND (notify_mask & $ibr)";
        $notifications =& CCNotifications::GetTable();
        $notify_row = $notifications->QueryRow($where);
        if( $notify_row )
        {
            $body = "\n\n" . 
                    _('PLEASE DO NOT RESPOND TO THIS EMAIL') . "\n\n" . 
                    _('This is a notification that "%s" has just received a rating of %d.00') . "\n\n%s\n";

            $body = sprintf($body, $record['upload_name'],
                                   $rating,
                                   $record['file_page_url'] );

            $subject = _('Ratings notification');
            $nrarray = array( $notify_row );
            $this->_do_notify($subject, $body, $nrarray  );
        }
    }


    function OnReview(&$review)
    {
        $uploads =& CCUploads::GetTable();
        $uploader = $uploads->QueryItemFromKey('upload_user',$review['topic_upload']);
        if( $uploader )
        {
            $ibr = CC_NTFY_IVE_BEEN_REVIEWED;
            $where = "(notify_user = $uploader) AND (notify_mask & $ibr)";
            $notifications =& CCNotifications::GetTable();
            $notify_row = $notifications->QueryRow($where);
            if( $notify_row )
            {
                $reviews =& CCReviews::GetTable();
                $review_rec = $reviews->GetRecordFromRow($review);
                $body = "\n\n" . _('PLEASE DO NOT RESPOND TO THIS EMAIL') . 
                "\n\n" . 
                _('This is a notification that "%s" has been reviewed by %s') .
                "\n\n%s\n";
                $upload_name = $uploads->QueryItemFromKey('upload_name',$review['topic_upload']);
                $body = sprintf($body,$upload_name,
                                      $review_rec['user_real_name'],
                                      $review_rec['topic_permalink']);

                $subject = _('Review notification');
                $nrarray = array( $notify_row );
                $this->_do_notify($subject, $body, $nrarray  );
            }
        }
    }

    function OnReply(&$reply, &$original)
    {
        // figure out everybody who's in
        // on this thread:

        $this_poster = $reply['topic_user'];
        $posters = array();
        $C =& $original;
        $topic_tree =& CCTopicTree::GetTable();
        $topics =& CCTopics::GetTable();
        while( $C )
        {
            if( ($C['topic_user'] != $this_poster) )
                $posters[] = $C['topic_user'];

            if( $C['topic_type'] != 'reply' )
                break;

            $ttarg['topic_tree_child'] = $C['topic_id'];
            $parent_id = $topic_tree->QueryItem('topic_tree_parent',$ttarg);
            if( empty($parent_id) )
                break;

            $row = $topics->QueryKeyRow($parent_id);
            $C =& $row;
        }

        // this shouldn't happen in today's code
        // but there's no reason to break tomorrows...

        if( empty($C) )
            return;

        if( !empty($C['topic_upload']) )
        {
            // $C should be pointing to a post at this point
            // let's add the person that uploaded the thing

            $uploads =& CCUploads::GetTable();
            $uploader = $uploads->QueryItemFromKey('upload_user', $C['topic_upload']);
            if( $uploader != $this_poster )
                $posters[] = $uploader;
        }

        // Do any of the participants even want to
        // get notified about replies?
        //
        $posters = array_unique($posters);

        if( empty($posters) )
            return;

        $wq = array();
        foreach( $posters as $poster )
        {
            $wq[] = "(notify_user = $poster)";
        }
        $wq = join( ' OR ', $wq );
        $nrr = CC_NTFY_REVIEW_REPLY;
        $where = "(notify_mask & $nrr) AND ($wq)";

        $notifications =& CCNotifications::GetTable();
        $notify_rows = $notifications->QueryRows($where);

        if( empty($notify_rows) )
            return;

        // Do the notifications
        //
        if( $C['topic_type'] == 'review' )
        {
            $reviews =& CCReviews::GetTable();
            $topic_rec = $reviews->GetRecordFromRow($C);
        }
        else
        {
            $topic_rec = $topics->GetRecordFromRow($C);
            $topic_rec['topic_permalink'] = ccl('thread',$C['topic_thread']) . '#' . $C['topic_id'];
        }

        $url = $topic_rec['topic_permalink'];

        $subject = _('Topic Reply Notification');
        $body = "\n\n" . _('PLEASE DO NOT RESPOND TO THIS EMAIL') . "\n\n" . 
        'This is a notification of a reply to a review thread, "%s":' . 
        "\n\n%s\n";
        $body = sprintf( $body, $C['topic_name'], $url );

        $this->_do_notify($subject,$body,$notify_rows);
    }

    function OnEdPick($upload_id)
    {
        /*
        $notifications =& CCNotifications::GetTable();
        $notify_rows = $notifications->QueryRows($where);
        */
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    * 
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'} 
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id,$op,&$parents)
    {
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);

        $uploader = $record['upload_user'];

        // people who want get notified when this user
        // has uploaded anything:

        $notify_rows = array();
        $where = "((notify_mask & " . CC_NTFY_THEY_UPLOADED . ") AND (notify_other_user = $uploader)) ";

        if( $parents )
        {
            // upload has remixes...

            $count = count($parents);
            $sampled_users = array();
            for( $i = 0; $i < $count; $i++ )
            {
                $sampled_users[] = $parents[$i]['user_id'];
            }

            if( $sampled_users )
            {
                $sampled_users = array_unique($sampled_users);
                $they_remixed = array();
                $me_remixed = array();
                foreach( $sampled_users as $sampled_user )
                {
                    $they_remixed[] = "(notify_other_user = $sampled_user)";
                    $me_remixed[] = "(notify_user = $sampled_user)";
                }

                // people who want to get notified when some one ELSE
                // is remixed:

                $tbr = CC_NTFY_THEY_BEEN_REMIXED;
                $they_remixed = join( ' OR ', $they_remixed );
                $where .= "OR ((notify_mask & $tbr) AND ($they_remixed)) ";

                // people who want to know when they themselves are
                // remixed...
                
                $ibr = CC_NTFY_IVE_BEEN_REMIXED;
                $me_remixed = join( ' OR ', $me_remixed );
                $where .= "OR ((notify_mask & $ibr) AND ($me_remixed))";
            }
        }

        $notifications =& CCNotifications::GetTable();
        $notify_rows = $notifications->QueryRows($where);

        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');
        $site_name = $ttags['site-title'];
        
        $subject = sprintf(_('Upload by %s'),$record['user_real_name']);
        $body = "\n\n" . _('PLEASE DO NOT RESPOND TO THIS EMAIL') . "\n\n" . 
        _('This is a notification that %s has just uploaded new material to %s')
        . "\n\n\"%s\"\n\n%s\n";
        $body = sprintf($body, $record['user_real_name'],
                               $ttags['site-title'],
                               $record['upload_name'],
                               $record['file_page_url'] );

        if( $parents )
        {
            $body .= _("It uses samples from:\n");
            $count = count($parents);
            for( $i = 0; $i < $count; $i++ )
            {
                $R =& $parents[$i];
                $body .= sprintf(_('%s by %s'), "\n \"{$R['upload_name']}\"", "{$R['user_real_name']}\n");
            }
        }

        $body .= "\n" . sprintf(_("Thank you, %s"), "\n" . "\n" . $ttags['site-title']);

        $this->_do_notify($subject, $body, $notify_rows);
    }


    function _do_notify($subject,$body,&$notifies)
    {
        global $CC_GLOBALS;

        $count = count($notifies);
        $user_ids = array();
        for( $i = 0; $i < $count; $i++ )
        {
            $user_ids[] = $notifies[$i]['notify_user'];
        }
        require_once('ccextras/cc-mail.inc');

        $user_ids = array_unique($user_ids);

        $mailer = new CCMailer();
        $mailer->Subject( $subject );
        $mailer->Body($body);
        $users =& CCUsers::GetTable();
        foreach( $user_ids as $user_id )
        {
            $address = $users->QueryItemFromKey('user_email',$user_id);
            $mailer->To($address);
            $ok = @$mailer->Send();
            if( !$ok )
            {
                CCDebug::Log("Message '$subject' failed to send");
            }
        }
    }

}



?>
