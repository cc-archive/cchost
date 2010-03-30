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
* @subpackage folksonomy
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-tags.php');
/**
*/
define('CC_MAX_TAG_LEN',  25);
define('CC_MIN_TAG_LEN',  3);
define('CC_MIN_TAG_SHOW',  0);

// deprecated...
// define('CC_TAG_SPLITTER', ...

class CCTagAliases extends CCTable
{
    function CCTagAliases()
    {
        $this->CCTable('cc_tbl_tag_alias','tag_alias_tag');
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCTagAliases();
        return( $_table );
    }
}

/**
 * Table wrapper for user and system attribute tagging
 *
 *  Just to confuse things there are two types of 'tags':
 *
 *   <ul><li>ID3 tags that are read from and stamped into and from files (like MP3s)
 *         This class has nothing to do with that</li>
 *     
 *        <li>Tags as in del.io.us and flikr where an item is catalogued
 *         according to some attributes. The attributes are searchable
 *         across the system to find 'like' items. This class is for
 *         facilitating this kind of 'tag'.</li>
 *   </ul>
*/
class CCTags extends CCTable
{
    function CCTags()
    {
        $this->CCTable('cc_tbl_tags','tags_tag');
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCTags();
        return( $_table );
    }

    function Replace($old_tags,$new_tags)
    {
        if( !is_array($new_tags) )
            $new_tags = CCTag::TagSplit($new_tags);
        if( !is_array($old_tags) )
            $old_tags = CCTag::TagSplit($old_tags);

        $tossed   = array_diff($old_tags,$new_tags);
        $used     = array_diff($new_tags,$old_tags);

        $this->Update($used);
        $this->TagDelete($tossed);
    }

    function Insert($tags,$type,$update_type = true)
    {
        if( !is_array($tags) )
        {
            require_once('cchost_lib/cc-tags.php');
            $tags = CCTag::TagSplit($tags);
        }
        if( empty($tags) )
            return;
        $tags = array_map('strtolower',$tags);

        $where = $this->GetWhereFilter($tags);
        $this->SetOffsetAndLimit( 0, count($tags) );
        $diff_tags = $this->QueryKeys($where);
        $new_tags = array_diff($tags,$diff_tags);
        if( $update_type && (($type & (CCTT_ADMIN | CCTT_SYSTEM)) != 0) )
        {
            $old_tags = array_diff($diff_tags,$new_tags);
            if( $old_tags )
            {
                // these are tags that some user entered
                // that are now being 'promoted' to system
                // tags or admin tags:

                $in_where = $this->_get_in_set($old_tags);
                $upargs = array();
                $upargs['tags_type'] = $type;
                $this->UpdateWhere($upargs,$in_where);
            }
        }

        $this->Add($new_tags,$type,0);
    }

    function _get_in_set($tags)
    {
        if( is_string($tags) )
            $tags = CCTag::TagSplit($tags);
        $tag_quoted = array();
        foreach( $tags as $tag )
            $tag_quoted[] = "'" . strtolower(addslashes($tag)) . "'";

        return ' LOWER(tags_tag) IN (' . join(',',$tag_quoted) . ') ';
    }

    function TagDelete($tags)
    {
        if( empty($tags) )
            return;
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        $where = $this->GetWhereFilter($tags);
        $updates['tags_count'] = 'tags_count - 1';
        $this->UpdateWhere($updates,$where,false);
        $this->DeleteWhere('tags_count = 0 AND tags_type = ' . CCTT_USER);
    }

    function Update($tags)
    {
        if( empty($tags) )
            return;

        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);

