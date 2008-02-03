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

require_once('cchost_lib/cc-pools.php');


/**
*/
class CCPoolUI
{
    function Pool($pool_id='',$alpha='')
    {
        $pool_id = CCUtil::StripText($pool_id);
        if( empty($pool_id) )
            return $this->ShowPools();

        $pools =& CCPools::GetTable();
        if( !$pools->KeyExists($pool_id) )
            return;

        require_once('cchost_lib/cc-page.php');
        $pool = CCDatabase::QueryRow('SELECT pool_description,pool_name,pool_id,pool_site_url FROM cc_tbl_pools WHERE pool_id='.$pool_id);
        CCPage::SetTitle( 'str_pool_name_s', $pool['pool_name'] );
        CCPage::PageArg( 'pool_info', $pool );
        $this->_build_bread_crumb_trail($pool['pool_id'],$pool['pool_name']);

        CCPage::PageArg('pool_id',$pool_id,'pool_alpha');
        CCPage::PageArg('pool_alpha_char',$alpha);

        $where =<<<END
            (pool_item_pool = $pool_id) AND 
            ((pool_item_num_remixes > 0) OR (pool_item_num_sources > 0))
END;
        if( !empty($alpha) )
            $where .= " AND (pool_item_artist LIKE '{$alpha}%')";

        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('t=pool_listing&datasource=pool_items&sort=user&ord=ASC');
        $sqlArgs['where'] = $where;
        $query->QuerySQL($args,$sqlArgs);

    }
    
    function ShowPools()
    {
        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail();
        CCPage::SetTitle('str_pools_link');
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('t=pools_list&datasource=pools&sort=');
        $query->Query($args);
    }

    function PoolHook($cmd,$pool_id)
    {
        switch($cmd)
        {
            case 'alpha':
            {
                $sql =<<<END
                    SELECT DISTINCT LOWER(SUBSTRING(`pool_item_artist`,1,1)) c
                       FROM `cc_tbl_pool_item` WHERE                  
                    (pool_item_pool = $pool_id) AND 
                    ((pool_item_num_remixes > 0) OR (pool_item_num_sources > 0))
                    ORDER BY c
END;
                $args= CCDatabase::QueryItems($sql);
                break;
            }
        }

        if( !empty($args) )
            CCUtil::ReturnAjaxData($args);
        exit;
    }

    function Item($pool_item_id='')
    {
        $id = CCUtil::StripText($pool_item_id);
        if( empty($id) )
            return;

        list( $pool_id, $pool_name, $pool_item_name ) = 
            CCDatabase::QueryRow('SELECT pool_id, pool_name, pool_item_name FROM cc_tbl_pool_item JOIN cc_tbl_pools '.
                                   'ON pool_item_pool=pool_id WHERE pool_item_id='.$pool_item_id,false);
        $this->_build_bread_crumb_trail($pool_id,$pool_name,$pool_item_id,$pool_item_name);

        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('t=pool_item&datasource=pool_items&ids='.$pool_item_id);
        $query->Query($args);
        CCPage::SetTitle( 'str_pool_item_page' );
    }

