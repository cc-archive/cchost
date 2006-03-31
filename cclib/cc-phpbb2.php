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

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCPhpBB2',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPhpBB2',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCPhpBB2',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCPhpBB2',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPhpBB2',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPhpBB2' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_USER_REGISTERED,    array( 'CCPhpBB2' , 'OnUserRegistered') );
CCEvents::AddHandler(CC_EVENT_LOGIN_FORM,         array( 'CCPhpBB2' , 'OnLoginForm') );
CCEvents::AddHandler(CC_EVENT_LOGOUT,             array( 'CCPhpBB2' , 'OnLogout') );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCPhpBB2',  'OnUploadDelete'));
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_CHANGED,      array( 'CCPhpBB2',  'OnUserProfileChanged'));

define('CC_COMMENT_MENU_ITEM', 'commentcommand');

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
        require_once('cclib/cc-phpbb2-cb.php');
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
    * Handles forum/thread URL and generates short form of comments left for a file
    *
    * This method outputs Javascript document.write statements that generate 
    * comments threads for a given file. 
    * 
    * @param integer $upload_id Upload ID to generate comment thread for
    */ 
    function SeeThread($upload_id)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            CCPage::Prompt(cct("phpBB2 integration is not enabled"));
            return;
        }

        $uploads =& CCUploads::GetTable();
        $topic_id = $uploads->QueryItemFromKey('upload_topic_id',$upload_id);
        $this->_call_phpbb('show_thread',$topic_id);
        cc_exit();
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
    * Method called by phpBB2 cchost customized skin to output ccHost look/feel.
    *
    * The call to this method is made directly from the ccHost template
    * look in phpBB2/templates/cchost/overall_header.tpl and simple_header.tpl
    *
    * To see what this method returns type: http://yourserver/media/getheader
    * and http://yourserver/media/getheader/simple into your browser.
    *
    * Not really sure if this will work for all cases but it outputs a bunch
    * of Javascript document.writes that includes style tags, menus, banners, etc.
    *
    * @param string $simple phpBB template system has a 'simple' version which means no adornments
    */
    function GetHeader($simple='')
    {
        $page =& CCPage::GetPage();
        $page->AddScriptBlock('ajax_block');
        $page->ShowHeaderFooter( !$simple, false );
        $naviator_api = new CCNavigator();
        $naviator_api->ShowTabs($page);
        
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

        // cleave off everthing before the stylesheet links and after
        // the content start marker
        $html = $page->Show(false);
        $html = preg_replace('/<(!DOCTYPE|html).*(<link .*)<!-- CONTENT STARTS -->.*/si','\2',$html);
        $html .= <<<END
<style>
.cc_centercontent, td
{
	font-family: Lucida Grande, helvetica, arial, sans serif;
	font-size: 11px;
	color: black;
}
</style>
END;
        $html = addslashes($html);
        $lines = split("\n",$html);

        header("Content-type: text/plain");
        foreach($lines as $line)
        {
            $line = trim($line);
            if( $line )
                print( " document.writeln('$line');\n ");
        }

        if( !empty($_REQUEST['mode']) && ($_REQUEST['mode'] == 'reply') && !empty($_REQUEST['t']) )
        {
            $uploads =& CCUploads::GetTable();
            $where['upload_topic_id'] = CCUtil::StripText($_REQUEST['t']);
            $key = $uploads->QueryKey($where);
            if( !empty($key) )
            {
                $uploads->SetExtraField( $key, 'num_reviews', 0);
            }
        }
            
        exit;
    }

    /**
    * Method called by phpBB2 cchost customized skin to output ccHost look/feel.
    *
    * The call to this method is made directly from the ccHost template
    * look in phpBB2/templates/cchost/overall_footer.tpl and simple_footer.tpl
    *
    * To see what this method returns type: http://yourserver/media/getfooter
    * and http://yourserver/media/getfooter/simple into your browser.
    *
    * Not really sure if this will work for all cases but it outputs a bunch
    * of Javascript document.writes that includes the site's footers etc.
    *
    * @param string $simple phpBB template system has a 'simple' version which means no adornments
    */
    function GetFooter($simple='')
    {
        $page = new CCPage();
        $page->ShowHeaderFooter( false, !$simple );
        $page->AddScriptBlock('dl_popup_script',true);
        $html = preg_replace('/.*<!-- CONTENT ENDS -->/si','',$page->Show(false));
        $html = addslashes($html);
        $lines = split("\n",$html);

        header("Content-type: text/plain");
        foreach($lines as $line)
        {
            $line = trim($line);
            if( $line )
                print( " document.writeln('$line');\n ");
        }
        exit;
    }

    /**
    * Handles forum/post URL
    *
    * Called when the user clicks on 'comment' from an upload
    * listing. Transfers logic to phpBB invokes a 
    * 'reply' or 'newtopic' thread  for this upload. 
    * 
    * @see PostReview
    * @param integer $upload_id Upload ID coresponding of the topic
    */
    function PostComment($upload_id)
    {
        global $CC_GLOBALS, $CC_CFG_ROOT;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            CCPage::Prompt("phpBB2 integration is not enabled");
            return;
        }

        $uploads =& CCUploads::GetTable();
        $R = $uploads->GetRecordFromID($upload_id);

        $topic_id = $R['upload_topic_id'];
        if( $topic_id )
        {
            $url = $this->_call_phpbb('get_post_topic_url',$topic_id);
        }
        else
        {
            $url = $this->_call_phpbb('get_new_topic_url', "Review: " . $R['upload_name'], $R['upload_id']);
        }

        // flag the num reviews field
        $uploads->SetExtraField( $R, 'num_reviews', 0);

        CCUtil::SendBrowserTo( $url );
        exit;
    }

    /**
    * Handler for phpBB2 new topic form
    * 
    * This methods acts as an HTTP client to call into the
    * phpBB2 code, then looks at the resultant HTML (actually
    * the redirection URL) for the post topic, then associates
    * the topic with the upload record being reviewed.
    *
    */
    function PostReview()
    {
        require_once('cclib/magpie/extlib/Snoopy.class.inc');
        $snoopy = new Snoopy();

        global $CC_GLOBALS;

        // build the path to the posting file
        $phpEx = $this->_call_phpbb('get_ext');
        $phpbb_root_path = $CC_GLOBALS['phpbb2_root_path'];
        $post_url  = cc_get_root_url() . '/' . $phpbb_root_path . "posting.$phpEx";

        // phpbb will not log in this user without the
        // auto-login-bit set (For some reason this is not
        // an issue on test machines (e.g. localhost) where
        // the client and server on the same IP)
        //
        $cookies = $_COOKIE;
        if( !empty($cookies['phpbb2mysql_data']) )
        {
            $arr = unserialize(stripslashes($cookies['phpbb2mysql_data']));
            $arr['autologinid'] = $CC_GLOBALS['user_password'];
            $cookies['phpbb2mysql_data'] = serialize($arr);
        }
        $snoopy->cookies = $cookies;

        // We have a 'ping' in the javascript on the
        // post page, setting this flag prevents that
        // from happening
        $_POST['ccnoping'] = 1; 
        $ok = $snoopy->Submit($post_url, CCUtil::StripSlash($_POST));
        
        if( !empty($_REQUEST['preview']) )
        {
            // we're in 'preview' mode, just dump the HTML
            print($snoopy->results);
            exit;
        }

        // yep, it seems to just be sitting there...
        $url = $snoopy->lastredirectaddr;

        if( empty($url) )
        {
            // if it's not there there was probably an error
            print($snoopy->results);
            exit;
        }

        if( !empty($_REQUEST['ccup']) )
        {
            // ok, we have an upload_id, attach the post thread...
            $this->_call_phpbb('attach_thread', $_REQUEST['ccup'], $url);
        }

        // redirect back to where phpBB2 says...

        CCUtil::SendBrowserTo($url);
    }

    /**
    * Ajax ping for er, ???
    * 
    * @param int $topic_id as prescribed by phpBB2
    *
    */
    function TopicPosted($topic_or_post='',$topic_id='')
    {
        $topic_or_post = CCUtil::StripText($topic_or_post);
        $topic_id = CCUtil::StripText($topic_id);

        if( empty($topic_or_post) || empty($topic_id) )
        {
            CCDebug::Log("Illegal call to TopicPosted");
            exit;
        }

        $uploads =& CCUploads::GetTable();
        $where['upload_topic_id'] = $topic_id;
        $id = $uploads->QueryKey($where);
        if( !empty($id) )
            $uploads->SetExtraField( $id, 'num_reviews', 0 );
        exit; 
    }

    /**
    * Ajax callback for getting the upload record header given a topic id
    * 
    * @param int $topic_id as prescribed by phpBB2
    *
    */
    function ShowRecord($topic_id)
    {
        $uploads =& CCUploads::GetTable();
        $where['upload_topic_id'] = $topic_id;
        $records = $uploads->GetRecords($where);
        if( empty( $records) )
            exit; // !

        $R =& $records[0];
        $menuitems = CCUpload::GetRecordLocalMenu($R);
        unset($menuitems['comment']);
        unset($R['reviews_link']);
        $R['local_menu'] = $menuitems;
        $R['skip_remixes'] = true;

        global $CC_GLOBALS;
        $args = $CC_GLOBALS;
        $args['root-url'] = ccd();
        $args['auto_execute'] = array( 'comment_topic_head' );
        $args['file_records'] = $records;
        $template = new CCTemplate( $CC_GLOBALS['skin-map'] );
        print( $template->SetAllAndParse($args) );
        exit;
    }
    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow( &$record )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) || 
            !empty($record['upload_banned']) ||
            empty($record['upload_topic_id']))
        {
            $record['reviews_link'] = false;
            return;
        }

        if( !empty($record['works_page']) )
        {
            CCPage::AddScriptBlock('ajax_block');
            $record['file_macros'][] = 'comment_thread';
            $url = ccl( 'forum', 'thread', $record['upload_id'] );
            $record['comment_thread_url'] = $url;
        }

        $uploads =& CCUploads::GetTable();

        $topic_id    = $record['upload_topic_id'];
        $count       = $uploads->GetExtraField( $record, 'num_reviews');

        if( empty($count) )
        {
            // db is out of sync
            $count = $this->_call_phpbb('get_topic_count', $topic_id);
            $uploads->SetExtraField( $record, 'num_reviews', $count);

            if( empty($count) )
            {
                // ok, looks like the topic was deleted
                $uargs['upload_id'] = $record['upload_id'];
                $uargs['upload_topic_id'] = 0;
                $uploads->Update($uargs);
                $topic_id = 0;
            }
        }

        if( $topic_id )
        {
            $record['reviews_link'] = array( 'url' => $this->_get_topic_url($topic_id),
                                        'text' => sprintf(cct("Reviews (%s)"),$count) );
        }
    }

    function OnUploadDelete(&$record)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) || empty($record['upload_topic_id']) )
        {
            return;
        }
        
        $this->_call_phpbb('delete_thread', $record['upload_topic_id'] );
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
    * Event handler for CC_EVENT_BUILD_UPLOAD_MENU
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['comments'] = 
                 array(  'menu_text'  => cct('Write Review'),
                         'weight'     => 95,
                         'group_name' => 'comment',
                         'id'         => CC_COMMENT_MENU_ITEM,
                         'access'     => CC_MUST_BE_LOGGED_IN );
    }

    /**
    * Event handler for CC_EVENT_UPLOAD_MENU
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu
    */
    function OnUploadMenu(&$menu,&$record)
    {
        global $CC_GLOBALS;

        if( !empty($record['upload_banned']) || 
            empty($CC_GLOBALS['phpbb2_enabled']) || 
            !CCUser::IsLoggedIn() )
        {
            $menu['comments']['access'] = CC_DISABLED_MENU_ITEM;
        }
        else
        {
            $menu['comments']['action'] = ccl('forum','post', $record['upload_id']  );
        }
    }


    function _get_topic_url($topic_id)
    {
        static $baseurl;
        if( !isset($baseurl) )
        {
            $baseurl = $this->_call_phpbb('get_topic_url');
        }
        return( ccr( sprintf($baseurl,$topic_id) ) );
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('user','login','bb'),   array( 'CCPhpBB2', 'UserLogin'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','post'),        array( 'CCPhpBB2', 'PostComment'),  CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','thread'),      array( 'CCPhpBB2', 'SeeThread'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','getheader'),   array( 'CCPhpBB2', 'GetHeader'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','getfooter'),   array( 'CCPhpBB2', 'GetFooter'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','postreview'),   array( 'CCPhpBB2', 'PostReview'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','showrecord'),   array( 'CCPhpBB2', 'ShowRecord'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( ccp('forum','topicposted'),   array( 'CCPhpBB2', 'TopicPosted'),    CC_DONT_CARE_LOGGED_IN);
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
            $fields['phpbb2_forum_id'] =
               array(  'label'      => 'phpBB2 Comment Forum ID',
                       'form_tip'   => 'Forum ID number of where to put comments/reviews',
                       'value'      => '4',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

}

?>