<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPlaylists',  'OnMapUrls'),          'ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPlaylists' , 'OnGetConfigFields') , 'ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCPlaylists',  'OnApiQueryFormat'),   'ccextras/cc-playlist.inc' ); 
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPlaylistHV', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCPlaylistHV', 'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCPlaylistHV', 'OnUserProfileTabs'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCPlaylistHV', 'OnUploadRow'));

class CCPlaylistHV 
{

    function OnUploadRow( &$record ) 
    {
        if( !cc_playlist_enabled() || empty($record['upload_published']) || !empty($record['upload_banned']) )
        {
            $record['fplay_url'] = '';
        }
        else
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('remote_files');
            $remoting = !empty($settings['enable_streaming']);
            $fkeys = array_keys($record['files']);
            foreach( $fkeys as $fkey )
            {
                $F =& $record['files'][$fkey];

                // Flash only understands MP3s with a 44k sample rate
                if( !empty($F['file_format_info']['sr']) && 
                    ($F['file_format_info']['format-name'] == 'audio-mp3-mp3') && 
                    ($F['file_format_info']['sr'] == '44k') )
                {
                    if( !$remoting || empty($F['file_extra']['remote_url'] ) )
                        $url = $F['download_url'];
                    else
                        $url = $F['file_extra']['remote_url'];
                    $record['fplay_url'] = $url;
                    break;
                }
            }
        }
    }

    function OnUserProfileTabs( &$tabs )
    {
        if( !cc_playlist_enabled() )
            return;
        $tabs['playlists'] = array(
                    'text' => 'Playlists',
                    'help' => 'Playlists',
                    'tags' => 'playlists',
                    'access' => CC_DONT_CARE_LOGGED_IN,
                    'function' => 'url',
                    'user_cb' => array( 'CCPlaylists', 'User' ),
                    'user_cb_mod' => 'ccextras/cc-playlist.inc',
            );
    }

    /**
    * Event handler for {@link CC_EVENT_BUILD_UPLOAD_MENU}
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu()
    */
    function OnBuildUploadMenu(&$menu)
    {
        if( !cc_playlist_enabled() )
            return;
        $menu['playlist_menu'] = 
                     array(  'menu_text'  => _('Add to Playlist'),
                             'weight'     => 130,
                             'group_name' => 'playlist',
                             'access'     => CC_MUST_BE_LOGGED_IN,
                        );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu()
    */
    function OnUploadMenu(&$menu,&$record)
    {
        if( !cc_playlist_enabled() || empty($record['upload_published']) || !empty($record['upload_banned']) )
            return;

        if( CCUser::IsLoggedIn() )
        {
            $parent_id = 'playlist_menu_' . $record['upload_id'];
            $menu['playlist_menu']['parent_id'] = $parent_id;
            $menu['playlist_menu']['action'] = "javascript://{$parent_id}";
            $menu['playlist_menu']['id']     = 'commentcommand';
            $menu['playlist_menu']['class']  = "cc_playlist_button";
            $menu['playlist_menu']['access'] = CC_MUST_BE_LOGGED_IN;
        }
        else
        {
            $menu['playlist_menu']['access'] = CC_DISABLED_MENU_ITEM;
        }
    }
}

function cc_playlist_enabled()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['enable_playlists']);
}

function CC_recent_playlists_impl()
{
    if( !cc_playlist_enabled() )
        return;

    require_once('ccextras/cc-cart-table.inc');
    $sql =<<<EOF
SELECT DISTINCT cart_id
FROM `cc_tbl_cart_items`
JOIN cc_tbl_uploads ON upload_id = cart_item_upload
JOIN cc_tbl_cart ON cart_id = cart_item_cart
WHERE (cart_user != upload_user) AND (cart_num_items > 3)
ORDER BY cart_date DESC
LIMIT 5 
EOF;
    $cart_ids = CCDatabase::QueryItems($sql);

    $carts = new CCPlaylist(true);
    $carts->SetOrder('cart_date','DESC');
    $carts->SetOffsetAndLimit(0,5);
    $where = 'cart_id in (' . join(',',$cart_ids) . ')';
    return $carts->QueryRows($where);
}

function CC_popular_playlist_tracks()
{ 
    require_once('ccextras/cc-cart-table.inc');
    /*
select count(*) as cnt , cart_item_upload , upload_name
from cc_tbl_cart_items 
join cc_tbl_uploads on upload_id = cart_item_upload
group by cart_item_upload order by cnt desc    
*/
    $items =& CCPlaylistItems::GetTable();
    $items->AddExtraColumn( 'count(*) as track_count' );
    $j1 = $items->AddJoin( new CCPlaylist(), 'cart_item_cart' );
    $uploads = new CCUploads();
    $j2 = $items->AddJoin( $uploads, 'cart_item_upload' );
    $j3 = $items->AddJoin( new CCUsers(), $j2 . '.upload_user' );
    $items->GroupOn( 'cart_item_upload' );
    $items->SetOrder( 'track_count', 'DESC' );
    $items->SetOffsetAndLimit(0,25);
    $rows = $items->QueryRows('');
    $rows = $uploads->GetRecordsFromRows($rows);
    return $rows;
}

?>