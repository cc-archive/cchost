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
require_once('cchost_lib/ccextras/cc-playlist.inc');
require_once('cchost_lib/ccextras/cc-tag-editor.inc');
require_once('cchost_lib/cc-form.php');
require_once('cchost_lib/cc-admin.php');
require_once('cchost_lib/cc-page.php');

class CCPlaylistTagsAdminForm extends CCTagEditorForm
{
    function CCPlaylistTagsAdminForm()
    {
        $this->CCTagEditorForm('playlist_tags',_('Add Tag'));
    }
}

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

            '_tags' => 
                array( 'label'      => _('Create Tags'),
                       'value'      => "<a href=\"".ccl('admin','playlists','tags')."\">" . _('Edit default tags for create form') . '</a>',
                       'formatter'  => 'statictext',
                       'form_tip'   => _('Edit which tags are shown in the Create Playlist form'),
                       'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
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

    function AdminTags()
    {
        CCPage::SetTitle(_('Config Create Playlist Tags'));

        $form = new CCPlaylistTagsAdminForm();
        if( empty($_POST['playlisttagsadmin']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $channels = array();
            if( !empty($_POST['S']) )
                $this->_inject($channels,$_POST['S']);
            if( !empty($_POST['new']) )
                $this->_inject($channels,$_POST['new']);
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig('playlist_tags',$channels,CC_GLOBAL_SCOPE,false);

            CCPage::Prompt(_('Tags information saved'));
        }

    }

    function _inject(&$results,$arr)
    {
        CCUtil::StripSlash($arr);
        foreach( $arr as $channel )
        {
            if( empty($channel['delete']) )
                $results[] = $channel;
        }
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
        $api = new CCPlaylists();
        $api->_test_pl('',$playlist_id, /* test upload: */ false, 
                                        /* test_playlist */ true,
                                        /* test_owner */ true);
        

        require_once('cchost_lib/cc-page.php');
        $api->_build_bread_crumb_trail($playlist_id,'str_pl_edit_properties');
        CCPage::SetTitle('str_pl_edit_properties');
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
            $url = ccl('playlist','browse',$playlist_id);
            CCUtil::SendBrowserTo($url);
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
        $api = new CCPlaylists();
        $api->_test_pl('',$playlist_id, /* test upload: */ false, 
                                        /* test_playlist */ true,
                                        /* test_owner */ true);
        

        require_once('cchost_lib/cc-page.php');
        $api->_build_bread_crumb_trail($playlist_id,'str_pl_empty_playlist');
        CCPage::SetTitle('str_pl_empty_playlist');
        if( empty($_POST['confirmplaylistempty']) )
        {
            require_once('cchost_lib/ccextras/cc-playlist-forms.inc');
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
        $carts =& CCPlaylist::GetTable();
        $wx['cart_num_items'] = 0;
        $wx['cart_id'] = $playlist_id;
        $carts->Update($wx);
    }

    function DeletePL($playlist_id='')
    {
        $api = new CCPlaylists();
        $api->_test_pl('',$playlist_id, /* test upload: */ false, 
                                        /* test_playlist */ true,
                                        /* test_owner */ true);
        

        require_once('cchost_lib/cc-page.php');
        $api->_build_bread_crumb_trail($playlist_id,'str_pl_delete_playist');
        CCPage::SetTitle('str_pl_delete_playist');
        if( empty($_POST['confirmplaylistdelete']) )
        {
            require_once('cchost_lib/ccextras/cc-playlist-forms.inc');
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
        require_once('cchost_lib/ccextras/cc-playlist-rank.inc');
        $msg = calc_plays();
        if( empty($msg) )
            CCPage::Prompt(_('Playlists rankings recalcuated'));
        else
            CCPage::Prompt($msg);
    }
}

?>