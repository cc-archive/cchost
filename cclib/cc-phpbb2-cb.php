<?php
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

if( !defined('IN_PHPBB') )
{
    define('IN_PHPBB',true);
    $phpbb_root_path = $CC_GLOBALS['phpbb2_root_path'];
    require_once($phpbb_root_path . 'extension.inc');
    require_once($phpbb_root_path . 'common.'.$phpEx);

    $userdata = session_pagestart($user_ip, PAGE_INDEX);
    init_userprefs($userdata);

    if (!empty($HTTP_POST_VARS['sid']) || !empty($HTTP_GET_VARS['sid']))
    {
        $sid = (!empty($HTTP_POST_VARS['sid'])) ? $HTTP_POST_VARS['sid'] : $HTTP_GET_VARS['sid'];
    }
    else
    {
        $sid = '';
    }

    if( !empty($CC_GLOBALS['user_name']) && ($CC_GLOBALS['user_name'] != $userdata['username']) )
    {
        if( !cc_phpbb_login_user($CC_GLOBALS['user_name']) )
        {
            CCDebug::Log("Couldn't sync up user {$CC_GLOBALS['user_name']} with phpBB2, got {$userdata['username']} instead");
        }
    }

    if( !empty($userdata['username']) )
    {
        //CCDebug::Log("phpBB logged in {$userdata['username']}");
    }

    //return( $userdata['username'] ); // interesting...
}

require_once($phpbb_root_path . 'includes/bbcode.'.$phpEx);
require_once($phpbb_root_path . 'includes/functions_post.'.$phpEx);
require_once($phpbb_root_path . 'common.'.$phpEx);

function cc_phpbb_get_ext()
{
    global $phpEx;
    return( $phpEx );
}


function cc_phpbb_update_user($dont_use_this,&$record)
{
    global $db;

    $email = $record['user_email'];
    $avatar = $record['user_image'];
    $password = $record['user_password'];
    $atype = empty($avatar) ? 0 : 1;
    $username = $record['user_name'];

    $sql = "UPDATE " . USERS_TABLE . " SET user_password = '$password', user_avatar_type = $atype, user_avatar = '$avatar', user_email = '$email' " .
           " WHERE username = '$username'";

    if ( !$db->sql_query($sql) )
    {
        message_die(CRITICAL_ERROR, "Error updating user data during login.", "", __LINE__, __FILE__, $sql);
    }
}

function cc_phpbb_login_user($username,$ip='')
{
    global $CC_GLOBALS, $db, $board_config, $HTTP_GET_VARS, $HTTP_POST_VARS;
    global $userdata, $user_ip, $session_id, $phpEx;

    if( $userdata['session_logged_in'] )
    {
        // um, someone is logged .... log them out?
        CCDebug::Log("trying to login as '$username' but '{$userdata['username']}' is in the way");
        cc_phpbb_logout();
    }

    $sql = "SELECT user_id, username, user_password, user_active, user_level " .
            " FROM " . USERS_TABLE . " WHERE username = '" . str_replace("'", "''", $username) . "'";

    if ( !($result = $db->sql_query($sql)) )
    {
        message_die(CRITICAL_ERROR, "Error obtaining user data during login.", "", __LINE__, __FILE__, $sql);
    }
    $row = $db->sql_fetchrow($result);

    if( empty($row) )
    {
        CCDebug::Log("// phpbb is out of synch, trying to log in as '$username'");
        return(false);
    }

    if( $row['user_level'] != ADMIN && $board_config['board_disable'] )
    {        
        CCDebug::Log("// The board is disabled.  ");
        return(false);
    }

    if( !$row['user_active'] )
    {
        CCDebug::Log("// er, user is not activated");
        return(false);
    }

    $autologin = 1; 
    $admin = CCUser::IsAdmin();
    if( !empty($ip) )
        $user_ip = $ip;
    $session_id = session_begin($row['user_id'], $user_ip, PAGE_INDEX, FALSE, $autologin, $admin);

    if( !$session_id )
    {
        CCDebug::Log("// couldn't convince phpbb to log us in");
        return(false);
    }

    // user is logged in, clean up old session goo for this user
    $sql = "DELETE FROM " . SESSIONS_TABLE . " WHERE session_user_id = -1 AND session_ip = '$user_ip'";
    if ( !$db->sql_query($sql) )
    {
        message_die(CRITICAL_ERROR, 'Error clearing sessions table', '', __LINE__, __FILE__, $sql);
    }

    return(true);
}

