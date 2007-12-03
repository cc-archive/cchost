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

require_once('ccextras/cc-reviews-table.inc');


/**
* Review API
*
*/
class CCReview
{
    function Reviews( $user_name='', $upload_id='' )
    {
        global $CC_GLOBALS;

        require_once('cclib/cc-page.php');

        // 
        // These args will be passed to the template
        //
        $args = array();

        //
        // Setup the page and initialize useful things
        //

        $users =& CCUsers::GetTable();

        if( !empty($user_name) )
        {
            $qkarg['user_name'] = $user_name;
            $user_id = $users->QueryKey($qkarg);
            if( empty($user_id) )
            {
                CCPage::Prompt(_('Cannot find that user'));
                CCUtil::Send404(false);
                return;
            }

            if( !empty($upload_id) )
            {
                $cntwhere['upload_id'] = $upload_id;
                $uploads =& CCUploads::GetTable();
                $cnt = $uploads->CountRows($cntwhere);
                if( !$cnt )
                {
                    CCPage::Prompt(_('Cannot find that upload. It may have been removed by the owner.'));
                    CCUtil::Send404(false);
                    return;
                }
            }
        }

        //
        // OK, figure out what the template should be
        //
        if( empty($upload_id) )
        {
            if( empty($user_name) )
            {
                //
                // This is just a 'recent review' listing for the whole site
                //
                CCPage::SetTitle('str_recent_reviews');
                $template = 'reviews_recent';
            }
            else
            {
                //
                // This is request for listing a users' reviews
                //

                $template = 'reviews_user';
                $user_id   = $user_row['user_id'];

                $qtype = CCUtil::StripText($_REQUEST['qtype']);
                if( !empty($qtype) && $qtype == 'leftby' )
                {
                    $dataview = 'reviews_leftby';
                }
                else
                {
                    $dataview = 'reviews_leftfor';
                }
                   
            }
        }
        else
        {
            $this->_build_bread_crumb_trail($user_name,$upload_id);
            $uploads =& CCUploads::GetTable();
            $upload_name = $uploads->QueryItemFromKey('upload_name',$upload_id);
            $query = new CCQuery();
            $args = $query->ProcessAdminArgs('nopaging=1&f=page&t=reviews_upload&datasource=topics');
            $args['title'] = array( 'str_reviews_for_s', $upload_name);
            $sqlargs['where'] = 'topic.topic_upload=' . $upload_id;
            CCPage::PageArg('topic_upload',$upload_id);
            $query->QuerySQL($args,$sqlargs);
            return;
        }

        //
        // Output the listing...
        //
        CCPage::PageArg('review_list_macro', 'topics.xml/' . $macro );
        foreach( $args as $argname => $arg )
            CCPage::PageArg($argname,$arg);
        CCPage::PageArg('topics',$topics,'review_list_macro');

        //
        // Attach feed buttons
        //
        if( $user_name )
        {
            if( $upload_id )
            {
                $url = ccl('feed','rss','reviews',$user_name,$upload_id);
                $help = '<br />' . sprintf( _('Reviews left for "%s"'), '<br /> ' . CC_strchop($R['upload_name'],16) );
            }
            else
            {
                $url = ccl('feed','rss','reviews',$user_name);
                $help = '<br />' . sprintf( _('Reviews left for "%s"'), '<br /> ' . CC_strchop($user_row['user_real_name'],16) );
            }
        }
        else
        {
            $url = ccl('feed','rss','reviews');
            $help = _('Reviews');
        }

        $CC_GLOBALS['page-has-feed-links'] = 1;

        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml', $url, "RSS 2.0");
        CCPage::AddLink( 'feed_links', 'alternate', 'application/rss+xml', $url, "RSS 2.0", "xml",$help );


    }

    function _build_bread_crumb_trail($user_name,$upload_id='',$cmd_url='',$cmd_text='')
    {
        $trail = array();
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );

        if( empty($user_name) )
        {
            $trail[] = array( 'url' => ccl('reviews'), 'text' => _('Reviews') );
        }
        else
        {
            $trail[] = array( 'url' => ccl('people'), 'text' => _('People') );

            $users   =& CCUsers::GetTable();
            $user_real_name = $users->QueryItem('user_real_name',"user_name = '$user_name'");

            if( empty($user_real_name) )
            {
                $user_name = '';
                $upload_id = '';
            }
            else
            {
                $trail[] = array( 'url' => ccl('people',$user_name), 'text' => $user_real_name );

                if( empty($upload_id) )
                {
                    $trail[] = array( 'url' => ccl('reviews',$user_name), 'text' => _('Reviews') );
                }
                else
                {
                    $uploads =& CCUploads::GetTable(); 

                    $upload_name = $uploads->QueryItemFromKey('upload_name',$upload_id);
                    $upload_name = '"' . $upload_name . '"';
                    $trail[] = array( 'url' => ccl('files',$user_name,$upload_id), 'text' => $upload_name );
                    $trail[] = array( 'url' => ccl('reviews',$user_name,$upload_id), 'text' => _('Reviews') );

                    if( !empty($cmd_text) )
                        $trail[] = array( 'url' => $cmd_url, 'text' => $cmd_text );
                }
            }
        }

