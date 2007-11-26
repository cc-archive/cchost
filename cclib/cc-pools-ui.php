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
* Module for admin management of sample pools
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to ccHost');

require_once('cclib/cc-pools.php');


/**
*/
class CCPoolUI
{
    function Pool($pool_id='',$alpha='')
    {
        $pool_id = CCUtil::StripText($pool_id);
        if( empty($pool_id) )
            return;

        $pools =& CCPools::GetTable();
        $pool = $pools->QueryKeyRow($pool_id);
        if( empty( $pool ) )
            return;

        $pool_items = new CCPoolItems();
        $where =<<<END
            (pool_item_pool = $pool_id) AND 
            ((pool_item_num_remixes > 0) OR (pool_item_num_sources > 0))
END;
        if( !empty($alpha) )
            $where .= " AND (pool_item_artist LIKE '{$alpha}%')";
        $pool_items->SetSort('pool_item_artist','ASC');
        CCPage::AddPagingLinks($pool_items,$where);
        $items = $pool_items->QueryRows($where);
        $count = count($items);
        $remixpool =&  CCLocalPoolRemixes::GetTable();
        $sourcepool =& CCLocalPoolSources::GetTable();
        for( $i = 0; $i < $count; $i++ )
        {
            $this->_prep_for_display($items[$i], $remixpool,$sourcepool,true);
        }

        $sql =<<<END
            SELECT DISTINCT LOWER(SUBSTRING(`pool_item_artist`,1,1)) c
               FROM `cc_tbl_pool_item` WHERE                  
            (pool_item_pool = $pool_id) AND 
            ((pool_item_num_remixes > 0) OR (pool_item_num_sources > 0))
            ORDER BY c

END;

        $burl = ccl('pools','pool',$pool_id) . '/';
        $chars = CCDatabase::QueryItems($sql);
        $len = count($chars);
        $alinks = array();
        for( $i = 0; $i < $len; $i++ )
        {
            $c = $chars[$i];
            if( $c == $alpha )
            {
                $alinks[] = array( 
                                'url' => '', 
                                'text' => "<b>$c</b>" );
            }
            else
            {
                $alinks[] = array( 
                                'url' => $burl . $c, 
                                'text' => $c );
            }
        }
        CCPage::SetTitle( 'str_sample_pool_s', $pool['pool_name'] );
        CCPage::PageArg( 'pool_info', $pool, 'pool_info_head' );
        CCPage::PageArg( 'pool_items', $items, 'pool_item_listing' );
        CCPage::PageArg( 'pool_links', $alinks );
    }

    function Item($pool_item_id='')
    {
        $id = CCUtil::StripText($pool_item_id);
        if( empty($id) )
            return;

        require_once('cclib/cc-pools.php');
        $pool_items =& CCPoolItems::GetTable();
        $where['pool_item_id'] = $id;
        $item       = $pool_items->QueryRow($where);
        if( empty($item) )
        {
            CCUtil::Send404(true);
            return;
        }

        $item['works_page'] = true;
        CCPage::PageArg( 'dochop', false );
        $remixpool  =& CCLocalPoolRemixes::GetTable();
        $sourcepool =& CCLocalPoolSources::GetTable();
        $this->_prep_for_display($item, $remixpool,$sourcepool);
        $pools =& CCPools::GetTable();
        $pool = $pools->QueryKeyRow($item['pool_item_pool']);
        CCPage::PageArg( 'pool_info', $pool, 'pool_info_head' );
        CCPage::PageArg( 'pool_items', array( $item ), 'pool_item_listing' );
        CCPage::SetTitle( _('Sample Pool Item: ') . $item['pool_item_name'] );
    }

    function _prep_for_display(&$item, &$remixpool,&$sourcepool)
    {
        require_once('cclib/cc-remix.php');

        $children = $remixpool->GetRemixes($item);
        CCRemix::_mark_row($item,'has_children','remix_children',$children,'more_children_link',false);
        $parents = $sourcepool->GetSources($item);
        CCRemix::_mark_row($item,'has_parents','remix_parents',$parents,'more_parents_link',false);

        // hmmm... I'm sure this is here for a great reason...
        $item['upload_name'] = $item['pool_item_name'];

    }

