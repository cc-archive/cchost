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
* @subpackage user
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*
*
*/
class CCOpenID
{
    function Login()
    {
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/ccextras/cc-openid-forms.inc');
        $title = 'str_openid_login_title';
        CCPage::SetTitle($title);
        $form = new CCOpenIDLoginForm();
        if( empty($_POST['openidlogin']) || !$form->ValidateFields() )
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $form->GetFormValues($values);
            require_once('cchost_lib/ccextras/cc-openid-handler.inc');
            cc_openid_handler('login',$values['openid_url']);
        }
    }

    function OnFormInit(&$form)
    {
        $class = strtolower( get_class($form) );
        switch( $class )
        {
            case 'cccontactmailerform':
            {
                // hmm, well, hack the URL to get the username
                preg_match('#/[a-z0-9_]+$#i',$_GET['ccm'],$m);
                $m = ltrim($m[0],'/');
                if( empty($m) )
                {
                    return; //this should never happen
                }
                $user_email = CCDatabase::QueryItem('SELECT user_email FROM cc_tbl_user WHERE user_name=\''.$m.'\'');
                if( !empty($user_email) )
                    return; // we're cool, let the system handle it.
                $url = ccl('openid','contact',$m);
                CCUtil::SendBrowserTo($url);
                break;
            }
            case 'cceditusernotificationform':
            {
                $user_name = CCUser::CurrentUserName();
                $user_email = CCDatabase::QueryItem(
                    'SELECT user_email FROM cc_tbl_user WHERE user_name=\''.$user_name.'\'');
                if( !empty($user_email) )
                    return; // we're cool, let the system handle it.
                $url = ccl('openid','notify',$user_name);
                CCUtil::SendBrowserTo($url);
                break;
            }
        }

    }

    function Contact($user_name)
    {
        require_once('cchost_lib/cc-page.php');
        $page =& CCPage::GetPage();
        $page->SetTitle('str_openid_contact');
        $user_id = CCUser::IDFromName($user_name);
        $openids = $this->_get_openids($user_id);
        $page->SetArg('contact_info',$openids,'openid_contact');
    }

    function Notify($user_name)
    {
        require_once('cchost_lib/cc-page.php');
        $page =& CCPage::GetPage();
        $page->SetTitle('str_openid_notify');
        $page->SetArg('user_name',$user_name,'openid_notify');
    }
    
    /**
    * Event handler for {@link CC_EVENT_FORM_VERIFY}
    * 
    * @param object &$form CCForm object
    * @param boolean &$retval Set this to false if fields fail to verify
    */
    function OnFormVerify(&$form,&$retval)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['openid-type']) )
            return;

        // nb: this method will alter the db (data loss!)
        // and possibly email the user
        // even though we are not 100% sure the data in the form will
        // be saved. We are assuming here that that our OnFormVerify
        // is the last one called. If there are any others after us
        // that reject the data in the form then this was all a
        // wasted (and confusing) effort - in fact, things might
        // even break.
        //
        // To fix this, we really need a FORM_SAVE event, after
        // everybody has verified all the fields.
        //
        
        // don't bother is someone else rejected this form already
        if( !$retval )
            return;
            
        $class = strtolower( get_class($form) );
        
        switch( $class )
        {
            case 'ccuserprofileform':
            {
                $value = $form->GetFormValue('openids_stop');
                if( $value )
                {
                    $openid = $form->GetFormValue('openids');
                    $table = new CCTable('cc_tbl_openids','openid');
                    $table->DeleteKey($openid);
                    $user_id = CCUser::CurrentUser();
                    $ids = $this->_get_openids($user_id);
                    if( empty($ids) )
                    {
                        // that was the last openid, we have to setup
                        // a new password for this person.
                        require_once('cchost_lib/cc-login.php');
                        $login = new CCLogin();
                        $new_password = $login->_make_new_password();
                        // this will save the new pw to the record
                        // and set the cookie to reflect the change
                        $form->SetFormValue('user_password',$new_password);
                        $user_name = CCUser::CurrentUserName();
                        // we have to get this from the form in case
                        // it's brand new (it hasn't been saved yet)
                        $email = $form->GetFormValue('user_email');
                        $login->_send_login_info($user_name,'str_openid_new_pass','str_openid_you_are_rec',
                                        $new_password,$email);
                    }
                    
                }
            }
        }
        $retval = true;
        return true;
    }

    function _get_openids($user_id)
    {
        return CCDatabase::QueryItems('SELECT openid FROM cc_tbl_openids WHERE openid_user='.$user_id);
    }
    
    function OnFilterUserProfile(&$records)
    {
        $user_rec =& $records[0];
        $openids = $this->_get_openids($user_rec['user_id']);
        if( empty($openids) )
            return;
        $text = array();
        foreach( $openids as $openid )
        {
            $text[] = "<a href=\"{$openid}\" class=\"cc_openid_link\">{$openid}</a>";
        }
        $text = join('<br />',$text);
        $arr = array( 'label' => 'str_openids', 'value' => $text, 'id' => 'openids' );
        if( count($user_rec['user_fields']) > 0 )
        {
            array_splice( $user_rec['user_fields'], 1, 0, array( $arr ) );
        }
        else
        {
            $row['user_fields'][] = $arr;
        }
    }
    
    function OnFormFields( &$form, &$fields )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['openid-type']) )
            return;

        $class = strtolower( get_class($form) );
        
        switch( $class )
        {
            case 'ccnewuserform':
            case 'ccuserloginform':
            {
                
                $url = ccl('login','openid');
                $link1 = "<a href=\"{$url}\" id=\"cc_openid_enabled\">";
                $link2 = '</a>';
                $form->SetHelpText(array('str_openid_login_help',$link1,$link2));
                break;
            }
            
            case 'ccuserprofileform':
            {
                $user_id = CCUser::CurrentUser();
                $openids = $this->_get_openids($user_id);
                if( !empty($openids) )
                {
                    // keep the password fields around in case
                    // we need to generate a new one later, it
                    // will make things a lot easier.
                    $fields['user_password']['formatter'] = 'hidden';
                    $fields['user_password']['flags']     = CCFF_HIDDEN;  // do NOT populate
                    $fields['user_password']['value']     = '';  // CCForm requires 'value' is present

                    $tip = count($openids) == 1 ? 'str_openid_tip2' : 'str_openid_tip';
                    $field['openids'] = array (
                     'label'      => '',
                     'form_tip'   => '',
                     'formatter'  => 'select',
                     'options'    => array_combine($openids,$openids),
                     'flags'      => CCFF_NOUPDATE );
                    $field['openids_stop'] = array (
                     'label'      => 'str_openid_stop',
                     'form_tip'   => $tip,
                     'formatter'  => 'checkbox',
                     'value'      => '',
                     'flags'      => CCFF_NOUPDATE );
                    $form->InsertFormFields( $field, 'after', 'user_real_name');
                }
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
            $fields['openid-type'] =
               array(  'label'      => _('OpenID'),
                       'form_tip'   => _('Enable OpenID log in and registration.'),
                       'value'      => '',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE  ); 
        }
    }

    function OnIDFailed($msg)
    {
	    require_once('cchost_lib/cc-page.php');
	    CCPage::Prompt( $msg );
    }
        
    function OnTryMatch()
    {
        if( empty($_POST['openidinfo']) )
            CCUtil::Send404();
        $args = unserialize( urldecode($_POST['openidinfo']) );
        //d($args);
        require_once('cchost_lib/ccextras/cc-openid-forms.inc');
        $form = new CCOpenIDHookUpForm($args);
        if( !$form->ValidateFields() )
        {
            require_once('cchost_lib/cc-page.php');
            CCPage::SetTitle('str_openid_login_title');
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            if( !empty($form->_match_user_id) )
            {
                $this->_do_assoc($form->_args['openid'],$form->_match_user_id);
                $this->_do_login($form->_match_user_id);
            }
            elseif( !empty($form->_new_user_info) )
            {
                require_once('cchost_lib/cc-login.php');
                $login = new CCLogin();
                $new_password = $login->_make_new_password();
                $fields['user_password'] = md5($new_password);
                $fields['user_registered'] = date( 'Y-m-d H:i:s' );
                $fields['user_name'] = $form->_new_user_info[0];
                $fields['user_real_name'] = empty($form->_new_user_info[1]) ? $fields['user_name'] : 
                                                    $form->_new_user_info[1];
                $users =& CCUsers::GetTable();
                $fields['user_id'] = $users->NextID();
                $users->Insert($fields);
                $this->_do_assoc($form->_args['openid'],$fields['user_id']);
                $this->_do_login($fields['user_id']);
            }
            else
            {
                // this should never happen
            }

        }
    }

    function _do_assoc($openid,$user_id)
    {
        $rec['openid'] = rtrim($openid,'/');
        $rec['openid_user'] = $user_id;
        $openids = new CCTable('cc_tbl_openids','openid');
        $openids->Insert($rec);
    }
        
    function _do_login($user_id)
    {
        require_once('cchost_lib/cc-login.php');
        list( $user, $pw ) = CCDatabase::QueryRow(
           'SELECT user_name,user_password FROM cc_tbl_user WHERE user_id='.$user_id,false);
        $login = new CCLogin();
        $login->_create_login_cookie(1,$user,$pw);
        $login->_do_login_redirect(ccl('people',$user));
    }
    
	function OnIDVerified( $openid,$canonicalID,$sreg)
	{
	    /*
            [fullname] => victor stone
            [nickname] => victorstone
            [email] => victor.stone@gmail.com
	     */
        if( empty($canonicalID) )
            $canonicalID = rtrim($openid,'/');
        $args['connonical'] = $canonicalID;
        $row = CCDatabase::QueryRow("SELECT * FROM cc_tbl_openids WHERE openid = '{$canonicalID}'");
        if( empty($row) )
        {
            require_once('cchost_lib/cc-page.php');
            CCPage::SetTitle('str_openid_login_title');
            $args = $sreg;
            $args['openid'] = $openid;
            $user_name = cc_fancy_user_sql('display_name');
            $sql = "SELECT user_id, user_email, {$user_name} FROM cc_tbl_user WHERE (user_homepage LIKE '{$canonicalID}%') ";
            if( !empty($args['nickname']) )
            {
                $sql .= "OR (user_name = '{$args['nickname']}') ";
                $nick_n = $args['nickname'];
                $taken = CCUser::IDFromName($nick_n);
                $cnt = 0;
                while( !empty($taken) )
                {
                    $nick_n = sprintf($args['nickname'] . '%0d',++$cnt);
                    $taken = CCUser::IDFromName($nick_n);
                }
                $args['free_user_name'] = $nick_n;
            }
            if( !empty($args['email']) )
            {
                $sql .= "OR (user_email = '{$args['email']}') ";
            }
            if( !empty($args['fullname']) )
            {
                $sql .= "OR (user_real_name = '{$args['fullname']}') ";
            }
            $args['matches'] = CCDatabase::QueryRows($sql);
              
            require_once('cchost_lib/ccextras/cc-openid-forms.inc');
            $form = new CCOpenIDHookUpForm($args);
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $this->_do_login($row['openid_user']);
        }
    }
    
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('login','openid'), array( 'CCOpenID', 'Login' ), CC_ONLY_NOT_LOGGED_IN, ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('openid'), 'cc_openid_handler', CC_DONT_CARE_LOGGED_IN, dirname(__FILE__) . '/cc-openid-handler.inc' ); 
        CCEvents::MapUrl( ccp('openid','contact'), array( 'CCOpenID', 'Contact' ), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__)); 
        CCEvents::MapUrl( ccp('openid','notify'), array( 'CCOpenID', 'Notify' ), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__)); 
    }
}


?>
