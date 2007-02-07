<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('ccextras/cc-cart-table.inc');

class CCPlaylists
{
    function Create()
    {
        global $CC_GLOBALS;

        require_once('cclib/cc-page.php');
        require_once('cclib/cc-query.php');
        require_once('cclib/zend/json-encoder.php');

        CCPage::SetTitle(_('Create Dynamic Playlist'));
        $args = array();
        $args['promo_tag'] = empty($CC_GLOBALS['playlist_promo_tag']) ? '' : $CC_GLOBALS['playlist_promo_tag'];
        $query = new CCQuery();
        $qargs = array_filter($query->ProcessUriArgs());
        $args['edit_query'] = CCZend_Json_Encoder::encode($qargs);
        $args['submit_url'] = ccl('api','query');
        $args['submit_text'] = _('Create Playlist');
        CCPage::PageArg( 'playlist_create_dyn', 'playlist.xml/playlist_create_dyn' );
        CCPage::PageArg( 'plargs', $args, 'playlist_create_dyn' );
    }

    function EditQuery($playlist_id='',$commit='')
    {
        $this->_test_pl(0,$playlist_id,false,true,true);
        $cart =& CCPlaylist::GetTable();
        $row = $cart->QueryKeyRow($playlist_id);
        require_once('cclib/cc-page.php');
        CCPage::SetTitle(sprintf(_('Edit Dynamic Playlist: "%s"'),$row['cart_name']));
        if( empty($commit) )
        {
            parse_str($row['cart_dynamic'],$qargs);
            require_once('cclib/zend/json-encoder.php');
            $args['edit_query'] = CCZend_Json_Encoder::encode($qargs);
            $args['promo_tag'] = '';
            $args['submit_url'] = ccl('playlist','editquery',$playlist_id,'commit');
            $args['submit_text'] = _('Save Playlist Query');
            CCPage::PageArg( 'playlist_create_dyn', 'playlist.xml/playlist_create_dyn' );
            CCPage::PageArg( 'plargs', $args, 'playlist_create_dyn' );
        }
        else
        {
            require_once('cclib/cc-query.php');
            $query = new CCQuery();
            $qargs = array_filter($query->ProcessUriArgs());
            $qargs = $query->SerializeArgs($qargs);
            $up['cart_id'] = $playlist_id;
            $up['cart_dynamic'] = $qargs;
            $cart->Update($up);
            $this->_send_prompt(_('Dynamic playlist changed.'));
        }
    }

    function OnApiQueryFormat( &$records, $query_args, &$result, &$result_mime )
    {
        global $CC_GLOBALS;

        if( strtolower($query_args['format']) != 'playlist' )
            return;

        extract($query_args);
        $results = array();
        $query = new CCQuery();

        $args['playlist'] = $this->_get_default_playlist();

        // strictly so add_menu() will work...
        $args['playlist']['cart_dynamic'] = $qstring;

        if( empty($records) )
        {
            $url = url_args( ccl('playlist','create'), $qstring );
            $link1 = "[url=$url]";
            $link2 = '[/url]';
            $msg1 = sprintf(_('No records match your dynamic playlist query. You can try again by modifiying your query %shere%s'),
                                  $link1, $link2);
        }
        else
        {
            $url = url_args( ccl('api','query'), $qstring . '&f=playlist');
            $link1 = "[url=$url]";
            $link2 = '[/url]';
            $msg1 = sprintf(_('This is a \'dynamic\' playlist which means it will change depending on the latest uploads to this site. You can bookmark this playlist, or you can drag %sthis link%s to your browser toolbar. '),$link1,$link2);
            if( CCUser::IsLoggedIn() )
            {
                $wpromo = empty($CC_GLOBALS['playlist_promo_tag']) ? '' : _('(without the promo tracks)');
                $qstr = preg_replace('/promo_tag=[^&]+/','',$qstring);
                $url = url_args( ccl('playlist','save'), $qstr );
                $link1 = "[url=$url]";
                $msg1 .= "\n\n" . sprintf(_('You can also save this playlist %s to customize it by clicking %shere%s'),
                                        $wpromo, $link1,$link2);
            }
            $args = $this->_add_menu($args,$args['playlist']['cart_id']);
        }
        $args['records'] = $records;
        $args['playlist']['cart_desc'] = $msg1;
        CCPage::SetTitle(_('Dynamic Playlist'));
        CCPage::PageArg('playlist_show_one', 'playlist.xml/playlist_show_one' );
        CCPage::PageArg('args',$args,'playlist_show_one');

        $result = true;
    }

