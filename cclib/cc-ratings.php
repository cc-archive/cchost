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
* Module for handling ratings
*
* @package cchost
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Ratings table wrapper
*
*/
class CCRatings extends CCTable
{
    /**
    * Constructor
    *
    * @see GetTable
    */
    function CCRatings()
    {
        $this->CCTable('cc_tbl_ratings','ratings_id');
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
        static $_table;
        if( !isset($_table) )
            $_table = new CCRatings();
        return($_table);
    }

    /**
    * Determine if the current user is allowed to rate a given record
    *
    * @param array $record Upload record
    */
    function IsRateBlocked($record)
    {
        global $CC_GLOBALS;

        if( !CCUser::IsLoggedIn() )
        {
            return true;
        }

        $configs =& CCConfigs::GetTable();
        $chart = $configs->GetConfig('chart');
        if( !empty($chart['ratings_ban']) )
        {
            $banlist = CCTag::TagSplit($chart['ratings_ban']);
            $username = CCUser::CurrentUserName();
            if( in_array($username,$banlist) )
            {
                return true;
            }
        }

        $user_id = CCUser::CurrentUser();
        $upload_id = $record['upload_id'];

        if( !empty($chart['requires-review']) ) 
        {
            require_once('ccextras/cc-reviews-table.inc');
            $reviews =& CCReviews::GetTable();
            $rev_q['topic_user'] = $user_id;
            $rev_q['topic_upload'] = $upload_id;
            $count = $reviews->CountRows($rev_q);
            if( intval($count) < 1 )
            {
                return true;
            }
        }

        $remote_ip = $_SERVER['REMOTE_ADDR'];
        $ip = CCUtil::EncodeIP($remote_ip);
        if( $ip == substr($record['user_last_known_ip'],0,8) )
        {
            return true;
        }
        
        $where =<<<END
            (  
                ( ratings_user = '$user_id' ) 
                OR 
                ( ratings_ip =  '$remote_ip')
            )
            AND
            ratings_upload = '$upload_id'
END;

        $rows = $this->QueryRows($where);

        $blocked = !empty($rows);

        return($blocked);
    }
}