        require_once('cclib/cc-page.php');
        CCPage::AddBreadCrumbs($trail);
    }

    function OnTopicRow(&$row)
    {
        if( $row['topic_type'] != 'review' )
            return;

        $upload_id = $row['topic_upload'];

        $dv = new CCDataview();
        $arg['where'] = 'upload_id = ' . $upload_id;
        $remixer = $dv->PerformFile('upload_owner',$arg,CCDV_RET_ITEM);

        $row['topic_permalink'] = ccl('reviews', $remixer, $upload_id. '#' . 
                                         $row['topic_id'] );

        $row['user_post_count'] = $row['user_num_reviews'];
        $row['user_post_text']  = _('Reviews');

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        if( empty($settings['newuserpage']) )
            $row['user_post_url']   = ccl( 'reviews', $row['user_name'] ) . '?qtype=leftby';
        else
            $row['user_post_url']   = ccl( 'people', $row['user_name'], 'reviews' ) . '?qtype=leftby';

        if( $this->_can_review($upload_id) )
        {
            $row['commands']['new_review'] = 
                                     array( 
                                        'url' => ccl('reviews','post',
                                                     $upload_id ),
                                        'script' => '',
                                        'text' => _('Write Review') );
        }

        if( !empty($row['upload_user']) )
        {
            // this will happen when viewing reviews for 
            // more than one upload

            $users =& CCUsers::GetTable();
            $where['user_id'] = $row['topic_user'];
            $row['reviewer'] = $users->QueryRow($where);
        }
    }

    function OnTopicDelete($deltype,$topic_id)
    {
        $reviews =& CCReviews::GetTable();
        $row = $reviews->QueryKeyRow($topic_id);
        $upload_id = $row['topic_upload'];
        if( empty($upload_id) ) // never mind, it wasn't a review
            return;
        $reviewer = $row['topic_user'];
        $this->Sync($upload_id,$reviewer,-1);
    }

    function _can_review($row_or_id)
    {
        return CCReviewsHV::_can_review($row_or_id);
    }

    function Sync($upload_id,$reviewer,$add=0)
    {
        $reviews =& CCReviews::GetTable();
        $where['topic_upload'] = $upload_id;
        $count = $reviews->CountRows($where) + $add;
        $uploads =& CCUploads::GetTable();
        $uploads->SetExtraField( $upload_id, 'num_reviews', $count );

        if( !$add )
            $add = 1;

        $reviewee = $uploads->QueryItemFromKey('upload_user',$upload_id);

        $users =& CCUsers::GetTable();
        $arg1['user_id'] = $reviewer;
        $count = $users->QueryItemFromKey('user_num_reviews',$reviewer) + $add;
        $arg1['user_num_reviews'] = $count;
        $users->Update($arg1);

        $arg2['user_id'] = $reviewee;
        $count = $users->QueryItemFromKey('user_num_reviewed',$reviewee) + $add;
        $arg2['user_num_reviewed'] = $count;
        $users->Update($arg2);
    }

    /**
    * Event hander for {@link CC_EVENT_DELETE_UPLOAD}
    * 
    * @param array $record Upload database record
    */
    function OnUploadDelete(&$record)
    {
        global $CC_GLOBALS;

        $reviews =& CCReviews::GetTable();
        $upload_id = $record['upload_id'];
        $w['topic_upload'] = $upload_id;
        $rows = $reviews->QueryRows($w,'topic_id,topic_user');
        
        $topic_api = new CCTopic();

        // q: should we be throwing an topic_delete event??
        foreach( $rows as $row)
        {
            $topic_id = $row['topic_id'];
            $reviewer = $row['topic_user'];
            $this->Sync($upload_id,$reviewer,-1);
            $topic_api->_delete_tree($topic_id);
        }
    }

    function RssFeed($username='',$upload_id='')
    {
        global $CC_GLOBALS;

        // yes, yes, this should be an admin option

        $CC_GLOBALS['topics_license_url'] = 'http://creativecommons.org/licenses/by/2.5';

        $items =& $this->_get_feed_items($username,$upload_id,$title);
        $feed = new CCTopicsFeed();
        $feed->Feed($items,$title,'rss',ccl('reviews'));
    }

    function & _get_feed_items($username,$upload_id,&$title)
    {
        $reviews = new CCReviews(); // don't get global because we're recking it
        $reviews->AddExtraColumn('1 as topic_is_feed');
        $reviews->SetOrder('topic_date','DESC');
        $items = array();
        $w = array();
        if( !empty($username) )
        {
            $users =& CCUsers::GetTable();
            $uw['user_name'] = $username;
            $urow = $users->QueryRow($uw,'user_id,user_real_name');
            if( empty($urow) )
                exit;
            $user_id = $urow['user_id'];
            $user_real_name = $urow['user_real_name'];
            if( !empty($upload_id) )
            {
                $uploads =& CCUploads::GetTable();
                $upload_name = $uploads->QueryItemFromKey('upload_name',$upload_id);
                if( !empty($upload_name) )
                {
                    $w['topic_upload'] = $upload_id;
                    $title = sprintf( _('Reviews left for %s by %s'), $upload_name, $user_real_name );
                }
            }
            else
            {
                $uploads = new CCUploads();
                $uploads->AddJoin( new CCReviews(true), 'upload_id');
                $uploads->SetSort('topic_date','DESC');
                $uploads->SetOffsetAndLimit(0,CC_MAX_TOPIC_FEED_ITEMS);
                $uploads->SetDefaultFilter(true,true);
                $where['topic_type'] = 'review';
                $where['upload_user'] = $user_id;
                $krows = $uploads->QueryRows($where,'topic_id');
                $keys = array();
                foreach($krows as $krow)
                    $keys[] = $krow['topic_id'];
                $items =& $reviews->GetRecordsFromKeys($keys);
                $title = sprintf( _('Reviews left for %s'), $user_real_name );
            }
        }
        else
        {
            $title = _('Reviews');
        }

        if( empty($items) )
        {
            $reviews->SetOffsetAndLimit(0,CC_MAX_TOPIC_FEED_ITEMS);
            $items =& $reviews->GetRecords($w);
        }

        return $items;
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
            $fields['reviews_enabled'] =
               array(  'label'      => _('Enable Reviews'),
                       'form_tip'   => _('Allow users to review uploads'),
                       'value'      => '1',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );

            /*
            $fields['reviews_access'] =
                  array(
                    'label'      => 'Who Can Write Reviews',
                    'value'      => CC_MUST_BE_LOGGED_IN,
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only"
                                        ),
                    'flags'      => CCFF_NONE );
            */

        }
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        if( strtolower( get_class($form) ) == 'ccsearchform' )
        {
            /*
            *  Add Reviews to search
            */
            $options = $fields['search_in']['options'];
            $sorted = $options;
            ksort($sorted);
            $nextbit = 1;
            foreach( $sorted as $key => $value )
            {
                if( $key & $nextbit )
                {
                    $nextbit <<= 1;
                }
            }
            $options[$nextbit] = _('Reviews');
            $fields['search_in']['options'] = $options;
            $form->SetHiddenField('reviews_search',$nextbit);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_DO_SEARCH}
    * 
    * @param boolean &$done_search Set this to true if you handle the search
    */
    function OnDoSearch(&$done_search)
    {
        if( $done_search )
            return;

        if( empty($_POST['reviews_search']) )
            return;

        $reviews_field = CCUtil::StripText($_POST['reviews_search']);
        if( !intval($reviews_field) )
            return;

        if( ($_POST['search_in'] != $reviews_field) || (empty($_POST['search_text'])))
            return;

        $type   = CCUtil::StripText($_REQUEST['search_type']);

        $query = trim($_POST['search_text']);
        if( empty($query) )
            return;
        $query = addslashes($query);
        $qlower = strtolower($query);
        if( $type == 'phrase' )
            $terms = array( $qlower );
        else
            $terms = preg_split('/\s+/',$qlower);

        $fields = array( 'topic_name', 'topic_text' );
        $filter = CCSearch::BuildFilter($fields, $qlower, $type);

        $uploads = new CCUploads();
        $uploads->AddJoin( new CCReviews(true), 'upload_id');
        $uploads->SetSort('topic_date','DESC');
        $uploads->SetOffsetAndLimit(0,NUM_REVIEWS_PER_PAGE);
        $filter .= 'AND (topic_id > 0 AND topic_type = \'review\')';
        $up_results =& $uploads->GetRecords($filter);
        $count = count($up_results);

        if( empty($count) )
        {
            CCPage::Prompt(_('Sorry, no reviews match.'));
        }
        else
        {
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $up_results[$i][$field] . ' ';
                $up_results[$i]['topic_search_result_info'] = CCSearch::_highlight_results($extra,$terms);
            }

            CCPage::PageArg( 'hot_topics', false );
            CCPage::PageArg( 'topic_results_macro', 'topics.xml/recent_reviews');
            CCPage::PageArg( 'topics', $up_results, 'topic_results_macro' );
        }

        $done_search = true;
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('reviews'),        array( 'CCReview', 'Reviews'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[user_name]/[upload_id]', 
            _('See reviews by a person or for a specific upload'), CC_AG_REVIEWS );

        CCEvents::MapUrl( ccp('reviews','post'), array( 'CCReviewFormAPI', 'PostReview'),  
            CC_MUST_BE_LOGGED_IN, 'ccextras/cc-review-forms.inc', '[upload_id]', 
            _('Display a review form'), CC_AG_REVIEWS );

        CCEvents::MapUrl( ccp('feed','rss','reviews'), array( 'CCReview', 'RssFeed'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[user_name]/[upload_id]', 
            _('Reviews by a person or for a specific upload'), CC_AG_FEEDS );

        // ajax callbacks...

        CCEvents::MapUrl( ccp('reviews','thread'), array( 'CCReview', 'SeeThread'),    
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );

        CCEvents::MapUrl( ccp('reviews','hottopics'), array( 'CCReview', 'HotTopics'),    
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
    }

}

?>
