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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCImportPhpBBReviews', 'OnMapUrls'));

class CCImportPhpBBReviews
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('phpbb2','import'),  
                          array( 'CCImportPhpBBReviews', 'DoPhpBBImport'),  
                          CC_ADMIN_ONLY);

    }

    function DoPhpBBImport($phase)
    {
        if( $phase == 1 )
            $this->_import_1();
        elseif( $phase == 2 )
            $this->_import_2();
    }

    function _import_2()
    {
    }

    function _import_1()
    {
        $topics =& CCTopics::GetTable();
        $threads =& CCForumThreads::GetTable();
        $forums =& CCForum::GetTable();
        $forumgroups =& CCForumGroups::GetTable();
        
        $threads->DeleteWhere('1');
        $forums->DeleteWhere('1');
        $forumgroups->DeleteWhere('1');

        $forumx[1] = 2; // help
        $forumx[2] = 4; // bugs
        $forumx[3] = 6; // features
        $forumx[4] = 1; // announcements
        // 5 reviews
        $forumx[7] = 7; // diy
        $forumx[8] = 3; // off topic
        $forumx[9] = 8; // pluggy

        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (3, 'The Site', 1)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (4, 'The Music', 2)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (5, 'Off Beats', 10)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (1, 8, 4, 1, 'Announcements', 'Messages from the admins', 3)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (2, 1, 4, 2, 'Help', 'get aid', 3)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (3, 1, 4, 3, 'The Big OT', 'off topic stuff', 5)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (4, 1, 1, 5, 'Bugs', 'Report bugs here', 3)";
//      $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (5, 8, 4, 1, 'Reviews', 'always constructive, riiiiiigggght', 4)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (6, 1, 4, 9, 'Features', 'request and talk about features', 3)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (7, 1, 4, 2, 'DIY', 'tips etc.', 4)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (8, 1, 4, 11, 'Pluggy Plugs', 'plug thyself', 5)";
        $sql[] = 'DELETE FROM phpbb_topics WHERE forum_id = 5';
        $sql[] = 'DELETE FROM cc_tbl_topics WHERE topic_forum > 0';

        CCDatabase::Query($sql);

        $sql = 'SELECT * FROM phpbb_topics';
        $qr = mysql_query($sql);


        while( $topic = mysql_fetch_array($qr) )
        {
            $tid = $topic['topic_id'];

            $sql =<<<EOF
SELECT p.post_id, post_time, mu.user_id, post_text
FROM phpbb_posts p
JOIN phpbb_posts_text t ON p.post_id = t.post_id
JOIN phpbb_users pu ON poster_id = pu.user_id
JOIN cc_tbl_user mu ON pu.username = mu.user_name
WHERE p.topic_id = $tid
ORDER BY post_time
EOF;

            $rows = CCDatabase::QueryRows($sql);

            $P =& $rows[0];

            $parent_topic = $topics->NextID();
            $thread_id    = $threads->NextID();

            $R = array();
            $R['topic_id']     = $parent_topic;
            $R['topic_user']   = $P['user_id'];
            $R['topic_date']   = date('Y-m-d H:i:s', $P['post_time']);
            $R['topic_text']   = $this->convert_text($P['post_text']);
            $R['topic_forum']  = $forumx[ $topic['forum_id'] ];
            $R['topic_thread'] = $thread_id;
            $R['topic_name']   = $topic[ 'topic_title' ];
            $R['topic_type']   = 'forum';
            $topics->Insert($R);

            $T = array();
            $T['forum_thread_id'] = $thread_id;
            $T['forum_thread_oldest'] = 
            $T['forum_thread_newest'] = $parent_topic;
            $T['forum_thread_date'] = $R['topic_date'];
            $T['forum_thread_user'] = $R['topic_user'];
            $T['forum_thread_forum'] = $R['topic_forum'];

            $count = count($rows);
            for( $n = 1; $n < $count; $n++ )
            {
                $P =& $rows[$n];
                $child_topic = $topics->NextID();
                $R = array();
                $R['topic_id']    = $child_topic;
                $R['topic_user']  = $P['user_id'];
                $R['topic_date']  = date('Y-m-d H:i:s', $P['post_time']);
                $R['topic_text']  = $this->convert_text($P['post_text']);
                $R['topic_forum'] = $forumx[ $topic['forum_id'] ];
                $R['topic_thread'] = $thread_id;
                $R['topic_name']  = 'Reply to "' . $topic[ 'topic_title' ] . '"';
                $R['topic_type']  = 'reply';
                $R['topic_top']   = $parent_topic;
                $topics->Insert($R);
                CCTopic::Sync( $parent_topic, $child_topic );

                if( $R['topic_date'] > $T['topic_date'] )
                {
                    $T['forum_thread_newest'] = $R['topic_id'];
                    $T['forum_thread_date']   = $R['topic_date'];
                }
            }

            $threads->Insert($T);
        }

        $sql =<<<END
SELECT topic_user AS user_id, count( * ) AS user_num_posts
FROM `cc_tbl_topics`
WHERE topic_thread > 0 AND topic_user > 0
GROUP BY topic_user
END;

        $users =& CCUsers::GetTable();
        $updates = CCDatabase::QueryRows($sql);
        foreach( $updates as $update )
            $users->Update($update);
    }

    function ImportReviews($phase)
    {
        if( $phase == 1 )
        {
            $reviews = new CCReviews();
            $reviews->DeleteWhere('1');

            $uploads = new CCUploads();
            $rows = $uploads->QueryRows('upload_topic_id > 0','upload_id,upload_topic_id');
            
            $sql =<<<END
                    SELECT upload_id, post_time, post_text, poster_id, 
                                muser.user_id, user_name
                    FROM cc_tbl_uploads uploads
                    JOIN phpbb_posts      p ON upload_topic_id = p.topic_id
                    JOIN phpbb_posts_text t ON p.post_id = t.post_id
                    JOIN phpbb_users puser  ON poster_id = puser.user_id
                    JOIN cc_tbl_user muser  ON puser.username = muser.user_name
                    WHERE post_text > ''
END;

            $qr = mysql_query($sql);

            $id = 0;

            while( $row = mysql_fetch_array($qr) )
            {
                $R = array();
                $R['topic_id'] = ++$id;
                $R['topic_upload'] = $row['upload_id'];
                $R['topic_user'] = $row['user_id'];
                $R['topic_type'] = 'review';
                $R['topic_date'] = date('Y-m-d H:i:s', $row['post_time']);
                $R['topic_text'] = $this->convert_text($row['post_text']);
                $reviews->Insert($R);
            }
        }
        elseif( $phase == 2 )
        {
            $topic_tree =& CCTopicTree::GetTable();
            $topic_tree->DeleteWhere('1');

            $reviews =& CCReviews::GetTable();
            $reviews->SetSort('topic_upload,topic_date','ASC');
            $rows   = $reviews->QueryRows('');

            $sql =<<<END
                SELECT *, user_real_name
                FROM cc_tbl_topics
                JOIN cc_tbl_user ON user_id = topic_user
                ORDER BY topic_upload, topic_date ASC
END;

            $uploads =& CCUploads::GetTable();
            $current_upload = 0;
            $reviewee = 0;
            $topics = array();
            $args   = array();
            $prev_topic = 0;
            $upload_name = '';

            $qr = mysql_query($sql);

            while( $row = mysql_fetch_array($qr) )
            {
                if( $row['topic_upload'] != $current_upload )
                {
                    $topics = array();
                    $prev_topic = 0;
                    $current_upload = $row['topic_upload'];
                    $w['upload_id'] = $current_upload;
                    $urow = $uploads->QueryRow($w,'upload_user,upload_name');
                    $reviewee = $urow['upload_user'];
                    $upload_name = $urow['upload_name'];
                }
                
                $reviewer  = $row['topic_user'];
                $topic_id  = $row['topic_id'];

                if( $reviewee == $reviewer )
                {
                    if( $prev_topic == 0 )
                    {
                        $args['topic_id'] = $topic_id;
                        $args['topic_type'] = 'self_review';
                        $reviews->Update($args);
                        continue;
                    }
                }
                elseif( ($prev_topic == 0) || !array_key_exists($reviewer,$topics) )
                {
                    $topics[ $reviewer ] = $topic_id;
                    $prev_topic = $topic_id;
                    $w2['topic_id'] = $topic_id;
                    $w2['topic_name'] = sprintf('%s review of "%s"', 
                                                     $row['user_real_name'],
                                                     $upload_name );
                    continue;
                }
                    
                $args['topic_id'] = $topic_id;
                $args['topic_type'] = 'reply';
                $reviews->Update($args);

                CCTopic::Sync( $prev_topic, $topic_id );
            }
        }
        elseif( $phase == 3 )
        {
            $sql =<<<END
                UPDATE cc_tbl_user SET user_num_reviews = 0, user_num_reviewed = 0
END;
            CCDatabase::Query($sql);

            $reviews =& CCReviews::GetTable();
            $rows =& $reviews->QueryRows('','topic_user,topic_upload');
            $count = count($rows);
            for( $i = 0; $i < $count; $i++ )
            {
                CCReview::Sync($rows[$i]['topic_upload'],$rows[$i]['topic_user']);
            }
        }
        elseif( $phase == 4 )
        {
            $sql =<<<END
                SELECT user_real_name,
                       upload_name, 
                       topic_id
                       
                FROM cc_tbl_topics 
                JOIN cc_tbl_uploads ON upload_id = topic_upload
                JOIN cc_tbl_user    ON topic_user = user_id
                where topic_upload > 0
END;
            $qr = CCDatabase::QueryRows($sql);
            
            $reviews =& CCReviews::GetTable();

            $i = 0;
            while( $R = next($qr) )
            {
                $u['topic_name'] = $R['user_real_name'] . ' review of  "' . $R['upload_name'] . '"';
                $u['topic_id']   = $R['topic_id'];
                $reviews->Update($u);
                $i++;
            }

            CCPage::Prompt("updated $i records");
        }

        CCPage::Prompt("Done with import phase: $phase");
    }

    function convert_text($text)
    {
        $text= preg_replace(
                  array( 
                        '#\[(?!url=)([^:]+):[^\[]+\]#e', 
                        '#\[url=(http://[^:]+):[^\[]+\]#', 
                        '#\[/([^:]+):[^\[]+\]#e' ),
                    array( "'[' . strtolower('$1') . ']'", 
                           '[url=$1]',
                           "'[' . strtolower('/$1') . ']'",
                          ),
                    $text);

        $text= preg_replace(
                  array( 
                    '#\[size([^\]]+)]#i', 
                    '#\[/size\]#i', 
                    '#\[color([^\]]+)]#i', 
                    '#\[/color\]#i', 
                    '#\[quote([^\]]?)]#i', 
                    '#\[/quote\]#i' ), 
                 array( 
                    '[big]', 
                    '[/big]', 
                    '[blue]', 
                    '[/blue]',
                    '[quote]',
                    '[/quote]', 
                     ),
                 $text );

        return $text;
    }

}


?>