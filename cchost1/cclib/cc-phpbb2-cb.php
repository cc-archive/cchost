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

function cc_phpbb_delete_thread($topic_id)
{
    global $userdata,$db;

    $mode = 'delete';
    $pt = POSTS_TABLE;
    $tt = TOPICS_TABLE;
    $ft = FORUMS_TABLE;
    
    // get all post_ids for topic_id

    $sql = "SELECT post_id FROM $pt WHERE topic_id = $topic_id";
    if( $result1 = $db->sql_query($sql) )
    {
        // for each post id....

        while( $row = $db->sql_fetchrow($result1) )
        {
            $post_id = $row[0];

            CCDebug::Log("Deleteing: post: $post_id");

            // build up sql to get post row

            $sql =<<<END
      SELECT f.*, t.topic_id, t.topic_status, t.topic_type, t.topic_first_post_id, 
                  t.topic_last_post_id, t.topic_vote, p.post_id, p.poster_id
                FROM $pt p, $tt t, $ft f
                WHERE p.post_id = $post_id 
                    AND t.topic_id = p.topic_id 
                    AND f.forum_id = p.forum_id
END;

            if ( $result = $db->sql_query($sql) )
            {
                $post_info = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);

                $forum_id = $post_info['forum_id'];
                $forum_name = $post_info['forum_name'];
                $topic_id = $post_info['topic_id'];

                //$post_data['poster_post'] = ( $post_info['poster_id'] == $userdata['user_id'] ) ? true : false;
                $post_data['first_post'] = ( $post_info['topic_first_post_id'] == $post_id ) ? true : false;
                $post_data['last_post'] = ( $post_info['topic_last_post_id'] == $post_id ) ? true : false;
                $post_data['last_topic'] = ( $post_info['forum_last_post_id'] == $post_id ) ? true : false;
                $post_data['topic_type'] = $post_info['topic_type'];
                $post_data['poster_id'] = $post_info['poster_id'];

                $poll_id = 0;
                $post_data['has_poll'] = false; // ( $post_info['topic_vote'] ) ? true : false; 

                $message = '';
                $meta    = '';

                cc_delete_post($mode, &$post_data, &$message, &$meta, &$forum_id, &$topic_id, &$post_id, &$poll_id);

                if( empty($message) )
                {
                    $user_id = $post_data['poster_id'];
                    update_post_stats($mode, $post_data, $forum_id, $topic_id, $post_id, $user_id);
                    $notify_user = 0;
                    user_notification($mode, $post_data, $post_info['topic_title'], $forum_id, $topic_id, $post_id, $notify_user);
                }
                else
                {
                    CCDebug::Log("No delete post (1)");
                    // ummmm
                }
            }
            else
            {
                    CCDebug::Log("No delete post (2)");
                // uh..
            }
        }
    }
    else
    {
                    CCDebug::Log("No delete post (3) sql: $sql");
        // errr...
    }
}

// this is WAY more code than I wanted to snarf but these guys put 
// an 'include' instread of 'include_once' and that made the loop
// above blow chunks.
function cc_delete_post($mode, &$post_data, &$message, &$meta, &$forum_id, &$topic_id, &$post_id, &$poll_id)
{
	global $board_config, $lang, $db, $phpbb_root_path, $phpEx;
	global $userdata, $user_ip;

    require_once($phpbb_root_path . 'includes/functions_search.'.$phpEx);

    $sql = "DELETE FROM " . POSTS_TABLE . " WHERE post_id = $post_id";
    if (!$db->sql_query($sql))
    {
        message_die(GENERAL_ERROR, 'Error in deleting post', '', __LINE__, __FILE__, $sql);
    }

    $sql = "DELETE FROM " . POSTS_TEXT_TABLE . " WHERE post_id = $post_id";
    if (!$db->sql_query($sql))
    {
        message_die(GENERAL_ERROR, 'Error in deleting post', '', __LINE__, __FILE__, $sql);
    }

    if ($post_data['last_post'])
    {
        if ($post_data['first_post'])
        {
            // this next line was dangling in the original
            //$forum_update_sql .= ', forum_topics = forum_topics - 1';

            $sql = "DELETE FROM " . TOPICS_TABLE . " WHERE topic_id = $topic_id OR topic_moved_id = $topic_id";
            if (!$db->sql_query($sql))
            {
                message_die(GENERAL_ERROR, 'Error in deleting post', '', __LINE__, __FILE__, $sql);
            }

            $sql = "DELETE FROM " . TOPICS_WATCH_TABLE . " WHERE topic_id = $topic_id";
            if (!$db->sql_query($sql))
            {
                message_die(GENERAL_ERROR, 'Error in deleting post', '', __LINE__, __FILE__, $sql);
            }
        }
    }

    remove_search_post($post_id);

	$message =  '';
	return;
}


