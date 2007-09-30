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

require_once('cclib/cc-user.inc');

/**
* Change the default avatar used by new users
*
*/
class CCDefaultAvatarForm extends CCUploadForm
{
    function CCDefaultAvatarForm($avatar_dir)
    {
        global $CC_GLOBALS;

        $this->CCUploadForm();

        $path = empty($CC_GLOBALS['default_user_image']) ? '' : $CC_GLOBALS['default_user_image'];
        $fields = array( 
                    'default_user_image' =>
                       array(  'label'      => _('Image'),
                               'formatter'  => 'avatar',
                               'form_tip'   => _('Image file (can not be bigger than 93x93)'),
                               'upload_dir' => $avatar_dir,
                               'value'      => basename($path),
                               'maxwidth'   => 93,
                               'maxheight'  => 94,
                               'flags'      => CCFF_NONE ),
                        );

        $this->AddFormFields( $fields );
        $this->EnableSubmitMessage(false);
    }

}


/**
* Change a user's password
* 
*/
class CCChangePasswordForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCChangePasswordForm($user_id)
    {
        $this->CCUserForm();

        $users =& CCUsers::GetTable();
        $row = $users->QueryKeyRow($user_id);
        $username = $row['user_name'];
        $email = $row['user_email'];

        $fields = array( 
                    'lname' =>
                        array( 'label'      => _('Login Name'),
                               'formatter'  => 'statictext',
                               'value'      => $username,
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),

                    'user_password' =>
                       array( 'label'       => _('New Password'),
                               'formatter'  => 'password',
                               'flags'      => CCFF_NONE ),

                    'user_email' =>
                       array(   'label'       => _('email'),
                               'value'      => $email,
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_REQUIRED ),

                    );

        $this->AddFormFields( $fields );
        $this->SetHiddenField('user_name',$username);
    }
}

class CCDeleteUserFilesForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCDeleteUserFilesForm($username,$prompt)
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => _('Login Name'),
                               'formatter'  => 'statictext',
                               'value'   => $username,
                               'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),

                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => _('Security Key'),
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => CCSecurityVerifierForm::GetSecurityTip(),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE),
                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText( $prompt );
    }

}
class CCIPManageForm extends CCGridForm
{
    /**
    * Constructor
    */
    function CCIPManageForm($ip_masks)
    {
        $this->CCGridForm();

        $heads = array( _("Delete"), _("Regular Expression Mask"));
        $this->SetColumnHeader($heads);

        $i = 1;
        foreach( $ip_masks as $mask )
        {
            $K = "masks[$i]";
    
            $a = array(  
                array(
                    'element_name'  => $K . '[delete]',
                    'value'      => '',
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => $K . '[mask]',
                    'value'      => htmlspecialchars($mask),
                    'formatter'  => 'regex',
                    'flags'      => CCFF_NONE ),
                );

            $this->AddGridRow($i++,$a);
        }
    }
}

