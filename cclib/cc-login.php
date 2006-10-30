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
* Module for handling user login
*
* @package cchost
* @subpackage user
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCLogin' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,  array( 'CCLogin',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_APP_INIT,   array( 'CCLogin',  'InitCurrentUser'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,   array( 'CCLogin',  'OnMapUrls'));

/**
* Wrapper for cc_tbl_keys database table, used in register spam prevention
*/
class CCSecurityKeys extends CCTable
{
    /**
    * Constructor (use GetTable() to get an instance of this table)
    *
    * @see GetTable
    */
    function CCSecurityKeys()
    {
        $this->CCTable('cc_tbl_keys','keys_id');
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
            $_table = new CCSecurityKeys();
        return( $_table );
    }

    /**
    * Add a key record to the database and returns a key that should match later
    *
    * @returns integer $id ID of this key
    */
    function AddKey($key)
    {
        $this->CleanUp();
        $ip = $_SERVER["REMOTE_ADDR"];
        $dbargs['keys_key']  = $key;
        $dbargs['keys_ip']   = $ip;
        $dbargs['keys_time'] = date('Y-m-d H:i');
        $this->Insert($dbargs);
        $id = $this->QueryKey("keys_key = '$key' AND keys_ip = '$ip'");
        return($id);
    }

    /**
    * Clean up utility function, empties the database of record over an hour old.
    */
    function CleanUp()
    {
        $this->DeleteWhere('keys_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    }

    /** 
    * Verify a key/id pair are a match
    */
    function IsMatch($key,$id)
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $real_id = $this->QueryKey("keys_key = '$key' AND keys_ip = '$ip'");
        return( $real_id === $id );
    }

    /**
    * Generate a fairly unique, kinda sorta unpredictable key that
    * doesn't use confusing characters like l1 oO0 8B zZ2 and 9g.
    */
    function GenKey()
    {
        $hash = md5(uniqid(rand(),true));
	    return( substr($hash,intval($hash[0],16),5) );
    }

    /**
    * Static function to return standard form tip for security field.
    */
    function GetSecurityTip()
    {
        return _('Type in the characters above. Valid characters are 0-9 and A-F. The zero (0) has a line through it, the D does not.');
    }
}

/**
* Registeration form
*/
class CCNewUserForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCNewUserForm()
    {
        global $CC_GLOBALS;

        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => _('Login Name'),
                               'formatter'  => 'newusername',
                               'form_tip'   => _('This must consist of letters, numbers or underscores (_) and be no longer than 25 characters.'),
                               'flags'      => CCFF_REQUIRED  ),
                    'user_email' =>
                       array( 'label'       => _('e-mail'),
                               'formatter'  => 'email',
                               'form_tip'   => _('This address will never show on the site. It is required for creating a new account and for password recovery in case you forget your private password.'),
                               'flags'      => CCFF_REQUIRED ),
                );

        $has_mail = !empty($CC_GLOBALS['reg-type']) && ($CC_GLOBALS['reg-type'] != CC_REG_NO_CONFIRM);

        if( !$has_mail )
        {
            $fields += array(
                    'user_password' =>
                       array( 'label'       => _('Password'),
                               'formatter'  => 'password',
                               'form_tip'   => _('This must be at least 5 characters long.'),
                               'flags'      => CCFF_REQUIRED )
                );
        }

        $fields += array( 
                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => _('Security Key'),
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => CCSecurityKeys::GetSecurityTip(),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE)
            );

        if( $has_mail )
        {
            $fields += array(
                    '_lost_password' =>
                       array( 'label'       => _('Lost Password?'),
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . 
                                               ccl('lostpassword') . '">' 
                                               . _('Click Here') . '</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                        );
        }

        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Register'));
    }

    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_newusername($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }

    /**
    * Handles validator for HTML field, called during ValidateFields()
    * 
    * Validates uniqueness of name as well as character checks and length.
    * 
    * @see CCForm::ValidateFields()
    * 
    * @param string $fieldname Name of the field will be passed in.
    * @returns bool $ok true means field validates, false means there were errors in user input
    */
    function validator_newusername($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            if( preg_match('/[^A-Za-z0-9_]/', $value) )
            {
                $this->SetFieldError($fieldname," " . _("This must letters, numbers or underscores (_)"));
                return(false);
            }

            if( strlen($value) > 25 )
            {
                $this->SetFieldError($fieldname, " " . _("This must be less than 25 characters."));
                return(false);
            }

            $users =& CCUsers::GetTable();
            $user = $users->GetRecordFromName( $value );

            if( empty($user) )
            {
                $tags =& CCTags::GetTable();
                $user = $tags->QueryKeyRow($value);
            }

            if( $user )
            {
                $this->SetFieldError($fieldname,_("That username is already in use or is reserved by the system."));
                return(false);
            }


            return( true );
        }

        return( false );
    }
}

