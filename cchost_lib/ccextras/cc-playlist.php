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
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPlaylists',  'OnMapUrls'),          'cchost_lib/ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_API_QUERY_SETUP,    array( 'CCPlaylists',  'OnApiQuerySetup'),    'cchost_lib/ccextras/cc-playlist.inc' ); 
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCPlaylists',  'OnApiQueryFormat'),   'cchost_lib/ccextras/cc-playlist.inc' ); 
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCPlaylists',  'OnUploadDelete'),     'cchost_lib/ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPlaylistHV', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_USER_PROFILE_TABS,  array( 'CCPlaylistHV', 'OnUserProfileTabs'));
CCEvents::AddHandler(CC_EVENT_FILTER_MACROS,      array( 'CCPlaylistHV', 'OnFilterMacros')      );
CCEvents::AddHandler(CC_EVENT_FILTER_USER_PROFILE,array( 'CCPlaylistHV', 'OnFilterUserProfile') );


CCEvents::AddHandler(CC_EVENT_FILTER_PLAY_URL,        array( 'CCPlaylistHV', 'OnFilterPlayURL'));

//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPlaylists' , 'OnGetConfigFields') , 'cchost_lib/ccextras/cc-playlist.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCPlaylistManage',  'OnAdminMenu'),     'cchost_lib/ccextras/cc-playlist-forms.inc' );


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


    /**
    * Event handler for {@link CC_EVENT_FILTER_USER_PROFILE}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnFilterUserProfile(&$rows)
    {
        if( !cc_playlist_enabled() )
            return;

        $row =& $rows[0];
        $sql =<<<EOF
        SELECT sum( upload_num_playlists )
            FROM cc_tbl_uploads
            WHERE upload_user = {$row['user_id']}
EOF;
        $count = CCDatabase::QueryItem($sql);
        if( !$count )
            return;

        require_once('cchost_lib/cc-page.php');
        $page =& CCPage::GetPage();
        $title = $page->String( array( 'str_pl_user_title', $row['user_real_name'] ) );
        $url = url_args( ccl('api','query'), 'sort=num_playlists&t=pop_playlists&user=' . $row['user_name'] . '&title=' . $title);

        if( $count == 1 )
            $value = array('str_pl_user_num',$row['user_real_name'], "<a href=\"$url\">", '</a>');
        else
            $value = array('str_pl_user_nums',$row['user_real_name'], "<a href=\"$url\">",$count,'</a>');

        $row['user_fields'][] = array( 'label' => 'str_playlists', 
                                       'value' => $value
                                      );

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


        require_once('cchost_lib/ccextras/cc-cart-table.inc');
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
                    'user_cb_mod' => 'cchost_lib/ccextras/cc-playlist.inc',
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

    function OnFilterMacros(&$records)
    {
        if( !cc_playlist_enabled() )
            return;

        $k = array_keys($records);
        $c = count($k);

        if( $c && !isset($records[$k[0]]['upload_num_playlists']) )
        {
            // NOTE: this code should probably in cc-filter somewhere ? maybe ?

            // there's no playlist info in the record, we
            // have to dig it out

            if( !isset($records[$k[0]]['upload_id']) )
            {
                // there's nothing we can do...
                return;
            }
            $ids = array();
            for( $i = 0; $i < $c; $i++ )
                $ids[] = $records[$k[$i]]['upload_id'];
            $plcs = CCDatabase::QueryRows( 'SELECT upload_id,upload_num_playlists FROM cc_tbl_uploads WHERE upload_id IN (' .
                                              join(',',$ids) . ')' );
            $plcounts = array();
            foreach( $plcs as $plc )
                $plcounts[$plc['upload_id']] = $plc['upload_num_playlists'];
            for( $i = 0; $i < $c; $i++ )
            {
                $R =& $records[$k[$i]];
                $R['upload_num_playlists'] = $plcounts[ $R['upload_id'] ];
            }
        }

        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];

            if( empty($R['upload_num_playlists']) )
                continue;

            $R['file_macros'][] = 'file_macros.php/print_num_playlists';
        }
    }


}

function cc_playlist_enabled()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['enable_playlists']);
}

?>