/**
* Ratings API
*/
class CCRating
{
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
                'ratingschart'   => 
                    array( 'menu_text'  => _('Ratings'),
                           'menu_group' => 'configure',
                           'help'      => _('How ratings are calculated, who can rate, etc.'),
                           'access' => CC_ADMIN_ONLY,
                           'weight' => 10,
                           'action' =>  ccl('admin','ratings') ),
                );
        }
    }


    /**
    * Catch all for ratings admin UI
    *
    * @param string $cmd One of 'chart' (default), 'user', 'msg'
    * @param integer $user_id User to operate on
    * @param integet $ragings_id Rating id to operate on
    * @param string $cmd2 One of 'delete', 'banuser', 'deluser'
    * @param string $confirmed If present and set to 'confirmed' operation will proceed without UI
    * @see CCRatingsAdmin::Admin()
    */
    function Admin($cmd='',$user_id='',$ratings_id='',$cmd2='',$confirmed='')
    {
        require_once('cclib/cc-ratings-admin.inc');
        $api = new CCRatingsAdmin();
        $api->Admin($cmd,$user_id,$ratings_id,$cmd2,$confirmed);
    }

    /**
    * Racalculate all ratings in system
    */
    function Recalc()
    {
        require_once('cclib/cc-ratings-admin.inc');
        $api = new CCRatingsAdmin();
        $api->Recalc();
    }

    /**
    * Returns array of records sorted by uploaded ranks desc.
    *
    * @param integer $limit Max number of records to return
    * @param string $since String representing date of first record to return
    * @return array $records Upload records
    */
    function GetChart($limit,$since='')
    {
        $configs =& CCConfigs::GetTable();
        $C = $configs->GetConfig('chart',CC_GLOBAL_SCOPE);
        if( empty($C['bayesian-min']) )
            $C['bayesian-min'] = 1;

        $where = "(upload_num_scores >= {$C['bayesian-min']})";
        if( $since != 'forever' )
        {
            $cutoff_t = strtotime( empty($since) ? $C['cut-off'] : $since);
            $cutoff = date('Y-m-d H:i:s', $cutoff_t);
            $where .= " AND  (upload_date > '$cutoff')";
        }

        $uploads = new CCUploads(); // getting new let's us step all over it fearlessly
        $uploads->SetSort('upload_rank','DESC');
        $uploads->SetOffsetAndLimit(0,$limit);
        $records = $uploads->GetRecords($where);
        return $records;
    }

    /**
    * Rate an upload
    *
    * This is an AJAX callback and will print stars to the browser and
    * call {@link exit()} the session when done.
    *
    * @param integer $upload_id Upload id of record to rate
    * @param integer $score Number between 100-500
    */
    function Rate($upload_id,$score=0)
    {
        if( empty($score) )
        {
            return;
        }
        
        require_once('cclib/cc-upload-table.php');
        require_once('cclib/cc-sync.php');

        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        $ratings =& CCRatings::GetTable();
        $has_rated = $ratings->IsRateBlocked($record);

        if( !$has_rated )
        {
            $score *= 100;
            $R['ratings_score'] = $score;
            $R['ratings_upload'] = $record['upload_id'];
            if( CCUser::IsLoggedIn() )
            {
                $R['ratings_user'] = CCUser::CurrentUser();
            }

            if( !empty($_SERVER['REMOTE_ADDR']) )
                $R['ratings_ip'] = $_SERVER['REMOTE_ADDR'];

            $ratings->Insert($R);
            CCSync::Ratings($record,$ratings);
            CCEvents::Invoke( CC_EVENT_RATED, array( $R, $score/100, &$record ) );
        }

        global $CC_GLOBALS;
        $args = $CC_GLOBALS;
        $args['root-url'] = ccd();
        $args['auto_execute'] = array( 'ratings_stars' );
        $record =& $uploads->GetRecordFromID($upload_id);
        CCRatingsHV::_fill_scores($record,'upload');
        $args['record'] = $record;
        $template = new CCTemplate( $CC_GLOBALS['skin-map'] );
        $template->SetAllAndPrint($args);
        exit;
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_LISTING}
    *
    * Final chance to massage a record before being displayed in a list
    * 
    * @param array &$row Record to massage with extra display information
    */
    function OnUploadListing(&$record)
    {
        $configs    =& CCConfigs::GetTable();
        $settings   = $configs->GetConfig('settings'); // local
        $chart      = $configs->GetConfig('chart');    // global
        $ratings    =& CCRatings::GetTable();           
        $ratings_on = !empty($chart['ratings']) && !empty($settings['ratings']);

        $is_me = $record['upload_user'] == CCUser::CurrentUser();

        if( $is_me || 
            !$ratings_on || 
            !empty($record['upload_banned']) || 
            $ratings->IsRateBlocked($record) )
        {
            $record['ok_to_rate'] = false;
        }
        else
        {
            $record['ok_to_rate'] = true;
        }

        if( !$ratings_on || empty($record['upload_score']) )
            return;

        require_once('cclib/cc-ratings.inc');

        CCRatingsHV::_fill_scores($record,'upload');
    }

    /**
    * Event handler for {@link CC_EVENT_USER_DELETED}
    *
    * Nuke the user's ratings and update appropriate uploads. The user's
    * record and uploads still exist at the point of this event.
    *
    * @param integer User id 
    */
    function OnUserDelete($user_id)
    {
        $this->Admin('deluser',$user_id);
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
        global $CC_CFG_ROOT;

        if( $scope != CC_GLOBAL_SCOPE )
        {
            $url = ccl('admin','ratings');
            $link1 = "<a href=\"$url\">";
            $link2 = '</a>';
            $help = _('Allow ratings for %s virtual root. (Click %shere%s to configure global ratings)');

            $fields['ratings'] =
               array(  'label'      => 'Ratings',
                       'form_tip'   => sprintf($help,$CC_CFG_ROOT,$link1,$link2),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE,
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
        CCEvents::MapUrl( ccp('rate'),                  array('CCRating','Rate'), 
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','ratings'),       array('CCRating','Admin'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Display admin ratings form'), CC_AG_RATINGS );
        CCEvents::MapUrl( ccp('admin','ratings','recalc'), array('CCRating','Recalc'), 
            CC_ADMIN_ONLY, ccs(__FILE__) );
    }
}

?>
