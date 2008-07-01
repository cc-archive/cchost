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
require_once('cchost_lib/ccextras/cc-cart-table.inc');

class CCPlaylists
{
    function Create()
    {
        global $CC_GLOBALS;

        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-query.php');
        require_once('cchost_lib/zend/json-encoder.php');

        CCPage::SetTitle('str_pl_create_dynamic_playlist');
        $args = array();
        $args['promo_tag'] = empty($CC_GLOBALS['playlist_promo_tag']) ? '' : $CC_GLOBALS['playlist_promo_tag'];
        $query = new CCQuery();
        $qargs = array_filter($query->ProcessUriArgs());
        $args['edit_query'] = CCZend_Json_Encoder::encode($qargs);
        $args['submit_url'] = ccl('api','query');
        $args['submit_text'] = 'str_pl_create_playlist';
        CCPage::PageArg( 'plargs', $args, 'playlist.xml/playlist_create_dyn');
    }

    function EditQuery($playlist_id='',$commit='')
    {
        $this->_test_pl(0,$playlist_id,false,true,true);
        $this->_build_bread_crumb_trail($playlist_id,
                        array(ccl('playlist','edit',$playlist_id),'str_pl_edit_properties'),'str_pl_edit_dynamic_playlist');
        $cart =& CCPlaylist::GetTable();
        $row = $cart->QueryKeyRow($playlist_id);
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle(array('str_pl_edit_dynamic_playlist_s',$row['cart_name']));
        if( empty($commit) )
        {
            parse_str($row['cart_dynamic'],$qargs);
            require_once('cchost_lib/zend/json-encoder.php');
            $args['edit_query'] = CCZend_Json_Encoder::encode($qargs);
            $args['promo_tag'] = '';
            $args['submit_url'] = ccl('playlist','editquery',$playlist_id,'commit');
            $args['submit_text'] = 'str_pl_save_playlist_query';
            CCPage::PageArg( 'playlist_create_dyn', 'playlist.xml/playlist_create_dyn' );
            CCPage::PageArg( 'plargs', $args, 'playlist_create_dyn' );
        }
        else
        {
            require_once('cchost_lib/cc-query.php');
            $query = new CCQuery();
            $qargs = array_filter($query->ProcessUriArgs());
            $qargs = $query->SerializeArgs($qargs);
            $up['cart_id'] = $playlist_id;
            $up['cart_dynamic'] = $qargs;
            $cart->Update($up);
            $url = ccl('playlist','browse',$playlist_id);
            CCUtil::SendBrowserTo($url);
        }
    }

    function OnApiQuerySetup( &$args, &$queryObj, $validate)
    {
        if( $args['format'] != 'playlist' )
            return;
        $args['template'] = 'playlist_show_one';
        $queryObj->GetSourcesFromTemplate($args['template']);

        if( !empty($args['playlist']) )
        {
            $args['playlist'] = sprintf('%0d',$args['playlist']);
            if( empty($args['playlist']) )
            {
                unset($args['playlist']);
            }
            else
            {
                if( empty($args['sort'])  )
                    $this->args['nosort'] = 1; // this is magic and will trigger a sort by cart_item_order, sry
            }
        }
    }

    function OnApiQueryFormat( &$records, $query_args, &$result, &$result_mime )
    {
        global $CC_GLOBALS;

        if( strtolower($query_args['format']) != 'playlist' )
            return;

        extract($query_args);

        $args['playlist'] = $this->_get_default_playlist();

        // strictly so add_menu() will work...
        $args['playlist']['cart_dynamic'] = $qstring = $queryObj->SerializeArgs($query_args);

        if( empty($records) )
        {
            $url = url_args( ccl('playlist','create'), $qstring );
            $link1 = "<a href=\"$url\">";
            $link2 = '</a>';
            $args['playlist']['cart_msgs'][]= array('str_pl_no_recs_match',$link1, $link2);
        }
        else
        {
            $url = url_args( ccl('api','query'), $qstring . '&f=playlist');
            $link1 = "<a href=\"$url\">";
            $link2 = '</a>';
            $args['playlist']['cart_msgs'][] = array('str_pl_dyn_msg1',$link1,$link2);
            if( CCUser::IsLoggedIn() )
            {
                $qstr = preg_replace('/promo_tag=[^&]+/','',$qstring);
                $url = url_args( ccl('playlist','save'), $qstr );
                $link1 = "<a href=\"$url\">";
                $args['playlist']['cart_msgs'][] = array('str_pl_dyn_msg2','', $link1,$link2);
            }
            $args = $this->_add_menu($args,$args['playlist']['cart_id']);
        }
        $args['records'] = $records;
        $this->_build_bread_crumb_trail($args['playlist']['cart_id']);
        CCPage::SetTitle('str_pl_dynamic_playlist');
        CCPage::PageArg('args',$args,'playlist_show_one');

        $result = true;
    }

