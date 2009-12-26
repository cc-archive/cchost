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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCTagCat',  'OnMapUrls')    );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,        array( 'CCTagCat', 'OnUploadDone')  );

//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCCollab' , 'OnGetConfigFields' ), 'cchost_lib/ccextras/cc-collab.inc' );


class CCTagCat 
{
    function OnUploadDone()
    {
        
    }
    
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/tagcat', array('CCTagCat','AdminTags'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/tagcat/cats', array('CCTagCat','AdminCats'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/fixtags', array('CCTagCat','AdminStragglers'), CC_ADMIN_ONLY );
    }
    
    function _get_paging(&$offset,$show_all=false,$where='',$is_search='')
    {
        $page_size = 50;

        if(empty($_REQUEST['offset']))
        {
            $offset = 0;
            $prev = 0;
            $next = $page_size;
        }
        else
        {
            $offset = $_REQUEST['offset'];
            $prev = $offset - $page_size;
            $next = $offset + $page_size;
            
        }

        $link = ccl('admin','tagcat');
        $edit_url = $link . '/';
        $search_url = $link . '?search=';

        $help =<<<EOF
<div style="float:right;margin-right:10px;">
specific tag: <input id="specific_tag" style="width:10em" /> <a class="small_button" href="javascript://" id="get_tag"
onclick="document.location = '{$edit_url}' + $('specific_tag').value;">edit</a><br /><br />
search for: <input id="search_term" style="width:10em" /><a class="small_button" href="javascript://" id="get_search"
onclick="document.location = '{$search_url}' + $('search_term').value;">find</a>
</div>
EOF;
        $total = CCDatabase::QueryItem("SELECT COUNT(*) FROM cc_tbl_tags {$where}");
        if( $total > $page_size )
        {
            $showflag = $show_all ? '&showall=1' : '';
            $plink = url_args( $link, 'offset='.$prev . $showflag);
            $prev_link = $show_all && empty($offset) ? '' : "<a href=\"{$plink}\" class=\"small_button\">prev</a>";
            $nlink = url_args( $link, 'offset='.$next . $showflag);
            $next_link = empty($next) ? '' : "<a href=\"{$nlink}\" class=\"small_button\">next</a>";
            if( $show_all )
            {
                $slink = "<a class=\"small_button\" href=\"{$link}\">only show unassigned tags</a>";
            }
            else {
                $surl = url_args($link,'showall=1');
                $slink = "<a class=\"small_button\" href=\"{$surl}\">show all (assigned and unnassigned)</a>";
            }
            $help .=<<<EOF
This range of tags: {$offset} to {$next} of {$total}<br />
{$prev_link} {$next_link}
<p>{$slink}</p>
EOF;
        }
    
        return $help;    
    }
    
    function _cat_for_one_tag($tag)
    {
        $tag_info = CCDatabase::QueryRow('SELECT tags_tag,tags_category FROM cc_tbl_tags WHERE tags_tag="'.$tag.'"');
        if( empty($tag_info) )
        {
            CCUtil::Send404();
            return;
        }
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-form.php');
        require_once('cchost_lib/cc-admin.php');
        $title1 = _('Assign Tag Categories');
        $title = _('Assign Tag Category for ') . $tag;
        $admin = new CCAdmin();
        $admin->BreadCrumbs(true,array( 'url'=>ccl('admin','tagcat'),'text'=>$title1 ),array('url'=>'','text'=>$title));
        $page =& CCPage::GetPage();
        $page->SetTitle($title);
        $cats = CCDatabase::QueryRows('SELECT * FROM cc_tbl_tag_category');
        $opts = array();
        $opts[''] = '(none)';
        foreach( $cats as $cat )
            $opts[ $cat['tag_category_id'] ] = $cat['tag_category'];
        $form = new CCForm();
        $fields = array(
            'tags_category' =>
                array(
                    'label' => "Tag category for {$tag}",
                    'options' => $opts,
                    'value' => $tag_info['tags_category'],
                    'formatter' => 'select',
                    'flags' => CCFF_NONE
                )
        );
        $form->AddFormFields($fields);
        if( empty($_POST) || !$form->ValidateFields() )
        {
            $page->AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $values['tags_tag'] = $tag;
            $table = new CCTable('cc_tbl_tags','tags_tag');
            $table->Update($values);
            $page->Prompt(_('Tag category has been updated'));
        }
        
    }
    
