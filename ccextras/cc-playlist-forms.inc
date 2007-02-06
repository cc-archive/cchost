<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('ccextras/cc-cart-table.inc');
require_once('ccextras/cc-playlist.inc');
require_once('cclib/cc-form.php');

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

class CCPlaylistManage
{
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
}

?>