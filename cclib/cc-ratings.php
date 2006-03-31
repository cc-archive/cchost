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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCRating' , 'OnGetConfigFields' ));
CCEvents::AddHandler(CC_EVENT_UPLOAD_LISTING,     array( 'CCRating',  'OnUploadListing'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCRating',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCRating' , 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCRating' , 'OnUserRow') );

class CCRatings extends CCTable
{
    function CCRatings()
    {
        $this->CCTable('cc_tbl_ratings','ratings_id');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCRatings();
        return($_table);
    }

    function IsRateBlocked($record)
    {
        global $CC_GLOBALS;

        if( !CCUser::IsLoggedIn() )
            return true;

        if( !empty($CC_GLOBALS['ratings_ban']) )
        {
            $banlist = CCTag::TagSplit($CC_GLOBALS['ratings_ban']);
            $username = CCUser::CurrentUserName();
            if( in_array($username,$banlist) )
                return true;
        }

        $remote_ip = $_SERVER['REMOTE_ADDR'];
        $ip = CCUtil::EncodeIP($remote_ip);
        if( $ip == substr($record['user_last_known_ip'],0,8) )
            return true;
        
        $user_id = CCUser::CurrentUser();
        $upload_id = $record['upload_id'];
        $where =<<<END
            (  
                ( ratings_user = '$user_id' ) 
                OR 
                ( ratings_ip =  '$remote_ip')
            )
            AND
            ratings_upload = '$upload_id'
END;

        $row = $this->QueryRow($where);
        //CCDebug::PrintVar($row);
        return !empty($row);
    }
}


class CCRating
{
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array( 
                'ratingschart'   => array( 'menu_text'  => 'Ratings Settings',
                                 'menu_group' => 'configure',
                                 'help'      => 'Configure how ratings are calculated',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10,
                                 'action' =>  ccl('admin','ratings') ),
                'ratingsmanage'   => array( 'menu_text'  => 'Ratings Manage',
                                 'menu_group' => 'configure',
                                 'help'      => 'View and edit current ratings',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10,
                                 'action' =>  ccl('admin','ratings','manage') )
                );
        }
    }


    function Admin($cmd='',$user_id='',$ratings_id='',$cmd2='',$confirmed='')
    {
        require_once('cclib/cc-ratings-admin.inc');
        $api = new CCRatingsAdmin();
        $api->Admin($cmd,$user_id,$ratings_id,$cmd2,$confirmed);
    }

    function GetChart($limit,$since='')
    {
        $configs =& CCConfigs::GetTable();
        $C = $configs->GetConfig('chart',CC_GLOBAL_SCOPE);

        if( $since == 'forever' )
        {
            $date_where = '';
        }
        else
        {
            $cutoff_t = strtotime( empty($since) ? $C['cut-off'] : $since);
            $cutoff = date('Y-m-d H:i:s', $cutoff_t);
            $date_where = " upload_date > '$cutoff' ";
        }

        if( ($since == 'forever') || empty($C['per-hour']) )
        {
            $rank_col = 'upload_rank as rank';
        }
        else
        {
            $min_time = CCDatabase::QueryItem("SELECT MIN(UNIX_TIMESTAMP(upload_date) / 360) FROM cc_tbl_uploads");
            if( empty($min_time) )
                $min_time = '0';
            $rank_col = "((UNIX_TIMESTAMP(upload_date)/360) - $min_time) * {$C['per-hour']} * upload_rank as rank"; 
        }

        $uploads = new CCUploads(); // getting new let's us step all over it fearlessly
        $uploads->AddExtraColumn($rank_col);
        $uploads->SetSort('rank','DESC');
        $uploads->SetOffsetAndLimit(0,$limit);
        $records = $uploads->GetRecords($date_where);
        return $records;
    }

    function Rate($upload_id,$score=0)
    {
        if( empty($score) )
            return;

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
            CCEvents::Invoke( CC_EVENT_RATED, array( $record, $score/100 ) );
        }

        global $CC_GLOBALS;
        $args = $CC_GLOBALS;
        $args['root-url'] = ccd();
        $args['auto_execute'] = array( 'ratings_stars' );
        $this->OnUploadListing(&$record);
        $args['record'] = $record;
        $template = new CCTemplate( $CC_GLOBALS['skin-map'] );
        $template->SetAllAndPrint($args);
        exit;
    }


    function OnUploadListing(&$record)
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $ratings =& CCRatings::GetTable();

        $is_me = $record['upload_user'] == CCUser::CurrentUser();

        if( $is_me || 
            empty($settings['ratings']) || 
            !empty($record['upload_banned']) || 
            $ratings->IsRateBlocked($record) )
        {
            $record['ok_to_rate'] = false;
        }
        else
        {
            $record['ok_to_rate'] = true;
        }

        if( empty($settings['ratings']) || empty($record['upload_score']) )
            return;

        $this->_fill_scores($record,'upload');
    }

    function _fill_scores(&$record,$prefix)
    {
        $average = $record[$prefix . '_score'] / 100;
        $count = $record[$prefix . '_num_scores'];
        $stars = floor($average);
        $half  = ($record[$prefix . '_score'] % 100) > 25;

        for( $i = 0; $i < $stars; $i++ )
            $record['ratings'][] = 'full';

        if( $half )
        {
            $record['ratings'][] = 'half';
            $i++;
        }
        
        for( ; $i < 5; $i++ )
            $record['ratings'][] = 'empty';
        
        $record['ratings_score'] = number_format($average,2) . '/' . $count;
    }

    function OnUserRow(&$record)
    {
        if( !empty($record['user_num_scores']) ) // && $row['user_num_scores'] > 10 )
        {
            $this->_fill_scores($record,'user');
        }
    }
    
    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['ratings'] =
               array(  'label'      => 'Ratings',
                       'form_tip'   => 'Allow users to rate uploads',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE,
                    );
        }

        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['ratings_ban'] =
                           array(  'label'      => 'Ratings Ban List',
                                   'form_tip'   => 'Users not allowed to rate',
                                   'formatter'  => 'textarea',
                                   'flags'      => CCFF_POPULATE);
        }

    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('rate'),                  array('CCRating','Rate'), CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('admin','ratings'),       array('CCRating','Admin'), CC_ADMIN_ONLY);
    }
}

?>