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
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
require_once('cchost_lib/ccextras/cc-topics.inc');


/**
* Wrapper for cc_tbl_forums table
*/
class CCForum extends CCTable
{
    function CCForum()
    {
        $this->CCTable('cc_tbl_forums','forum_id');
        $this->AddJoin( new CCForumGroups(), 'forum_group' );
        $this->SetSort( 'forum_group_weight,forum_weight', 'ASC' );
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCForum();
        return $_table;
    }
}

/**
* Wrapper for cc_tbl_forums_groups
*
* String a UI artifact to group things on the make forum page
*/
class CCForumGroups extends CCTable
{
    function CCForumGroups()
    {
        $this->CCTable('cc_tbl_forum_groups','forum_group_id');
        $this->SetSort('forum_group_weight','ASC');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCForumGroups();
        return $_table;
    }
}

/**
* Wrapper for cc_tbl_forum_threads table
*
* Keeps forum topics organized by threads, remembers the
* first topic (that defines the thread), the forum, and
* the latest post in the thread (that defines the age of 
* the thread.
*/
class CCForumThreads extends CCTable
{
    function CCForumThreads()
    {
        $this->CCTable('cc_tbl_forum_threads','forum_thread_id');
        $this->SetSort('forum_thread_sticky DESC, forum_thread_date', 'DESC');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCForumThreads();
        return $_table;
    }
}

/**
* Form for posting topics to the current forum
class CCForumPostForm extends CCTopicForm
{
    function CCForumPostForm()
    {
        $this->CCTopicForm( _('New Topic Text'), _('Submit Topic'), true );
    }
}
*/

/**
* Forums API
*
*/
class CCForumAPI
{
    function Index($forum_id='')
    {
        if( !empty($forum_id) )
        {
            $this->_show_forum($forum_id);
        }
        else
        {
            $this->_show_forum_home();
        }

        $this->_build_bread_crumb_trail($forum_id);
        $this->_add_feed_links();
    }

    function _show_forum_home()
    {
        //
        // Set up forum group table
        //
        $forum_groups =& CCForumGroups::GetTable();
        $groups = $forum_groups->QueryRows('');
        $num_groups = count($groups);
        $visible_groups = array();
    
        //
        // Forums
        //
        $forums =& CCForum::GetTable();
        $mask = CCMenu::GetAccessMask();

        //
        // Threads
        //
        $threads =& CCForumThreads::GetTable();
        $this->_add_last_topic_join($threads);
        $threads->SetOffsetAndLimit(0,1);
        $threads->SetSort('forum_thread_date', 'DESC');


        //
        // and finally Topics
        //
        $topics =& CCTopics::GetTable();

        for( $i = 0; $i < $num_groups; $i++ )
        {
            $G =& $groups[$i];
            $where['forum_group'] = $G['forum_group_id'];
            $forum_rows = $forums->QueryRows($where);
            $num_forums_in_groups = count($forum_rows);
            $visible_forums = array();

            for( $x = 0; $x < $num_forums_in_groups; $x++ )
            {
                $F =& $forum_rows[$x];
                if( ($F['forum_read_access'] & $mask) != 0 )
                {
                    $forum_id = $F['forum_id'];

                    $wf['forum_thread_forum'] = $forum_id;
                    $tf['topic_forum'] = $forum_id;

                    $F['num_posts']     = $topics->CountRows($tf);
                    $F['num_threads']   = $threads->CountRows($wf);

                    $latest_post = $threads->QueryRow($wf);

                    if( !empty($latest_post) )
                    {
                        $url = ccl('thread',$latest_post['forum_thread_id']) . '#' . 
                                                $latest_post['topic_id'];
                        $latest_post['permalink'] = $url;
                        $latest_post['user_url'] = ccl('people',$latest_post['user_name']);
                    }

                    $F['latest_post']   = $latest_post;
                    $F['link']          = ccl('forums', $forum_id );
                    $visible_forums[] = $F;
                }
            }

            if( !empty($visible_forums) )
            {
                $G['forums'] = $visible_forums;
                $visible_groups[] = $G;
            }
        }

        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle('str_forums');
        CCPage::PageArg('forums',$visible_groups,'forum_index');
    }

    function _add_last_topic_join(&$threads)
    {
        $jtopic = $threads->AddJoin( new CCTopics(), 'forum_thread_newest' );
        $threads->AddJoin( new CCUsers(),  "$jtopic.topic_user" );
        return $jtopic;
    }