    function Admin()
    {
        CCPage::SetTitle(_("Sample Pools Administration"));
        $args =
            array(
                array( 'action' => ccl( 'admin', 'pools', 'settings' ),
                       'menu_text' => _('Sample Pool Settings'),
                       'help' => _('Edit global settings for interacting with remote pools') ),
                array( 'action' => ccl( 'admin', 'pools', 'manage' ),
                       'menu_text' => _('Manage Sample Pools'),
                       'help' => _('Manage pools known to this site.') ),
                array( 'action' => ccl( 'admin', 'pools', 'approve' ),
                       'menu_text' => _('Approve Remote Remixes'),
                       'help' => _('Validate pending remote remixes.') ),
               );
        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }

    function Approve($submit='')
    {
        CCPage::SetTitle(_("Approve Pending Remixes"));
        if( $submit )
        {
            $ids = $_POST['approve'];
            if( !empty($ids) )
            {
                $pool_items = CCPoolItems::GetTable();
                foreach( $ids as $id )
                {
                    $id = CCUtil::StripText($id);
                    if( !empty($id) && intval($id) )
                    {
                        $where[] = "(pool_item_id = $id)";
                    }
                }

                if( !empty($where) )
                {
                    $sql = implode( ' OR ', $where );
                    $f['pool_item_approved'] = 1;
                    $pool_items->UpdateWhere($f, $sql, false);
                }
            }
        }
        $remixes =& CCPoolRemixes::GetTable();
        $args['records'] = $remixes->GetUnapproved();
        if( empty($args['records']) )
        {
            CCPage::Prompt(_("There are no pending remote remixes"));
        }
        else
        {
            $args['heads'] = array( _('Show'), _('Remix'), _('Download from Site'), _('by Remixer'), _('Original') );
            $args['approve_url'] = ccl( 'admin', 'pools', 'approve', 'submit' );
            //CCDebug::PrintVar($args);
            CCPage::PageArg( 'pool_info', $args, 'pool_approvals' );
        }
    }

    function Manage()
    {
        CCPage::SetTitle(_("Manage Sample Pools"));

        $pools =& CCPools::GetTable();
        $rows = $pools->QueryRows('');
        $args = array();
        foreach( $rows as $pool_row )
        {
            $args[] = array( 
                        'action' => ccl( 'admin', 'pool', 'edit', $pool_row['pool_id'] ),
                       'menu_text' => '[' . _('EDIT') . ']',
                       'help' => $pool_row['pool_name'] 
                     );
        }
        CCPage::PageArg('client_menu',$args,'print_client_menu');

    }

