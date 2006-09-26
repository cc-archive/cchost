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

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCTag', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCTag', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCTag', 'OnAdminMenu'));

/**
*/
define('CC_MAX_TAG_LEN',  25);
define('CC_MIN_TAG_LEN',  3);
define('CC_MIN_TAG_SHOW',  0);

define('CC_TAG_SPLITTER', '/\W+/');

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
            $tags = CCTag::TagSplit($tags);
        if( empty($tags) )
            return;
        $tags = array_map('strtolower',$tags);

        $where = $this->GetWhereFilter($tags);
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

    function Add($new_tags,$type,$count)
    {
        if( empty($new_tags) )
            return;
        if( !is_array($new_tags) )
            $new_tags = CCTag::TagSplit($new_tags);

        $values = array();
        foreach( $new_tags as $tag )
        {
            $values[] = array( strtolower($tag), $count, $type );
        }
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
            $tag = preg_replace('/[^\w_]+/','_',$tag);
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
                $result[] = $row['tag_alias_alias'];
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

    function ExpandOnRow(&$row,$inkey,$baseurl,$outkey,$label='',$usehash=false)
    {
        $this->ExpandOnRowA($row,$inkey,$baseurl,$outkey,$label, $row, $usehash );
    }

    function ExpandOnRowA(&$row,$inkey,$baseurl,$outkey,$label, &$outrow, $usehash )
    {
        if( empty($row[$inkey]) )
            return;
        if( empty($outrow) )
            $outrow =& $row;

        $tagstr = $row[$inkey];
        $tags = CCTag::TagSplit($tagstr);
        if( !empty($label) )
        {
            $count = empty($outrow[$outkey]) ? '0' : count($outrow[$outkey]);
            $outsubkey = $outkey . $count;
            $outrow[$outkey][$outsubkey]['label'] = $label;
        }
        foreach($tags as $tag)
        {
            $taglink = array( 'tagurl' => $baseurl . '/' . $tag,
                              'tag'    => $tag );

            if( $usehash )
                $taglink['tagurl'] .= '#' . $tag;

            if( empty($label) )
            {
                $outrow[$outkey][] = $taglink;
            }
            else
            {
                $outrow[$outkey][$outsubkey]['value'][] = $taglink;
            }
        }
    }

    function & GetRecords()
    {
        return( $this->QueryRows('') );
    }

}

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

        $fields = array(
            'aliases' => array(
                'label' => 'Aliases',
                'form_tip' => 'Put one alias on each line, use a "=&gt;" to indicate what the user entered tag should become.' .
                              ' (e.g. <b>drums and base=&gt;DNB</b> will turn all user entries \'drums and bass\' in to \'DNB\')',
                'formatter' =>  'textarea',
                'value' => $text,
                'flags' => CCFF_POPULATE
                ),
            'runnow' => array(
                'label' => 'Run Now',
                'form_tip' => 'Check this to run the alias rules now',
                'formatter' =>  'checkbox',
                'flags' => CCFF_NONE
                ),
            'reserved' => array(
                'label' => 'Reserved Tags',
                'form_tip' => 'These tags will be reserved by the system. If a user tries to use these tags it will be removed.',
                'formatter' =>  'textarea',
                'value' => implode(', ',$admintags),
                'flags' => CCFF_POPULATE
                ),
            'mintaglen' => array(
                'label' => 'Minimum tag allowed',
                'form_tip' => 'Tags shorter than this length will be thrown away',
                'formatter' =>  'textedit',
                'class' => 'cc_form_input_short',
                'value'   => $min,
                'flags' => CCFF_POPULATE
                ),
            'maxtaglen' => array(
                'label' => 'Maximum tag allowed',
                'form_tip' => 'Tags longer than this will be truncated',
                'formatter' =>  'textedit',
                'class' => 'cc_form_input_short',
                'value'   => $max,
                'flags' => CCFF_POPULATE
                ),
            'mintagshow' => array(
                'label' => 'Minimum display tags',
                'form_tip' => 'Minimum number of tags to display in "Browse Tags"',
                'formatter' =>  'textedit',
                'class' => 'cc_form_input_short',
                'value'   => $minshow,
                'flags' => CCFF_POPULATE
                ),
            );

        $this->AddFormFields($fields);
    }
}
/**
 * Helper API and system event watcher for attribute tags
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
class CCTag
{
    function Admin()
    {
        CCPage::SetTitle('Admin Tags');
        $form = new CCAdminTagsForm();
        if( empty($_POST['admintags']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
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

            if( !empty($reserved) )
            {
                $tags->Insert($reserved,CCTT_ADMIN);
                $run = true;
            }

            $min = $form->GetFormValue('mintaglen');
            $max = $form->GetFormValue('maxtaglen');
            $minshow = $form->GetFormValue('mintagshow');
            $configs =& CCConfigs::GetTable();
            $configs->SetValue('config','tags-min-length',$min,CC_GLOBAL_SCOPE);
            $configs->SetValue('config','tags-max-length',$max,CC_GLOBAL_SCOPE);
            $configs->SetValue('config','tags-min-show',$minshow,CC_GLOBAL_SCOPE);
            global $CC_GLOBALS;
            $CC_GLOBALS['tags-min-length'] = $min;
            $CC_GLOBALS['tags-max-length'] = $max;

            $text = $form->GetFormValue('aliases');
            if( !empty($text) )
            {
                $tuples = split("\n",$text);
                $values = array();
                foreach( $tuples as $tuple )
                {
                    if( empty($tuple) )
                        continue;

                    $arr = explode('=>',$tuple);
                    $arr = array_map('trim',$arr);
                    if( count($arr) != 2 || empty($arr[0]) || empty($arr[1]) )
                        continue;
                    $values[] = $arr;
                }
                $columns = array( 'tag_alias_tag', 'tag_alias_alias' );
                $aliases->InsertBatch($columns,$values);
                $run = true;
                CCPage::Prompt("Changes Saved");
            }

            if( $run && !empty($_POST['runnow']) )
            {
                /* slow way:
                $uploads =& CCUploads::GetTable();
                $ids = $uploads->QueryKeys();
                foreach($ids as $id)
                {
                    CCUploadAPI::_recalc_upload_tags( $id );
                }
                */
                $ups = CCDatabase::Query('SELECT upload_id,upload_extra FROM cc_tbl_uploads');
                while( $row = mysql_fetch_row($ups) )
                {
                    $extra = unserialize($row[1]);
                    if( empty($extra['usertags']) )
                        continue;
                    CCUploadAPI::_recalc_upload_tags( $row[0] );
                }

                CCPage::Prompt("Tags updated with new alias rules");
            }
        }
    }

    function TagSplit($tagstr)
    {
        if( empty($tagstr) )
            return( array() );
        return( preg_split(CC_TAG_SPLITTER,$tagstr) );
    }

    function InTag($needles,$haystack)
    {
        if( is_array($haystack) )
            $haystack = implode(', ',$haystack);

        $needles = preg_replace('/, ?/','|',$needles);

        $regex =  "/(^| |,)($needles)(,|\$)/";

        return( preg_match( $regex, $haystack ) );
    }

    function Reset()
    {
        $tags =& CCTags::GetTable();
        $tags->DeleteWhere('1');
        $ups = CCDatabase::Query('SELECT upload_id,upload_tags,upload_extra FROM cc_tbl_uploads');

        while( $row = mysql_fetch_row($ups) )
        {
            // [ccud] => media,acappella
            // [usertags] => female_vocals,melody
            // [systags] => sampling_plus,audio,mp3,44k,mono,128kbps
            $extra = unserialize($row[2]);
            if( $extra['ccud'] )
                $tags->Insert( $extra['ccud'], CCTT_ADMIN, false );
            if( $extra['usertags'] )
                $tags->Insert( $extra['usertags'], CCTT_USER, false );
            if( $extra['systags'] )
                $tags->Insert( $extra['systags'], CCTT_SYSTEM, false );

            $tags->Update( $row[1] );
        }

        CCUtil::SendBrowserTo( ccl('tags') );
    }

    function OnBrowseTags($tagstr='')
    {
        CCPage::SetTitle(_("Tags ") . $tagstr);

        if( empty($tagstr) )
        {
            $this->ShowAllTags();
            return;
        }

        $this->BrowseTags($tagstr);
    }

    function BrowseTags($tagstr,$search_type='all')
    {
        $currtags = preg_split(CC_TAG_SPLITTER,$tagstr);
        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter($currtags,$search_type);
        $uploads->SetOrder('upload_date','DESC');
        CCPage::AddPagingLinks($uploads,'');
        $records = $uploads->GetRecords('');
        
        CCUpload::ListRecords($records);

        // All tags ('+')
        //
        $all_tags = array();
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $records[$i]['upload_tags'] )
                $all_tags[] = $records[$i]['upload_tags'];
        }
        $all_tags = implode(',',$all_tags);
        $all_tags = array_unique( preg_split(CC_TAG_SPLITTER,$all_tags) );
        $alltags  = array_diff($all_tags,$currtags);
        sort($alltags);
        CCPage::PageArg('all_tags', $alltags);

        // 'Minus tags'
        //
        $minustags = array();
        if( count($currtags) == 1 )
        {
            $minustags[] = array( 'url' => ccl('tags'),
                                  'tag' => $tagstr );
        }
        else
        {
            foreach( $currtags as $currtag )
            {
                $diff = array_diff($currtags,array($currtag));
                $diff = implode('+',$diff);
                $minustags[] = array( 'url' => ccl('tags',$diff),
                                      'tag' => $currtag );
            }
        }

        CCPage::PageArg("minus_tags", $minustags);

        // Base URLs
        $base_addtag_url = ccl('tags', urlencode($tagstr) . '+');
        CCPage::PageArg("base_addtag_url", $base_addtag_url);
        CCPage::PageArg("base_tag_url", ccl('tags'));
        $taghelp = strlen($tagstr) > 10 ? substr($tagstr,0,8) . '...' : $tagstr;
        CCFeeds::AddFeedLinks($tagstr,'','Tags: ' . $taghelp );

    }

    function ShowAllTags()
    {
        global $CC_GLOBALS;

        $tags =& CCTags::GetTable();
        $tags->SetSort('tags_tag');
        
        if( empty($CC_GLOBALS['tags-min-show']) )
            $minshow = 0;
        else
            $minshow = $CC_GLOBALS['tags-min-show'];

        $where = "tags_count > $minshow";

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

        $switch_link = "<table class=\"cc_tag_switch_link\"><tr><td><a class=\"cc_gen_button\" href=\"$switch_url\"><span>$switch_text</span></a></td></tr></table>";

        $records =& $tags->QueryRows($where);
        $count = count($records);
        $max_count = $tags->Max('tags_count',$where);
        for( $i = 0; $i < $count; $i++ )
        {
            $c = $records[$i]['tags_count'] - 1;
            $percent = ($c / $max_count) * 100;
            $records[$i]['fontsize'] = 11 + intval($percent * 0.6); // set max px size is 60
            $records[$i]['tagurl'] = ccl('tags',  $records[$i]['tags_tag']);
        }

        CCPage::SetTitle(_("Tags"));
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
            'tagalias'   => array( 'menu_text'  => 'Tags',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 80,
                             'help' => 'Edit tag aliases, reserve tags, counts, etc.',
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
        CCEvents::MapUrl( ccp('tags'), array('CCTag','OnBrowseTags'), CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( ccp('admin','tags'), array('CCTag','Admin'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('admin','tags','reset'), array('CCTag','Reset'), CC_ADMIN_ONLY );
    }
}


?>