        $where = $this->GetWhereFilter($tags);
        $updates['tags_count'] = 'tags_count + 1';
        $this->UpdateWhere($updates,$where,false);
    }

    function _prep_add(&$new_tags,$type,$count,$names=false)
    {
        if( empty($new_tags) )
            return;
        if( !is_array($new_tags) )
            $new_tags = CCTag::TagSplit($new_tags);

        $values = array();
        foreach( $new_tags as $tag )
        {
            if( $names )
            {
                $values[] = array( 'tags_tag' => strtolower($tag),
                                 'tags_count' => $count,
                                 'tags_type' => $type );
            }
            else
            {
                $values[] = array( strtolower($tag), $count, $type );
            }
        }
        return $values;
    }

    function SmartAdd($new_tags,$type)
    {
        $values = $this->_prep_add($new_tags,$type,1,true);
        if( empty($values) )
            return;
        foreach( $values as $V )
        {
            $row = CCDatabase::QueryRow('SELECT tags_tag,tags_count FROM cc_tbl_tags WHERE tags_tag=\''.$V['tags_tag'].'\'');
            if( !empty($row) )
            {
                ++$row['tags_count'];
                parent::Update($row);
            }
            else
            {
                parent::Insert($V);
            }
        }
    }
        
    function Add($new_tags,$type,$count)
    {
        $values = $this->_prep_add($new_tags,$type,$count);
        if( empty($values) )
            return;
        $fields = array( 'tags_tag', 'tags_count', 'tags_type' );
        foreach( $values as $v )
            if( empty($v[0]) )
               CCDebug::StackTrace();
        $this->InsertBatch($fields,$values);
    }

    function Normalize($tags)
    {
        global $CC_GLOBALS;

        if( empty($tags) )
            return('');

        if( !is_array($tags) )
        {
            $tags = preg_replace('/\s+/','_',$tags);
            require_once('cchost_lib/cc-tags.php');
            $tags = CCTag::TagSplit($tags);
        }
        $tags = array_unique($tags);
        $tags2 = array();
        $min = isset($CC_GLOBALS['tags-min-length']) ? $CC_GLOBALS['tags-min-length'] : CC_MIN_TAG_LEN;
        $max = isset($CC_GLOBALS['tags-max-length']) ? $CC_GLOBALS['tags-max-length'] : CC_MAX_TAG_LEN;
        foreach( $tags as $tag )
        {
            if( empty($tag) )
                continue;
            $tag = preg_replace('/[^-\w_]+/','_',$tag);
            $tag = trim($tag,'_');
            if( strlen($tag) < $min)
                continue;
            if( strlen($tag) > $max )
                $tag = substr($tag,0,$max);
            if( !in_array( $tag, $tags2 ) )
                $tags2[] = $tag;
        }
    
        return(implode(',', $tags2));
    }

    function CheckAliases($tags)
    {
        if( empty($tags) )
            return('');
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);

        $aliases =& CCTagAliases::GetTable();

        $result = array();
        $where = array();
        foreach($tags as $tag)
        {
            $where['tag_alias_tag'] = $tag;
            $row = $aliases->QueryRow($where);
            if( empty($row) )
            {
                $result[] = $tag;
            }
            else
            {
                $res = split(',',$row['tag_alias_alias']);
                foreach( $res as $R )
                {
                    $R = trim($R);
                    if( empty($R) )
                        continue;
                    $result[] = $R;
                }
            }
        }
        
        $result = array_unique($result);
        $result = implode(',', $result);
        return( $result );
    }

    function CleanSystemTags($tags)
    {
        if( empty($tags) )
            return('');

        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);

        $tags = array_map('strtolower',$tags);
        $tags = array_unique($tags);
        $where = $this->GetWhereFilter($tags,CCTT_SYSTEM | CCTT_ADMIN);
        $diff_tags = $this->QueryKeys($where);
        if( !empty($diff_tags) )
            $tags = array_diff($tags,$diff_tags);

        return( implode(',',$tags) );
    }

    function GetWhereFilter($tags,$filter_type='')
    {
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);

        $in_where = $this->_get_in_set($tags);

        if( $filter_type )
            $in_where = " (($in_where) AND tags_type & $filter_type) ";

        return $in_where;
    }

    // here for compat with <3.2
    function ExpandOnRow(&$row,$inkey,$baseurl,$outkey,$label='',$usehash=false)
    {
        CCTag::ExpandOnRowA($row,$inkey,$baseurl,$outkey,$label, $row, $usehash );
    }

    function ExpandOnRowA(&$row,$inkey,$baseurl,$outkey,$label, &$outrow, $usehash )
    {
        CCTag::ExpandOnRowA($row,$inkey,$baseurl,$outkey,$label, $outrow, $usehash );
    }

    function & GetRecords()
    {
        return( $this->QueryRows('') );
    }

}