    function Settings()
    {
        require_once('cclib/cc-feedreader.php');
        require_once('cclib/cc-pools-forms.php');

        CCPage::SetTitle( _('Sample Pools Settings') );
        $form = new CCAdminPoolsForm();
        $form->ValidateFields(); // you have to call this to get values out... hmmm
        $values = array();
        $form->GetFormValues($values);

        if( !empty($_POST['doitnow_pool-push-hub'] ) )
        {
            if( empty($values['pool-push-hub']) )
            {
                $form->SetFieldError( 'pool-push-hub', _("This can not be left blank.") );
            }
            else
            {
                $me = urlencode(ccl( 'api' ));
                $url = CCRestAPI::MakeUrl( $values['pool-push-hub'], 'poolregister', $me );
                $fr = new CCFeedStatusReader();
                $xml = $fr->cc_parse_url($url);
                //CCDebug::PrintVar($xml);
                if( $xml && ($xml->status['status'] == 'ok') )
                {
                    CCPage::Prompt(_('Registration with sample pool succeeded.'));
                }
            }
        }
        elseif( !empty($_POST['doitnow_pool-pull-hub'] ) )      
        {
            $form->GetFormValues($values);
            if( empty($values['pool-pull-hub']) )
            {
                $form->SetFieldError( 'pool-pull-hub', _("This can not be left blank.") );
            }
            else
            {
                $pools =& CCPools::GetTable();
                $where['pool_api_url'] = $values['pool-pull-hub'];
                if( $pools->CountRows($where) == 0 )
                {
                    $api = new CCPool();
                    $pool = $api->AddPool($where['pool_api_url']);

                    if( is_string($pool) )
                    {
                        $form->SeFieldError( 'pool-push-hub', $pool );
                    }
                    else
                    {
                        $url = ccl( 'admin', 'pool', 'edit', $pool['pool_id'] );
                        CCUtil::SendBrowserTo($url);
                    }
                }
                else
                {
                    CCPage::Prompt(_("That Sample Pool is already registered here."));
                }
            }
        }
        elseif( !empty($_POST['adminpools']) )
        {
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig($this->_typename, $values);
            CCPage::Prompt(_("Settings saved"));
        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    function Edit($pool_id)
    {
        CCPage::SetTitle(_("Edit Pool Information"));

        require_once('cclib/cc-pools-forms.php');

        $form = new CCAdminEditPoolForm();
        $show = true;

        $pools =& CCPools::GetTable();
        if( empty( $_POST['admineditpool'] ) )
        {
            $row = $pools->QueryKeyRow($pool_id);
            $form->PopulateValues($row);
        }
        else
        {
            $show = !$form->ValidateFields();
        }

        if( $show )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $values['pool_id'] = $pool_id;
            $pools->Update($values);
            CCPage::Prompt(_("Changes to pool saved"));
            $this->Manage();
        }
    }

    function Delete($pool_id)
    {
        /*
        $pools =& CCPools::GetTable();
        $pools->DeleteKey($pool_id);
        CCPage::Prompt("Pool deleted");
        */
        CCPage::Prompt(_("This is not implemented."));
    }

    /**
    * Event hander for {@link CC_EVENT_DELETE_UPLOAD}
    * 
    * @param array $record Upload database record
    */
    function OnUploadDelete( &$row )
    {
        $id = $row['upload_id'];
        $where = "(pool_tree_parent = $id) OR (pool_tree_child = $id)";
        $tree = new CCPoolTree('pool_tree_parent','pool_tree_child');
        $tree->DeleteWhere($where);
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp( 'pools', 'pool'),     array( 'CCPoolUI', 'Pool'),    
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolid}', 
            _('Show sample pool'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'pools', 'item' ),    array( 'CCPoolUI', 'Item'),    
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolitemid}', 
            _('Show a sample pool item'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'pools', 'search' ),    array( 'CCPool', 'Search'),
            CC_DONT_CARE_LOGGED_IN , 'cclib/cc-pools.php' , '{poolid}', 
            _('Search a given pool (perhaps remotely) with a string'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pools'),                array( 'CCPoolUI', 'Admin'),    
            CC_ADMIN_ONLY , ccs(__FILE__) , '', _('Display admin pools menu'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pools', 'settings' ),   array( 'CCPoolUI', 'Settings'), 
            CC_ADMIN_ONLY , ccs(__FILE__) , '', _('Display pool admin settings form'), 
            CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pools', 'manage' ),     array( 'CCPoolUI', 'Manage'),   
            CC_ADMIN_ONLY , ccs(__FILE__) , '', _('Display list of pools to admin'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pool',  'edit' ),       array( 'CCPoolUI', 'Edit'),     
            CC_ADMIN_ONLY , ccs(__FILE__) , '{poolid}', _('Edit properties of pool'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pool',  'delete' ),     array( 'CCPoolUI', 'Delete'),   
            CC_ADMIN_ONLY , ccs(__FILE__) , '{poolid}', _('Delete a sample pool'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pools', 'approve' ),    array( 'CCPoolUI', 'Approve'),   
            CC_ADMIN_ONLY , ccs(__FILE__) , '', _('Display admin pool approval menu'), 
            CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'admin', 'pools', 'approve', 'item' ),    
            array( 'CCPoolUI', 'ApproveItem'),   CC_ADMIN_ONLY , ccs(__FILE__) , '{poolitem}', 
            _('Approve a remote remix'), CC_AG_SAMPLE_POOL );
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu($items,$scope)
    {
        if( $scope == CC_LOCAL_SCOPE )
            return;

        global $CC_GLOBALS;

        $enabled = empty($CC_GLOBALS['allow-pool-ui']) ? false : $CC_GLOBALS['allow-pool-ui'];

        if( $enabled )
        {
            $items += array( 
                'pool' => array( 'menu_text'  => _('Sample Pools'),
                                 'menu_group' => 'configure',
                                 'help' => _('Sub menu for managing sample pools'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10000,
                                 'action' =>  ccl('admin','pools')
                                 ),
                    );
        }
    }

}

?>