    function User($user)
    {
        require_once('cchost_lib/cc-page.php');
        $args = $this->_args_to_json( array( 'user' => $user ) );
        CCPage::PageArg( 'playlist_show_browser', 'playlist.xml/playlist_show_browser' );
        CCPage::PageArg( 'args', $args, 'playlist_show_browser' );
    }

    function _args_to_json($args)
    {
            // user: '${pluser}', upload: ${upid},  
        require_once('cchost_lib/zend/json-encoder.php');
        return CCZend_Json_Encoder::encode($args);
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('api','playlist','new'),      array( 'CCPlaylists', 'NewPL'),   CC_MUST_BE_LOGGED_IN,   ccs(__FILE__),
         '[upload_id]', _('Create a new playlist, optionally starting with upload_id'), CC_AG_PLAYLIST );
        CCEvents::MapUrl( ccp('api','playlist','add'),      array( 'CCPlaylists', 'Add'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__),
         '{upload_id},{playlist_id}', _('Add upload to playlist'), CC_AG_PLAYLIST );
        CCEvents::MapUrl( ccp('api','playlist','browse'),   array( 'CCPlaylists', 'Browse'),  CC_DONT_CARE_LOGGED_IN, ccs(__FILE__),
         '[username]', _('Playlist playlists, optionally just for user'), CC_AG_PLAYLIST );
        CCEvents::MapUrl( ccp('api','playlist','view'),     array( 'CCPlaylists', 'View'),    CC_DONT_CARE_LOGGED_IN, ccs(__FILE__),
         '{playlist}', _('Display contents of playlist'), CC_AG_PLAYLIST );
        CCEvents::MapUrl( ccp('api','playlist','remove'),   array( 'CCPlaylists', 'Remove'),  CC_MUST_BE_LOGGED_IN,   ccs(__FILE__),
         '{upload_id},{playlist_id}', _('Remove upload from playlist'), CC_AG_PLAYLIST );
        CCEvents::MapUrl( ccp('api','playlist','bump'),     array( 'CCPlaylists', 'Bump'),    CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) ,
                   '{update_id}', _('Bump playlist count'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('api','playlist','with'),     array( 'CCPlaylists', 'With'),    CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ,
                   '{upload_id}', _('Return playlists with-w/o this upload'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('playlist','create'), array( 'CCPlaylists', 'Create'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ,
                   '', _('Display create playlist form'), CC_AG_PLAYLIST ); 
        CCEvents::MapUrl( ccp('playlist','save'),   array( 'CCPlaylists', 'Save'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ,
                   '', _('Save dynamic playlist'), CC_AG_PLAYLIST ); 
        CCEvents::MapUrl( ccp('playlist','editquery'),   array( 'CCPlaylists', 'EditQuery'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ,
                   '', _('Display edit query form'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('playlist','editorder'),   array( 'CCPlaylists', 'EditOrder'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ,
                   '', _('Display reorder playlist form'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('playlist','browse'), array( 'CCPlaylists', 'BrowsePL'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ,
                   '{playlist_id}', _('Display browse playlist UI'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('playlist','popup'), array( 'CCPlaylists', 'Popup'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ,
                   '{playlist_id}', _('Display playlist for popup'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('playlist','empty'),          array( 'CCPlaylistManage', 'EmptyPL'),   CC_MUST_BE_LOGGED_IN,   
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' ,
                   '{playlist_id}', _('Display empty playlist UI'), CC_AG_PLAYLIST ); 
        CCEvents::MapUrl( ccp('playlist','delete'),         array( 'CCPlaylistManage', 'DeletePL'),  CC_MUST_BE_LOGGED_IN,   
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' ,
                   '{playlist_id}', _('Display delete playlist form'), CC_AG_PLAYLIST ); 
        CCEvents::MapUrl( ccp('playlist','edit'),           array( 'CCPlaylistManage', 'Edit'), CC_MUST_BE_LOGGED_IN,   
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' ,
                   '{playlist_id}', _('Display edit playlist props form'), CC_AG_PLAYLIST ); 
        CCEvents::MapUrl( ccp('admin','playlists'),           array( 'CCPlaylistManage', 'Admin'), CC_ADMIN_ONLY,   
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' ,
                   '', _('Display admin playlist form'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('admin','playlists','recalc'),           array( 'CCPlaylistManage', 'Recalc'), CC_ADMIN_ONLY,   
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' ,
                   '', _('Playlist api'), CC_AG_PLAYLIST ); 

        CCEvents::MapUrl( ccp('admin','playlists','tags'), 
                          array( 'CCPlaylistManage', 'AdminTags'),  CC_ADMIN_ONLY,
                                            'cchost_lib/ccextras/cc-playlist-forms.inc' , '',
                          _('Configure playlist tags'), 
                          CC_AG_PLAYLIST );

    }

    function Save()
    {
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $qstring = $query->SerializeArgs($_GET);
        if( empty($qstring) )
            CCUtil::Send404();
        $new_name = $this->_gen_name();
        $row = $this->_create_playlist('playlist',$new_name,'',0,$qstring);
        $this->BrowsePL($row['cart_id']);
    }

    function Bump($upload_encoded='')
    {
        exit; // ugh, we need to implement this properly

        if( empty($upload_encoded) )
            CCUtil::Send404();

        global $CC_GLOBALS;
        $ip = $_SERVER['REMOTE_ADDR'];
        $user = CCUser::CurrentUser();
        $time = date('Y-m-d H:i:s');
        $yesterday = date('Y-m-d H:i:s',strtotime('1 days ago'));
        CCDatabase::Query("DELETE FROM cc_tbl_play_track WHERE play_track_date < '{$yesterday}'");

        if( preg_match('/_ep_([0-9]+)_([0-9]+)$/',$upload_encoded,$playparts) )
        {
            $playlist_id = $playparts[1];
            $upload_id = $playparts[2];
        }
        else
        {
            preg_match('/_ep_([0-9]+)$/',$upload_encoded,$playparts);
            $playlist_id = 0;
            $upload_id = $playparts[1];
        }

        $hit = CCDatabase::QueryItem("SELECT COUNT(*) FROM cc_tbl_play_track WHERE (play_track_ip='{$ip}' OR play_track_user={$user}) AND play_track_upload={$upload_id}");
        if( $hit )
            die('counted');
        $table = new CCTable('cc_tbl_play_track','play_track_ip');
        $args['play_track_ip'] = $ip;
        $args['play_track_date'] = $time;
        $args['play_track_upload'] = $upload_id;
        $args['play_track_user'] = $user;
        $table->Insert($args);
        
        CCDatabase::Query('UPDATE cc_tbl_uploads SET upload_num_plays = upload_num_plays+1 WHERE upload_id='.$upload_id);
        if( $playlist_id > 1 )
            CCDatabase::Query('UPDATE cc_tbl_cart   SET cart_num_plays   = cart_num_plays+1   WHERE cart_id='.$upload_id);

        print 'ok';
        exit;
    }

    function _build_bread_crumb_trail($playlist_id,$cmd='',$cmd2='')
    {
        list( $user_id, $playlist_name ) = CCDatabase::QueryRow(
                'SELECT cart_user, cart_name FROM cc_tbl_cart WHERE cart_id='.$playlist_id,false);
        list( $user_name, $user_real_name ) = CCDatabase::QueryRow(
            'SELECT user_name, user_real_name FROM cc_tbl_user WHERE user_id='.$user_id,false);

        $trail = array();
        $trail[] = array( 'url' => ccl(),                                'text' => 'str_home' );
        $trail[] = array( 'url' => ccl('people'),                        'text' => 'str_people' );
        $trail[] = array( 'url' => ccl('people',$user_name),             'text' => $user_real_name );
        $trail[] = array( 'url' => ccl('people',$user_name,'playlists'), 'text' => 'str_playlists' );
        if( empty($cmd) )
        {
            $trail[] = array( 'url' => '', 'text' => $playlist_name );
        }
        else
        {
            $trail[] = array( 'url' => ccl('playlist','browse',$playlist_id), 'text' => $playlist_name );
            if( is_array($cmd) )
            {
                $trail[] = array( 'url' => $cmd[0], 'text' => $cmd[1]);
                $trail[] = array( 'url' => '', 'text' => $cmd2 );
            }
            else
            {
                $trail[] = array( 'url' => '', 'text' => $cmd );
            }
        }

        require_once('cchost_lib/cc-page.php');
        CCPage::AddBreadCrumbs($trail);
    }

    function EditOrder($playlist_id='',$cmd='')
    {
        $this->_test_pl(0,$playlist_id,false,true,true);
        if( empty($cmd) )
        {
            require_once('cchost_lib/cc-page.php');
            require_once('cchost_lib/cc-query.php');
            $this->_build_bread_crumb_trail($playlist_id,'str_pl_edit_playlist_order');
            CCPage::SetTitle('str_pl_edit_playlist_order');
            CCPage::PageArg('playlist_id',$playlist_id);
            $query = new CCQuery();
            $args = $query->ProcessAdminArgs('t=playlist_reorder&playlist='.$playlist_id);
            $query->Query($args);
        }
        else
        {
            $ids = CCDatabase::QueryItems("SELECT cart_item_id FROM cc_tbl_cart_items WHERE cart_item_cart = $playlist_id ORDER by cart_item_order ASC");
            foreach( $_GET['file_order'] as $new_pos => $old_pos )
            {
                $id = $ids[$old_pos-1];
                $sql = "UPDATE cc_tbl_cart_items SET cart_item_order = $new_pos WHERE cart_item_id = $id";
                CCDatabase::Query($sql);
            }
            $url = ccl('playlist','browse',$playlist_id);
            CCUtil::SendBrowserTo($url);
        }
    }

    function Remove($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $cart_items =& CCPlaylistItems::GetTable();
        $w['cart_item_upload'] = $upload_id;
        $w['cart_item_cart'] = $playlist_id;
        $cart_items->DeleteWhere($w);
        $carts =& CCPlaylist::GetTable();
        $carts->Dec('cart_num_items',$playlist_id);
        $this->_sync_upload($upload_id);
        $args['command'] = 'delete';
        $args['upload_id'] = $upload_id;
        $args['cart_id'] = $playlist_id;
        $args[CC_AJAX_MESSAGE] = 'str_pl_track_has_been_removed';
        CCUtil::ReturnAjaxData($args);
    }

    function View($playlist_id='')
    {
        $this->_test_pl(0,$playlist_id,false,true,false);
        $args = $this->_get_playlist_info($playlist_id);
        if( empty($args) )
            CCUtil::Send404();
        $this->_output($args);
    }

    function _get_playlist_info($playlist_id,$is_popup=false)
    {
        $carts = new CCPlaylist(true);
        $args['playlist'] = $carts->QueryKeyRow($playlist_id);
        if( empty($args['playlist']) )
            return null;
        $args['playlist']['artist_page_url'] = ccl('people',$args['playlist']['user_name']);
        if( $args['playlist']['cart_dynamic'] || ($args['playlist']['cart_subtype'] == 'default') )
        {
            require_once('cchost_lib/cc-query.php');
            $query = new CCQuery();
            if( $args['playlist']['cart_subtype'] == 'default' )
                $qstring = $query->ProcessUriArgs(array('format' => 'playlist'));
            else
                $qstring = $args['playlist']['cart_dynamic'];
            $qargs = $query->ProcessAdminArgs($qstring,array('format'=>'php'),false);
            list( $args['records'] ) = $query->Query($qargs);
            $args['feed_q'] = url_args( ccl('api','query'), $qstring . '&f=rss' );
        }
        else
        {

            $purl = ccl('files') . '/';
            $ccp = ccl('people') . '/';
            $sql =<<<EOF
                SELECT upload_name, user_name, user_real_name, upload_id, upload_contest,
                  CONCAT('$ccp',user_name) as artist_page_url ,
                  CONCAT('$purl',user_name,'/',upload_id) as file_page_url
                  FROM cc_tbl_cart_items
                  JOIN cc_tbl_uploads on cart_item_upload = upload_id
                  JOIN cc_tbl_user ON upload_user = user_id 
                  WHERE cart_item_cart = $playlist_id
                  ORDER BY cart_item_order ASC
EOF;

            $args['records'] =& CCDatabase::QueryRows($sql);
            $args['feed_q'] = url_args( ccl('api','query'), 'f=rss&playlist=' . $playlist_id );
        }
        $dv = new CCDataView();
        $filters = array( 'e' => array( CC_EVENT_FILTER_FILES, CC_EVENT_FILTER_DOWNLOAD_URL, CC_EVENT_FILTER_PLAY_URL ) ); // sic
        $dv->FilterRecords($args['records'],$filters);
        return $this->_add_menu($args,$playlist_id,$is_popup);
    }

    function _add_menu($args,$playlist_id,$is_popup=false)
    {
        $is_dynamic = !empty($args['playlist']['cart_dynamic']);

        $args['menu'] = array();
        if( $is_dynamic || !empty($args['records']) )
        {
            $args['menu'][] = array( 'url'   => ccl( 'playlist', 'play', $playlist_id ),
                                     'class' => 'cc_playlist_playlink',
                                     'id'    => '_pla_' . $playlist_id,
                                     'text'  => 'str_pl_play_all_tracks' );
            if( !$is_popup )
            {
                $args['menu'][] = array( 'url'   => 'javascript://play in window',
                                         'class' => 'cc_playlist_playwindow',
                                         'id'    => '_plw_' . $playlist_id,
                                         'text'  => 'str_pl_play_in_window' );
            }
        }

        $args['is_logged_in'] = false;
        $args['is_owner'] = false;

        if( CCUser::IsLoggedIn() )
        {
            $user = CCUser::CurrentUser();
            $args['is_logged_in'] = true;

            if( !$is_popup )
            {
                if( $args['playlist']['cart_user'] ==  $user )
                {
                    $args['is_owner'] = true;
                    $args['menu'][] = array( 'url' => ccl( 'playlist', 'edit', $playlist_id ),
                                                'class' => '',
                                                'id'    => '',
                                                'text' => 'str_pl_edit_properties' );

                    if( !$is_dynamic && !empty($args['records']) )
                    {
                        $args['menu'][] = array( 'url' => ccl( 'playlist', 'editorder', $playlist_id ),
                                                                'class' => '',
                                                                'id'    => '',
                                                                'text' => 'str_pl_reorder playlist' );

                        $args['menu'][] = array( 'url' => ccl( 'playlist', 'empty', $playlist_id ),
                                                                'class' => '',
                                                                'id'    => '',
                                                                'text' => 'str_pl_empty_playlist');
                    }

                    if( $args['playlist']['cart_subtype'] != 'favorites' )
                    {
                        $args['menu'][] = array( 'url' => ccl( 'playlist', 'delete', $playlist_id ),
                                                'class' => '',
                                                'id'    => '',
                                                'text' => 'str_pl_delete_playist');
                    }
                }
            }
        }

        return $args;
    }

    function With($upload_id='')
    {
        $this->_test_pl($upload_id,0,true,false,false);
        $ret =& $this->_playlist_with($upload_id);
        $this->_output($ret);
    }

    function & _playlist_with($upload_id)
    {
        $cart = new CCPlaylist();
        $wuser['cart_user'] = $user = CCUser::CurrentUser();
        $wuser['cart_dynamic'] = '';
        $all_my_carts = $cart->QueryKeys($wuser);
        if( empty($all_my_carts) )
        {
            global $CC_GLOBALS;

            $title = sprintf( _("%s's Favorites"), $CC_GLOBALS['user_real_name'] );
            $ret['without'][] = $this->_create_playlist('favorites',$title);
            $ret['with'] = array();
        }
        else
        {
            $where = "cart_item_upload = {$upload_id} AND cart_item_cart IN (" . join(',',$all_my_carts) . ")";
            $cart_items =& CCPlaylistItems::GetTable();
            $carts_with = $cart_items->QueryItems( 'cart_item_cart', $where );
            if( empty($carts_with) )
            {
                $carts_with = array();
                $carts_without = $all_my_carts;
            }
            else
            {
                $carts_without = array_diff( $all_my_carts, $carts_with );
            }
            $ret['without'] = $cart->QueryKeyRows($carts_without);
            $ret['with']    = $cart->QueryKeyRows($carts_with);
        }
        $ret['upload_id'] = $upload_id;
        return $ret;
    }

    /**
    * Top level browse playlist(s) - mapped to playlist/browse/{playlist_id}
    *
    */
    function BrowsePL($playlist_id='')
    {
        require_once('cchost_lib/cc-page.php');

        $playlist_id = sprintf("%0d",CCUtil::Strip($playlist_id));
        $fulllist = empty($playlist_id) || ($playlist_id < 1);
        if( $fulllist )
        {
            if( !empty($_GET['id']) )
            {
                $upload_id = intval(CCUtil::Strip($_GET['id']));
                if( empty($upload_id) || ($upload_id < 1) )
                    CCUtil::Send404();
                $uploads =& CCUploads::GetTable();
                if( !$uploads->KeyExists($upload_id) )
                    CCUtil::Send404();
                $name = $uploads->QueryItemFromKey('upload_name',$upload_id);
                $args['upload'] = $upload_id;
                CCPage::SetTitle(array('str_pl_browse_playlists_s',$name) );
            }
            else
            {
                if( !empty($_GET['min']) )
                    $args['min'] = intval($_GET['min']);
                if( !empty($_GET['since']) )
                    $args['since'] = date('Y-m-d', strtotime($_GET['since']));
                if( !empty($_GET['offset']) )
                    $args['offset'] = intval($_GET['offset']);
                $args['hot'] = !empty($_GET['hot']);
                if( $args['hot'] )
                    $title = 'str_pl_hot_playlists';
                else
                    $title = 'str_pl_new_playlists';
                CCPage::SetTitle($title);
            }

            $args = $this->_args_to_json($args);
            CCPage::PageArg('playlist_show_browser', 'playlist.xml/playlist_show_browser' );
            CCPage::PageArg('args',$args,'playlist_show_browser');
        }
        else
        {
            $this->_test_pl(0,$playlist_id,false,true,false);
            $this->_build_bread_crumb_trail($playlist_id);
            $args = $this->_get_playlist_info($playlist_id);
            if( empty($args) )
                CCUtil::Send404();
            CCPage::SetTitle(array(_('str_pl_playlist_s'),$args['playlist']['cart_name']) );
            CCPage::PageArg('args',$args,'playlist_show_one');
        }
    }

    function Popup($playlist_id='')
    {
        if( empty($playlist_id) )
            $playlist_id = $this->_get_default_playlist(true);

        $args = $this->_get_playlist_info($playlist_id,true);

        if( empty($args) )
            CCUtil::Send404();
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle( array('str_pl_playlist_s',$args['playlist']['cart_name']) );
        CCPage::PageArg('playlist_popup_window', 'playlist.xml/playlist_popup_window' );
        CCPage::PageArg('args',$args,'playlist_popup_window');
        CCPage::ShowHeaderFooter( false, false  );
    }

    /**
    *  Calling from ajax to browse playlists
    *
    */
    function Browse($user='')
    {
        $where = '1 '; 

        $carts = new CCPlaylist(true);
        $carts->AddExtraColumn("REPLACE(SUBSTR(cart_tags,1,110), ',', ' ') as cart_tags_munged");

        if( empty($user) )
        {
            $where .= 'AND (cart_subtype <> "default")';
            if( !empty($_GET['min']) )
                $where .= " AND (cart_num_items >= '{$_GET['min']}')";
        }
        else
        {
            $where .= ' AND cart_user = ' . CCUser::IDFromName($user);
        }

        if( empty($_GET['hot']) )
            $carts->SetOrder('cart_date','desc');
        else
            $carts->SetOrder('cart_num_plays','desc');

        if( !empty($_GET['since']) )
            $where .= " AND cart_date >= '{$_GET['since']}'";

        if( empty($_GET['upload_id']) )
        {
            require_once('cchost_lib/cc-page.php');
            $offset = empty($_GET['offset']) ? 0 : $_GET['offset'];
            $limit  = empty($_GET['limit']) ? 20 : $_GET['limit'];
            $carts->SetOffsetAndLimit($offset,$limit);
            $args = CCPage::AddPagingLinks($carts,$where,$limit);
        }
        else
        {
            $upload_id = CCUtil::Strip($_GET['upload_id']);
            $this->_test_pl($upload_id,0,true,false,false);
            $uploads =& CCUploads::GetTable();

            // this will weed out banned and non-published uploads (and bad ids)
            $record = $uploads->QueryKeyRow($upload_id);
            if( empty($record) )
                CCUtil::Send404();
            $items = new CCPlaylistItems();
            $items->_key_field = 'cart_item_cart'; // here's that hack again
            $carts->AddJoin( $items, 'cart_id' );
            $where .= ' AND cart_item_upload = ' . $upload_id;
            $args = array();
        }
        
        $args['records'] = $carts->QueryRows($where);

        $this->_output($args,'',true);
    }

    function _add($upload_id='',$playlist_id='')
    {
        $cart_items =& CCPlaylistItems::GetTable();
        $check['cart_item_upload']   = $upload_id;
        $check['cart_item_cart'] = $playlist_id;
        $check_row = $cart_items->CountRows($check);
        if( $check_row )
        {
            CCUtil::ReturnAjaxMessage('str_pl_upload_is_already',CC_AJAX_WARNING);
        }

        $carts =& CCPlaylist::GetTable();
        $carts->Inc('cart_num_items',$playlist_id);

        $cart_items =& CCPlaylistItems::GetTable();
        $new_row['cart_item_cart'] = $playlist_id;
        $count = $cart_items->CountRows($new_row);
        $new_row['cart_item_upload'] = $upload_id;
        $new_row['cart_item_order'] = $count;
        $cart_items->Insert($new_row);

        $this->_sync_upload($upload_id);
    }

    function _sync_upload($upload_id)
    {
        $sql =<<<EOF
        UPDATE cc_tbl_uploads SET upload_num_playlists = (
            SELECT COUNT( * )
            FROM cc_tbl_cart_items
            WHERE cart_item_upload = upload_id
            )
        WHERE upload_id = {$upload_id}
EOF;
        CCDatabase::Query($sql);
    }

    function Add($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $this->_add($upload_id,$playlist_id);
        CCUtil::ReturnAjaxMessage('str_pl_track_added');
    }
 
    function _get_default_playlist($id_only=false)
    {
        $ccp = ccl('people');

        $cart_fields =<<<EOF
cc_tbl_cart.*, user_real_name,
                  CONCAT('$ccp','/',user_name) as artist_page_url,
                  DATE_FORMAT(cart_date, '%W, %M %e, %Y @ %l:%i %p') as cart_date_format
EOF;

        if( $id_only )
        {
            $sql =<<<EOF
                SELECT cart_id
                  FROM cc_tbl_cart
                  JOIN cc_tbl_user ON cart_user = user_id 
                  WHERE cart_type = 'playlist' AND cart_subtype = 'default'
                  LIMIT 1
EOF;
            $id = CCDatabase::QueryItem($sql);
            return $id;
        }
        else
        {
            $sql =<<<EOF
                SELECT {$cart_fields}
                  FROM cc_tbl_cart
                  JOIN cc_tbl_user ON cart_user = user_id 
                  WHERE cart_type = 'playlist' AND cart_subtype = 'default'
                  LIMIT 1
EOF;
            $row = CCDatabase::QueryRow($sql);
            if( $row )
                return $row;

        }

        // find an admin
        $users =& CCUsers::GetTable();
        $w2['user_name'] = 'admin';
        $admin_id = $users->QueryKey($w2);
        if( empty($admin_id) )
        {
            $config =& CCConfigs::GetTable();
            $settings = $config->GetConfig('settings');
            $admins = preg_split('/(\W|,)/', $settings['admins'] );
            $w2['user_name'] = $admins[0];
            $admin_id = $users->QueryKey($w2);
            if( empty($admin_id) )
                CCUtil::Send404();
        }
    
        $newrow = $this->_create_playlist('default','str_pl_dynamic_playlist','',$admin_id);

        if( $id_only )
            return $new_row['cart_id'];

        $sql =<<<EOF
            SELECT {$cart_fields}
              FROM cc_tbl_cart
              JOIN cc_tbl_user ON cart_user = user_id 
              WHERE cart_id = {$newrow['cart_id']}
              LIMIT 1
EOF;
        $row = CCDatabase::QueryRow($sql);
        return $row;
    }

    function & _create_playlist($sub_type='',$title_str = '',$desc ='',$user=0,$query='')
    {
        global $CC_GLOBALS;

        $carts =& CCPlaylist::GetTable();
        $new_row['cart_id']      = $carts->NextID();
        $new_row['cart_user']    = $user ? $user : CCUser::CurrentUser();
        $new_row['cart_type']    = 'playlist';
        $new_row['cart_subtype'] = $sub_type;
        $new_row['cart_name']    = $title_str;
        $new_row['cart_desc']    = $desc;
        $new_row['cart_date']    = date('Y-m-d H:i:s');
        $new_row['cart_dynamic'] = $query;
        $new_row['cart_tags']    = '';
        $carts->Insert($new_row);
        return $new_row;
    }

    function NewPL($upload_id='')
    {
        $msg = 'str_pl_new_playlist_created';

        if( !empty($_REQUEST['cart_name']) )
            $new_name = CCUtil::Strip($_REQUEST['cart_name']);

        if( empty($new_name) )
            $new_name = $this->_gen_name();

        $desc = empty($_REQUEST['cart_desc']) ? '' : CCUtil::Strip($_REQUEST['cart_desc']);

        $new_row =& $this->_create_playlist('',$new_name,$desc);
        $playlist_id = $new_row['cart_id'];

        if( !empty($upload_id) )
        {
            $this->_test_pl($upload_id,$playlist_id,true,true,true);
            $this->_add($upload_id,$playlist_id);
            $msg = 'str_pl_and_track_added';
        }

        CCUtil::ReturnAjaxMessage($msg);
    }

    function _gen_name()
    {
        global $CC_GLOBALS;

        $carts =& CCPlaylist::GetTable();
        $wuser['cart_user'] = CCUser::CurrentUser();
        $num = $carts->CountRows($wuser);
        return sprintf( _("%s's Collection (%d) "),$CC_GLOBALS['user_real_name'], $num + 1);
    }

    function _output($obj,$pmacro='',$merge=false)
    {
        $format = CCUtil::Strip($_GET['f']);
        if( empty($format) )
            $format = empty($pmacro) ? 'json' : 'html';

        switch( $format )
        {
            case 'json':
                require_once('cchost_lib/zend/json-encoder.php');
                $text = CCZend_Json_Encoder::encode($obj);
                header( "X-JSON: $text");
                header( 'Content-type: text/plain');
                print($text);
                break;

            case 'html':
                require_once('cchost_lib/cc-template.php');
                $template = CCUtil::Strip($_GET['t']);
                if( empty($template) )
                    $template = 'playlist.xml';
                else if( strpos( $template, '.xml' ) === false )
                    $template .= '.xml';
                $macro = CCUtil::Strip($_GET['m']);
                if( !empty($macro) )
                {
                    $macro = '/' . $macro;
                }
                $tobj = new CCSkinMacro($template . $macro);
                if( empty($tobj) )
                {
                    print(" $template / $macro not found <br />\n" );
                    CCUtil::Send404();
                }

                $targs['macro'] = $macro;
                $targs['root-url'] = cc_get_root_url() . '/';
                $targs['home-url'] = ccl();
                if( $merge )
                    $targs = array_merge($targs,$obj);
                else
                    $targs['args'] = $obj;
                $tobj->SetAllAndPrint($targs);
                break;
        }
        exit;
    }

    function _send_prompt($msg)
    {
        $url = ccl('people', CCUser::CurrentUserName(), 'playlists' );
        $link = "<a href=\"$url\">";
        $link2 = '</a>';
        CCPage::Prompt(array('str_pl_return_to',$msg,$link,$link2));
    }

    function _test_pl($upload_id,$playlist_id,$test_upload,$test_playlist,$test_owner)
    {
        $ok = true;
        if( $test_upload )
        {
            $upload_id = intval(CCUtil::Strip($upload_id));
            $ok = !empty($upload_id) && ($upload_id > 0);
        }
        if( $ok && $test_playlist )
        {
            $playlist_id = intval(CCUtil::Strip($playlist_id));
            $ok = !empty($playlist_id) && ($playlist_id > 0);
            if( $ok )
                $ok = CCDatabase::QueryItem('SELECT COUNT(*) FROM cc_tbl_cart WHERE cart_id='.$playlist_id);
        }
        if( $ok && $test_owner )
        {
            $owner = CCDatabase::QueryItem('SELECT cart_user FROM cc_tbl_cart WHERE cart_id='.$playlist_id);
            $ok = !empty($owner) && ($owner == CCUser::CurrentUser());
        }
        if( !$ok )
        {
            CCUtil::Send404();
        }
        return $ok;
    }

    /**
    * Event hander for {@link CC_EVENT_DELETE_UPLOAD}
    * 
    * @param array $record Upload database record
    */
    function OnUploadDelete(&$record)
    {
        $sql =<<<EOF
            SELECT cart_item_cart
            FROM cc_tbl_cart_items
            WHERE cart_item_upload = {$record['upload_id']}
EOF;
        $carts = CCDatabase::QueryItems($sql);
        if( !empty($carts) )
        {
            $cart_set = join(',',$carts);
            $sql =<<<EOF
                UPDATE cc_tbl_cart 
                SET cart_num_items = cart_num_items-1
                WHERE cart_id IN ({$cart_set})
EOF;
            CCDatabase::Query($sql);
            $sql =<<<EOF
                DELETE FROM cc_tbl_cart_items
                WHERE cart_item_upload = {$record['upload_id']}
EOF;
            CCDatabase::Query($sql);
        }
    }
}

?>