require_once('cchost_lib/cc-form.php');

/**
* @package cchost
* @subpackage admin
*/
class CCAdminTagsForm extends CCForm
{
    function CCAdminTagsForm()
    {
        global $CC_GLOBALS;

        // Get aliases
        //
        $this->CCForm();
        $aliases =& CCTagAliases::GetTable();
        $aliases->SetSort('tag_alias_tag');
        $rows = $aliases->QueryRows('');
        $text = '';
        foreach( $rows as $row )
        {
            $text .= $row['tag_alias_tag'] . '=&gt;' . $row['tag_alias_alias'] . "\n";
        }
        if( empty($text) )
            $text = "hiphop=&gt;hip_hop\nblack_and_white=>bw\naccapella=&gt;acappella\na_capella=&gt;acappella\n";

        // Get admin reserved tags
        //
        $tags =& CCTags::GetTable();
        $tags->SetSort('tags_tag');
        $where['tags_type'] = CCTT_ADMIN;
        $rows = $tags->QueryRows($where);
        $tags->SetSort('');
        $admintags = array();
        foreach( $rows as $row )
            $admintags[] = $row['tags_tag'];
        if( empty($admintags) )
            $admintags = array( 'with','that','this' );

        // Get min/max
        //
        $min = isset($CC_GLOBALS['tags-min-length']) ? $CC_GLOBALS['tags-min-length'] : CC_MIN_TAG_LEN;
        $max = isset($CC_GLOBALS['tags-max-length']) ? $CC_GLOBALS['tags-max-length'] : CC_MAX_TAG_LEN;

        $minshow = isset($CC_GLOBALS['tags-min-show']) ? $CC_GLOBALS['tags-min-show'] : CC_MIN_TAG_SHOW;

        $inherit = isset($CC_GLOBALS['tags-inherit']) ? join(',',$CC_GLOBALS['tags-inherit']) : '';

        $fields = array(
            'aliases' => array(
                'label'     => _('Aliases'),
                'form_tip'  => _("Put one alias on each line, use a '=&gt;' to indicate what the user entered tag should become (e.g. <b>drums and base=&gt;DNB</b> will turn all user entries 'drums and bass' in to 'DNB')"),
                'formatter' =>  'textarea',
                'value'     => $text,
                'flags'     => CCFF_POPULATE
                ),
            'reserved' => array(
                'label'     => _('Reserved Tags'),
                'form_tip'  => _('These tags will be reserved by the system. If a user tries to use these tags it will be removed.'),
                'formatter' =>  'textarea',
                'value'     => implode(', ',$admintags),
                'flags'     => CCFF_POPULATE
                ),
            'mintaglen' => array(
                'label'     => _('Minimum tag length allowed'),
                'form_tip'  => _('Tags shorter than this length will be thrown away.'),
                'formatter' =>  'textedit',
                'class'     => 'cc_form_input_short',
                'value'     => $min,
                'flags'     => CCFF_POPULATE
                ),
            'maxtaglen' => array(
                'label'     => _('Maximum tag allowed'),
                'form_tip'  => _('Tags longer than this will be truncated'),
                'formatter' =>  'textedit',
                'class'     => 'cc_form_input_short',
                'value'     => $max,
                'flags'     => CCFF_POPULATE
                ),
            'mintagshow' => array(
                'label'     => _('Minimum display tags'),
                'form_tip'  => _('Minimum number of tags to display in "Browse Tags"'),
                'formatter' =>  'textedit',
                'class'     => 'cc_form_input_short',
                'value'     => $minshow,
                'flags'     => CCFF_POPULATE
                ),
            'inherittags' => array(
                'label'     => _('Inherited tags'),
                'form_tip'  => _("Remixes will automatically 'inherit' these tags from their sources"),
                'formatter' =>  'textedit',
                'value'     => $inherit,
                'flags'     => CCFF_POPULATE
                ),
            );

        $this->AddFormFields($fields);
    }
}

