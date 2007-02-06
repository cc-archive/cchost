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

define('NUM_REVIEWS_PER_PAGE', 20);
define('CC_EVENT_REVIEW','review');

require_once('ccextras/cc-topics.php'); // for EVENT_TOPIC_*



/**
*/
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCReviewsHV',  'OnBuildUploadMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCReviewsHV',  'OnUploadMenu')       );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCReviewsHV',  'OnUploadRow')     );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCReviewsHV',  'OnUserRow')      );
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCReviewsHV',  'OnUserProfileTabs')      );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCReview',  'OnMapUrls')         , 'ccextras/cc-reviews.inc' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCReview' , 'OnGetConfigFields') , 'ccextras/cc-reviews.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCReview',  'OnUploadDelete')    , 'ccextras/cc-reviews.inc' );
CCEvents::AddHandler(CC_EVENT_TOPIC_ROW,          array( 'CCReview' , 'OnTopicRow')        , 'ccextras/cc-reviews.inc' );
CCEvents::AddHandler(CC_EVENT_TOPIC_DELETE,       array( 'CCReview' , 'OnTopicDelete')     , 'ccextras/cc-reviews.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCReviewFormAPI',  'OnFormFields')      , 'ccextras/cc-review-forms.inc' );
CCEvents::AddHandler(CC_EVENT_DO_SEARCH,          array( 'CCReviewFormAPI',  'OnDoSearch')        , 'ccextras/cc-review-forms.inc' );

class CCReviewsHV
{
    /**
    * Event handler for {@link CC_EVENT_BUILD_UPLOAD_MENU}
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu()
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['comments'] = 
                 array(  'menu_text'  => _('Write Review'),
                         'weight'     => 95,
                         'group_name' => 'comment',
                         'id'         => 'commentcommand',
                         'access'     => CC_MUST_BE_LOGGED_IN );
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
        if( empty($record['artist_page']) )
            return;

        $name = $record['user_real_name'];
        $url   = ccl('reviews',$record['user_name']);

        if( empty($record['user_num_reviews']) )
        {
            if( empty($record['user_num_reviewed']) )
                return;

            $text = sprintf( _('%s has not left any reviews'), $name) . ' ';
        }
        else
        {
            $count = $record['user_num_reviews'];
            $byurl = url_args($url,'qtype=leftby');
            $link  = "<a href=\"$byurl\">";
	        $link_close = "</a>";
            
            $fmt = ngettext('%s has left %s%d review.%s',
                            '%s has left %s%d reviews.%s', $count ) . ' ';
            
            $text  = sprintf($fmt, $name, $link, $count, $link_close );
        }

        if( empty($record['user_num_reviewed']) )
        {
            $text .= _('and has not been reviewed');
        }
        else
        {
            $count = $record['user_num_reviewed'];
            $link  = "<a href=\"$url\">";
	        $link_close = "</a>";

            $fmt = ngettext('and has been reviewed %s%d time%s.',
                            'and has been reviewed %s%d times%s.', $count);

            $text  .= sprintf($fmt, $link, $count, $link_close);
        }

        $record['user_fields'][] = array( 'label'   => _('Reviews'), 
                                          'value'   => $text,
                                          'id'      => 'user_review_stats' );

    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['reviews_enabled']) || 
            !empty($record['upload_banned']) ||
            empty($record['upload_extra']['num_reviews']))
        {
            $record['reviews_link'] = false;
            return;
        }

        if( !empty($record['topic_id']) )
        {
            // reviewes were joined in which means
            // the table definition is already in memory (right?)

            $reviews =& CCReviews::GetTable();
            $reviews->GetRecordFromRow($record); // this will invoke CC_EVENT_TOPIC_ROW (right?)
            //CCEvents::Invoke(CC_EVENT_TOPIC_ROW,array(&$record));
        }

        if( !empty($record['works_page']) )
        {
            $record['file_macros'][] = 'comment_thread';
            $url = ccl( 'reviews', 'thread', $record['upload_id'] );
            $record['comment_thread_url'] = $url;
        }

        $count = $record['upload_extra']['num_reviews'];

        if( $count )
        {
            $url = ccl('reviews', $record['user_name'], $record['upload_id']);
            $record['reviews_link'] = array( 'url' => $url,
                                        'text' => sprintf(_("Reviews (%s)"),$count) );
        }
    }

    function OnUserProfileTabs( &$tabs )
    {
        $tabs['reviews'] = array(
                    'text' => 'Reviews',
                    'help' => 'Reviews',
                    'tags' => 'reviews',
                    'access' => 4,
                    'function' => 'url',
                    'user_cb' => array( 'CCReview', 'Reviews' ),
                    'user_cb_mod' => 'ccextras/cc-reviews.inc',
            );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu()
    */
    function OnUploadMenu(&$menu,&$record)
    {
        global $CC_GLOBALS;

        if( !empty($record['upload_banned']) || 
            empty($CC_GLOBALS['reviews_enabled']) || 
            !$this->_can_review($record) )
        {
            $menu['comments']['access'] = CC_DISABLED_MENU_ITEM;
        }
        else
        {
            $menu['comments']['action'] = ccl('reviews','post', $record['upload_id']  )  . '#edit';
        }
    }


    function _can_review($row_or_id)
    {
        if( CCUser::IsLoggedIn() )
        {
            if( is_array($row_or_id) )
            {
                $user_id   = $row_or_id['upload_user'];
                $upload_id = $row_or_id['upload_id'];
            }
            else
            {
                $uploads   =& CCUploads::GetTable();
                $user_id   = $uploads->QueryItemFromKey('upload_user',$row_or_id);
                $upload_id = $row_or_id;
            }

            $current_user = CCUser::CurrentUser();
            if( $user_id != $current_user )
            {
                require_once('ccextras/cc-reviews.inc');
                $reviews =& CCReviews::GetTable();
                $where['topic_upload'] = $upload_id;
                $where['topic_user'] = $current_user;
                $count = $reviews->CountRows($where);
                return !$count;
            }
        }

        return false;
    }
}


?>
