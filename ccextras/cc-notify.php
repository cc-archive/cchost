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
require_once('ccextras/cc-reviews.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCNotify',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCNotify' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCNotify' , 'OnUserRow') );

CCEvents::AddHandler(CC_EVENT_REVIEW,         array( 'CCNotify',  'OnReview'));
CCEvents::AddHandler(CC_EVENT_TOPIC_REPLY,    array( 'CCNotify',  'OnReply'));
CCEvents::AddHandler(CC_EVENT_ED_PICK,        array( 'CCNotify',  'OnEdPick'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCNotify',  'OnUploadDone')); 
CCEvents::AddHandler(CC_EVENT_RATED,          array( 'CCNotify',  'OnRated')); 

/**
*
*
*/
class CCNotify
{

    function EditMyNotifications($other_user_name='')
    {
        if( !$this->_is_notify_on() )
            return;
        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->EditMyNotifications($other_user_name);
    }

    function OnRated($rating_rec,$rating,&$record)
    {
        if( !$this->_is_notify_on() )
            return;
        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->OnRated($rating_rec,$rating,$record);
    }

    function OnReview(&$review)
    {
        if( !$this->_is_notify_on() )
            return;
        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->OnReview($review);
    }

    function OnReply(&$reply, &$original)
    {
        if( !$this->_is_notify_on() )
            return;

        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->OnReply($reply, $original);
    }

    function OnEdPick($upload_id)
    {
        if( !$this->_is_notify_on() )
            return;

        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->OnEdPick($upload_id);
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    * 
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'} 
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id,$op,$parents=array())
    {
        if( $op != CC_UF_NEW_UPLOAD || !$this->_is_notify_on() )
            return;

        require_once('ccextras/cc-notify.inc');
        $notify_api = new CCNotifyAPI();
        $notify_api->OnUploadDone($upload_id,$op,$parents);
    }


    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$row)
    {
        if( $this->_is_notify_on() && CCUser::IsLoggedIn())
        {
            if( CCUser::CurrentUser() == $row['user_id'] )
            {
                $url = ccl('people','notify','edit');
                $text = _('Edit My Notifications');
            }
            else
            {
                $url = ccl('people','notify','edit',$row['user_name']);
                $text = sprintf(_('Get Notified About %s'),$row['user_real_name']);
            }

            $row['user_fields'][] = array( 'label' => _('Notifcations'), 
                                           'value' => "<a href=\"$url\">$text</a>" );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('people', 'notify', 'edit'), array('CCNotify','EditMyNotifications'), CC_MUST_BE_LOGGED_IN);
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
            $fields['notify'] =
               array(  'label'      => 'Allow email notifcations',
                       'form_tip'   => 'Is it ok to let users get notified on activity on their accounts and others?',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
        }
    }

    function _is_notify_on()
    {
        global $CC_GLOBALS;

        return !empty($CC_GLOBALS['notify']) ;
    }

}



?>