class CCTagEvents
{
    function Reset()
    {
        print "<pre>This is will completely cleanup tags and tag counts for you entire system.\nIt may take a while...\n\n";
        require_once('cchost_lib/cc-tags-recalc.php');
        print '</pre>';
        $url = ccl('admin','tags');
        print "Go back to <a href=\"{$url}\">Manage Tags</a>\n";
        exit;
    }
    
    function OnBrowseTags($tagstr='')
    {
        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle('str_tags_s',$tagstr);

        if( empty($tagstr) )
        {
            $this->ShowAllTags();
            return;
        }

        $this->BrowseTags($tagstr);
    }

    function BrowseTags($tagstr,$search_type='all')
    {
        CCPage::PageArg('tagstr',$tagstr,'tags.php/tag_picker');
        require_once('cchost_lib/cc-query.php');
        $q = 'tags=' . $tagstr . '&type=' .$search_type;
        $query = new CCQuery();
        $args= $query->ProcessAdminArgs($q);
        $query->Query($args);
        return;
    }

    function ShowAllTags()
    {
        global $CC_GLOBALS;

        $tagbase = empty($_GET['tagbase']) ? ccl('tags') . '/' : ccl($_GET['tagbase']);
        $tags =& CCTags::GetTable();
        $tags->SetSort('tags_tag');
        
        if( empty($CC_GLOBALS['tags-min-show']) )
            $minshow = 0;
        else
            $minshow = $CC_GLOBALS['tags-min-show'];

        $where = "tags_count >= $minshow";

        if( !empty($_GET['tabgbase']) )
        {
            if( empty($_REQUEST['all']) )
            {
                $where .=  ' AND tags_type = ' . CCTT_USER;
                $switch_url = url_args( ccl('tags'), 'all=1' );
                $switch_text = _('Turn System Tags ON');
            }
            else
            {
                $switch_url = ccl('tags');
                $switch_text = _('Turn System Tags OFF');
            }

            $switch_link = "<table class=\"cc_tag_switch_link\"><tr><td><a class=\"cc_gen_button\" href=\"$switch_url\"><span>$switch_text</span></a></td><td></td></tr></table>";
        }
        else
        {
            $switch_link = '';
            $where .=  ' AND tags_type = ' . CCTT_USER;
        }


        $records =& $tags->QueryRows($where);
        $count = count($records);
        $max_count = $tags->Max('tags_count',$where);
        for( $i = 0; $i < $count; $i++ )
        {
            $c = $records[$i]['tags_count'] - 1;
            $percent = ($c / $max_count) * 100;
            $records[$i]['fontsize'] = 11 + intval($percent * 0.6); // set max px size is 60
            $records[$i]['tagurl'] = $tagbase . $records[$i]['tags_tag'];
        }

        CCPage::SetTitle(empty($_GET['title']) ? 'str_tags' : CCUtil::Strip($_GET['title']) );
        CCPage::PageArg('tag_switch_link',$switch_link);
        CCPage::PageArg('tag_array',$records,'tags');
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'tagalias'   => array( 'menu_text'  => _('Tags'),
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 11,
                             'help' => _('Edit tag aliases, reserve tags, counts, etc.'),
                             'action' =>  ccl('admin','tags')
                             ),
            );
    }


    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $items = array( 
            'tags'   => array( 'menu_text'  => _('Browse Tags'),
                             'menu_group' => 'visitor',
                             'weight' => 5,
                             'action' =>  ccp('tags'),
                             'access' => CC_DONT_CARE_LOGGED_IN
                             ),
                               
                );

        CCMenu::AddItems($items);

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('tags'), array('CCTagEvents','OnBrowseTags'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[tags]', _('Browse Tags'), CC_AG_TAGS );
        CCEvents::MapUrl( ccp('admin','tags'), array('CCTagEvents','Admin'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Admin Tags'), CC_AG_TAGS ); 
        CCEvents::MapUrl( ccp('admin','tags','reset'), array('CCTagEvents','Reset'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Resync all tag counts (slow)'), CC_AG_TAGS);
    }

    function Admin($props='')
    {
        if( $props == 'properties' )
        {
            return $this->AdminProperties();
        }
        
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _("Manage Tags");
        CCAdmin::BreadCrumbs(true,array('url'=>'','text'=>$title));
        CCPage::SetTitle($title);

        $args =
            array(
                array( 'action' => ccl( 'admin', 'tags', 'properties' ),
                       'menu_text' => _('Tag Properties'),
                       'help' => _('Rules tag translations, reserved tags, etc. Will re-calculate tags for the entire system.') ),
                array( 'action' => ccl( 'admin', 'tagcat','cats' ),
                       'menu_text' => _('Create Tag Categories'),
                       'help' => _('Create and edit tag categories') ),
                array( 'action' => ccl( 'admin', 'tagcat' ),
                       'menu_text' => _('Assign Tag Categories'),
                       'help' => _('Assign tags into categories') ),
                array( 'action' => ccl( 'admin', 'tags', 'reset' ),
                       'menu_text' => _('Recalculate all tags'),
                       'help' => _('Reset tag counters, apply tag rules and categories (already part of <b>Properties</b>)') ),
               );
        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }
    
    function AdminProperties()
    {
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-form.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _('Tag Properties');
        $admin = new CCAdmin();
        $admin->BreadCrumbs(true,array('url'=>ccl('admin','tags'),'text'=>_('Manage Tags')),array('url'=>'','text'=>$title));
        $page =& CCPage::GetPage();
        $page->SetTitle($title);
        

        $form = new CCAdminTagsForm();
        if( empty($_POST['admintags']) || !$form->ValidateFields() )
        {
            $page->AddForm( $form->GenerateForm() );
        }
        else
        {
            $aliases =& CCTagAliases::GetTable();
            $aliases->DeleteWhere('1');

            $tags =& CCTags::GetTable();
            $reserved = $form->GetFormValue('reserved');
            $run = false;

            $cctBits = ~CCTT_ADMIN;
            $sql = "UPDATE cc_tbl_tags SET tags_type = (tags_type & $cctBits)";
            CCDatabase::Query($sql);

            if( empty($reserved) )
            {
                $where = "(tags_type & $cctBits)";
                $run = $tags->CountRows($where) > 0;
            }
            else
            {
                $tags->Insert($reserved,CCTT_ADMIN);
                $run = true;
            }

            $min = $form->GetFormValue('mintaglen');
            $max = $form->GetFormValue('maxtaglen');
            $minshow = $form->GetFormValue('mintagshow');

            // there's a bit of hole here:
            // If the admin DELETES an inherit tag we
            // don't update tracks with that tag and the user
            // is stuck with it until they do 'Edit Remixes'
            //
            $inherit_tags = CCTag::TagSplit($form->GetFormValue('inherittags'));
            $tags->Insert($inherit_tags,CCTT_ADMIN);


            $configs =& CCConfigs::GetTable();
            $configs->SetValue('config','tags-min-length',$min,CC_GLOBAL_SCOPE);
            $configs->SetValue('config','tags-max-length',$max,CC_GLOBAL_SCOPE);
            $configs->SetValue('config','tags-min-show',$minshow,CC_GLOBAL_SCOPE);
            $configs->SetValue('config','tags-inherit',$inherit_tags,CC_GLOBAL_SCOPE);

            global $CC_GLOBALS;
            $CC_GLOBALS['tags-min-length'] = $min;
            $CC_GLOBALS['tags-max-length'] = $max;

            $text = $form->GetFormValue('aliases');
            if( !empty($text) )
            {
                $left_side = array();
                $tuples = split("\n",$text);
                $values = array();
                foreach( $tuples as $tuple )
                {
                    if( empty($tuple) )
                        continue;

                    $arr = explode('=>',$tuple);
                    $arr = array_map('trim',$arr);
                    if( count($arr) != 2 || empty($arr[0]) || empty($arr[1]) || in_array($arr[0],$left_side) )
                        continue;
                    $values[] = $arr;
                    $left_side[] = $arr[0];
                }
                $columns = array( 'tag_alias_tag', 'tag_alias_alias' );
                $aliases->InsertBatch($columns,$values);
                $run = true;
                $page->Prompt(_("Changes Saved"));
            }
            
            $url = ccl('admin','tags','reset');
            $text = "Rules have been saved, but you need to run <a href=\"{$url}\">Recalculate all tags</a> commands to have them go in affect.";
            $page->Prompt($text);
        }
    }

}

?>
