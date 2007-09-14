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
* Shows user recommendations
*
* @package cchost
* @subpackage feature
*/

CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCRecommends',  'OnUserProfileTabs')      );

class CCRecommends
{
    function OnUserProfileTabs( &$tabs, &$record )
    {
        require_once('cclib/cc-ratings.php');
        $ratings =& CCRatings::GetTable();
        $w = "(ratings_user = {$record['user_id']}) AND (ratings_score > 400)";
        $count = $ratings->CountRows($w);
        if( empty($count) )
            return;

        $tabs['recommends'] = array(
                    'text' => 'Recommends',
                    'help' => 'Recommendation for and by this person',
                    'tags' => 'recommends',
                    'access' => CC_DONT_CARE_LOGGED_IN,
                    'function' => 'url' ,
                    'user_cb' => array( 'CCRecommends', 'User' ),
            );
    }

    function User($user)
    {
        $users =& CCUsers::GetTable();
        $w['user_name'] = $user;
        $fullname = $users->QueryItem('user_real_name',$w);
        $args['ruser'] = $user;
        $args['fullname'] = $fullname;
        CCPage::PageArg('get',$args);
        CCPage::ViewFile('recommends');
    }

}

?>