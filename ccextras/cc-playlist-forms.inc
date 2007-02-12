<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('ccextras/cc-cart-table.inc');
require_once('ccextras/cc-playlist.inc');
require_once('cclib/cc-form.php');
require_once('cclib/cc-admin.php');

class CCPlaylistForm extends CCForm
{
    function CCPlaylistForm($dynamic,$playlist_id)
    {
        $this->CCForm();
        $fields = array(
            'cart_name' => 
                        array( 'label'      => _('Name'),
                               'formatter'  => 'textedit',
                               'form_tip'   => _(''),
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
            'cart_tags' => 
                        array( 'label'      => _('Tags'),
                               'formatter'  => 'tagsedit',
                               'form_tip'   => _('(e.g. chill, high_energy, guitar_stuff)'),
                               'flags'      => CCFF_POPULATE),
            'cart_desc' => 
                        array( 'label'      => _('Description'),
                               'formatter'  => 'textarea',
                               'want_formatting' => true,
                               'form_tip'   => _(''),
                               'flags'      => CCFF_POPULATE),
                );

        if( $dynamic )
        {
            $url = ccl('playlist','editquery',$playlist_id);
            $fields += array(
                '_dynlink' => 
                            array( 'label'      => _('Dynamic Query'),
                                   'value'      => "<a href=\"$url\">" . _('Edit dynamic query') . '</a>',
                                   'formatter'  => 'statictext',
                                   'form_tip'   => _('Edit the dynamic query for this playlist'),
                                   'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
                );
        }
        else
        {
            $url = ccl('playlist','editorder',$playlist_id);
            $fields += array(
                '_dynlink' => 
                            array( 'label'      => _('Track Order'),
                                   'value'      => "<a href=\"$url\">" . _('Edit track order') . '</a>',
                                   'formatter'  => 'statictext',
                                   'form_tip'   => _('Edit the order in which the tracks play'),
                                   'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
                );
        }

        $this->AddFormFields($fields);
    }
}

class CCConfirmPlaylistDeleteForm extends CCForm
{
    function CCConfirmPlaylistDeleteForm()
    {
        $this->CCForm();
        $this->SetHelpText(_('This action can not be reversed...'));
        $this->SetSubmitText(_("Are you sure you want to delete this playlist?"));
    }
}

class CCConfirmPlaylistEmptyForm extends CCForm
{
    function CCConfirmPlaylistEmptyForm()
    {
        $this->CCForm();
        $this->SetHelpText(_('This action can not be reversed...'));
        $this->SetSubmitText(_("Are you sure you want to empty all contents this playlist?"));
    }
}

class CCAdminPlaylistsForm extends CCEditConfigForm
{
    function CCAdminPlaylistsForm()
    {
        $this->CCEditConfigForm('config',CC_GLOBAL_SCOPE);
        $this->SetModule(ccs(__FILE__));
        $fields = array(

            'enable_playlists' =>
               array(  'label'      => _('Enable Playlists'),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),

            'playlist_promo_tag' =>
               array(  'label'      => _('Promomotion tag for playlist'),
                       'form_tip'   => _('Uploads with this tag will be mixed in with dynamic playlists'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

            'playlist_excl_user' =>
               array(  'label'      => _('Playlist Abuse List'),
                       'form_tip'   => _('Users who have abused playlists'),
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE ),

            'playlist_rank' =>
               array(  'label'      => _('Use Playlist For Rankings'),
                       'form_tip'   => _('Use playlists stats to rank uploads'),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),

            'playlist_feat' =>
               array(  'label'      => _('Featured Song'),
                       'form_tip'   => _('This will be excluded from rankings and play counts. (Use the numberic upload id)'),
                       'class'      => 'cc_form_input_short',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

            'playlist_minsec' =>
               array(  'label'      => _('Minimum Play Time'),
                       'form_tip'   => _('The minimum number of seconds before a song gets credit for a Play'),
                       'class'      => 'cc_form_input_short',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

            'playlist_weigh_play' =>
               array(  'label'      => _('Weight: Play'),
                       'form_tip'   => _('How much do plays influence rankings'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

            'playlist_weigh_add' =>
               array(  'label'      => _('Weight: Add'),
                       'form_tip'   => _('How much do adds to playlists influence rankings'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

            'playlist_weigh_rate' =>
               array(  'label'      => _('Weight: Rate'),
                       'form_tip'   => _('How much do user ratings influence rankings (set this to zero to ignore ratings)'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),

                );


        $this->AddFormFields($fields);
    }
}

class CCPlaylistManage
{
    function Admin()
    {
        CCPage::SetTitle(_('Admin Playlists'));
        $form = new CCAdminPlaylistsForm();
        CCPage::AddForm($form->GenerateForm());
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array( 
                'playlists'   => array( 'menu_text'  => 'Playlists',
                                 'menu_group' => 'configure',
                                 'help'      => _('Manage Playlists'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 700,
                                 'action' =>  ccl('admin','playlists')
                                 ),
                );
        }
    }

    function Edit($playlist_id='')
    {
        $playlist_id = intval(CCUtil::Strip($playlist_id));
        if( empty($playlist_id) || $playlist_id < 1 )
            CCUtil::Send404();

        require_once('cclib/cc-page.php');

        CCPage::SetTitle(_('Edit Playlist Properties'));
        $carts =& CCPlaylist::GetTable();
        $row = $carts->QueryKeyRow($playlist_id);
        $form = new CCPlaylistForm($row['cart_dynamic'],$playlist_id);
        $show = false;
        if( empty($_POST['playlist']) )
        {
            $form->PopulateValues($row);
            $show = true;
        }
        else
        {
            $show = !$form->ValidateFields();
        }
        if( $show  )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $values['cart_name'] = substr($values['cart_name'],0,60);
            $values['cart_id'] = $playlist_id;
            $carts->Update($values);
            $this->_send_prompt(_('Playlist has been updated.'));
        }
    }

    function _send_prompt($msg)
    {
        $url = ccl('people', CCUser::CurrentUserName(), 'playlists' );
        $link = "<a href=\"$url\">";
        $link2 = '</a>';
        CCPage::Prompt($msg . ' ' . sprintf(_('Return to %syour playlists%s'),$link,$link2));
    }

    function EmptyPL($playlist_id='')
    {
        CCPlaylists::_test_pl(0,$playlist_id,false,true,true);
        require_once('cclib/cc-page.php');
        CCPage::SetTitle(_('Empty Playlist'));
        if( empty($_POST['confirmplaylistempty']) )
        {
            require_once('ccextras/cc-playlist-forms.inc');
            $form = new CCConfirmPlaylistEmptyForm();
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->_empty_playlist($playlist_id);
            $this->_send_prompt(_('Playlist is now empty.'));
        }
    }

    function _empty_playlist($playlist_id)
    {
        $cartitems =& CCPlaylistItems::GetTable();
        $w['cart_item_cart'] = $playlist_id;
        $cartitems->DeleteWhere($w);
    }

    function DeletePL($playlist_id='')
    {
        CCPlaylists::_test_pl(0,$playlist_id,false,true,true);
        require_once('cclib/cc-page.php');
        CCPage::SetTitle(_('Delete Playlist'));
        if( empty($_POST['confirmplaylistdelete']) )
        {
            require_once('ccextras/cc-playlist-forms.inc');
            $form = new CCConfirmPlaylistDeleteForm();
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->_empty_playlist($playlist_id);
            $carts =& CCPlaylist::GetTable();
            $carts->DeleteKey($playlist_id);
            $this->_send_prompt(_('Playlist has been deleted.'));
        }
    }

    function Recalc()
    {
        global $CC_GLOBALS;
        require_once('ccextras/cc-playlist-rank.inc');
        $msg = calc_plays();
        if( empty($msg) )
            CCPage::Prompt(_('Playlists rankings recalcuated'));
        else
            CCPage::Prompt($msg);
    }
}

?>