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

define('NUM_TOPICS_RESULTS_PER_PAGE', 30 );

/**
* Forums Search API
*
*/
class CCForumsSearchAPI
{

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields($api, &$form,&$fields)
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
            $options[$nextbit] = _('Forum Topics');
            $fields['search_in']['options'] = $options;
            $form->SetHiddenField('forums_search',$nextbit);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_DO_SEARCH}
    * 
    * @param boolean &$done_search Set this to true if you handle the search
    */
    function OnDoSearch($api, &$done_search)
    {
        if( $done_search )
            return;

        if( empty($_POST['forums_search']) )
            return;

        $forums_field = CCUtil::StripText($_POST['forums_search']);
        if( !intval($forums_field) )
            return;

        if( ($_POST['search_in'] != $forums_field) || (empty($_POST['search_text'])))
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

        $topics = new CCTopics();
        $topics->AddJoin( new CCForum(), 'topic_forum' );
        $topics->SetSort('topic_date','DESC');
        $topics->SetOffsetAndLimit(0,NUM_TOPICS_RESULTS_PER_PAGE);
        $mask = CCMenu::GetAccessMask();
        $filter .= "AND (topic_forum > 0) AND (forum_read_access & $mask)";
        //CCDebug::PrintVar($filter);
        $results =& $topics->GetRecords($filter);
        $count = count($results);

        if( empty($count) )
        {
            CCPage::Prompt(_('Sorry, no topics match.'));
        }
        else
        {
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $results[$i][$field] . ' ';
                $results[$i]['topic_search_result_info'] = CCSearch::_highlight_results($extra,$terms);
            }


            CCPage::PageArg('topics',$results,'forum_search_results');
            CCTopic::AddLinks();
        }

        $done_search = true;
    }

}

?>
