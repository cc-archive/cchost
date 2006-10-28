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
require_once('ccextras/cc-topics.php');

define('CC_MAX_USER_TOPICS', 30 );
define('CC_MAX_FEED_TOPICS', 25 );
define('CC_EVENT_FORUM_POST', 'forumpost' );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCForums',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCForums',  'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_DO_SEARCH,          array( 'CCForums',  'OnDoSearch') );
CCEvents::AddHandler(CC_EVENT_TOPIC_REPLY,        array( 'CCForums',  'OnTopicReply') );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCForums',  'OnUserRow') );
CCEvents::AddHandler(CC_EVENT_TOPIC_DELETE,       array( 'CCForums' , 'OnTopicDelete') );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCForums', 'OnAdminMenu'));


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
*/
class CCForumPostForm extends CCTopicForm
{
    function CCForumPostForm()
    {
        $this->CCTopicForm( _('New Topic Text'), _('Submit Topic'), true );
    }
}

/**
* Forums API
*
* A delegator class that catches events and forwards the requests
* to the code that does the real work. @see CCForumsAPI
*/
class CCForums
{
    function Index($forum_id='')
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->Index($this,$forum_id);
    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$record)
    {
        if( empty($record['artist_page']) || empty($record['user_num_posts']) )
            return;

        $name = $record['user_real_name'];
        $url   = ccl('forums', 'people', $record['user_name']);
        $link1 = "<a href=\"%s\">";
        $link2 = '</a>';
        
        $text = sprintf( _("%s has posted %s%d forum messages%s"), $name, $link1,  
                                      $record['user_num_posts'], $link2 );

        $record['user_fields'][] = array( 'label' => _('Forum posts'), 
                                          'value' => $text,
                                          'id' => 'user_post_stats' );
    }


    function OnTopicDelete($deltype,$topic_id)
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->OnTopicDelete($this,$deltype,$topic_id);
    }

    function OnTopicReply(&$reply, &$original)
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->_on_new_topic($reply);
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        if( strtolower( get_class($form) ) != 'ccsearchform' )
            return;

        require_once('ccextras/cc-forums-search.inc');
        $fsa = new CCForumsSearchAPI();
        $fsa->OnFormFields($this,$form,$fields);
    }

    /**
    * Event handler for {@link CC_EVENT_DO_SEARCH}
    * 
    * @param boolean &$done_search Set this to true if you handle the search
    */
    function OnDoSearch(&$done_search)
    {
        require_once('ccextras/cc-forums-search.inc');
        $fsa = new CCForumsSearchAPI();
        $fsa->OnDoSearch($this,$done_search);
    }

    function PostNew($forum_id)
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->PostNew($this,$forum_id);
    }

    function ViewThread($thread_id)
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->ViewThread($this,$thread_id);
    }

    function User($username)
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->User($this,$username);
    }

    function MoveThread($thread_id)
    {
        require_once('ccextras/cc-forums-admin.inc');
        $fsa = new CCForumsAdmin();
        $fsa->MoveThread($this,$thread_id);
    }

    function Admin($param1='',$param2='',$param3='')
    {
        require_once('ccextras/cc-forums-admin.inc');
        $forumadmin = new CCForumsAdmin();
        $forumadmin->Admin($param1,$param2,$param3);
    }

    function RssFeed($type='',$param='')
    {
        require_once('ccextras/cc-forums.inc');
        $fsa = new CCForumAPI();
        $fsa->RssFeed($this,$type,$param);
    }

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
                'forumadmin'   => array( 'menu_text'  => 'Forum',
                                 'menu_group' => 'configure',
                                 'help'      => _('Config forums access, groups, etc.'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 90,
                                 'action' =>  ccl('admin','forums') ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('thread'),              array( 'CCForums', 'ViewThread'),  
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{thread_id}', 
                _('View a forum thread'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums'),              array( 'CCForums', 'Index'),   
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[forum_id]', 
                _('View forums index or specific forum'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums','post'),       array( 'CCForums', 'PostNew'), 
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{forum_id}', 
                _('Post a new topic'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums','people'),     array( 'CCForums', 'User'),    
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{user_name}', 
                _('Display forum topics for user'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('feed','rss','forums'), array( 'CCForums', 'RssFeed'), 
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), 
                array(  'feed/rss/forums', 
                        'feed/rss/forums/user/{user_name}',
                        'feed/rss/forums/thread/{thread_id}' ), 
                _('Various forum feeds'), CC_AG_FEEDS );

        CCEvents::MapUrl( ccp('admin','forums'),      array( 'CCForums', 'Admin'),   
                CC_ADMIN_ONLY, ccs(__FILE__), '', 
                _('Configure forums'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('admin','forums','move'), array( 'CCForums', 'MoveThread'),   
                CC_ADMIN_ONLY, ccs(__FILE__), '{thread_id}', 
                _('Displays \'Move Thread\' form'), CC_AG_FORUMS );
    }

}

?>