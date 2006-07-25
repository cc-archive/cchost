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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCUserAdmin', 'OnMapUrls'));

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

        $username = empty($user_id) ? '' : CCUser::GetUserName($user_id);

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => 'Login Name',
                               'formatter'  => 'username',
                               'value'      => $username,
                               'flags'      => CCFF_REQUIRED ),

                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => 'Security Key',
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => CCSecurityKeys::GetSecurityTip(),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE),

                    'user_password' =>
                       array( 'label'       => 'New Password',
                               'formatter'  => 'password',
                               'flags'      => CCFF_REQUIRED ),

                    );

        $this->AddFormFields( $fields );
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
                        array( 'label'      => 'Login Name',
                               'formatter'  => 'statictext',
                               'value'   => $username,
                               'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),

                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => 'Security Key',
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => CCSecurityKeys::GetSecurityTip(),
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

        $heads = array( "Delete", "Regular Expression Mask");
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
    function ChangePassword($user_id ='')
    {
        CCPage::SetTitle("Change a User's Password");

        $form = new CCChangePasswordForm($user_id);

        if( empty($_POST['changepassword']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $arg['user_password'] = md5($values['user_password']);
            $where = "LOWER(user_name) = '" . strtolower($values['user_name']) . "'";
            $users =& CCUsers::GetTable();
            $users->UpdateWhere($values,$where);
            $user_id = $users->QueryKey($where);
            $dummy = array();
            CCEvents::Invoke(CC_EVENT_USER_PROFILE_CHANGED, array( $user_id, &$dummy));
            CCPage::Prompt("User password changed");
        }
    }

    function Admin($user_id,$cmd='')
    {
        $users =& CCUsers::GetTable();
        $record = $users->GetRecordFromKey($user_id);
        if( empty($record) )
            return;

        $delfileslink = ccl('admin','user',$user_id,'delfiles');
        $deluserlink = ccl('admin','user',$user_id,'deluser');
        $ban_ip_link = ccl('admin','user',$user_id,'banip');
        $change_pass = ccl('admin','password',$user_id);

        $username = $record['user_name'];

        CCPage::SetTitle("Manage User Account for " . $username );

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
                             'menu_text' => $spanR . 'Delete All Files For ' . $uq . $spanC,
                             'help' => 'This action can not be un-done ' );;
        }
        else
        {
            $args[] = array( 'action' => '',
                             'menu_text' => '',
                             'help' => $uq . ' does not have any uploads to delete.' );
        }

        $args[] = array( 'action' => $deluserlink,
                         'menu_text' => $spanR . "Delete $uq Account" . $spanC,
                         'help' =>'This action can not be un-done');

        if( !empty($record['user_last_known_ip']) )
        {
            $ip = ' (' . CCUtil::DecodeIP(substr($record['user_last_known_ip'],0,8)) . ')';
            $args[] = array( 'action' => $ban_ip_link,
                             'menu_text' => "Manage IP address for " . $uq . $ip,
                             'help' => 'Allow/Deny access to site' );
        }
        else
        {
            $args[] = array( 'action' => '',
                             'menu_text' => '',
                             'help' => "(Can not  ban $uq IP because it has not been recorded)" );
        }

        $args[] = array( 'action' => $change_pass,
                         'menu_text' => "Change Password for " . $uq,
                         'help' => 'Create A New Password For This Account' );
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
            foreach( $ids as $id )
                CCUploadAPI::DeleteUpload($id);
            $url = ccl('people',$record['user_name']);
            return("Files have been deleted for {$record['user_name']}. See <a href=\"$url\">here</a> if you don't believe us.");
        }
    }

    function _del_user(&$record)
    {
        $username = $record['user_name'];
        $prompt = "Delete Account for '$username'";
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
            CCPage::Prompt("User account for {$record['user_name']} has been deleted.");
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
            $this->_save_banned_ips();
            return( "New IP Information Saved" );
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
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/password',   array('CCUserAdmin','ChangePassword'),  CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/user',       array('CCUserAdmin','Admin'),           CC_ADMIN_ONLY );

    }

}
?>