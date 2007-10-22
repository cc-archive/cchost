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
require_once('ccextras/cc-topics-forms.inc');


class CCReviewForm extends CCTopicForm
{
    function CCReviewForm()
    {
        $this->CCTopicForm(_('Review'),'Submit Review');
    }
}


class CCReviewFormAPI
{
    /**
    * Handles reviews/post URL
    *
    * @param integer $upload_id Upload ID to review
    */
    function PostReview($upload_id)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['reviews_enabled']) )
        {
            CCPage::Prompt(_('Review integration is not enabled.'));
            return;
        }

        $uploads =& CCUploads::GetTable();
        $R = $uploads->GetRecordFromID($upload_id);
        if( empty($R) )
        {
            CCPage::Prompt(_('Cannot find that upload.'));
            CCUtil::Send404(false);
            return;
        }

        global $CC_GLOBALS, $CC_CFG_ROOT;

        $form = new CCReviewForm();

        require_once('ccextras/cc-reviews.inc');

        if( empty($_POST['review']) || !$form->ValidateFields() )
        {
            CCReview::_build_bread_crumb_trail($R['user_name'],$upload_id,'',_('create new'));

            CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING,array(&$R));
            $R['local_menu'] = CCUpload::GetRecordLocalMenu($R);
            unset($R['local_menu']['comment']);

            CCPage::SetTitle(sprintf(_('Write a Review for "%s"'),$R['upload_name']));

            $reviews =& CCReviews::GetTable();
            $topics = $reviews->GetReviewsForUpload($upload_id,false,'DESC');

            CCPage::PageArg('record',$R);
            CCPage::PageArg('review_post_macro','topics.xml/post_review');
            CCPage::PageArg('topics',$topics,'review_post_macro');
            // ug sorry about this
            if( function_exists('cc_playlist_enabled') && cc_playlist_enabled() )
            {
                CCPage::AddMacro('playerembed.xml/eplayer');
                CCPage::AddMacro('playlist.xml/playlist_menu');
            }
            
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            if( !CCReviewsHV::_can_review($R) )
            {
                // submit was hit twice
                CCPage::Prompt(_("You have already reviewed this upload."));
            }
            else
            {
                $form->GetFormValues($values);
                $reviews =& CCReviews::GetTable();
                $values['topic_id'] = $reviews->NextID();
                $values['topic_upload'] = $upload_id;
                $values['topic_date'] = date('Y-m-d H:i:s',time());
                $values['topic_user'] = CCUser::CurrentUser();
                $values['topic_type'] = 'review';
                $user_real = CCUser::CurrentUserField('user_real_name');
                $values['topic_name'] = sprintf(_("Review of '%s' by '%s'"), 
                                                $R['upload_name'], $user_real);
                $reviews->Insert($values);
                CCReview::Sync($upload_id,$values['topic_user']);

                // EVENT_REVIEW expects a *real* row
                // code in the handler will break if you don't
                // give them one

                $row = $reviews->QueryKeyRow($values['topic_id']);

                CCEvents::Invoke( CC_EVENT_REVIEW, array( &$row ) );

                $url = ccl('reviews',$R['user_name'],$upload_id);
                CCUtil::SendBrowserTo($url);
            }

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
            CCPage::PageArg( 'topics', $up_results, 'topics.xml/recent_reviews');
        }

        $done_search = true;
    }
}

?>
