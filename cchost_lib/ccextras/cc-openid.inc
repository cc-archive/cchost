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
* $id$
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
            $form->GetValues($values);
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
                       'formatter'  => 'select',
                       'options'    => array( 
                                        CC_OPENID_NONE => 
                                        _('Do not use OpenID'),
                                        CC_OPENID_AND_REG => 
                                        _('Use OpenID in addition to standard registration'),
                                        CC_OPENID_ONLY => 
                                        _('Use OpenID only')
                                        ),
                       'flags'      => CCFF_POPULATE  ); 
        }
    }


    // THIS METHOD IS NOT USE (yet)
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
                'openid'   => array( 'menu_text'  => _('OpenID'),
                                 'menu_group' => 'configure',
                                 'help'      => _('Configure OpenID '),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 5,
                                 'action' =>  ccl('admin','forums') ),
                );
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
            // bingo, log 'em in
        }
    }
    
	function OnIDVerified( $openid,$canonicalID,$sreg)
	{
	    /*
            [0] => http://openid.claimid.com/victorstone
            [1] => 
            [2] => Array
                (
                    [fullname] => victor stone
                    [nickname] => victorstone
                    [email] => victor.stone@gmail.com
                )
	     */
        $row = CCDatabase::QueryRow("SELECT * FROM cc_tbl_openids WHERE openid = '{$openid}'");
        if( empty($row) )
        {
            require_once('cchost_lib/cc-page.php');
            CCPage::SetTitle('str_openid_login_title');
            $args = $sreg;
            $args['openid'] = $openid;
            $args['connonical'] = $canonicalID;
            $user_name = cc_fancy_user_sql('display_name');
            $sql = "SELECT user_id, user_email, {$user_name} FROM cc_tbl_user WHERE (user_homepage = '{$openid}') ";
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
            $user_id = $row['openid_user'];
            // log them in!
        }
    }
    
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('login','openid'), array( 'CCOpenID', 'Login' ), CC_ONLY_NOT_LOGGED_IN, ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('openid'), 'cc_openid_handler', CC_DONT_CARE_LOGGED_IN, dirname(__FILE__) . '/cc-openid-handler.inc' ); 
    }
}


?>