function cc_phpbb_logout()
{
    global $HTTP_GET_VARS, $HTTP_POST_VARS, $userdata;

    session_end($userdata['session_id'], $userdata['user_id']);
}


function cc_phpbb_register_new_user( $username, $ignore_this, $password, $email  )
{
    global $db;

    $status = array();

    $time = time();
    $phpbb_users = USERS_TABLE;

    $sql = "SELECT MAX(user_id) AS total FROM $phpbb_users";
    $qr = '';
    if( ($qr = $db->sql_query($sql)) && ($row = $db->sql_fetchrow($qr)) )
    {
        $user_id = $row['total'] + 1;
        $sql = "SELECT user_id,username,user_email FROM $phpbb_users WHERE username = '$username' OR user_email = '$email'";
        $qr = $db->sql_query($sql);
    }

    if( !$qr )
    {
        $err = $db->sql_error();
        $status['error'] = 'Error processing user information (phpBB)';
        $status['sql_error'] = $err['message'];
        CCDebug::LogVar('status ' . __LINE__ ,$status);
        return($status);
    }
    if( $row = $db->sql_fetchrow($qr) )
    {
        if( $row['username'] == $username )
        {
            $status['error_field'] = 'user_name';
            $status['error'] = "That login name is already in use (phpBB)";
        }
        elseif( $row['user_email'] == $email )
        {
            $status['error_field'] = 'user_email';
            $status['error'] = "That email address is already in use (phpBB)";
        }

        return($status);
    }

    $sql =<<<END
INSERT INTO $phpbb_users (user_id,    username,   user_regdate, user_password, user_email,  user_icq, user_website, user_occ, user_from, user_interests, user_sig, user_sig_bbcode_uid, user_avatar, user_avatar_type, user_viewemail, user_aim, user_yim, user_msnm, user_attachsig, user_allowsmile, user_allowhtml, user_allowbbcode, user_allow_viewonline, user_notify, user_notify_pm, user_popup_pm, user_timezone, user_dateformat,  user_lang, user_style, user_level, user_allow_pm, user_active, user_actkey)
                 VALUES ('$user_id', '$username', '$time',      '$password',   '$email',    '',       '',           '',       '',        '',             '',       '',                  '',          0,                0,              '',       '',       '',        1,              1,               0,              1,                1,                     0,           1,              1,             0,             'D M d, Y g:i a', 'english', 2,          0,          1,             1,           '')
END;

    $qr = $db->sql_query($sql);
    if( !$qr )
    {
        $err = $db->sql_error();
        $status['error'] = 'Error adding user';
        $status['sql_error'] = $err['message'];
        CCDebug::LogVar('status ' . __LINE__ ,$status);
        return($status);
    }

    $status['user_id'] = $user_id;

    $groups = GROUPS_TABLE;

    $sql =<<<END
INSERT INTO $groups (group_name, group_description, group_single_user, group_moderator)
                  VALUES ('',         'Personal User',   1,                 0)
END;
    $qr = $db->sql_query($sql);
    if( !$qr )
    {
        $err = $db->sql_error();
        $status['error'] = 'Error adding user group';
        $status['sql_error'] = $err['message'];
        CCDebug::LogVar('status ' . __LINE__ ,$status);
        return($status);
    }

    $group_id = $db->sql_nextid();

    if( !$group_id )
    {
        $err = $db->sql_error();
        $status['error'] = 'Error getting group id:';
        $status['sql_error'] = $err['message'];
        CCDebug::LogVar('status ' . __LINE__ ,$status);
        return($status);
    }

    $status['group_id'] = $group_id;

    $user_group = USER_GROUP_TABLE;

    $sql =<<<END
INSERT INTO $user_group (user_id,  group_id,  user_pending) VALUES ($user_id, $group_id, 0)
END;

    $qr = $db->sql_query($sql);

    if( !$qr )
    {
        $err = $db->sql_error();
        $status['error'] = 'Error adding to user group';
        $status['sql_error'] = $err['message'];
        CCDebug::LogVar('status ' . __LINE__ ,$status);
        return($status);
    }

    return( $status );
}



?>