    function Admin()
    {
        require_once('cchost_lib/cc-page.php');
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
                       'menu_text' => _('Approve Trackbacks'),
                       'help' => _('Approve trackbacks and remote remixes') ),
               );
        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }

    function Approve($submit='')
    {
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle(_("Approve Pending Remixes"));
        if( $submit )
        {
            $approved = array();
            $upload_ids = array();
            foreach( $_POST['action'] as $id => $action )
            {
                if( $action == 'nothing' )
                    continue;
                $id = CCUtil::StripText($id);
                $uids = CCDatabase::QueryItems("SELECT pool_tree_parent FROM cc_tbl_pool_tree WHERE pool_tree_pool_child = $id");
                $upload_ids = array_merge( $upload_ids, $uids );

                if( $action == 'delete' )
                {
                    CCDatabase::Query('DELETE FROM cc_tbl_pool_item WHERE pool_item_id = '.$id);
                    CCDatabase::Query("DELETE FROM cc_tbl_pool_tree WHERE (pool_tree_pool_parent = $id) && (pool_tree_pool_child = $id)");
                }
                else
                {
                    $approved[] = $id;
                }
            }

            if( !empty($approved) )
            {
                $approved = join(',',$approved);
                $sql = "UPDATE cc_tbl_pool_item SET pool_item_approved = 1, pool_item_num_sources =  (pool_item_num_sources+1) WHERE pool_item_id IN ({$approved})";
                CCDatabase::Query($sql);
            }
            $upload_ids = array_filter(array_unique($upload_ids));
            require_once('cchost_lib/cc-sync.php');
            foreach( $upload_ids as $upload_id )
                CCSync::Upload($upload_id);
            if( !empty($approved) )
            {
                $rows = CCDatabase::QueryRows("SELECT pool_item_extra FROM cc_tbl_pool_item WHERE pool_item_id IN ({$approved})");
                require_once('cchost_lib/cc-uploadapi.php');
                foreach( $rows as $row )
                {
                    if( empty($row['pool_item_extra']) )
                        continue;
                    $ex = unserialize($row['pool_item_extra']);
                    if( empty($ex['ttype']) )
                        continue;
                    $upload_id = $ex['upload_id'];
                    CCUploadAPI::UpdateCCUD($upload_id,'in_' . $ex['ttype'] . ',trackback','');
                }
            }
        }

        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('t=pool_approvals&datasource=pool_items');
        $query->Query($args);
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
        require_once('cchost_lib/cc-feedreader.php');
        require_once('cchost_lib/cc-pools-forms.php');

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

        require_once('cchost_lib/cc-pools-forms.php');

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
        $tree = new CCPoolTree();
        $tree->DeleteWhere($where);
    }

    function _build_bread_crumb_trail($pool_id='',$pool_name='',$pool_item='',$pool_item_name='')
    {
        $trail = array();
        $trail[] = array( 'url' => ccl(), 'text' => 'str_home' );
        if( $pool_id )
        {
            $trail[] = array( 'url' => ccl('pools'), 'text' => 'str_pools_link' );
            if( $pool_item )
            {
                $trail[] = array( 'url' => ccl('pools','pool',$pool_id), 'text' => $pool_name );
                $trail[] = array( 'url' => '', 'text' => $pool_item_name );
            }
            else
            {
                $trail[] = array( 'url' => '', 'text' => $pool_name );
            }

        }
        else
        {
            $trail[] = array( 'url' => '', 'text' => 'str_pools_link' );
        }

        require_once('cchost_lib/cc-page.php');
        CCPage::AddBreadCrumbs($trail);
    }



    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp( 'pools', 'pool_hook'),     array( 'CCPoolUI', 'PoolHook'), 
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolid},{cmd}'); // ajax callbck
        CCEvents::MapUrl( ccp( 'pools'),     array( 'CCPoolUI', 'Pool'),
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolid}', 
            _('List sample pools'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'pools', 'pool'),     array( 'CCPoolUI', 'Pool'),    
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolid}', 
            _('Show sample pool'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'pools', 'item' ),    array( 'CCPoolUI', 'Item'),    
            CC_DONT_CARE_LOGGED_IN , ccs(__FILE__) , '{poolitemid}', 
            _('Show a sample pool item'), CC_AG_SAMPLE_POOL );
        CCEvents::MapUrl( ccp( 'pools', 'search' ),    array( 'CCPool', 'Search'),
            CC_DONT_CARE_LOGGED_IN , 'cchost_lib/cc-pools.php' , '{poolid}', 
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
                                 'help' => _('Managing sample pools and trackbacks'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10000,
                                 'action' =>  ccl('admin','pools')
                                 ),
                    );
        }
    }

}

?>