/**
* Login form 
*/
class CCUserLoginForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCUserLoginForm()
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => _('Login Name'),
                               'formatter'  => 'username',
                               'flags'      => CCFF_REQUIRED ),

                    'user_password' =>
                       array( 'label'       => _('Password'),
                               'formatter'  => 'matchpassword',
                               'flags'      => CCFF_REQUIRED ),

                    'user_remember' =>
                       array( 'label'       => _('Remember Me'),
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_NONE ),

                    '_new_user' =>
                       array( 'label'       => _('New User?'),
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('register') . '">' .
                                             _('Click Here') . '</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                        );

        $has_mail = empty($CC_GLOBALS['reg-type']) || ($CC_GLOBALS['reg-type'] == CC_REG_NO_CONFIRM);

        if( $has_mail )
        {
            $fields += array( 
                    '_lost_password' =>
                       array( 'label'       => _('Lost Password?'),
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . 
                                               ccl('lostpassword') . '">' .
                                               _('Click Here') . '</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                    );
        }

        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Log In'));
    }
}

/**
* Form for when user need a password reminder
*/
class CCLostPasswordForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCLostPasswordForm()
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => _('Login Name'),
                               'formatter'  => 'username',
                               'flags'      => CCFF_REQUIRED ),

                    '_new_user' =>
                       array( 'label'       => _('New User?'),
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('register') . '">' . _('Click Here') . '</a>',
                               'flags'      => CCFF_NOUPDATE  | CCFF_STATIC),
                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Retrieve Password'));
    }
}

