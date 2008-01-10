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

        $uid = CCUser::IDFromName($username);
        if( $username && empty($uid) )
        {
            CCUtil::Send404(false);
            CCPage::SetTitle(_('People'));
            CCPage::Prompt( $username . '? ' . _('Sorry, we don\'t know who that is...'));
            return;
        }

        if( empty($username) )
        {
            $this->BrowseUsers();
            return;
        }

        $originalTab = $tab;
        $tabs = $this->_get_tabs($username,$tab);
        $tagfilter = '';
        require_once('cclib/cc-page.php');
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

        $this->_show_feed_links($username, $cb_tabs['tags'] != 'uploads');

    }

    function Profile($username)
    {
        require_once('cclib/cc-page.php');
        $user_real_name = CCDatabase::QueryItem("SELECT user_real_name FROM cc_tbl_user WHERE user_name ='{$username}'");
        if( !$user_real_name )
        {
            CCPage::Prompt(_("The system does not know that user."));
            CCUtil::Send404(false);
        }
        else
        {
            CCPage::SetTitle($user_real_name);
            require_once('cclib/cc-query.php');
            $query = new CCQuery();
            $args = $query->ProcessAdminArgs('t=user_profile&datasource=user');
            $sqlargs['where'] = "user_name='{$username}'";
            $query->QuerySQL($args,$sqlargs);
        } 
    }

    function Uploads($username,$tagfilter='')
    {
        //CCDebug::StackTrace();
        CCPage::PageArg('user_tags_user',$username,'user_tags');
        CCPage::PageArg('user_tags_tag',$tagfilter);
        $where['user_name'] = $username;
        $users =& CCUsers::GetTable();
        $q = 'title=' . $users->QueryItem('user_real_name',$where);
        if( !empty($tagfilter) )
            $q .= ' (' . $tagfilter .')&tags=' . $tagfilter;
        $q .= '&user=' . $username . '&t=list_files&f=page';
        require_once('cclib/cc-query.php');
        $query = new CCQuery();
        $query->ProcessAdminArgs($q);
        $query->Query();
    }

    function Hidden($username)
    {
        $q = 'title=' . _('Non-public Files');
        require_once('cclib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('title=str_hidden_list&t=unpub&f=page&unpub=1&mod=1&user='.$username);
        $query->Query($args);
    }

    function _show_feed_links($username,$uploads=true)
    {
        // hack ?... use the page to get string defs
        require_once('cclib/cc-page.php');
        $page = CCPage::GetPage();

        $img = '<img src="' . ccd('ccskins','shared','images','feed-icon16x16.png') . '" title="[ RSS 2.0 ]" /> ';
        $user_real_name = CCDatabase::QueryItem('SELECT user_real_name FROM cc_tbl_user WHERE user_name=\''.$username .'\'');
        $title = $page->String(array('str_remixes_of_s',$user_real_name));
        $url = url_args( ccl('api','query'), 'f=rss&t=rss_20&remixesof=' .$username .'&title=' . urlencode($title));
        CCPage::AddLink('feed_links', 'alternate', 'application/rss+xml', $url, $title, $img . $title, 'feed_remixes_of' );

        if( $uploads )
        {
            $url = url_args( ccl('api','query'), 'f=rss&t=rss_20&user=' . $username . '&title=' . urlencode($user_real_name) );
            CCPage::AddLink('feed_links', 'alternate', 'application/rss+xml', $url, $user_real_name, $img . $username, 'feed_rss' );
        }
    }

    function _get_tabs($user,&$default_tab_name)
    {
        global $CC_GLOBALS;

        $record = CCDatabase::QueryRow(
             "SELECT user_id, user_name, user_real_name, user_num_uploads, user_num_reviews, user_num_reviewed,user_num_scores,user_num_posts "
             . "FROM cc_tbl_user WHERE user_name = '{$user}'");

        $tabs = array();

        if( $record['user_num_uploads'] )
        {
            $tabs['uploads'] = array (
                    'text' => 'Uploads',
                    'help' => 'Uploads',
                    'tags' => "uploads",
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( $this, 'Uploads' ),
                    );
        }

        $tabs['profile'] = array (
                    'text' => 'Profile',
                    'help' => 'Profile',
                    'tags' => "profile",
                    'limit' => '',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( $this, 'Profile' ),
            );
    
        CCEvents::Invoke( CC_EVENT_USER_PROFILE_TABS, array( &$tabs, &$record ) );

        $isadmin = CCUser::IsAdmin();

        if( $isadmin )
        {
            $tabs['admin'] = array (
                        'text' => 'Admin',
                        'help' => 'Admin',
                        'tags' => "admin",
                        'limit' => '',
                        'access' => 4,
                        'function' => 'url',
                        'user_cb' => array( 'CCUserAdmin', 'Admin' ),
                        'user_cb_mod' => 'cclib/cc-user-admin.php',
                     );
        }

        if( $record['user_num_uploads'] )
        {
            $itsme = CCUser::CurrentUserName() == $user;
            if( $itsme || $isadmin )
            {
                $userid = CCUser::IDFromName($user);
                $sql = 'SELECT COUNT(*) FROM cc_tbl_uploads WHERE (upload_published=0 OR upload_banned=1) AND upload_user='.$userid;
                $hidden = CCDatabase::QueryItem($sql);
                if( $hidden )
                {
                    $tabs['hidden'] = array (
                                'text' => 'Hidden',
                                'help' => 'non-public files',
                                'tags' => "hidden",
                                'limit' => '',
                                'access' => 4,
                                'function' => 'url',
                                'user_cb' => array( $this, 'Hidden' ),
                        );
                }
            }
        }

        $keys = array_keys($tabs);
        for( $i = 0; $i < count($keys); $i++ )
        {
            $K = $keys[$i];
            $tabs[$K]['tags'] = str_replace('%user_name%',$user,$tabs[$K]['tags']);
        }

        if( empty($default_tab_name) && !empty($CC_GLOBALS['user_extra']['prefs']['default_user_tab']) )
            $default_tab_name = $CC_GLOBALS['user_extra']['prefs']['default_user_tab'];

        require_once('cclib/cc-navigator.php');
        $navapi = new CCNavigator();
        $url = ccl('people',$user);
        $navapi->_setup_page($default_tab_name, $tabs, $url, true, $default_tab, $tab_info );
        return $tab_info;
    }

    function BrowseUsers()
    {
        $alpha           = '';
        $where           = 'user_num_uploads > 0';
        $qargs           = 't=user_list&datasource=user';
        $sqlargs['where'] = 'user_num_uploads > 0';

        if( !isset($_GET['p']) )
        {
            $qargs .= '&sort=user_registered&ord=DESC';
        }
        else
        {
            $alpha = CCUtil::StripText($_GET['p']);
            $sqlargs['where'] .= " AND (user_name LIKE '{$alpha}%')";
            $qargs .= '&sort=user_name&ord=ASC';
        }

        $sql =<<<END
                SELECT DISTINCT LOWER(SUBSTRING(user_name,1,1)) c
                   FROM `cc_tbl_user` 
                   WHERE user_num_uploads > 0
                ORDER BY c
END;

        $burl = ccl('people');
        $chars = CCDatabase::QueryItems($sql);
        $len = count($chars);
        $alinks = array();
        for( $i = 0; $i < $len; $i++ )
        {
            $c = $chars[$i];
            if( $c == $alpha )
            {
                $alinks[] = array( 
                                'url' => '', 
                                'text' => "<b>$c</b>" );
            }
            else
            {
                $alinks[] = array( 
                                'url' => $burl . '?p=' . $c, 
                                'text' => $c );
            }
        }

        require_once('cclib/cc-page.php');
        require_once('cclib/cc-query.php');

        CCPage::PageArg('user_index',$alinks);
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs($qargs);
        $query->QuerySQL($args,$sqlargs);
    }

    function OnFilterUserProfile(&$records)
    {
        require_once('cclib/cc-tags.php');

        $row =& $records[0];

        $row['user_homepage_html'] = '';
        if( !empty($row['user_homepage']) )
        {
            $row['user_homepage_html'] = "<a href=\"{$row['user_homepage']}\">{$row['user_homepage']}</a>";
        }

        $user_fields = array( 'str_user_about_me'  => 'user_description_html',
                              'str_user_home_page' => 'user_homepage_html',
                              );

        $row['user_fields'] = array();
        foreach( $user_fields as $name => $uf  )
        {
            if( empty($row[$uf]) )
                continue;
            $row['user_fields'][] = array( 'label' => $name, 'value' => $row[$uf], 'id' => $uf );
        }

        if( CCUser::IsLoggedIn() && ($row['user_id'] != CCUser::CurrentUser()) )
        {
            $current_favs = strtolower(CCUser::CurrentUserField('user_favorites'));
            $favs = CCTag::TagSplit($current_favs);
            
            $favurl = ccl('people','addtofavs',$row['user_name']);
            $link = "<a href=\"{$favurl}\">{$row['user_real_name']}</a>";

            if( in_array( strtolower($row['user_name']), $favs ) )
                $msg = array('str_favorites_remove_s',$link);
            else
                $msg = array('str_favorites_add_s',$link);

            $row['user_fields'][] = array( 'label' => 'str_favorites',
                                           'value' => $msg,
                                           'id'    => 'fav' );
        }

        $row['user_tag_links'] = array();

        $favs = CCTag::TagSplit($row['user_favorites']);
        if( !empty($favs) )
        {
            $links = array();
            foreach( $favs as $fav )
                $links[] = "(user_name = '$fav')";
            $where = join(' OR ' ,$links);
            $baseurl = ccl('people') . '/';
            $sql =<<<END
                SELECT REPLACE(user_real_name,' ','&middot;') as tag, 
                       CONCAT('$baseurl',user_name) as tagurl
                FROM cc_tbl_user
                WHERE $where
END;
            $links = CCDatabase::QueryRows($sql);
            $row['user_tag_links']['links0'] = array( 'label' => _('Favorite people'),
                                              'value' => $links );
        }

        CCTag::ExpandOnRow($row,'user_whatilike',ccl('search/people', 'whatilike'), 'user_tag_links',
                                'str_prof_what_i_like');
        CCTag::ExpandOnRow($row,'user_whatido',  ccl('search/people', 'whatido'),'user_tag_links', 
                                'str_prof_what_i_pound_on');
        CCTag::ExpandOnRow($row,'user_lookinfor',ccl('search/people', 'lookinfor'),'user_tag_links',
                                'str_prof_what_im_looking_for', true);
//CCDebug::PrintVar($row);
    }


}

?>