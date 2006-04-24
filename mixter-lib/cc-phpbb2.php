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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCPhpBB2',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPhpBB2',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPhpBB2' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_USER_REGISTERED,    array( 'CCPhpBB2' , 'OnUserRegistered') );
CCEvents::AddHandler(CC_EVENT_LOGIN_FORM,         array( 'CCPhpBB2' , 'OnLoginForm') );
CCEvents::AddHandler(CC_EVENT_LOGOUT,             array( 'CCPhpBB2' , 'OnLogout') );
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_CHANGED,      array( 'CCPhpBB2',  'OnUserProfileChanged'));

/**
* Class for interfacing with phpBB2
*
*/
class CCPhpBB2
{
    function _call_phpbb($function)
    {
        global $CC_GLOBALS, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS;
        global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config;
        $args = func_get_args();
        array_shift($args);
        require_once('mixter-lib/cc-phpbb2-cb.inc');
        $function = 'cc_phpbb_' . $function;
        if( $CC_GLOBALS['phpbb2_closedb'] )
            CCDatabase::DBClose();
        CCDebug::QuietErrors();
        $result = call_user_func_array( $function, $args );
        CCDebug::RestoreErrors();
        return($result);
    }


    /**
    * Handle CC_EVENT_LOGIN_FORM event
    *
    * Populates the form with our handler and magic hidden fields
    * Submit is handled in the UserLogin() method.
    *
    * @see UserLogin
    * @param object $form Instance of CCForm about to be displayed
    */
    function OnLoginForm( &$form )
    {
        global $CC_GLOBALS, $CC_CFG_ROOT;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;

        // this handler will login the user to both, ccHost and phpBB
        $form->SetHandler( ccl( 'user', 'login', 'bb') );
        
    }

    /**
    * Form submit handler for user log in form
    * 
    * We override the dafault submit button in the 
    * login form to redirect the submi here where
    * we login to ccHost, then phpBB2.
    *
    * @see OnLoginForm
    */
    function UserLogin()
    {
        if( CCLogin::Login(false) )
        {
            $username = CCUser::CurrentUserName();
            $this->_call_phpbb('login_user', $username );
            // what if that failed ?
            CCUtil::SendBrowserTo( ccl( 'people', $username ) );
        }
    }

    /**
    * Catch the CC_EVENT_LOGOUT event here to log us out of phpBB2
    *
    * User has already been logged out of ccHost
    *
    * This method redirects, probably a design hole that it 
    * needs to but it does.
    *
    */
    function OnLogout()
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;

        $this->_call_phpbb('logout');
        CCUtil::SendBrowserTo();
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;

        $items = array( 
            'forum'   => array( 'menu_text'  => cct('Forums'),
                             'menu_group' => 'visitor',
                             'weight' => 8,
                             'action' =>  ccd( $CC_GLOBALS['phpbb2_root_path'] ),
                             'access' => CC_DONT_CARE_LOGGED_IN
                             ),
            );

        CCMenu::AddItems($items);
    }


    /**
    * Handles CC_EVENT_USER_REGISTERED event, called after a new user has registered
    *
    * This method only partially works, it's a WIP (work in progress).
    *
    * Passed the registration information along to phpBB
    */
    function OnUserRegistered( $fields, &$status )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            return;
        }

        $username = $fields['user_name'];
        $password = $fields['user_password'];
        $email    = $fields['user_email'];
        $user_id  = 0; // $fields['user_id'];

        $status = $this->_call_phpbb('register_new_user',$username, $user_id, $password, $email  );

    }

    /**
    * Depricated use skin/getheader instead
    */
    function GetHeader($simple='')
    {
        global $CC_GLOBALS;
        if( file_exists( $CC_GLOBALS['phpbb2_root_path'] . '/rss.php') ) 
        {
            $title = 'XML';
            $link_text = 'XML';
            $feed_url = ccd('forum','rss.php');
            CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml', $feed_url, $title );
            CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', $feed_url, $title,     
                                                                        $link_text, cct('Forum') );
        }

        // bit of hack this:
        $CC_GLOBALS['page-has-feed-links'] = 1;

        if( !empty($simple) )
            $simple = '/' . $simple;

        $url = '/skin/getheader' . $simple;
        $action = CCEvents::ResolveUrl( $url, true );
        CCEvents::PerformAction($action);
    }

    /**
    * Depricated use skin/getfooter instead
    */
    function GetFooter($simple='')
    {
        if( !empty($simple) )
            $simple = '/' . $simple;

        $url = '/skin/getfooter' . $simple;
        $action = CCEvents::ResolveUrl( $url, true );
        CCEvents::PerformAction($action);
    }

    function OnUserProfileChanged($user_id,&$old_record)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            return;
        }

        $users =& CCUsers::GetTable();
        $where['user_id'] = $user_id;
        $record = $users->GetRecordFromID($user_id);

        $fields = array( 'user_email', 'user_image', 'user_password' );
        $need_update = false;
        foreach( $fields as $field )
        {
            if( empty($record[$field]) || empty($old_record[$field]) )
                continue;

            if( $record[$field] != $old_record[$field] )
            {
                $need_update = true;
                break;
            }
        }

        if( $need_update )
        {
            $this->_call_phpbb('update_user', $user_id, $record );
        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('user','login','bb'),   array( 'CCPhpBB2', 'UserLogin'),    
                                                               CC_DONT_CARE_LOGGED_IN);

        /* Depricated: use skin/getheader and skin/getfooter instead */

        CCEvents::MapUrl( ccp('forum','getheader'),   array( 'CCPhpBB2', 'GetHeader'),    
                                                             CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','getfooter'),   array( 'CCPhpBB2', 'GetFooter'),    
                                                             CC_DONT_CARE_LOGGED_IN);
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
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
            $fields['phpbb2_enabled'] =
               array(  'label'      => 'phpBB2 Integration Enabled',
                       'form_tip'   => 'Enabled phpbb2',
                       'value'      => '1',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
            $fields['phpbb2_root_path'] =
               array(  'label'      => 'phpBB2 Directory',
                       'form_tip'   => 'Relative path to phpbb2 root directory (e.g. "phpBB2/")',
                       'value'      => 'phpBB2/',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

}

?>