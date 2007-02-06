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

require_once('ccextras/cc-topics.inc');


/**
* Returns an array of reviewer names and links to their latest reviews
*
* The name is slightly misleading because it returns a list of reviews
* but based on user name's. So if Joe reviewed 5 things, only  his 
* very latest review would only appear in the return array.
*
* @param integer $limit Maximum number of records to return
* @return array $review_records An array of latest reviews
*/
function CC_recent_reviews_impl($limit=5)
{
    $lim = 5 * $limit; // inc the limit to cover user's multiple reviews and banned,
                       // unpublished
    $uploads = new CCUploads();
    $uploads->SetDefaultFilter(true,true);
    $reviews =& CCReviews::GetTable();
    $reviews->SetOrder('topic_date','DESC');
    $reviews->SetOffsetAndLimit(0,$lim);
    $rows = $reviews->QueryRows('');
    $reviewers = array();
    $reviews = array();
    $count = count($rows);
    $users =& CCUsers::GetTable();
    for( $i = 0; $i < $count; $i++ )
    {
        $R =& $rows[$i];
        if( in_array($R['user_name'],$reviewers) )
            continue;

        // weed out unpublished and banned recs
        $uprow = $uploads->QueryKeyRow($R['topic_upload']);
        if( !empty($uprow) )
        {
            $reviewers[] = $R['user_name'];
            $reviewee = $users->QueryItemFromKey('user_name',$uprow['upload_user']);
            $R['topic_permalink'] = ccl( 'reviews', $reviewee,
                                         $R['topic_upload'] . '#' . $R['topic_id'] );
            $reviews[] = $R;
            if( count($reviews) == $limit )
                break;
        }
    }

    return $reviews;
}

class CCReviews extends CCTopics
{
    function CCReviews($doing_join=false)
    {
        $this->CCTopics();
        $this->LimitType('review');
        if( $doing_join )
            $this->_key_field = 'topic_upload';
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $table;
        if( !isset($table) )
            $table = new CCReviews();
        return $table;
    }

    /*
    function & GetRecordFromRow(&$row)
    {
        parent::GetRecordFromRow($row);
        return $row;
    }
    */

    function & GetReviewsForUpload($record_or_id,$deep,$sort)
    {
        $upload_id =  is_array($record_or_id) ? 
                        $record_or_id['upload_id'] : 
                        $record_or_id;

        $where['topic_upload'] = $upload_id;

        $this->SetSort('topic_date',$sort);

        $records =& $this->GetRecords($where);
        if( $deep )
            $this->GetTreeFromRecords($records);
 
        return $records;
    }

}

?>
