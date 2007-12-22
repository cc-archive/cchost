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
* Implements playlist feature
*
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*
*/
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPlaylists',  'OnMapUrls'),          'ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCPlaylists',  'OnApiQueryFormat'),   'ccextras/cc-playlist.inc' ); 
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCPlaylists',  'OnUploadDelete'),     'ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPlaylistHV', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCPlaylistHV', 'OnUserProfileTabs'));

CCEvents::AddHandler(CC_EVENT_FILTER_PLAY_URL,        array( 'CCPlaylistHV', 'OnFilterPlayURL'));

//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPlaylists' , 'OnGetConfigFields') , 'ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCPlaylistManage',  'OnAdminMenu'),     'ccextras/cc-playlist-forms.inc' );


class CCPlaylistHV 
{

    function OnFilterPlayURL( &$records ) 
    {
        if( !cc_playlist_enabled() )
            return;

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('remote_files');
        $remoting = !empty($settings['enable_streaming']);
        $c = count($records);
        $k = array_keys($records);
        for( $i = 0; $i < $c; $i++ )
        {
            $rec =& $records[$k[$i]];
            $cf = count($rec['files']);
            $ck = array_keys($rec['files']);
            for( $n = 0; $n < $cf; $n++ )
            {
                $R =& $rec['files'][$ck[$n]];

                foreach( array('file_extra','file_format_info') as $f )
                    if( is_string($R[$f]) )
                        $R[$f] = unserialize($R[$f]);
                if( !empty($R['file_format_info']['sr']) && 
                    ($R['file_format_info']['format-name'] == 'audio-mp3-mp3') && 
                    ($R['file_format_info']['sr'] == '44k') )
                {
                    if( !$remoting || empty($R['file_extra']['remote_url'] ) )
                        $url = $R['download_url'];
                    else
                        $url = $R['file_extra']['remote_url'];
                    $rec['fplay_url'] = $url;
                    break;
                }
            }
        }
    }


    function OnUserProfileTabs( &$tabs, &$record )
    {
        if( !cc_playlist_enabled() )
            return;

        if( empty($record['user_id']) )
        {
            $tabs['playlists'] = 'Playlists';
            return;
        }


        require_once('ccextras/cc-cart-table.inc');
        $carts = new CCPlaylist(false);
        $w['cart_user'] = $record['user_id'];
        $num = $carts->CountRows($w);
        if( empty($num) )
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
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    */
    function OnUploadMenu(&$menu,&$record)
    {
        if( !cc_playlist_enabled() || !CCUser::IsLoggedIn() || empty($record['upload_published']) || !empty($record['upload_banned']) )
            return;

        $menu['playlist_menu'] = 
                     array(  'menu_text'  => _('Add to Playlist'),
                             'weight'     => 130,
                             'group_name' => 'playlist',
                             'access'     => CC_MUST_BE_LOGGED_IN,
                        );
        $parent_id = 'playlist_menu_' . $record['upload_id'];
        $menu['playlist_menu']['parent_id'] = $parent_id;
        $menu['playlist_menu']['action'] = "javascript://{$parent_id}";
        $menu['playlist_menu']['id']     = 'commentcommand';
        $menu['playlist_menu']['class']  = "cc_playlist_button";
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

	if( empty($cart_ids) )
	{
		$playlists = $carts->QueryRows('');
	}
	else
	{
		$where = 'cart_id in (' . join(',',$cart_ids) . ')';
		$playlists = $carts->QueryRows($where);
	}

	return $playlists;
}

function CC_hot_playlists_impl($since)
{
    require_once('ccextras/cc-cart-table.inc');
    $carts =& new CCPlaylists(true); // true means include user info
    $carts->SetOrder('cart_num_plays','DESC');
    if( $since )
    {
        $date = date('Y-m-d', strtotime($since));
        $where = "cart_date >= '$date'";
    }
    else
    {
        $where = '';
    }
    return $carts->GetRecords($where);
}

?>