    function _show_forum($forum_id)
    {
        require_once('cchost_lib/cc-page.php');

        //
        // Get the forums row and check this
        // user's access rights
        //
        $forums =& CCForum::GetTable();
        $forum_row = $forums->QueryKeyRow($forum_id);
        $mask = CCMenu::GetAccessMask();
        if( ($mask & $forum_row['forum_read_access']) == 0 )
        {
            return $this->_show_forum_home();
        }

        // 
        // Set up the commands for this forum
        //
        $commands = array();
        if( ($mask & $forum_row['forum_post_access']) != 0 )
        {
            $commands[] = array( 'url' => ccl('forums','post',$forum_id),
                                 'text' => 'str_forum_post_new_topic' );
        }

        //
        // Get the relevant thread info for this forum
        //
        $threads =& CCForumThreads::GetTable();
        $newest     = $threads->AddJoin( new CCTopics(), 'forum_thread_newest' );
        $newestuser = $threads->AddJoin( new CCUsers(),  "{$newest}.topic_user" );
        $oldest     = $threads->AddJoin( new CCTopics(), 'forum_thread_oldest' );
        $oldestuser = $threads->AddJoin( new CCUsers(),  "{$oldest}.topic_user" );

        $columns =<<<EOF
        cc_tbl_forum_threads.*,
        {$oldestuser}.user_name      AS author_user_name, 
        {$oldestuser}.user_real_name AS author_real_name,   
        {$oldest}.topic_name         AS oldest_topic_name, 
        {$newestuser}.user_real_name AS newest_real_name, 
        {$newestuser}.user_name      AS newest_user_name, 
        DATE_FORMAT({$newest}.topic_date,'%a %b %e, %Y')         AS newest_topic_date, 
        {$newest}.topic_id           AS newest_topic_id
EOF;

        $wf['forum_thread_forum'] = $forum_id;
        CCPage::AddPagingLinks($threads,$wf);
        $forum_threads =& $threads->QueryRows($wf,$columns);

        $topic_counter = new CCTable('cc_tbl_topics','topic_id');

        //
        // Massage the forum data for display 
        //
        $count = count($forum_threads);
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $forum_threads[$i];
            $thread_id = $R['forum_thread_id'];
            $R['thread_url'] = ccl('thread',$thread_id);
            $w = array();
            $w['topic_thread'] = $thread_id;
            $R['num_topics'] = $topic_counter->CountRows($w) - 1;
            $R['newest_topic_url'] = ccl('thread',$thread_id) . '#' . $R['newest_topic_id'];
            $R['newest_user_url'] = ccl('people',$R['newest_user_name']);
            $R['author_url']  = ccl('people',$R['author_user_name']);
        }

