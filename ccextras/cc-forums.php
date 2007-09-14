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

require_once('ccextras/cc-topics.php');

define('CC_MAX_USER_TOPICS', 30 );
define('CC_MAX_FEED_TOPICS', 25 );
define('CC_EVENT_FORUM_POST', 'forumpost' );


CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCForumsSearchAPI',  'OnFormFields'), 'ccextras/cc-forums-search.inc');
CCEvents::AddHandler(CC_EVENT_DO_SEARCH,          array( 'CCForumsSearchAPI',  'OnDoSearch'),   'ccextras/cc-forums-search.inc' );

CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCForums',  'OnUserRow') );
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCForums',  'OnUserProfileTabs') );

CCEvents::AddHandler(CC_EVENT_TOPIC_DELETE,       array( 'CCForumAPI' , 'OnTopicDelete'), 'ccextras/cc-forums.inc' );
CCEvents::AddHandler(CC_EVENT_TOPIC_REPLY,        array( 'CCForumAPI',  'OnTopicReply'),  'ccextras/cc-forums.inc'  );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCForumsAdmin',  'OnMapUrls'),    'ccextras/cc-forums-admin.inc');
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCForumsAdmin',  'OnAdminMenu'), 'ccextras/cc-forums-admin.inc');


/**
* Forums API
*
* A delegator class that catches events and forwards the requests
* to the code that does the real work. @see CCForumsAPI
*/
class CCForums
{
    function OnUserProfileTabs( &$tabs, &$record )
    {
        if( empty($record['user_num_posts']) )
            return;

        $tabs['topics'] = array(
                    'text' => 'Topics',
                    'help' => 'Forums Topics',
                    'tags' => 'topics',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( 'CCForumAPI', 'User' ),
                    'user_cb_mod' => 'ccextras/cc-forums.inc',
            );
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

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        if( empty($settings['newuserpage']) )
            $url = ccl('forums', 'people', $record['user_name']);
        else
            $url = ccl('people',$record['user_name'],'topics');

        $link1 = "<a href=\"$url\">";
        $link2 = '</a>';
        
        $text = sprintf( _("%s has posted %s%d forum messages%s"), 
                           $name, $link1,  
                                      $record['user_num_posts'], $link2 );

        $record['user_fields'][] = array( 'label' => _('Forum posts'), 
                                          'value' => $text,
                                          'id' => 'user_post_stats' );
    }


}

?>
