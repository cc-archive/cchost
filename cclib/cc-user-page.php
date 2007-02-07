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
*/
class CCUserPage
{
    function People( $username='', $tab='' )
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');

        if( empty($settings['newuserpage']) || empty($username) )
        {
            // legacy handling...
            require_once('cclib/cc-user.inc');
            $uapi = new CCUserAPI();
            if( empty($username) )
                $uapi->BrowseUsers();
            else
                $uapi->UserPage($username,$tab);
            return;
        }

        require_once('cclib/cc-page.php');
        $pages = $configs->GetConfig('tab_pages');
        // let admin's override the coded behavoir 
        // if they created a 'person' subtab use that...
        if( empty($pages['person']) )
        {
            // no admin tab 
            $originalTab = $tab;
            $tabs = $this->_get_tabs($username,$tab);
            $tagfilter = '';
            CCPage::PageArg('sub_nav_tabs',$tabs);
            if( empty($tabs['tabs'][$originalTab]) )
            {
                // HACK
                // for legacy reasons, we treat this like an upload tag query
                $tagfilter = $originalTab; 
            }
            $cb_tabs = $tabs['tabs'][$tab];
            if( !empty($cb_tabs['user_cb_mod']) )
                require_once($cb_tabs['user_cb_mod']);
            if( is_array($cb_tabs['user_cb']) && is_string($cb_tabs['user_cb'][0]) )
                $cb_tabs['user_cb'][0] = new $cb_tabs['user_cb'][0]();

            call_user_func_array( $cb_tabs['user_cb'], array( $username, $tagfilter ) );
        }
        else
        {
            CCPage::PageArg('sub_nav_tabs',$pages['person'] );
        }

        $this->_setup_fplay(null,$username);
    }

    function Profile($username)
    {
        $users = new CCUsers();
        $where['user_name'] = $username;
        $users->AddExtraColumn('1 as artist_page');
        $records  = $users->GetRecords($where);
        if( empty($records) )
        {
            CCPage::Prompt(_("The system does not know that user."));
            CCUtil::Send404(false);
        }
        else
        {
            CCPage::SetTitle($records[0]['user_real_name']);
            CCPage::PageArg( 'user_record', $records, 'user_listing' );
        } 
    }

    function Uploads($username,$tagfilter='')
    {
        //CCPage::PageArg('browse_user','query_browser.xml/browse_user');
        //CCPage::PageArg('user_to_browse', $username, 'browse_user');

        $where['user_name'] = $username;
        $users =& CCUsers::GetTable();
        $title = $users->QueryItem('user_real_name',$where);
        if( !empty($tagfilter) )
            $title .= ' (' . $tagfilter .')';
        CCPage::SetTitle($title);
        require_once('cclib/cc-upload.php');
        CCUpload::ListMultipleFiles($where,$tagfilter);
        $this->_show_feed_links($username);
    }

    // er, copied from user.inc
    function _show_feed_links($username)
    {
        require_once('cclib/cc-feeds.php');
        CCPage::PageArg('artist_page',$username);
        CCFeeds::AddFeedLinks($username,'',sprintf(_('Uploads by %s'), $username) );
        CCFeeds::AddFeedLinks('','remixesof=' .$username, sprintf(_('Remixes of %s'), $username) );
        CCFeeds::AddFeedLinks('','remixedby=' .$username, sprintf(_('Remixed by %s'), $username) );
    }

    function _get_tabs($user,&$default_tab_name)
    {
        $tabs = 
            array (
                'uploads' => array (
                    'text' => 'Uploads',
                    'help' => 'Uploads',
                    'tags' => "uploads",
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( $this, 'Uploads' ),
                    ),
                'profile' => array (
                    'text' => 'Profile',
                    'help' => 'Profile',
                    'tags' => "profile",
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( $this, 'Profile' ),
                    ),
            );
    
        CCEvents::Invoke( CC_EVENT_USER_PROFILE_TABS, array( &$tabs ) );
        require_once('cclib/cc-navigator.php');
        $navapi = new CCNavigator();
        $url = ccl('people',$user);
        $navapi->_setup_page($default_tab_name, $tabs, $url, true, $default_tab, $tab_info );
        return $tab_info;
    }

    function _setup_fplay($user_real_name,$username)
    {
        global $CC_GLOBALS;
        if( empty($user_real_name) )
        {
            $users =& CCUsers::GetTable();
            $w['user_name'] = $username;
            $user_real_name = $users->QueryItem('user_real_name',$w);
        }
        $CC_GLOBALS['fplay_args'][] = "user=$username&reqtags=audio&limit=100";
        $CC_GLOBALS['fplay_title']  = sprintf(_("PLAY %s"),CC_strchop($user_real_name,20));
    }

}

?>