        //
        // Build the page
        //
        CCPage::SetTitle($forum_row['forum_name']);
        CCPage::PageArg( 'forum_cmds', $commands );
        CCPage::PageArg( 'threads', $forum_threads , 'forum_listing' );
    }

    function _add_feed_links( $thread = '', $user = '', $user_real_name='', $thread_name ='')
    {
        require_once('cchost_lib/cc-page.php');
        $page = CCPage::GetPage();

        if( !empty($thread) )
        {
            $q = '&thread='.$thread;
            if( empty($thread_name) )
                $thread_name = CCDatabase::QueryItem('SELECT forum_thread_name FROM cc_tbl_forum_threads WHERE forum_thread_id='.$thread);
            $title = empty($thread_name) ? $page->String('str_topic') : $thread_name;
        }
        elseif( !empty($user) )
        {
            $q = '&thread=-1&user='.$user;
            $title = $user_real_name;
        }
        else
        {
            $q = '&thread=-1'; // yea, magic hack
            $title = $page->String('str_forums');
        }

        $img = '<img src="' . ccd('ccskins','shared','images','feed-icon16x16.png') . '" title="[ RSS 2.0 ]" /> ';
        $url = url_args( ccl('api','query'), 'f=rss&datasource=topics' . $q .'&title=' . urlencode($title));
        CCPage::AddLink('feed_links', 'alternate', 'application/rss+xml', $url, $title, $img . $title, 'feed_topics' );
    }

    function _build_bread_crumb_trail($forum_id='',$topic_name='')
    {
        $trail = array();
        $trail[] = array( 'url' => ccl(), 'text' => 'str_home' );
        $trail[] = array( 'url' => ccl('forums'), 'text' => 'str_forums' );

        if( $forum_id )
        {
            $forum_name = CCDatabase::QueryItem('SELECT forum_name FROM cc_tbl_forums WHERE forum_id='.$forum_id);
            $trail[] = array( 'url' => ccl('forums',$forum_id), 'text' => $forum_name );

            if( $topic_name )
            {
                $trail[] = array( 'url' => 'dummy', 'text' => $topic_name );
            }
        }
        require_once('cchost_lib/cc-page.php');
        CCPage::AddBreadCrumbs($trail);
    }

    function OnTopicDelete($deltype,$topic_id)
    {
        // CCTDF_SHALLOW 
        // CCTDF_MARK
        // CCTDF_DEEP

        // we're not physically deleting
        if( $deltype == CCTDF_MARK ) 
            return;

        $api = new CCForums();

        $sql =<<<EOF
            SELECT * FROM cc_tbl_topics
            LEFT OUTER JOIN cc_tbl_forum_threads ON topic_thread=forum_thread_id
            WHERE topic_id = $topic_id
EOF;
        $topic = CCDatabase::QueryRow($sql);
        if( empty($topic['forum_thread_id']) )
            return;

        $thread_id = $topic['forum_thread_id'];

        $topic_threads = new CCForumThreads();

        if( $topic['forum_thread_oldest'] == $topic_id )
        {
            // no more topics left in this thread
            $topic_threads->DeleteKey( $thread_id );
        }
        else
        {
            // if this topic or one of its children is the 'newest' in the thread
            // then we have to reset the thread's 'newest' pointer to some other topic
            // that isn't being deleted

            if( !($is_newest = ($topic['forum_thread_newest'] == $topic_id)) )
            {
                // first let's see if one the newest topic in the thread
                // is one of our children

                $sql = 'SELECT topic_left, topic_right FROM cc_tbl_topics WHERE topic_id='.$topic['forum_thread_newest'];
                list( $newleft, $newright ) = CCDatabase::QueryRow($sql,false);
                $is_newest = ($newleft < $topic['topic_left']) || ($newright > $topic['topic_right']);
            }

            if( $is_newest )
            {
                // The thread's newest pointer needs to be reset (its not as new as it used to be)

                // get the next newest valid topic
                $w = "(topic_thread = $thread_id) AND (topic_date < '{$topic['topic_date']}')";
                $topics->SetOrder('topic_date','desc');
                $topics->SetOffsetAndLimit( 0, 1 );
                $newest_row = $topics->QueryRow($w);

                $tf['forum_thread_newest'] = $newest_row['topic_id'];
                $tf['forum_thread_date']   = $newest_row['topic_date'];
                $tf['forum_thread_id']     = $thread_id;
                $topic_threads->Update($tf);
            }
        }

        $this->_del_sync($topic_id, $deltype == CCTDF_DEEP);
    }

    function _del_sync($topic_id,$deep)
    {
        if( $deep )
        {
            list( $left, $right ) = CCDatabase::QueryRow('SELECT topic_left, topic_right FROM cc_tbl_topics WHERE topic_id='.$topic_id,false);
            $where = "topic_left >= $left AND topic_right <= $right";
        }
        else
        {
            $where = "topic_id = $topic_id";
        }

        $sql =<<<EOF
            SELECT user_num_posts - COUNT(user_num_posts) as user_num_posts, user_id 
            FROM cc_tbl_user
            JOIN cc_tbl_topics ON user_id=topic_user
            WHERE $where
            GROUP BY user_id
EOF;
        $rows = CCDatabase::QueryRows($sql);
        $users =& CCUsers::GetTable();
        foreach( $rows as $row )
            $users->Update($row);
    }

    function OnTopicReply(&$reply, &$original)
    {
        $this->_on_new_topic($reply);
    }

    function _on_new_topic(&$topic)
    {
        if( empty($topic['topic_thread']) )
            return;

        $users =& CCUsers::GetTable();
        $num_posts = $users->QueryItemFromKey('user_num_posts',$topic['topic_user']);
        $args['user_num_posts'] = $num_posts + 1;
        $args['user_id'] = $topic['topic_user'];
        $users->Update($args);
    }

    function PostNew($forum_id)
    {
        $api = new CCForums();
        $forums =& CCForum::GetTable();
        $row = $forums->QueryKeyRow($forum_id);
        $mask = CCMenu::GetAccessMask();
        if( empty($row) || (($row['forum_post_access'] & $mask) == 0) )
        {
            $url = ccl('forums');
            CCUtil::SendBrowserTo($url);
        }

        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle( 'str_forum_post_new_topic' );

        require_once('cchost_lib/ccextras/cc-topics-forms.inc');
        $form = new CCTopicForm( 'str_forum_new_topic_text', 'str_forum_submit_topic', true );
        if( empty($_POST['topic']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $topics =& CCTopics::GetTable();
            $topic_id = $topics->NextID();
            $threads =& CCForumThreads::GetTable();
            $thread_id = $threads->NextID();

            $values['topic_forum'] = $forum_id;
            $values['topic_date'] = date('Y-m-d H:i:s',time());
            $values['topic_user'] = CCUser::CurrentUser();
            $values['topic_id'] = $topic_id;
            $values['topic_thread'] = $thread_id;
            $topics->Insert($values,0);

            $ftvalues = array();
            $ftvalues['forum_thread_id'] = $thread_id;
            $ftvalues['forum_thread_forum'] = $forum_id;
            $ftvalues['forum_thread_user'] = CCUser::CurrentUser();
            $ftvalues['forum_thread_oldest'] = 
            $ftvalues['forum_thread_newest'] = $topic_id;
            $ftvalues['forum_thread_date'] = $values['topic_date'];
            $ftvalues['forum_thread_name'] = $values['topic_name'];
            $threads->Insert($ftvalues);

            $this->_on_new_topic($values);

            CCEvents::Invoke( CC_EVENT_FORUM_POST, array( &$values ) );

            $url = ccl('thread',$thread_id);
            CCUtil::SendBrowserTo($url);
        }
    }

    function ViewThread($thread_id)
    {
        global $CC_GLOBALS;

        $api = new CCForums();

        $threads =& CCForumThreads::GetTable();
        if( empty($thread_id) || !$threads->KeyExists($thread_id) )
        {
            $this->_build_bread_crumb_trail(1);
            CCPage::SetTitle( 'str_forum_thread_missing' );
            CCPage::Prompt('str_forum_thread_not_there');
            return;
        }

        $sql = <<<EOF
        SELECT topic_name, forum_thread_forum, forum_read_access, topic_id,
               forum_post_access, forum_thread_closed, forum_thread_sticky, forum_id, forum_name
            FROM cc_tbl_topics 
            JOIN cc_tbl_forum_threads ON forum_thread_oldest = topic_id 
            JOIN cc_tbl_forums        ON forum_thread_forum = forum_id
            WHERE forum_thread_id = $thread_id
EOF;

        $row = CCDatabase::QueryRow($sql);

        $mask = CCMenu::GetAccessMask();
        if( ($mask & $row['forum_read_access']) == 0 )
        {
            if( ($row['forum_read_access'] & CC_ADMIN_ONLY) != 0 )
                $msg = 'str_forum_admin_required';
            elseif( ($row['forum_read_access'] & CC_MUST_BE_LOGGED_IN) != 0 )
                $msg = 'str_forum_login_required';
            else
                $msg = 'str_forum_perm_denied';
            CCPage::SetTitle('str_forum_restricted_access');
            CCPage::Prompt($msg);
            return;
        }

        require_once('cchost_lib/cc-page.php');

        if( ($mask & $row['forum_post_access']) != 0 )
            $this->_thread_commands($row,$thread_id);
        
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('sort=left&paging=off&f=page&t=topic_thread&datasource=topics&title=' . $row['topic_name'],array(),false);
        $sqlargs['where'] = 'topic.topic_thread=' . $thread_id;
        $query->QuerySQL($args,$sqlargs);
        $name = $row['forum_name'] . '::' . $row['topic_name'];
        $this->_add_feed_links( $thread_id, '', '', $name );
        $this->_build_bread_crumb_trail($row['forum_id'],$row['topic_name']);
    }

    function _thread_commands($row,$thread_id)
    {
        $commands = array();
        $closed = $row['forum_thread_closed'];

        if( $closed )
        {
            $commands[] = array( 'url' => 0, 'msg' => 'str_forum_thread_closed');
        }
        else
        {
            $commands[] = array( 'url' => ccl('topics','reply',$row['topic_id']) . '#edit',
                             'text' => 'str_forum_post_topic_reply', 'msg' => 0 );
        }

        if( CCUser::IsAdmin() )
        {
            $commands[] = array( 'url' => ccl('admin','forums', 'move', $thread_id ),
                                 'text' => _('Move thread' ), 'msg' => 0  );

            $ctext = $closed ? _('Open') : _('Close');

            $commands[] = array( 'url' => ccl('admin','forums', 'close', $thread_id ),
                                 'text' => $ctext, 'msg' => 0  );

            $ctext = $row['forum_thread_sticky'] ? _('Unstick') : _('Stick');

            $commands[] = array( 'url' => ccl('admin','forums', 'stick', $thread_id ),
                                 'text' => $ctext, 'msg' => 0  );
        }

        CCPage::PageArg('thread_commands',$commands);
    }

    function User($user_name)
    {
        list( $user_real_name, $user_id ) = 
            CCDatabase::QueryRow('SELECT user_real_name, user_id FROM cc_tbl_user WHERE user_name = \'' . $user_name . '\'', false);
        if( empty($user_id) )
        {
            CCUtil::Send404();
            return;
        }
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('sort=date&ord=desc&t=topics_user&datasource=topics&limit=20',array(),false);
        $args['title'] = array( 'str_forum_topics_by', $user_real_name );
        $sqlargs['where'] = "topic_user ='$user_id'";
        $query->QuerySQL($args,$sqlargs);
        $this->_add_feed_links( '', $user_name,$user_real_name);
    }
}

?>