    function AdminTags($tag='')
    {
        if( !empty($tag) )
            $tag = CCUtil::StripText($tag);
            
        if( !empty($tag) )
        {
            $this->_cat_for_one_tag($tag);
            return;
        }

        $show_all = !empty($_REQUEST['showall']);
        
        $where = '';
        if( !$show_all )
        {
            if( !empty($_REQUEST['search']) )
            {
                $search_term = CCUtil::StripText($_REQUEST['search'] );
                if( !empty($search_term) ) 
                {
                    $where = "WHERE tags_tag LIKE '%{$search_term}%'";
                }
            }
            
            if( empty($where) )
                $where = 'WHERE tags_category = "0" OR ISNULL(tags_category) OR tags_category = "" ';   
        }
        

        $GLOBALS['skip_form_word_hack'] = true; // sigh. go global search for this if you care
      
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-form.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _('Assign Tag Categories');
        $admin = new CCAdmin();
        $admin->BreadCrumbs(true,array('url'=>ccl('admin','tags'),'text'=>_('Manage Tags')),array('url'=>'','text'=>$title));
        $page =& CCPage::GetPage();
        $page->SetTitle($title);
        
        
        $form = new CCGridForm();
        $cats = CCDatabase::QueryRows('SELECT * FROM cc_tbl_tag_category');

        $heads = array(_('Tag'), _('Category') );
        $form->SetColumnHeader($heads);

        $opts = array();
        $opts[''] = '(none)';
        foreach( $cats as $cat )
            $opts[ $cat['tag_category_id'] ] = cc_strchop($cat['tag_category'],7) . '&nbsp;&nbsp;';
        
        $offset = 0;    
        $help = $this->_get_paging($offset,$show_all,$where);
        $sql = "SELECT tags_tag,tags_category,tags_count FROM cc_tbl_tags {$where} ORDER by tags_count DESC LIMIT 50 OFFSET {$offset}";
        $tags = CCDatabase::QueryRows($sql);

        $count = count($tags);
        
        for( $i = 0; $i < $count; $i++ )
        {
            $T =& $tags[$i];
            $id = $T['tags_tag'];
            $pre = "mi[{$id}]";
            
            $a = array();
            $a[] = 
                array(
                    'element_name'  => $pre . '[tags_tagstatic]',
                    'value'      => $id . ' (' . $T['tags_count'] . ')',
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_STATIC);

            $a[] = 
                array(
                    'element_name'  => $pre . '[tags_category]',
                    'value'      => $T['tags_category'],
                    'options'    => $opts,
                    'nobr'       => true,
                    'formatter'  => 'radio',
                    'flags'      => CCFF_REQUIRED 
                );
                    
            $form->SetHiddenField($pre . '[tags_tag]',$id);
            $form->AddGridRow($id,$a);
        
        }

        $help .=<<<EOF
<style>
.form_row_0 {
    background-color: #DEF;
}
</style>
EOF;
        $form->SetFormHelp($help);

        if( empty($_POST) || !$form->ValidateFields() )
        {
            $page->AddForm( $form->GenerateForm() );
        }
        else
        {
            $mi = $_POST['mi'];
            $table = new CCTable('cc_tbl_tags','tags_tag');
            foreach( $mi as $tag )
            {
                $table->Update($tag);
            }
            $page->Prompt($help);
        }        
        
    }
    