/**
* General log in API and system event handler class
*/
class CCLogin
{
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
        if( $scope == CC_GLOBAL_SCOPE && class_exists('CCMailer') )
        {
            $fields['reg-type'] =
               array(  'label'      => _('Registration Confirmation'),
                       'form_tip'   => _('What type of registrations confirmation should the system use.'),
                       'value'      => 'usermail',
                       'formatter'  => 'select',
                       'options'    => array( 
                                        CC_REG_USER_EMAIL => 
                                        _('Send user email with new password'),
                                        CC_REG_ADMIN_EMAIL => 
                                        _('Send admin email to confirm new login information'),
                                        CC_REG_NO_CONFIRM => 
                                        _('On screen confirm (no emails used)')
                                        ),
                       'flags'      => CCFF_POPULATE  ); // do NOT require cookie domain, blank is legit
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $items = array(
            'register'  => array( 
                             'menu_text'  => _('Register'),
                             'access'  => CC_ONLY_NOT_LOGGED_IN,
                             'menu_group' => 'artist',
                             'weight' => 5,
                             'action' => ccp('register')
                             ),


            'login'  => array( 
                             'menu_text'  => _('Log In'),
                             'access'  => CC_ONLY_NOT_LOGGED_IN,
                             'menu_group' => 'artist',
                             'weight' => 1,
                             'action' => ccp('login')
                             ),

                );
    
        CCMenu::AddItems($items);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'register',       array( 'CCLogin', 'Register'),        
            CC_ONLY_NOT_LOGGED_IN, ccs(__FILE__), '', _('Show register form'), CC_AG_USER );
        CCEvents::MapUrl( 'login',          array( 'CCLogin', 'Login'),           
            CC_ONLY_NOT_LOGGED_IN, ccs(__FILE__), '', _('Show login form'), CC_AG_USER  );
        CCEvents::MapUrl( 'logout',         array( 'CCLogin', 'Logout'),          
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '', _('Logout current user'), CC_AG_USER  );
        CCEvents::MapUrl( 'lostpassword',   array( 'CCLogin', 'LostPassword'),    
            CC_ONLY_NOT_LOGGED_IN, ccs(__FILE__), '', _('Show lost password form'), CC_AG_USER  );
        CCEvents::MapUrl( 's',              array( 'CCLogin', 'OnSecurityCallback'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
    }

    /**
    * Puts up a registration for, handler for /register URL
    */
    function Register()
    {
        global $CC_GLOBALS;

        CCPage::SetTitle(_("Create A New Account"));
        $form = new CCNewUserForm();
        $form->SetHelpText(_('This site requires cookies to be enabled in your browser'));
        
        $show = empty($_POST['newuser']) || !$form->ValidateFields();

        if( !$show )
        {
            $form->GetFormValues($fields);
            $reg_type = empty($CC_GLOBALS['reg-type']) ? null : $CC_GLOBALS['reg-type'];
            if( !empty($reg_type) && $reg_type != CC_REG_NO_CONFIRM )
            {
                $new_password = $this->_make_new_password();
                $fields['user_password'] = md5($new_password);
            }

            $status = array();
            CCEvents::Invoke( CC_EVENT_USER_REGISTERED, array( $fields, &$status ) );
            $show = !empty($status['error']);
            if( $show )
            {
                if( !empty($status['error_field']) )
                {
                    $form->SetFieldError($status['error_field'],$status['error']);
                }
                else
                {
                    $msg= $status['error'];
                    if( CCDebug::IsEnabled() )
                        $msg .= " " . $status['sql_error'];
                    CCPage::SystemError($msg);
                }
            }
            else
            {
                $fields['user_registered'] = date( 'Y-m-d H:i:s' );
                $fields['user_real_name'] = $fields['user_name'];
                $users =& CCUsers::GetTable();

                if( empty($reg_type) || $reg_type == CC_REG_NO_CONFIRM )
                {
                    $url = ccl('login');
                }
                else
                {
                    if( $reg_type == CC_REG_ADMIN_EMAIL )
                    {
                        $why = _('A new account has been requested by:') . ' ' . $fields['user_name'] . ' ' . _('email') . ': ' .
                                $fields['user_email'] . ' ' . _('from IP:') . ' ' . $_SERVER['REMOTE_ADDR'];
                        $to = $CC_GLOBALS['mail_sender'];
                    }
                    else
                    {
                        $why = _('You are receiving this email because you requested a new account.');
                        $to =  $fields['user_email'];
                    }

                    $this->_send_login_info($fields['user_name'], _("New Account Registration"),$why,$new_password,$to);
                    $url =  ccl('login','new','confirm');

                }

                $users->Insert($fields);

                // We have to redirect here to the login form
                // because on some servers (IIS) throw away
                // cookie information. I guess we could check 
                // for platform but now we just act the same
                // no matter who you are
                CCUtil::SendBrowserTo($url);
            }
        }

        if( $show )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    /**
    * Handles /logout URL 
    */
    function Logout()
    {
        global $CC_GLOBALS;

        cc_setcookie(CC_USER_COOKIE,'',time());
        unset($_COOKIE[CC_USER_COOKIE]);
        CCPage::Prompt(_('You are now logged out'));
        CCPage::SetTitle(_("Log Out"));
        unset($CC_GLOBALS['user_name']);
        unset($CC_GLOBALS['user_id']);
        CCMenu::Reset();
        CCEvents::Invoke( CC_EVENT_LOGOUT );
    }

    /**
    * Handles /login URL, puts up log in form
    */
    function Login($do_ui=true,$confirm='')
    {
        global $CC_GLOBALS;

        $do_ui = is_string($do_ui) || $do_ui; // sorry bout that

        $form = new CCUserLoginForm();

        if( empty($_POST['userlogin']) || !$form->ValidateFields() )
        {
            if( !empty($confirm) )
            {
                $reg_type = empty($CC_GLOBALS['reg-type']) ? null : $CC_GLOBALS['reg-type'];
                if( $reg_type == CC_REG_ADMIN_EMAIL )
                    $rmsg = _('You registration request has been forwarded to the site administrators.');
                elseif( $reg_type == CC_REG_USER_EMAIL )
                    $rmsg = _("Your new login information has been sent to your email address.");
                if( !empty($rmsg) )
                    CCPage::Prompt($rmsg);
            }

            CCEvents::Invoke(CC_EVENT_LOGIN_FORM,array(&$form));
            CCPage::SetTitle(_("Log In"));
            CCPage::AddForm( $form->GenerateForm() );
            $ok = false;
        }
        else
        {
            $CC_GLOBALS = array_merge($CC_GLOBALS,$form->record);
            
            if( $form->GetFormValue('user_remember') == 1 )
                $time = time()+60*60*24*30;
            else
                $time = null;
            $val = array( $CC_GLOBALS['user_name'], $CC_GLOBALS['user_password'] );
            $val = serialize($val);
            cc_setcookie(CC_USER_COOKIE,$val,$time);
            if( $do_ui )
            {
                CCMenu::Reset();
                $userapi = new CCUser();
                $userapi->UserPage($CC_GLOBALS['user_name']);
            }
            $ok = true;
        }

        return( $ok );
    }

    /**
    * Handler for /lostpassword URL puts up form an responds to it (not implemented yet)
    */
    function LostPassword()
    {
        if( !class_exists('CCMailer') )
        {
            CCPage::Prompt(_('This installation does not support this feature.'));
            return;
        }

        CCPage::SetTitle(_("Recover Lost Password"));
        $form = new CCLostPasswordForm();
        if( empty($_POST['lostpassword']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($fields);
            $user_name = $fields['user_name'];
            $users =& CCUsers::GetTable();
            $row = $users->QueryRow($fields);
            $new_password = $this->_make_new_password();

            $args['user_id'] = $row['user_id'];
            $args['user_password'] = md5($new_password);
            $users->Update($args);
            CCEvents::Invoke(CC_EVENT_USER_PROFILE_CHANGED, array( $args['user_id'] , &$row));

            $why = _('You are receiving this email because you requested a new password.');
            $this->_send_login_info($user_name,_("Recover Lost Password"),$why,$new_password,$row['user_email']);
            CCPage::Prompt(_("New password has been sent to your email address."));
        }

    }

    function _make_new_password($len=6)
    {
        return( substr( md5(uniqid(rand(),true)), rand() & 7, $len ) );
    }

    function _send_login_info($user_name,$subject,$why,$new_password,$to)
    {
        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');
        $site_name = $ttags['site-title'];
        $mailer = new CCMailer();
        $mailer->To($to);
        $url = ccl('login');
        $msg =
            _("Hi from %s!") . "\n\n%s\n\n" . 
            _("Please visit: %s") . "\n\n" . 
            _("Use the following information to log in:") . "\n\n" . 
            _('Login name: %s') . "\n" . 
            _('Password: %s') . "\n" . 
            _('You should then edit your profile and change the password to whatever you like.') . 
            _('Thanks') . ",\n" . 
            _('Admin') . ", %s\n";

        $msg = sprintf(_($msg),$site_name,$why,$url,$user_name,$new_password,
                               $site_name);
        $mailer->Body($msg);
        $mailer->Subject($site_name . ': ' . $subject);
        $mailer->Send();
    }

    /**
    * Digs around the cookies looking for an auto-login. If succeeds, populate CC_GLOBALS with user data
    */
    function InitCurrentUser()
    {
        global $CC_GLOBALS;

        if( !empty($_COOKIE[CC_USER_COOKIE]) )
        {
            $users =& CCUsers::GetTable();
            $val = $_COOKIE[CC_USER_COOKIE];
            if( is_string($val) )
            {
                $val = unserialize(stripslashes($val));
                $record = $users->GetRecordFromName( $val[0] );
                if( !empty( $record ) && ($record['user_password'] == $val[1]) )
                {
                    $CC_GLOBALS = array_merge($CC_GLOBALS,$record);
                    $users->SaveKnownIP();
                }
            }
        }
    }

    /**
    * Handles /s URL
    * 
    * This function does NOT return, it sends an image back to the browser then exits.
    * 
    * @see CCNewUserForm::generator_securitykey()
    * @param integer $s Combination ID and index into a security key
    */
    function OnSecurityCallback($s)
    {
        $intval = intval($s);
        if( !$intval )
            exit;
        $key = intval($intval / 100);
        $offset = $intval % 100;
        $keys =& CCSecurityKeys::GetTable();
        $hash = $keys->QueryItemFromKey('keys_key',$key);
        $ip   = $keys->QueryItemFromKey('keys_ip',$key);
        if( empty($hash) || ($ip != $_SERVER['REMOTE_ADDR']) )
            exit;
        $ord  = ord($hash[$offset]);
        $fname = sprintf("ccimages/hex/f%x.png",$ord);
        header ("Content-Type: image/png");
        readfile($fname);
        exit;
    }

}

 


?>
