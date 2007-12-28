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

class CCUserHook
{
    function UploadList()
    {
        $ids = CCUtil::Strip($_GET['ids']);

        $sql =<<<EOF
            SELECT upload_id, upload_user, SUBSTRING(user_last_known_ip,1,8) as uploader_ip
            FROM cc_tbl_uploads 
            JOIN cc_tbl_user ON upload_user=user_id
            WHERE upload_id IN ($ids)
EOF;
        $ret               = array();
        $configs           =& CCConfigs::GetTable();
        $C                 = $configs->GetConfig('chart',CC_GLOBAL_SCOPE);
        $ret['rate_mode']  = empty($C['thumbs_up']) ? 'rate' : 'recommend';
        $ret['ok_to_rate'] = array();
        $recs              = CCDatabase::QueryRows($sql);
        $user_id           = CCUser::CurrentUser();
        $user_name         = CCUser::CurrentUserName();
        $user_blocked      = false;
        $remote_ip         = $_SERVER['REMOTE_ADDR'];
        $ip                = CCUtil::EncodeIP($remote_ip);

        if( !empty($C['ratings_ban']) )
        {
            require_once('cclib/cc-tags.php');
            $banlist = CCTag::TagSplit($C['ratings_ban']);
            $user_blocked = in_array($user_name,$banlist);
        }

        if( $user_blocked )
        {
            //die('user blocked');
        }
        else
        {
            foreach( $recs as $R )
            {
                if( $R['upload_user'] == $user_id )
                    continue;

                if( $ip == $R['uploader_ip'] )
                    continue;

                if( !empty($C['requires-review']) ) 
                {
                    $blocked = !CCDatabase::QueryItem(
                            "SELECT COUNT(*) FROM cc_tbl_topics WHERE topic_user = {$user_id} AND topic_upload = {$R['upload_id']} " .
                               " topic_type = 'review'" );
                    if( $blocked )
                    {
                        //die('review req');
                        continue;
                    }
                }


                $sql = "SELECT COUNT(*) FROM cc_tbl_ratings WHERE (ratings_ip = '{$remote_ip}' OR ratings_user = {$user_id}) AND " .
                           " ratings_upload = {$R['upload_id']}";
                $blocked = CCDatabase::QueryItem($sql);
                if( $blocked )
                {
                    //die('already rated');
                    continue;
                }

                $ret['ok_to_rate'][] = $R['upload_id'];
            }
        }

        require_once('cclib/zend/json-encoder.php');
        $text = CCZend_Json_Encoder::encode($ret);
        header( "X-JSON: $text");
        header( 'Content-type: text/plain');
        print $text;
        exit;
    }

    function Tags($user_name)
    {
        $rawtags = CCDatabase::QueryItems('SELECT DISTINCT upload_tags FROM cc_tbl_uploads JOIN cc_tbl_user ON upload_user=user_id ' .
                                          "WHERE user_name='$user_name'");
        $c = count($rawtags);
        $k = array_keys($rawtags);
        $tagarr = array();
        for($i = 0; $i < $c; $i++ )
        {
            $tagarr = array_merge($tagarr,array_filter(preg_split('/[\s,]/',$rawtags[$k[$i]])));
        }
        $tagarr = array_unique($tagarr);
        sort($tagarr);
        require_once('cclib/zend/json-encoder.php');
        $args = CCZend_Json_Encoder::encode($tagarr);
        header('Content-type: text/javascript');
        print($args);
        exit;
    }
    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('user_hook','upload_list'), array('CCUserHook','UploadList'), CC_MUST_BE_LOGGED_IN, ccs(__FILE__));
        CCEvents::MapUrl( ccp('user_hook','tags'),        array('CCUserHook','Tags'), CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
    }

}
?>