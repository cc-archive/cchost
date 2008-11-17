<?

CCEvents::AddHandler(CC_EVENT_FILTER_CART_MENU,   'playlist_feature_OnFilterCartMenu');
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           'playlist_feature_OnMapUrls');

function playlist_feature_OnFilterCartMenu(&$records)
{
    if( empty($records[0]['cart_id']) || !CCUser::IsAdmin() )
        return;

    $row =& $records[0];
    $playlist_id = $row['cart_id'];

    $row['menu'][] = array( 'url'   => ccl( 'playlist', 'feature', $playlist_id ),
                             'class' => 'cc_playlist_playlink',
                             'id'    => '_feat_' . $playlist_id,
                             'text'  => '*Feature' );
}

function playlist_feature_OnMapUrls()
{
    CCEvents::MapUrl( ccp('playlist', 'feature'), 'playlist_feature_playlist', CC_ADMIN_ONLY, ccs(__FILE__) );
}

function playlist_feature_playlist($playlist_id)
{
    require_once('cchost_lib/ccextras/cc-topics.inc');

    $text =<<<EOF
[left][query=t=avatar&u=mcjackinthebox][/query][/left][query=t=playlist_2_info&ids=%id%][/query]
[query=t=yahoo_black&playlist=%id%][/query]
EOF;

    $topics = new CCTopics();
    $values['topic_id'] = $topics->NextID();
    $values['topic_name']  = addslashes(CCDatabase::QueryItem('SELECT cart_name FROM cc_tbl_cart WHERE cart_id='.$playlist_id));
    if( preg_match('/cool music/i', $values['topic_name'] ) )
    {
        $last_cool_topic = CCDatabase::QueryItem('SELECT topic_name FROM cc_tbl_topics WHERE topic_name LIKE \'ccMixter Radio%\''.
                                                  ' ORDER BY topic_date DESC LIMIT 1');
        if( !empty($last_cool_topic) )
        {
            if( preg_match('/([0-9]+)(?:[^0-9]|$)/',$last_cool_topic,$m) )
            {
                $values['topic_name'] = 'ccMixter Radio ep. ' . ($m[0] + 1);
            }
        }
    }
    $values['topic_upload'] = 0;
    $values['topic_thread'] = 0;
    $values['topic_date'] = date('Y-m-d H:i:s',time());
    $values['topic_user'] = CCUser::CurrentUser();
    $values['topic_type'] = 'feat_playlist';
    $values['topic_text'] = str_replace('%id%', $playlist_id, $text );
    $topics->Insert($values,0);
    CCUtil::SendBrowserTo( ccl('view','media','playlists') );
}

?>