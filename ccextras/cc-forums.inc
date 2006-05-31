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
* $Id: cc-reviews.php 3291 2006-05-11 06:27:20Z fourstones $
*
*/

/**
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('CC_MAX_USER_TOPICS', 30 );
define('CC_EVENT_FORUM_POST', 'forumpost' );

/**
*/
require_once('ccextras/cc-topics.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCForums',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCForums',  'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_DO_SEARCH,          array( 'CCForums',  'OnDoSearch') );
CCEvents::AddHandler(CC_EVENT_TOPIC_REPLY,        array( 'CCForums',  'OnTopicReply') );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCForums',  'OnUserRow') );
CCEvents::AddHandler(CC_EVENT_TOPIC_DELETE,       array( 'CCForums' , 'OnTopicDelete') );


/**
* Forums API
*
*/
class CCForumAPI
{
    function Index($api, $forum_id='')
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
                        $url = ccl('thread',$latest_post['forum_thread_id']) . '#' . $latest_post['topic_id'];
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

        CCTopic::AddLinks();
        CCPage::SetTitle('Forums');
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
                                 'text' => cct('Post new topic' ) );
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
        {$newest}.topic_date         AS newest_topic_date, 
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
        CCTopic::AddLinks();
    }

    function _add_feed_links( $thread = '', $user = '' )
    {
        if( !empty($thread) )
        {
            $url = ccl('feed','rss','forums','thread',$thread);
            $help = cct('Topic');
        }
        elseif( !empty($user) )
        {
            $url = ccl('feed','rss','forums','user',$user);
            $help = $user;
        }
        else
        {
            $url = ccl('feed','rss','forums');
            $help = cct('Forum');
        }

        $CC_GLOBALS['page-has-feed-links'] = 1;
        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml', $url, "RSS 2.0");
        CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', $url, "RSS 2.0", "xml",$help );
    }

    function _build_bread_crumb_trail($forum_id='',$topic_name='')
    {
        $trail = array();
        $trail[] = array( 'url' => ccl(), 'text' => cct('Home') );
        $trail[] = array( 'url' => ccl('forums'), 'text' => cct('Forums') );

        if( $forum_id )
        {
            $forums =& CCForum::GetTable();
            $row = $forums->QueryKeyRow($forum_id);
            $trail[] = array( 'url' => ccl('forums',$forum_id), 'text' => $row['forum_name'] );

            if( $topic_name )
            {
                $trail[] = array( 'url' => 'dummy', 'text' => $topic_name );
            }
        }

        CCPage::AddBreadCrumbs($trail);
    }

    function OnTopicDelete($api, $deltype,$topic_id)
    {
        // CCTDF_SHALLOW 
        // CCTDF_MARK
        // CCTDF_DEEP

        // we're not physically deleting
        if( $deltype == CCTDF_MARK ) 
            return;

        $topics = new CCTopics();
        $topic_threads = new CCForumThreads();
        $topics->AddJoin( $topic_threads, 'topic_thread' );
        $topic = $topics->QueryKeyRow($topic_id);
        $thread_id = $topic['forum_thread_id'];
        if( $topic['forum_thread_oldest'] == $topic_id )
        {
            // no more topics left in this thread
            $topic_threads->DeleteKey( $thread_id );
        }
        elseif( $topic['forum_thread_newest'] == $topic_id )
        {
            // the thread is not as new as it used to be

            // get the next newest topic
            $topics->SetOffsetAndLimit( 0, 2 );
            $w['topic_thread'] = $thread_id;
            $rows = $topics->QueryRows($w);
            $newest_row =& $rows[1];

            $tf['forum_thread_newest'] = $newest_row['topic_id'];
            $tf['forum_thread_date']   = $newest_row['topic_date'];
            $tf['forum_thread_id']     = $thread_id;
            $topic_threads->Update($tf);
        }

        $this->_del_sync($topic_id, $deltype == CCTDF_DEEP);
    }

    function _del_sync($topic_id,$deep)
    {
        if( $deep )
        {
            $tree =& CCTopicTree::GetTable();
            $where['topic_tree_parent'] = $topic_id;
            $rows = $tree->QueryRows($where);
            foreach( $rows as $row )
                $this->_del_sync($row['topic_tree_child'],true);
        }
        $topics =& CCTopics::GetTable();
        $user_id = $topics->QueryItemFromKey('topic_user',$topic_id);
        $users =& CCUsers::GetTable();
        $num_posts = $users->QueryItemFromKey('user_num_posts',$user_id);
        $args['user_num_posts'] = $num_posts - 1 ;
        $args['user_id'] = $user_id;
        $users->Update($args);
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

    function PostNew($api,$forum_id)
    {
        $forums =& CCForum::GetTable();
        $row = $forums->QueryKeyRow($forum_id);
        $mask = CCMenu::GetAccessMask();
        if( empty($row) || (($row['forum_post_access'] & $mask) == 0) )
        {
            $url = ccl('forums');
            CCUtil::SendBrowserTo($url);
        }

        CCPage::SetTitle( cct('Post New Topic') );

        $form = new CCForumPostForm();
        if( empty($_POST['forumpost']) || !$form->ValidateFields() )
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
            $topics->Insert($values);

            $ftvalues = array();
            $ftvalues['forum_thread_id'] = $thread_id;
            $ftvalues['forum_thread_forum'] = $forum_id;
            $ftvalues['forum_thread_user'] = CCUser::CurrentUser();
            $ftvalues['forum_thread_oldest'] = 
            $ftvalues['forum_thread_newest'] = $topic_id;
            $ftvalues['forum_thread_date'] = $values['topic_date'];
            $threads->Insert($ftvalues);

            $this->_on_new_topic($values);

            CCEvents::Invoke( CC_EVENT_FORUM_POST, array( &$values ) );


            $url = ccl('thread',$thread_id);
            CCUtil::SendBrowserTo($url);
        }
    }

    function ViewThread($api,$thread_id)
    {
        global $CC_GLOBALS;

        $threads =& CCForumThreads::GetTable();
        $topic_id = $threads->QueryItemFromKey('forum_thread_oldest',$thread_id);

        $topics =& CCTopics::GetTable();
        $row = $topics->GetRecordFromID($topic_id);
        if( empty($row) )
        {
            $this->_build_bread_crumb_trail(1);
            CCPage::SetTitle( cct('Thread Missing') );
            CCPage::Prompt('That topic thread does not exist');
            return;
        }

        $forum_id = $row['topic_forum'];
        $forums =& CCForum::GetTable();
        $forum_row = $forums->QueryKeyRow($forum_id);
        $mask = CCMenu::GetAccessMask();
        if( ($mask & $forum_row['forum_read_access']) == 0 )
        {
            if( ($forum_row['forum_read_access'] & CC_ADMIN_ONLY) != 0 )
                $msg = cct('You must have admin rights to see this forum');
            elseif( ($forum_row['forum_read_access'] & CC_MUST_BE_LOGGED_IN) != 0 )
                $msg = cct('You must be logged in to see this forum');
            else
                $msg = cct('You do have proper permissions to see this forum');

            CCPage::SetTitle(cct('Restricted Access'));
            CCPage::Prompt($msg);
            return;
        }

        $commands = array();
        if( ($mask & $forum_row['forum_post_access']) != 0 )
        {
            $commands[] = array( 'url' => ccl('topics','reply',$topic_id) . '#edit',
                                 'text' => cct('Post topic reply' ) );

            if( CCUser::IsAdmin() )
            {
                $commands[] = array( 'url' => ccl('admin','forums', 'move', $thread_id ),
                                     'text' => cct('Move thread' ) );
            }
        }

        $records = array( &$row );
        $topics->GetTreeFromRecords($records);

        // this is a hack, but seems workable:
        // we flatten the topic's first line of children to 
        // make it look more forum-y
        $doflatten = true;

        if( $doflatten && $row['topic_children'] )
        {
            $children = $row['topic_children'];
            $row['topic_children'] = array();
            $records = array_merge($records,$children);
        }

        CCPage::PageArg('show_cmds', true);
        CCPage::PageArg('forum_cmds', $commands );
        CCPage::PageArg('topics',&$records,'forum_topic');

        $name = $row['topic_name'];
        $title = sprintf('"%s"',$name);
        CCPage::SetTitle($title);

        $this->_add_feed_links( $thread_id );
        CCTopic::AddLinks();
        $this->_build_bread_crumb_trail($row['topic_forum'],$row['topic_name']);
    }

    function User($api,$username)
    {
        $users =& CCUsers::GetTable();
        $w['user_name'] = $username;
        $user_id = $users->QueryKey($w);
        if( empty($user_id) )
            exit; // hack attempt
        $user_real_name = $users->QueryItemFromKey('user_real_name',$user_id);
        $topics = new CCTopics();
        $topics->AddJoin( new CCForum(), 'topic_forum' );
        $topics->AddJoin( new CCForumThreads(), 'topic_thread' );
        $mask = CCMenu::GetAccessMask();
        $where = "topic_thread > 0 && topic_user = $user_id AND forum_read_access & $mask";
        CCPage::AddPagingLinks( $topics, $where, CC_MAX_USER_TOPICS );
        $rows = $topics->GetRecords($where);


        $topics =& CCTopics::GetTable(); // get a clean table w/o joins
        $count = count($rows);
        for( $i = 0; $i< $count; $i++ )
        {
            if( empty($rows[$i]['topic_name']) )
            {
                $title = $topics->QueryItemFromKey('topic_name', 
                                                    $rows[$i]['forum_thread_oldest'] );
                $rows[$i]['topic_name'] = sprintf( cct('Reply to: "%s"') , $title ); 
            }
        }

        $this->_add_feed_links( '', $username);

        CCPage::PageArg('topics',$rows,'forum_user');
        CCPage::SetTitle( sprintf( cct("Forum Posts for %s"), $user_real_name ) );
        CCTopic::AddLinks();

        $trail = array();
        $trail[] = array( 'url' => ccl(),                    'text' => cct('Home') );
        $trail[] = array( 'url' => ccl('people'),            'text' => cct('People') );
        $trail[] = array( 'url' => ccl('people', $username), 'text' => $user_real_name );
        $trail[] = array( 'url' => '',                       'text' => cct('Forum posts') );
        CCPage::AddBreadCrumbs($trail);

    }


    function RssFeed($api, $type='',$param='')
    {
        global $CC_GLOBALS;

        // yes, yes, this should be an admin option

        $CC_GLOBALS['topics_license_url'] = 'http://creativecommons.org/licenses/by/2.5';

        $topics = new CCTopics();
        $topics->AddJoin( new CCForum(), 'topic_forum' );
        $topics->SetOffsetAndLimit( 0, CC_MAX_FEED_TOPICS );
        $topics->AddExtraColumn('1 as topic_is_feed');

        if( $type == 'user' )
        {
            $users =& CCUsers::GetTable();
            $wu['user_name'] = $param;
            $user_id = $users->QueryKey($wu);
            if( empty($user_id) )
                exit; // hack 
            $where = "(topic_user = $user_id) AND (topic_forum > 0)";
            $title = sprintf( cct('Feed for topics by %s'), $param );
        }
        elseif( $type == 'thread' )
        {
            $where = "(topic_thread  = $param) ";
            $threads = new CCForumThreads();
            $threads->AddJoin( new CCTopics(), 'forum_thread_oldest' );
            $topic_name = $threads->QueryItemFromKey('topic_name',$param);
            $title = sprintf( cct('Feed for topic: %s'), $topic_name );
        }
        else
        {
            $title = cct('Forums Feed');
            $where = 'topic_forum > 0';
        }

        $access = CC_DONT_CARE_LOGGED_IN;
        //$where = "($where) AND (forum_read_access & $access)";
        $records = $topics->GetRecords($where);
        $count = count( $records );
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $records[$i];

            if( ($R['forum_read_access'] & CC_DONT_CARE_LOGGED_IN) == 0 )
            {
                $R['topic_text'] = 
                $R['topic_text_plain'] = 
                $R['topic_text_html'] = cct('Contents requires login');
            }

            $R['topic_name'] = $R['forum_name'] . '::' . $R['topic_name'];
        }

        if( $type )
            $url = ccl('feed','rss','forums',$type,$param);
        else
            $url = ccl('feed','rss','forums');

        $feed = new CCTopicsFeed();
        $feed->Feed(&$records,$title,'rss',$url);

    }

}

?>