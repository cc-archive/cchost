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

/**
*/

require_once('cclib/cc-feedreader.php');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCPoolUI',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,     array( 'CCPoolUI',  'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_LISTING, array( 'CCPoolUI',  'OnUploadListing'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCPoolUI',  'OnUploadDelete'));


/**
* Form for editing the properties of a known pool
*
*/
class CCAdminEditPoolForm extends CCForm
{
    function CCAdminEditPoolForm()
    {
        $this->CCForm();
        $fields = array( 
            'pool_name' =>  
               array(  'label'      => 'Name',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
            'pool_short_name' =>  
               array(  'label'      => 'Internal Name',
                       'formatter'  => 'statictext',
                       'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
            'pool_description' =>
               array(  'label'      => 'Description',
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE ),
            'pool_api_url' =>  
               array(  'label'      => 'API URL',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),
            'pool_site_url' =>  
               array(  'label'      => 'Site URL',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),
            'pool_banned' =>  
               array(  'label'      => 'Banned',
                       'form_tip'   => 'Ignore communications from this pool',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),
            'pool_search' =>  
               array(  'label'      => "Allow to be searched remotely",
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),
            );

        $this->AddFormFields($fields);
    }
}

class CCAdminPoolsForm extends CCForm
{
    function CCAdminPoolsForm()
    {
        $this->CCForm();

        $fields = array( 
            /*
            'allow-pool-search' =>  
                   array(  'label'      => 'Allow users to search remote pools',
                           'formatter'  => 'checkbox',
                           'flags'      => CCFF_POPULATE  ),

            'pool-push-hub' =>  
                   array(  'label'      => 'Request to be a pool at:',
                           'form_tip'   => 'Must the URL to the site\'s pool API',
                           'formatter'  => 'doitnow',
                           'nowbutton'  => 'Request Now',
                           'flags'      => CCFF_POPULATE ),
            */
            'pool-remix-throttle' =>
                   array(  'label'      => 'Remote Remix Throttle',
                           'form_tip'   => 'Maximum remote unnapproved remixes.',
                           'formatter'  => 'textedit',
                           'class'      => 'cc_form_input_short',
                           'flags'      => CCFF_POPULATE  ),

            'pool-pull-hub' =>  
                   array(  'label'      => 'Add a sample pool to your site:',
                           'form_tip'   => 'Must be the URL to the site\'s pool API (e.g. http://ccmixter.org/media/api)',
                           'formatter'  => 'doitnow',
                           'nowbutton'  => 'Add Now',
                           'flags'      => CCFF_POPULATE ),
/*
            'allow-pool-register' =>  
                   array(  'label'      => 'Allow remote pools to register here',
                           'formatter'  => 'checkbox',
                           'flags'      => CCFF_POPULATE  ),
*/
               );
        $this->AddFormFields($fields);
    }

    function generator_doitnow($varname,$value='',$class='')
    {
        $html = $this->generator_textedit($varname,$value,$class);
        $caption = $this->GetFormFieldItem($varname,'nowbutton');
        $html .= " <input type='submit' id=\"doitnow_$varname\" name=\"doitnow_$varname\" value=\"$caption\" />";
        return( $html );
    }

    function validator_doitnow($fieldname)
    {
        return( $this->validator_textedit($fieldname) );
    }

    /**
     * Overrides base class in order to populate fields with current contents of environment's config.
     *
     */
    function GenerateForm($hiddenonly = false)
    {
        $configs =& CCConfigs::GetTable();
        $values = $configs->GetConfig('config');
        $this->PopulateValues($values);
        return( parent::GenerateForm($hiddenonly) );
    }
}


class CCPoolUI
{
    function Pool($pool_id='')
    {
        $pool_id = CCUtil::StripText($pool_id);
        if( empty($pool_id) )
            return;

        $pools =& CCPools::GetTable();
        $pool = $pools->QueryKeyRow($pool_id);
        if( empty( $pool ) )
            return;

        // yea, I'm pretty sure there's a saner way to do this....

        $pool_items = new CCPoolItems();
        $pool_items->GroupOnKey();
        $j1 = $pool_items->AddJoin( new CCPoolRemixes(), 'pool_item_id' );
        $j2 = $pool_items->AddJoin( new CCPoolSources(), 'pool_item_id' );
        $where = "(pool_item_pool = $pool_id) AND (($j1.pool_tree_id > 0) OR ($j2.pool_tree_id > 0)) ";

        CCPage::AddPagingLinks($pool_items,$where);
        $items = $pool_items->GetRecords($where);
        $count = count($items);
        $remixpool =&  CCLocalPoolRemixes::GetTable();
        $sourcepool =& CCLocalPoolSources::GetTable();
        for( $i = 0; $i < $count; $i++ )
        {
            $this->_prep_for_display($items[$i], $remixpool,$sourcepool,true);
        }

        CCPage::SetTitle( cct('Sample Pool: ') . $pool['pool_name'] );
        CCPage::PageArg( 'pool_info', $pool, 'pool_info_head' );
        CCPage::PageArg( 'pool_items', $items, 'pool_item_listing' );

    }

    function Item($pool_item_id='')
    {
        $id = CCUtil::StripText($pool_item_id);
        if( empty($id) )
            return;

        $pool_items =& CCPoolItems::GetTable();
        $where['pool_item_id'] = $id;
        $item       =& $pool_items->QueryRow($where);
        $item['works_page'] = true;
        CCPage::PageArg( 'chop', false );
        $remixpool  =& CCLocalPoolRemixes::GetTable();
        $sourcepool =& CCLocalPoolSources::GetTable();
        $this->_prep_for_display($item, $remixpool,$sourcepool);
        $pools =& CCPools::GetTable();
        $pool = $pools->QueryKeyRow($item['pool_item_pool']);
        CCPage::PageArg( 'pool_info', $pool, 'pool_info_head' );
        CCPage::PageArg( 'pool_items', array( $item ), 'pool_item_listing' );
        CCPage::SetTitle( cct('Sample Pool Item: ') . $item['pool_item_name'] );
    }

    function _prep_for_display(&$item, &$remixpool,&$sourcepool)
    {
        $children = $remixpool->GetRemixes($item);
        CCRemix::_mark_row($item,'has_children','remix_children',$children,'more_children_link',false);
        $parents = $sourcepool->GetSources($item);
        CCRemix::_mark_row($item,'has_parents','remix_parents',$parents,'more_parents_link',false);

        // hmmm... I'm sure this is here for a great reason...
        $item['upload_name'] = $item['pool_item_name'];

    }

    function Admin()
    {
        CCPage::SetTitle("Sample Pools Administration");
        $args =
            array(
                array( 'action' => ccl( 'admin', 'pools', 'settings' ),
                       'menu_text' => 'Sample Pool Settings',
                       'help' => 'Edit global settings for interacting with remote pools' ),
                array( 'action' => ccl( 'admin', 'pools', 'manage' ),
                       'menu_text' => 'Manage Sample Pools',
                       'help' => 'Manage pools known to this site.' ),
                array( 'action' => ccl( 'admin', 'pools', 'approve' ),
                       'menu_text' => 'Approve Remote Remixes',
                       'help' => 'Validate pending remote remixes.' ),
               );
        CCPage::PageArg( 'link_table_items', $args, 'link_table' );
    }

    function Approve($submit='')
    {
        CCPage::SetTitle("Approve Pending Remixes");
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
            CCPage::Prompt("There are no pending remote remixes");
        }
        else
        {
            $args['heads'] = array( 'Show', 'Remix', 'Download from Site', 'by Remixer', 'Original' );
            $args['approve_url'] = ccl( 'admin', 'pools', 'approve', 'submit' );
            //CCDebug::PrintVar($args);
            CCPage::PageArg( 'pool_info', $args, 'pool_approvals' );
        }
    }

    function Manage()
    {
        CCPage::SetTitle("Manage Sample Pools");
        $pools =& CCPools::GetTable();
        $rows = $pools->QueryRows('');
        $args = array();
        foreach( $rows as $pool_row )
        {
            $args[] = array( 
                        'action' => ccl( 'admin', 'pool', 'edit', $pool_row['pool_id'] ),
                       'menu_text' => '[EDIT]',
                       'help' => $pool_row['pool_name'] 
                     );
        }
        CCPage::PageArg( 'link_table_items', $args, 'link_table' );

    }

    function Settings()
    {
        CCPage::SetTitle( 'Sample Pools Settings' );
        $form = new CCAdminPoolsForm();
        $form->ValidateFields(); // you have to call this to get values out... hmmm
        $values = array();
        $form->GetFormValues($values);

        if( !empty($_POST['doitnow_pool-push-hub'] ) )
        {
            if( empty($values['pool-push-hub']) )
            {
                $form->SetFieldError( 'pool-push-hub', "can not be left blank" );
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
                    CCPage::Prompt('Registration with sample pool succeeded');
                }
            }
        }
        elseif( !empty($_POST['doitnow_pool-pull-hub'] ) )      
        {
            $form->GetFormValues($values);
            if( empty($values['pool-pull-hub']) )
            {
                $form->SetFieldError( 'pool-pull-hub', "can not be left blank" );
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
                        $form->SeFieldError( 'pool-push-hub', $pool );
                    else
                        CCPage::Prompt("Sample Pool as been registered here.");
                }
                else
                {
                    CCPage::Prompt("That Sample Pool is already registered here");
                }
            }
        }
        elseif( !empty($_POST['adminpools']) )
        {
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig($this->_typename, $values);
            CCPage::Prompt("Settings saved");
        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    function Edit($pool_id)
    {
        CCPage::SetTitle("Edit Pool Information");
        $form = new CCAdminEditPoolForm();
        $show = true;
        $pools =& CCPools::GetTable();
        if( empty( $_POST['admineditpool'] ) )
        {
            $row =& $pools->QueryKeyRow($pool_id);
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
            CCPage::Prompt("Changes to pool saved");
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
        CCPage::Prompt("no implemento");
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
    * Event handler for {@link CC_EVENT_UPLOAD_LISTING}
    *
    * Final chance to massage a record before being displayed in a list
    * 
    * @param array &$row Record to massage with extra display information
    */
    function OnUploadListing( &$row )
    {
        $fhome = ccl() . 'pools/item/';
        $phome = ccl() . 'pools/pool/';
        $upload_id = $row['upload_id'];
        $limit = empty($CC_GLOBALS['works_page']) ? 'LIMIT ' . CC_MAX_SHORT_REMIX_DISPLAY : '';

        // sorry about the column duplication, it's there so
        // ccHost v1.0 templates don't break. vs.

        $select =<<<END
            SELECT pool_item_name, 
                   pool_item_name as upload_name,
                   pool_item_artist,
                   pool_item_artist as user_real_name,
                   CONCAT('$fhome', pool_item_id) as file_page_url,
                   CONCAT('$fhome', pool_item_id) as artist_page_url,
                   CONCAT('$phome', pool_item_pool) as pool_item_pool_url 
                FROM cc_tbl_pool_item 
END;

        if( !empty($row['upload_num_pool_sources']) )
        {
            // fast version

            $sql =<<<END
                $select
                LEFT OUTER JOIN cc_tbl_pool_tree j3 ON pool_item_id = pool_tree_pool_parent  
                WHERE pool_tree_child = $upload_id
                $limit
END;

            $parents = CCDatabase::QueryRows($sql);

            // slow version:
            //
            //$remix_sources =& CCPoolSources::GetTable();
            //$parents = $remix_sources->GetSources($row);

            CCRemix::_mark_row($row,'has_parents','remix_parents',$parents,'more_parents_link');
        }

        if( !empty($row['upload_num_pool_remixes']) )
        {

            $sql =<<<END
                $select
                LEFT OUTER JOIN cc_tbl_pool_tree j3 ON pool_item_id = pool_tree_pool_child  
                WHERE j3.pool_tree_parent = '$upload_id' AND (pool_item_approved > 0)  
                $limit
END;

            $children = CCDatabase::QueryRows($sql);

            // slow version:
            //
            //$remixes =& CCPoolRemixes::GetTable();
            //$children = $remixes->GetApprovedRemixes($row);

            CCRemix::_mark_row($row,'has_children','remix_children',$children,'more_children_link');
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        global $CC_GLOBALS;

        $enabled = empty($CC_GLOBALS['allow-pool-ui']) ? false : $CC_GLOBALS['allow-pool-ui'];

        if( $enabled )
        {
            CCEvents::MapUrl( ccp( 'pools', 'pool'),     array( 'CCPoolUI', 'Pool'),    CC_DONT_CARE_LOGGED_IN );
            CCEvents::MapUrl( ccp( 'pools', 'item' ),    array( 'CCPoolUI', 'Item'),    CC_DONT_CARE_LOGGED_IN );

            CCEvents::MapUrl( ccp( 'admin', 'pools'),                array( 'CCPoolUI', 'Admin'),    CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pools', 'settings' ),   array( 'CCPoolUI', 'Settings'), CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pools', 'manage' ),     array( 'CCPoolUI', 'Manage'),   CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pool',  'edit' ),       array( 'CCPoolUI', 'Edit'),     CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pool',  'delete' ),     array( 'CCPoolUI', 'Delete'),   CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pools', 'approve' ),    array( 'CCPoolUI', 'Approve'),   CC_ADMIN_ONLY );
            CCEvents::MapUrl( ccp( 'admin', 'pools', 'approve', 'item' ),    array( 'CCPoolUI', 'ApproveItem'),   CC_ADMIN_ONLY );
        }
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
                'pool' => array( 'menu_text'  => 'Sample Pools',
                                 'menu_group' => 'configure',
                                 'help' => 'Sub menu for managing sample pools',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10000,
                                 'action' =>  ccl('admin','pools')
                                 ),
                    );
        }
    }

}

?>