    function User($user)
    {
        require_once('cclib/cc-page.php');
        $args['playlist_user'] = $user;
        CCPage::PageArg( 'playlist_show_browser', 'playlist.xml/playlist_show_browser' );
        CCPage::PageArg( 'plargs', $args, 'playlist_show_browser' );
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('api','playlist','new'),      array( 'CCPlaylists', 'NewPL'),   CC_MUST_BE_LOGGED_IN,   ccs(__FILE__)); 
        CCEvents::MapUrl( ccp('api','playlist','add'),      array( 'CCPlaylists', 'Add'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','browse'),   array( 'CCPlaylists', 'Browse'),  CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','view'),     array( 'CCPlaylists', 'View'),    CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','remove'),   array( 'CCPlaylists', 'Remove'),  CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','bump'),     array( 'CCPlaylists', 'Bump'),    CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) ); 

        CCEvents::MapUrl( ccp('api','playlist','with'),     array( 'CCPlaylists', 'With'),    CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','moveup'),   array( 'CCPlaylists', 'MoveUp'),  CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('api','playlist','movedown'), array( 'CCPlaylists', 'MoveDown'),CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 

        CCEvents::MapUrl( ccp('playlist','create'), array( 'CCPlaylists', 'Create'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('playlist','save'),   array( 'CCPlaylists', 'Save'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 
        CCEvents::MapUrl( ccp('playlist','editquery'),   array( 'CCPlaylists', 'EditQuery'),     CC_MUST_BE_LOGGED_IN,   ccs(__FILE__) ); 

        CCEvents::MapUrl( ccp('playlist','browse'), array( 'CCPlaylists', 'BrowsePL'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ); 

        CCEvents::MapUrl( ccp('playlist','popup'), array( 'CCPlaylists', 'Popup'),   CC_DONT_CARE_LOGGED_IN,   ccs(__FILE__) ); 

        CCEvents::MapUrl( ccp('playlist','empty'),          array( 'CCPlaylistManage', 'EmptyPL'),   CC_MUST_BE_LOGGED_IN,   
                                            'ccextras/cc-playlist-forms.inc' ); 
        CCEvents::MapUrl( ccp('playlist','delete'),         array( 'CCPlaylistManage', 'DeletePL'),  CC_MUST_BE_LOGGED_IN,   
                                            'ccextras/cc-playlist-forms.inc' ); 
        CCEvents::MapUrl( ccp('playlist','edit'),           array( 'CCPlaylistManage', 'Edit'), CC_MUST_BE_LOGGED_IN,   
                                            'ccextras/cc-playlist-forms.inc' ); 

            /*, 'ccextras/cc-forums.inc', '{user_name}', 
            _('Display forum topics for user'), CC_AG_FORUMS ); */
    }

    function Save()
    {
        require_once('cclib/cc-query.php');
        $query = new CCQuery();
        $qstring = $query->SerializeArgs($_GET);
        if( empty($qstring) )
            CCUtil::Send404();
        $new_name = $this->_gen_name();
        $row = $this->_create_playlist('playlist',$new_name,'',0,$qstring);
        $this->BrowsePL($row['cart_id']);
    }

    function Bump($upload_id='')
    {
        if( empty($upload_id) )
            CCUtil::Send404();
        global $CC_GLOBALS;
        $fname = $CC_GLOBALS['logfile-dir'] . 'bump_count.txt';
        $ip = $_SERVER['REMOTE_ADDR'];
        $user = CCUser::CurrentUserName();
        $file = fopen($fname,'a');
        $time = date('Y-m-d H:i:s');
        fwrite( $file, "$time,$upload_id,$user,$ip\n");
        fclose($file);
        chmod($fname, 0777 );
        exit;
    }

    function MoveUp($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $cart_items =& CCPlaylistItems::GetTable();
        $w['cart_item_cart'] = $playlist_id;
        $w['cart_item_upload'] = $upload_id;
        $cart_item = $cart_items->QueryRow($w);
        $w2['cart_item_cart'] = $playlist_id;
        $w2['cart_item_order'] = intval($cart_item['cart_item_order']) - 1;
        $other_pos = $cart_items->QueryKey($w2);
        $cart_items->Inc('cart_item_order',$other_pos);
        $cart_items->Dec('cart_item_order',$cart_item['cart_item_id']);
        $cmd['refresh'] = 1;
        $this->_output($cmd);
    }

    function MoveDown($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $cart_items =& CCPlaylistItems::GetTable();
        $w['cart_item_cart'] = $playlist_id;
        $w['cart_item_upload'] = $upload_id;
        $cart_item = $cart_items->QueryRow($w);
        $w2['cart_item_cart'] = $playlist_id;
        $w2['cart_item_order'] = intval($cart_item['cart_item_order']) + 1;
        $other_pos = $cart_items->QueryKey($w2);
        $cart_items->Dec('cart_item_order',$other_pos);
        $cart_items->Inc('cart_item_order',$cart_item['cart_item_id']);
        $cmd['refresh'] = 1;
        $this->_output($cmd);
    }

    function Remove($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $cart_items =& CCPlaylistItems::GetTable();
        $w['cart_item_upload'] = $upload_id;
        $w['cart_item_cart'] = $playlist_id;
        $cart_items->DeleteWhere($w);
        $carts =& CCPlaylist::GetTable();
        $carts->Inc('cart_num_items',$playlist_id);
        $msg = _('Track has been removed from playlist');
        $this->_output($msg);
    }

    function View($playlist_id='')
    {
        $this->_test_pl(0,$playlist_id,false,true,false);
        $args = $this->_get_playlist_info($playlist_id);
        $this->_output($args);
    }

    function _get_playlist_info($playlist_id,$is_popup=false)
    {
        $carts = new CCPlaylist(true);
        $args['playlist'] = $carts->GetRecordFromID($playlist_id);
        if( $args['playlist']['cart_dynamic'] || ($args['playlist']['cart_subtype'] == 'default') )
        {
            require_once('cclib/cc-query.php');
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
            $cart_items =& CCPlaylistItems::GetTable();
            $uploads = new CCUploads();
            $j1 = $cart_items->AddJoin( $uploads, 'cart_item_upload' );
            $j2 = $cart_items->AddJoin( new CCUsers(), $j1 . '.upload_user' );

            $w['cart_item_cart'] = $playlist_id;
            $offset = empty($_REQUEST['offset']) ? 0 : intval($_REQUEST['offset']);
            $limit  = empty($_REQUEST['limit']) ? 20 : intval($_REQUEST['limit']);
            $cart_items->SetOffsetAndLimit($offset,$limit);
            $cart_items->SetOrder('cart_item_order','ASC');
            $rows = $cart_items->QueryRows($w);
            $args['records'] =& $uploads->GetRecordsFromRows($rows);
            $args['feed_q'] = url_args( ccl('api','query'), 'f=rss&playlist=' . $playlist_id );
        }
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
                                     'text'  => _('Play All Tracks') );
            if( !$is_popup )
            {
                $args['menu'][] = array( 'url'   => 'javascript://play in window',
                                         'class' => 'cc_playlist_playwindow',
                                         'id'    => '_plw_' . $playlist_id,
                                         'text'  => _('Play in window') );
            }
        }

        $args['is_logged_in'] = false;
        $args['is_owner'] = false;

        if( CCUser::IsLoggedIn() )
        {
            $user = CCUser::CurrentUser();
            $args['is_logged_in'] = true;

            if( $args['playlist']['cart_user'] ==  $user )
            {
                $args['is_owner'] = true;
                $args['menu'][] = array( 'url' => ccl( 'playlist', 'edit', $playlist_id ),
                                            'class' => '',
                                            'id'    => '',
                                            'text' => _('Edit Properties') );

                if( !$is_dynamic && !empty($args['records']) )
                {
                    $args['menu'][] = array( 'url' => ccl( 'playlist', 'empty', $playlist_id ),
                                                            'class' => '',
                                                            'id'    => '',
                                                            'text' => _('Empty Playlist') );
                }

                if( $args['playlist']['cart_subtype'] != 'favorites' )
                {
                    $args['menu'][] = array( 'url' => ccl( 'playlist', 'delete', $playlist_id ),
                                            'class' => '',
                                            'id'    => '',
                                            'text' => _('Delete Playist') );
                }
            }
        }

        return $args;
    }

    function With($upload_id='')
    {
        $this->_test_pl($upload_id,0,true,false,false);

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
        $this->_output($ret);
    }

    function BrowsePL($playlist_id='')
    {
        $playlist_id = intval(CCUtil::Strip($playlist_id));
        $fulllist = empty($playlist_id) || ($playlist_id < 1);

        require_once('cclib/cc-page.php');
        if( $fulllist )
        {
            CCPage::SetTitle(_('Browse Playlists') );
            CCPage::PageArg('playlist_show_browser', 'playlist.xml/playlist_show_browser' );
            $dummy = array();
            CCPage::PageArg('args',$dummy,'playlist_show_browser');
        }
        else
        {
            $this->_test_pl(0,$playlist_id,false,true,false);
            $args = $this->_get_playlist_info($playlist_id);
            CCPage::SetTitle(sprintf(_('Playlist: %s'),$args['playlist']['cart_name']) );
            CCPage::PageArg('playlist_show_one', 'playlist.xml/playlist_show_one' );
            CCPage::PageArg('args',$args,'playlist_show_one');
        }
    }

    function Popup($playlist_id='')
    {
        //$this->_test_pl(0,$playlist_id,false,true,false);
        if( empty($playlist_id) )
        {
            $pl = $this->_get_default_playlist();
            $playlist_id = $pl['cart_id'];
        }
        $args = $this->_get_playlist_info($playlist_id,true);
        require_once('cclib/cc-page.php');
        CCPage::SetTitle(sprintf(_('Playlist: %s'),$args['playlist']['cart_name']));
        CCPage::PageArg('playlist_popup_window', 'playlist.xml/playlist_popup_window' );
        CCPage::PageArg('args',$args,'playlist_popup_window');
        CCPage::ShowHeaderFooter( false, false  );
    }

    function Browse($user='')
    {
        $where = 'cart_subtype <> "default"';
        if( !empty($user) )
            $where .= ' AND cart_user = ' . CCUser::IDFromName($user);
        $rows = $this->_browse($where);
        $this->_output($rows);
    }

    function _browse($where = array(), $limit_arg = 20, $offset_arg = 0)
    {
        $carts = new CCPlaylist(true);
        $offset = empty($_REQUEST['offset']) ? $offset_arg  : intval($_REQUEST['offset']);
        $limit  = empty($_REQUEST['limit']) ? $limit_arg : intval($_REQUEST['limit']);
        $carts->SetOffsetAndLimit($offset,$limit);
        $carts->SetOrder('cart_date','desc');
        $rows = $carts->QueryRows($where);
        return $rows;
    }

    function _add($upload_id='',$playlist_id='')
    {
        $cart_items =& CCPlaylistItems::GetTable();
        $check['cart_item_upload']   = $upload_id;
        $check['cart_item_cart'] = $playlist_id;
        $check_row = $cart_items->CountRows($check);
        if( $check_row )
        {
            $a = _('Upload is already in list');
            $this->_output($a);
        }

        $uploads  =& CCUploads::GetTable();
        $up_row   = $uploads->QueryKeyRow($upload_id);
        $up_tags  = $up_row['upload_tags'];
        $up_score = $up_row['upload_score'];

        $carts =& CCPlaylist::GetTable();
        $carts->Inc('cart_num_items',$playlist_id);

        $cart_items =& CCPlaylistItems::GetTable();
        $new_row['cart_item_cart'] = $playlist_id;
        $count = $cart_items->CountRows($new_row);
        $new_row['cart_item_upload'] = $upload_id;
        $new_row['cart_item_order'] = $count;
        $cart_items->Insert($new_row);
    }

    function Add($upload_id='',$playlist_id='')
    {
        $this->_test_pl($upload_id,$playlist_id,true,true,true);
        $this->_add($upload_id,$playlist_id);
        $this->_output(_('Track added to playlist'));
    }

    function _get_default_playlist()
    {
        $carts = new CCPlaylist(true);
        $w['cart_subtype'] = 'default';
        $rows = $carts->GetRecords($w);
        if( !empty($rows) )
            $row = $rows[0];
        if( empty($row) )
        {
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
    
            $newrow = $this->_create_playlist('default',_('Dynamic Playlist'),'',$admin_id);
            // we re-query to get the user record joined in
            $row = $carts->GetRecordFromID($newrow['cart_id']);
        }

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
        $carts->Insert($new_row);
        return $new_row;
    }

    function NewPL($upload_id='')
    {
        $msg = _('New playlist created');

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
            $msg .= _(' and track added.');
        }

        $this->_output($msg);
    }

    function _gen_name()
    {
        global $CC_GLOBALS;

        $carts =& CCPlaylist::GetTable();
        $wuser['cart_user'] = CCUser::CurrentUser();
        $num = $carts->CountRows($wuser);
        return sprintf( _("%s's Playlist (%d) "),$CC_GLOBALS['user_real_name'], $num + 1);
    }

    function _output($obj,$pmacro='')
    {
        $format = CCUtil::Strip($_GET['f']);
        if( empty($format) )
            $format = empty($pmacro) ? 'json' : 'html';

        CCEvents::_send_no_cache_headers();

        switch( $format )
        {
            case 'json':
                require_once('cclib/zend/json-encoder.php');
                $text = CCZend_Json_Encoder::encode($obj);
                header( "X-JSON: $text");
                header( 'Content-type: text/plain');
                print($text);
                break;

            case 'html':
                $template = CCUtil::Strip($_GET['t']);
                if( empty($template) )
                    $template = 'playlist.xml';
                else if( strpos( $template, '.xml' ) === false )
                    $template .= '.xml';
                $macro = CCUtil::Strip($_GET['m']);
                if( empty($macro) )
                {
                    if( empty($pmacro) )
                        CCUtil::Send404();
                    else
                        $macro = $pmacro;
                }
                $tobj = new CCTemplateMacro($template,$macro);
                if( empty($tobj) )
                {
                    print(" $template / $macro not found <br />\n" );
                    CCUtil::Send404();
                }

                $targs['macro'] = $macro;
                $targs['root-url'] = cc_get_root_url() . '/';
                $targs['home-url'] = ccl();
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
        CCPage::Prompt($msg . ' ' . sprintf(_('Return to %syour playlists%s'),$link,$link2));
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
        }
        if( $ok && $test_owner )
        {
            $cart =& CCPlaylist::GetTable();
            $owner = $cart->QueryItemFromKey('cart_user',$playlist_id);
            $x = array( $owner, CCUser::CurrentUser() );
            $ok = !empty($owner) && ($owner == CCUser::CurrentUser());
        }

        if( !$ok )
        {
            CCUtil::Send404();
        }
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
            $fields['enable_playlists'] =
               array(  'label'      => _('Enable Playlists'),
                       'weight'      => 600,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );

            $fields['playlist_promo_tag'] =
               array(  'label'      => _('Promomotion tag for playlist'),
                       'form_tip'   => _('Uploads with this tag will be mixed in with dynamic playlists'),
                       'weight'      => 601,
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }

    }
}

?>