class CCUserAdmin
{
    function DefaultAvatar()
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['avatar-dir']) )
            $upload_dir = CCUser::GetUploadDir(CCUser::CurrentUserName());
        else
            $upload_dir = $CC_GLOBALS['avatar-dir'];

        CCPage::SetTitle(_("Set Default User Avatar"));
        $form  = new CCDefaultAvatarForm($upload_dir );

        if( !empty($_POST['defaultavatar']) && $form->ValidateFields() )
        {
            $form->FinalizeAvatarUpload('default_user_image', $upload_dir);
            $form->GetFormValues($fields);
            if( $fields['default_user_image'] )
                $args['default_user_image'] = 
                    ccp($upload_dir,$fields['default_user_image']);
            else
                $args['default_user_image'] = 0;
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig('config',$args);
            CCPage::Prompt(_('Default avatar set'));
        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function ChangePassword($user_id ='')
    {
        CCPage::SetTitle(_("Change a User's Password/E-mail"));

        $users =& CCUsers::GetTable();
        $form = new CCChangePasswordForm($user_id);

        if( empty($_POST['changepassword']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            if( empty($values['user_password']) )
                unset($values['user_password']);
            $where = "LOWER(user_name) = '{$values['user_name']}'";
            $users->UpdateWhere($values,$where);
            $user_id = $users->QueryKey($where);
            $dummy = array();
            CCEvents::Invoke( CC_EVENT_USER_PROFILE_CHANGED, array( $user_id, &$dummy ) );
            CCUtil::SendBrowserTo( ccl('admin','user',$user_id) );
        }
    }

    function Admin($user_id,$cmd='')
    {
        $users =& CCUsers::GetTable();
        $record = $users->GetRecordFromKey($user_id);
        if( empty($record) )
            return;

        $username = $record['user_name'];

        $delfileslink = ccl('admin','user',$user_id,'delfiles');
        $deluserlink = ccl('admin','user',$user_id,'deluser');
        $ban_ip_link = ccl('admin','user',$user_id,'banip');
        $change_pass = ccl('admin','password',$user_id);
        $activity_user = url_args( ccl('activity'), 'user=' . $username );

        $ip = empty($record['user_last_known_ip']) ? '' : CCUtil::DecodeIP(substr($record['user_last_known_ip'],0,8)); 
        if( $ip )        
            $activity_ip = url_args( ccl('activity'), 'ip=' . $ip );


        CCPage::SetTitle(sprintf(_("Manage User Account for %s"), $username ));

        switch( $cmd )
        {
            case 'delfiles':
                $msg = $this->_del_user_files($record);
                if( $msg === false )
                    return;
                break;

            case 'deluser':
                $this->_del_user($record);
                return;

            case 'banip':
                $msg = $this->_ban_ip($record);
                if( empty($msg) )
                    return;
                break;

            default:
                $msg = '';
                break;
        }

        if( !empty($msg) )
            CCPage::Prompt($msg);

        $spanR = '<span style="color:red">';
        $spanC = '</span>';
        $uq = "'$username'";

        $args = array();
        $uploads =& CCUploads::GetTable();
        $wup['upload_user'] = $user_id;
        $num_uploads = $uploads->CountRows($wup);

        if( $num_uploads )
        {
            $args[] = array( 'action' => $delfileslink,
                             'menu_text' => $spanR . 
                                sprintf(_('Delete All Files For %s'), $uq . $spanC),
                             'help' => _('This action can not be un-done') . ' ' );
        }
        else
        {
            $args[] = array( 'action' => '',
                             'menu_text' => '',
                             'help' => sprintf(_('%s does not have any uploads to delete.'), $uq ) );
        }

        $args[] = array( 'action'    => $deluserlink,
                         'menu_text' => $spanR . sprintf(_("Delete %s Account"), $uq) . $spanC,
                         'help'      => _('This action can not be undone.'));

        if( !empty($ip) )
        {

            $args[] = array( 'action'    => $ban_ip_link,
                             'menu_text' => sprintf(_("Manage IP address for %s"), $uq . ' (' .  $ip . ')'),
                             'help'      => _('Allow or Deny access to the site.') );

            $args[] = array( 'action'    => $activity_ip,
                             'menu_text' => sprintf(_("Activity for %s"), $ip ),
                             'help'      => _('See Activity Log for this IP address.') );
        }
        else
        {
            $args[] = array( 'action'    => '',
                             'menu_text' => '',
                             'help'      => sprintf(_("(Cannot ban %s IP because it has not been recorded)"), $uq) );
        }

        $args[] = array( 'action' => $change_pass,
                         'menu_text' => sprintf(_("Change Password/E-mail for %s"), $uq),
                         'help' => _('Create A New Password and Change E-mail For This Account') );


        $args[] = array( 'action'    => $activity_user,
                         'menu_text' => sprintf(_("Activity for %s"), $uq ),
                         'help'      => _('See Activity Log for this user.') );

        CCPage::PageArg('link_table_items',$args,'link_table');

    }

    function _del_user_files(&$record)
    {
        $username = $record['user_name'];
        $prompt = "Delete all files for '$username'";
        $form = new CCDeleteUserFilesForm($username,$prompt);
        if( empty($_POST['deleteuserfiles']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
            return( false );
        }
        else
        {
            $uploads =& CCUploads::GetTable();
            $where['upload_user'] = $record['user_id'];
            $ids = $uploads->QueryKeys($where);
            require_once('cclib/cc-uploadapi.php');
            foreach( $ids as $id )
                CCUploadAPI::DeleteUpload($id);
            $url = ccl('people',$record['user_name']);

            return( sprintf(_("Files have been deleted for user, %s."), $record['user_name']) . sprintf(_("See %s if you don't believe us."), "<a href=\"$url\">here</a>") );
        }
    }

    function _del_user(&$record)
    {
        $username = $record['user_name'];
        $prompt = sprintf(_("Delete Account for user, %s"), $username);
        $form = new CCDeleteUserFilesForm($username,$prompt);
        if( empty($_POST['deleteuserfiles']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCEvents::Invoke( CC_EVENT_USER_DELETED, array( $record['user_id'] ) );
            $this->_del_user_files($record);
            $users =& CCUsers::GetTable();
            $where['user_id'] = $record['user_id'];
            $users->DeleteWhere($where);
            CCPage::Prompt(sprintf(_("User account for user, %s, has been deleted."), $record['user_name']));
        }
    }

    function _ban_ip(&$record)
    {
        global $cc_banned_ips;
        $ip = CCUtil::DecodeIP(substr($record['user_last_known_ip'],0,8));
        $new_ip = '(' . str_replace('.','\.',$ip) . ')';
        if( empty($cc_banned_ips) )
        {
            $cc_banned_ips[] = $new_ip;
        }
        else
        {
            array_unshift( $cc_banned_ips, $new_ip );
        }

        $form = new CCIPManageForm($cc_banned_ips);

        if( empty($_POST['ipmanage']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCEvents::Invoke( CC_EVENT_USER_IP_BANNED, array( &$record, $ip ) );
            $this->_save_banned_ips();
            return( _("New IP Information Saved") );
        }
    }

    function _save_banned_ips()
    {
        $masks = $_POST['masks'];
        $new_masks = '';
        foreach( $masks as $mask )
            if( empty($mask['delete']) )
                $new_masks .= "    '" . CCUtil::StripSlash($mask['mask']) . "',\n";

        $sphp = '<?';
        $ephp = '?>';

        $text =<<<END
$sphp
if( !defined('IN_CC_HOST') ) exit;
\$cc_banned_ips = array (
$new_masks
);
if( @preg_match('/' . implode('|',\$cc_banned_ips) . '/',\$_SERVER['REMOTE_ADDR']) ) exit;
$ephp
END;
        $f = fopen('.cc-ban.txt','w');
        fwrite($f,$text);
        fclose($f);
        chmod('.cc-ban.txt',cc_default_file_perms());

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
            'defaultavatar'   => array( 'menu_text'  => _('Default User Avatar'),
                             'menu_group' => 'configure',
                             'help' => _('Upload a default avatar for new users'),
                             'weight' => 18,
                             'action' =>  ccl('admin','avatar'),
                             'access' => CC_ADMIN_ONLY
                             )
            );
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/password',   array('CCUserAdmin','ChangePassword'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '{userid}', 
            _('Show admin "Account Management" form'), CC_AG_USER );

        CCEvents::MapUrl( 'admin/user',       array('CCUserAdmin','Admin'),           
            CC_ADMIN_ONLY, ccs(__FILE__), '{userid}', 
            _('Admin a user IP, profile, etc.'), CC_AG_USER );

        CCEvents::MapUrl( 'admin/avatar', array('CCUserAdmin','DefaultAvatar'),  
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _('Set the default user avatar'), CC_AG_USER );
    }

}
?>