    function AdminStragglers()
    {
        $GLOBALS['skip_form_word_hack'] = true; // sigh. go global search for this if you care
      
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-form.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _('Manage Bad Tags');
        $admin = new CCAdmin();
        $admin->BreadCrumbs(true,array('url'=>ccl('admin','tags'),'text'=>_('Manage Tags')),array('url'=>'','text'=>$title));
        $page =& CCPage::GetPage();
        $page->SetTitle($title);
        $form = new CCGridForm();
        $heads = array(_('Tag'), _('Reserve'),_('Becomes...') );
        $form->SetColumnHeader($heads);
        $sql = 'SELECT tags_tag FROM cc_tbl_tags WHERE tags_category = "del" AND tags_type = ' . CCTT_USER;
        $rows = CCDatabase::QueryItems($sql);
        $count = count($rows);

        for( $i = 0; $i < $count; $i++ )
        {
            // some tags (like 'language') map to stuff in
            // $GLOBALS which is where strings lookups happen
            // and cause conflcts. We prepend a goo to avoid
            // these conflicts
            
            $tag = '___' . $rows[$i];
            $pre = "mi[{$tag}]";
            
            $a = array(  
                array(
                    'element_name'  => $pre . "[tag]",
                    'value'      => $tag,
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_STATIC ),
                array(
                    'element_name'  => $pre . "[makerule]",
                    'value'      => '',
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => $pre . '[rule]',
                    'value'      => '',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_NONE),
                );

            $form->AddGridRow($tag,$a);
        
        }

        if( empty($_POST) || !$form->ValidateFields() )
        {
            $page->AddForm( $form->GenerateForm() );
        }
        else
        {
                $data = $_POST['mi'];
                $reserved = array();;
                $aliases = array();
                foreach( $data as $K => $V )
                {
                    $tag = substr($K,3);
                    foreach( $V as $k2 => $v2 )
                    {
                        if( $k2 == 'makerule' )
                        {
                            $reserved[] = $tag;
                            break;
                        }
                        if( empty($v2) )
                            continue;
                        if( $k2 == 'rule' )
                        {
                            if( $v2 == '_ ' )
                            {
                                $v2 =  str_replace('_',',',$v2);
                                break;
                            }
                            $aliases[] = array( $tag, $v2 );
                            break;
                        }
                    }
                }
                
                if( !empty($aliases) )
                {
                    $table = new CCTable('cc_tbl_tag_alias','tag_alias_tag');
                    $columns = array( 'tag_alias_tag', 'tag_alias_alias');
                    $table->InsertBatch($columns, $aliases);
                }
                
                $ttype = CCTT_ADMIN;
                foreach( $reserved as $R )
                {
                    $sql ="INSERT INTO cc_tbl_tags SET tags_tag = '{$R}', tags_type = {$ttype} " .
                             "ON DUPLICATE KEY UPDATE tags_type = {$ttype}";
                    CCDatabase::Query($sql);
                }
                CCUtil::SendBrowserTo( ccl('admin','tags','properties' ) );
                
        }        
        
    }

    function AdminCats()
    {
        $GLOBALS['skip_form_word_hack'] = true; // sigh
        
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-form.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _('Manage Tag Categories');
        $admin = new CCAdmin();
        $admin->BreadCrumbs(true,array('url'=>ccl('admin','tags'),'text'=>_('Manage Tags')),array('url'=>'','text'=>$title));
        $page =& CCPage::GetPage();
        $page->SetTitle($title);
    
        $form = new CCGridForm();
        $heads = array(_('Delete'), _('Name'), _('ID') );
        $form->SetColumnHeader($heads);
        $cats = CCDatabase::QueryRows('SELECT * FROM cc_tbl_tag_category');
        $count = count($cats);
        
        for( $i = 0; $i < $count; $i++ )
        {
            $id = $cats[$i]['tag_category_id'];
            $pre = "mi[{$id}]";
            
            $a = array(  
                array(
                    'element_name'  => $pre . "[delete]",
                    'value'      => '',
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
                array(
                    'element_name'  => $pre . '[name]',
                    'value'      => $cats[$i]['tag_category'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED),
                array(
                    'element_name'  => $pre . '[id]',
                    'value'      => $cats[$i]['tag_category_id'],
                    'class'      => 'cc_form_input_short',
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                );

            $form->AddGridRow($id,$a);
        
        }

        $pre = 'new[%i%]';
        
        $a = array(  
            array(
                'element_name'  => $pre . "[delete]",
                'formatter'  => 'checkbox',
                'flags'      => CCFF_NONE ),
            array(
                'element_name'  => $pre . '[name]',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED),
            array(
                'element_name'  => $pre . '[id]',
                'class'      => 'cc_form_input_short',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
            );
        
        $form->AddMetaRow($a, _('Add Category') );

        if( empty($_POST) || !$form->ValidateFields() )
        {
            $page->AddForm( $form->GenerateForm() );
        }
        else
        {
            $mi = array();
            if( !empty($_POST['mi']) )
                $mi = $_POST['mi'];
            if( !empty($_POST['new']) )
                $mi += $_POST['new'];
            CCUtil::StripSlash($mi);
            $keys = array_keys($mi);
            $count = count($keys);
            $tabs = array();
            for( $i = 0; $i < $count; $i++ )
            {
                $tab =& $mi[$keys[$i]];
                
                if( array_key_exists('delete',$tab ) )
                    continue;

                $tabs[] = $tab;
            }
            $table = new CCTable('cc_tbl_tag_category','tag_category_id');
            $table->DeleteWhere('1');
            $table->InsertBatch(array( 'tag_category', 'tag_category_id'), $tabs );
            
        }

    }

}

?>
