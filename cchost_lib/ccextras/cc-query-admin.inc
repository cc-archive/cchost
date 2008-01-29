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

require_once('cchost_lib/ccextras/cc-tag-editor.inc');


class CCQueryAdminForm extends CCTagEditorForm
{
    function CCQueryAdminForm()
    {
        $this->CCTagEditorForm('browse_query_tags',_('Add Tag'));
    }
}

class CCQueryAdmin
{
    function Admin()
    {
        CCPage::SetTitle(_('Query Admin'));

        $form = new CCQueryAdminForm();
        if( empty($_POST['queryadmin']) || !$form->ValidateFields() )
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
            $configs->SaveConfig('browse_query_tags',$channels,CC_GLOBAL_SCOPE,false);

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
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','browse'), 
                          array( 'CCQueryAdmin', 'Admin'),  CC_ADMIN_ONLY,
                          ccs(__FILE__), '', 
                          _('Configure browse interface'), 
                          CC_AG_RENDER );
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
                'qbrowse' => array( 
                                 'menu_text'  => _('Query Browser'),
                                 'menu_group' => 'configure',
                                 'help' => 'Configure query browser',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 1340,
                                 'action' =>  ccl('admin','browse')
                                 ),
                );
        }
    }

}

?>
