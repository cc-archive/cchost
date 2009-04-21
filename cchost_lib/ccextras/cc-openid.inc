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

    function OnFormFields( &$form, &$fields )
    {
        if( (strtolower( get_class($form) ) == 'ccnewuserform') ||
            (strtolower( get_class($form) ) == 'ccuserloginform') )
        {
            $url = ccl('login','openid');
            $html =<<<EOF
<p><a href="{$url}" id="cc_openid_enabled">Log in using OpenID</a>
EOF;
            $form->SetHelpText($html);
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
                $this->_do_login($form->_match_user_id,ccl('people',$user));
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
                $this->_do_login($fields['user_id'],ccl('people','profile'));
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
        
    function _do_login($user_id,$redir)
    {
        require_once('cchost_lib/cc-login.php');
        list( $user, $pw ) = CCDatabase::QueryRow(
           'SELECT user_name,user_password FROM cc_tbl_user WHERE user_id='.$user_id,false);
        $login = new CCLogin();
        $login->_create_login_cookie(1,$user,$pw);
        $login->_do_login_redirect($redir);
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
    }
}


?>
