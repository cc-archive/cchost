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
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCChannels', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCChannels', 'OnAdminMenu'));

class CCChannelsAdminForm extends CCGridForm
{
    function CCChannelsAdminForm()
    {
        $this->CCGridForm();

        global $CC_GLOBALS;
        
        $promo_tag = empty($CC_GLOBALS['site_promo_tag']) ? 'site_promo' : $CC_GLOBALS['site_promo_tag'];
        $input = $this->generator_textedit('site_promo_tag',$promo_tag,'cc_form_edit_short');
        $html = sprintf(_('Uploads with these tags %s will be mixed in with streams.'),$input);

        $this->SetHelpText($html);

        $heads = array( 
            _('Delete'),
            _('Tags'), 
            _('Caption'), 
         );
        
        $this->SetColumnHeader($heads);

        $configs =& CCConfigs::GetTable();
        $channels =  $configs->GetConfig('channels',CC_GLOBAL_SCOPE);
        $count = 0;
        foreach( $channels as $channel  )
        {
            $S = 'S[' . ++$count . ']';
            $a = array(
                  array(
                    'element_name'  => $S . '[delete]',
                    'value'      => false,
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_POPULATE ),
                  array(
                    'element_name'  => $S . '[tags]',
                    'value'      => $channel['tags'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_POPULATE ),
                  array(
                    'element_name'  => $S . '[text]',
                    'value'      => $channel['text'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_POPULATE ),
                );

            $this->AddGridRow( $count, $a );
        }

        $S = 'new[%i%]';
        $a = array(
              array(
                'element_name'  => $S . '[delete]',
                'value'      => false,
                'formatter'  => 'checkbox',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[tags]',
                'value'      => 'remix',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[text]',
                'value'      => '',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
            );

        $this->AddMetaRow($a, _('Add Channel') );
    }
}

class CCChannels
{

    function Admin()
    {
        CCPage::SetTitle(_('Configure Channels'));
        $form = new CCChannelsAdminForm();
        if( empty($_POST['channelsadmin']) || !$form->ValidateFields() )
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
            $configs->SaveConfig('channels',$channels,CC_GLOBAL_SCOPE,false);

            $b['site_promo_tag'] = $_POST['site_promo_tag'];
            $configs->SaveConfig('config',$b,CC_GLOBAL_SCOPE,true);

            CCPage::Prompt("Channel information saved");
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
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','channels'), array( 'CCChannels', 'Admin'),  CC_ADMIN_ONLY,
              ccs(__FILE__), '', _('Configure channels/radio interface'), CC_AG_RENDER );
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
                'channels' => array( 
                                 'menu_text'  => _('Channels Page'),
                                 'menu_group' => 'configure',
                                 'help' => 'Configure playlists for the channels page',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 1340,
                                 'action' =>  ccl('admin','channels')
                                 ),
                );
        }
    }

}

?>