function cc_phpbb_attach_thread($upload_id,$incoming_post_url)
{
    global $db; 

    $ppu = POST_POST_URL;
    preg_match("/$ppu=([0-9]+)[^0-9]/",$incoming_post_url,$m);
    $post_id = empty($m[1]) ? null : $m[1];
    if( empty($post_id) )
    {
        //er what to do here??
        CCDebug::Log("Could not get post id from posting url: $incoming_post_url!");
        return( false );
    }

    // we've actually gleened the post_id, not the topic_id
    // convert it here:
    $pt = POSTS_TABLE;
    $sql = "SELECT topic_id FROM $pt p WHERE p.post_id = '$post_id'";
    if ( !($result = $db->sql_query($sql)) || !($row = $db->sql_fetchrow($result)))
    {
        // this is even worse news
        CCDebug::Log("Could not convert post id to topic id!");
        return( false );
    }

    // wahoo, connect the topic id to the upload
    $uploads =& CCUploads::GetTable();
    $args['upload_id'] = $upload_id;
    $args['upload_topic_id'] = $row['topic_id'];
    $uploads->Update($args);
    return( true );
}

function cc_phpbb_update_user($user_id,&$record)
{
    global $db;

    $email = $record['user_email'];
    $avatar = $record['user_image'];
    $password = $record['user_password'];
    $atype = empty($avatar) ? 0 : 1;

    $sql = "UPDATE " . USERS_TABLE . " SET user_password = '$password', user_avatar_type = $atype, user_avatar = '$avatar', user_email = '$email' " .
           " WHERE user_id = '$user_id'";

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

    $autologin = 0;
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

function cc_phpbb_get_post_topic_url($topic_id)
{
    global $phpEx,$phpbb_root_path,$CC_CFG_ROOT;

    $ptu = POST_TOPIC_URL;
    $url = $phpbb_root_path . "posting.$phpEx?mode=reply&$ptu=$topic_id&cc_cfg_root=" . $CC_CFG_ROOT;
    $url = append_sid( $url, true);
    return( ccd($url) );
}

function cc_phpbb_get_new_topic_url($subject, $upload_id)
{
    global $phpEx,$phpbb_root_path,$CC_CFG_ROOT, $CC_GLOBALS;

    $subject = urlencode($subject);
    $forum = $CC_GLOBALS['phpbb2_forum_id'];
    $pfu = POST_FORUM_URL;
    $url = $phpbb_root_path . "posting.$phpEx?mode=newtopic&$pfu=$forum&cc_cfg_root=$CC_CFG_ROOT&ccreview=1&ccsubject=$subject&ccup=$upload_id";
    $url = append_sid( $url, true);
    return( ccd($url) );
}

function cc_phpbb_get_topic_count($topic_id)
{
    global $db;

    $pt = POSTS_TABLE;

    $sql = "SELECT COUNT(*) as total FROM $pt p WHERE p.topic_id = '$topic_id'";
    if ( !($result = $db->sql_query($sql)) || !($row = $db->sql_fetchrow($result)))
    {
        // rrrg
        $count = -1;
    }
    else
    {
        $count = $row['total'];
    }

    return( $count );
}

function cc_phpbb_get_topic_url()
{
    global $phpEx,$phpbb_root_path,$CC_CFG_ROOT,$db;

    $ptu = POST_TOPIC_URL;
    $url = $phpbb_root_path . "viewtopic.$phpEx?$ptu=%d&cc_cfg_root=$CC_CFG_ROOT";
    return( append_sid( $url, true) );
}

function cc_phpbb_get_post_url()
{
    global $phpEx,$phpbb_root_path,$CC_CFG_ROOT,$db;

    $ppu = POST_POST_URL;
    $url = $phpbb_root_path . "viewtopic.$phpEx?$ppu=%d&cc_cfg_root=$CC_CFG_ROOT";
    return( append_sid( $url, true) );
}

function cc_phpbb_register_new_user( $username, $user_id, $password, $email  )
{
    global $db;

    $status = array();

    $time = time();
    $phpbb_users = USERS_TABLE;

    if( !$user_id )
    {
        $sql = "SELECT MAX(user_id) AS total FROM $phpbb_users";
        $qr = $db->sql_query($sql);
        $row = $db->sql_fetchrow($qr);
        $user_id = $row['total'] + 1;
    }

    $sql = "SELECT user_id,username,user_email FROM $phpbb_users WHERE user_id = '$user_id' OR username = '$username' OR user_email = '$email'";
    $qr = $db->sql_query($sql);
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
        if( $row['user_id'] == $user_id )
        {
            $status['error'] = "That ID is already being used";
        }
        elseif( $row['username'] == $username )
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


function cc_phpbb_show_thread($topic_id,$limit = 10)
{
    global $CC_GLOBALS;
    global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config;

	// Board is disabled, no topics are available
	if ($board_config['board_disable'])
	{
        $msg = $lang['Board_disable'];
        exit("document.writeln('$msg');\n");
	}

	// Get most important vars out parameters
	$error = false;
	$errorarray = array();

    $tt = TOPICS_TABLE;
    $ut = USERS_TABLE;
    $pt = POSTS_TABLE;
    $xt = POSTS_TEXT_TABLE;

    $sql =<<<END
        SELECT post_time, username, post_text, bbcode_uid, enable_html, p.post_id
        FROM $pt p, $ut u, $xt txt
        WHERE poster_id = user_id AND
              txt.post_id = p.post_id AND
              p.topic_id = '$topic_id' 
        ORDER BY post_time DESC
        LIMIT 8
END;

    if ( !($result = $db->sql_query($sql)) )
    {
        message_die(CRITICAL_ERROR, 'General Error: Could not obtain topic information (topics sql)', '', __LINE__, __FILE__, $sql);
    }

    $base_post_url = cc_phpbb_get_post_url();

    while( $row = $db->sql_fetchrow($result) )
    {
        // format it
        cc_phpbb_compile_topic_text($row);

        // lose the bbcode formatting (not sure what to do if 
        // they actually have html enabled, strip_tags would
        // be devastating...)

        $row['post_text' ] = CCUtil::StripText(preg_replace('#<[^>]*>#s','',$row['post_text']));
        
        $row['post_date_format'] = create_date( $board_config['default_dateformat'], 
                                                $row['post_time'], 
                                                $board_config['board_timezone']);

        $row['post_url'] = ccr( sprintf($base_post_url,$row['post_id']) ) . '#' . $row['post_id'];

        $topic_rowset[] = $row;
    }

    $db->sql_freeresult($result);

    if( !empty($topic_rowset) )
    {
        $template = new CCTemplate( $CC_GLOBALS['skin-map'] );
        $args['auto_execute'][] = 'comment_thread_list';
        $args['posts'] = $topic_rowset;
        if( CCUser::IsLoggedIn() )
        {
            $url = append_sid($phpbb_root_path . 
                        "posting.$phpEx?mode=reply&amp;" . POST_TOPIC_URL . "=$topic_id");
            $args['reply_topic_url'] = ccd($url);
        }
        else
        {
            $args['reply_topic_url'] = false;
        }

        $base_topic_url = cc_phpbb_get_topic_url();
        $args['view_topic_url'] = ccr( sprintf($base_topic_url,$topic_id) ) ;

        $html = $template->SetAllAndParse($args,false,true);
        print($html);
    }

    if ($error)
	{
		ccppbb_output_die($errorarray);
	}

} 

function cc_phpbb_compile_topic_text(&$row)
{
    global $board_config;

	$message = $row['post_text'];
	$bbcode_uid = $row['bbcode_uid'];

	if ( !$board_config['allow_html'] )
	{
		if ( $row['enable_html'] )
		{
			$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
		}
	}

	if ( $board_config['allow_bbcode'] )
	{
		if ( $bbcode_uid != '' )
		{
			$message = ( $board_config['allow_bbcode'] ) ? bbencode_second_pass($message, $bbcode_uid) : preg_replace('/\:[0-9a-z\:]+\]/si', ']', $message);
		}
	}

    $row['post_text'] = $message;
}


?>