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

/**
*/
// Topic Delete Flags
define('CCTDF_MARK',            'mark');
define('CCTDF_SHALLOW',         'shallow');
define('CCTDF_DEEP',            'deep');

define('CC_MAX_TOPIC_FEED_ITEMS', 30 );


require_once('cclib/cc-feed.php');

class CCTopicsFeed extends CCFeed
{
    function Feed(&$topics,$title,$feed_type,$feed_url)
    {
        // TODO: Fix this to be generic for feed types...
        if( $feed_type == 'rss' )
            $template = 'rss_20_topics.xml';
        else
            die(_('You have requested an unknown feed type.')); // blaaa!

        $title = $this->_cct($title);
        $count = count($topics);
        for( $i = 0; $i < $count; $i++ )
        {
            $T =& $topics[$i];
            $T['topic_name']       = utf8_encode($this->_cct($T['topic_name']));
            $T['topic_text_plain'] = utf8_encode($this->_cct($T['topic_text_plain']));
            $T['user_real_name']   = utf8_encode($this->_cct($T['user_real_name']));
            $T['topic_text_html']  = utf8_encode($this->_cct($this->_clean_urls($T['topic_text_html'])));

        }

        $this->_gen_feed_from_records($template,$topics,$title,$feed_url,$feed_type);
    }
}

class CCTopicsi18n extends CCTable
{
    function CCTopicsi18n()
    {
        $this->CCTable('cc_tbl_topic_i18n', 'topic_i18n_topic');
        $this->AddJoin( new CCTopics(), 'topic_i18n_xlat_topic' );
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
        static $table;
        if( !isset($table) )
            $table = new CCTopicsi18n();
        return $table;
    }

    function & GetTranslations($topic_id)
    {
        $w['topic_i18n_topic'] = $topic_id;
        $rows = $this->QueryRows($w);
        $keys = array_keys($rows);
        $topics =& CCTopics::GetTable();
        foreach( $keys as $key )
        {
            $R =& $rows[$key];
            if( empty($R['topic_id']) )
            {
                // now featuring: Insta-sync!
                $delw['topic_i18n_topic'] = $topic_id;
                $delw['topic_i18n_xlat_topic'] = $R['topic_i18n_xlat_topic'];
                $this->DeleteWhere($delw);
                unset($rows[$key]);
                continue;
            }
            // force xlat off so we don't recurse
            $R['topic_can_xlat'] = 0;
            $topics->GetRecordFromRow($R);
            if( empty($R['topic_permalink']) )
                $R['topic_permalink'] = ccl('topics','view',$R['topic_id']);
        }
        return $rows;
    }

    function GetTranslationOf($topic_id,$topics)
    {
        $w['topic_i18n_xlat_topic'] = $topic_id;
        $id = $this->QueryKey($w);
        $row = $topics->QueryKeyRow($id);
        $row['topic_can_xlat'] = 0;
        return $topics->GetRecordFromRow($row);
    }
}

/**
* Base class wrapper for topics table
*
*/
class CCTopics extends CCTable
{
    var $_type_limit;
    var $_show_deleted;

    function CCTopics($show_deleted=false)
    {
        require_once('cclib/cc-user.php');

        $this->CCTable('cc_tbl_topics', 'topic_id');
        $this->SetOrder('topic_date','desc');
        $this->AddJoin( new CCUsers(), 'topic_user' );
        $this->AddExtraColumn('topic_text as topic_text_html, ' .
                              'topic_text as topic_text_plain, ' .
                              '0 as topic_permalink, ' .
                              '0 as topic_search_result_info'
                    );
        $this->_show_deleted = $show_deleted;
    }

    function ShowDeleted($show=true)
    {
        $old = $this->_show_deleted;
        $this->_show_deleted = $show;
        return $old;
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
        static $table;
        if( !isset($table) )
            $table = new CCTopics();
        return $table;
    }

    function LimitType($type)
    {
        $this->_type_limit = $type;
    }

    function _attach_user(&$row)
    {
        $users =& CCUsers::GetTable();
        $user = $users->GetRecordFromID($row['topic_user']);
        $row += $user;
    }

    function & GetRecordFromRow(&$row)
    {
        $this->_attach_user($row);

        if( $row['topic_can_xlat'] )
        {
            $translations =& CCTopicsi18n::GetTable();
            $row['translations'] = $translations->GetTranslations($row['topic_id']);
        }
        else
        {
            $row['translations'] = false;
        }

        if( $row['topic_thread'] )
        {
            // these are overrwritten by reviews
            $row['topic_permalink'] = ccl('thread', $row['topic_thread'] ) . 
                                        '#' . $row['topic_id'];
        }

        if( !empty($row['user_id']) )
        {
            // these are overrwritten by reviews
            $row['user_post_count'] = $row['user_num_posts'];
            $row['user_post_text']  = _('Posts');
            $row['user_post_url']   = ccl( 'forums', 'people', $row['user_name'] );
        }

        if( $row['topic_type'] == 'xlat' )
        {
            $translations =& CCTopicsi18n::GetTable();
            $row['translation_of'] = $translations->GetTranslationOf($row['topic_id'],$this);
            $row['str_translation_of'] = _('Translation of...');
        }

        CCEvents::Invoke(CC_EVENT_TOPIC_ROW,array(&$row) );

        if( isset($row['topic_is_feed']) )
        {
            $time = strtotime($row['topic_date']);
            $row['atom_pubdate'] = CCUtil::FormatDate( CC_RFC3339_FORMAT, $time );
            $row['rss_pubdate']  = CCUtil::FormatDate( CC_RFC822_FORMAT,  $time );
        }
        else
        {
            if( !isset($row['commands']) )
                $row['commands'] = array();

            if( $row['topic_can_xlat'] && CCUser::IsLoggedIn() )
            {
                $row['commands']['xlat'] = array( 
                                            'url' => ccl('topics','translate',$row['topic_id'])
                                                             . '#edit',
                                            'script' => '',
                                            'text' => _('Translate') );
            }

            if( CCUser::IsAdmin() || (CCUser::CurrentUser() == $row['topic_user']) )
            {
                $row['commands']['delete'] = array( 'url' => ccl('topics','delete',$row['topic_id']),
                                            'script' => '',
                                            'text' => _('Delete') );
                $row['commands']['edit'] = array( 'url' => ccl('topics','edit',$row['topic_id']),
                                            'script' => '',
                                            'text' => _('Edit') );

            }

            if( CCUser::IsAdmin() )
            {
                if( empty($row['topic_locked']) )
                    $text = _('Lock');
                else
                    $text = _('Unlock');

                $row['commands']['lock'] = array( 'url' => ccl('topics','lock',$row['topic_id']),
                                                  'script' => '',
                                                  'text' => $text );
                
                $tree =& CCTopicTree::GetTable();
                $awhere['topic_tree_parent'] = $row['topic_id'];
                $has_children = $tree->CountRows($awhere);
                if( $has_children )
                {
                    $row['commands']['killbranch'] 
                        = array( 'url' => ccl('topics','delete',$row['topic_id'],'branch'),
                                                      'script' => '',
                                                      'text' => _('Delete branch') );
                }
            }

            if( $this->_can_reply($row) )
            {
                $text = $this->GetReplyText($row);
                
                $row['commands']['quote'] = array( 'url' => ccl('topics','quote',$row['topic_id']),
                                            'script' => '',
                                            'text' => _('Reply with quote') );
                
                $row['commands']['reply'] = array( 'url' => ccl('topics','reply',$row['topic_id']),
                                            'script' => '',
                                            'text' => $text );

            }

        }

        return $row;
    }

    function GetReplyText(&$row)
    {
        if( CCUser::CurrentUser() == $row['topic_user'] )
        {
            $text = _('Reply to yourself');
        }
        elseif( !empty($row['user_real_name']) )
        {
            $text = sprintf(_('Reply to %s'),$row['user_real_name']);
        }
        else
        {
            $text = _('Reply');
        }

        return $text;
    }

    function GetTreeFromRecords(&$records,$sort='')
    {
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $this->GetTree($records[$i],$sort);
        }
    }

    function GetDescendantsIDs($topic_id)
    {
        $results = array();
        $topic_tree = CCTopicTree::GetTable();
        $this->_inner_get_descendants_ids($topic_id,$results,$topic_tree);
        return $results;
    }

    function _inner_get_descendants_ids($parent_id,&$results,&$tree)
    {
        $w['topic_tree_parent'] = $parent_id;
        $children = $tree->QueryRows($w);
        foreach( $children as $child )
        {
            $results[] = $child['topic_tree_child'];
            $this->_inner_get_descendants_ids($child['topic_tree_child'],$results,$tree);
        }
    }

    function GetTree(&$record,$sort='')
    {
        // this will return recs marked as deleted

        if( !empty($sort) )
            $order = "ORDER BY children.topic_date $sort";
        else
            $order = '';

        $parent_id = $record['topic_id'];

        // upon second look: this CAN'T be even close to the most 
        // efficient way to do this... why isn't this
        //
        //  select * from cc_tbl_topics_tree
        //        join cc_tbl_topics on topic_id = topic_tree_child
        //        where topic_tree_parent = $parent_id
        //
        // ????
        $sql =<<<END
            SELECT children.*, children.topic_text as topic_text_html, 
                               children.topic_text as topic_text_plain
            FROM cc_tbl_topics parent
            JOIN cc_tbl_topic_tree tree ON parent.topic_id = tree.topic_tree_parent
            JOIN cc_tbl_topics children ON children.topic_id = tree.topic_tree_child
            WHERE parent.topic_id = '$parent_id'
            $order
END;
        
        $rows = CCDatabase::QueryRows($sql);
        $count = count($rows);
        $users = CCUsers::GetTable();
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $rows[$i];
            $this->GetRecordFromRow($R);
            $this->GetTree($R);
        }

        $record['topic_children'] = $rows;
    }

    // overwrite parent's version to add type limit
    function _get_select($where,$columns='*')
    {
        if( !empty($this->_type_limit) )
        {
            $w = "topic_type = '" . $this->_type_limit . "'";

            if( empty($where) )
            {
                $where = $w;
            }
            else
            {
                $where = $this->_where_to_string($where);
                $where .= " AND ($w)";
            }
        }

        if( empty($this->_show_deleted) )
        {
            $w = 'topic_deleted < 1';

            if( empty($where) )
            {
                $where = $w;
            }
            else
            {
                $where = $this->_where_to_string($where);
                $where .= " AND ($w)";
            }
        }


        return parent::_get_select($where,$columns);
    }

    function _can_reply(&$row)
    {
        return (CCUser::IsLoggedIn() && empty($row['topic_locked'])) ||
                CCUser::IsAdmin();
    }

}


/**
* Wrapper for topics tree table
*
*/
class CCTopicTree extends CCTable
{
    function CCTopicTree()
    {
        $this->CCTable('cc_tbl_topic_tree', 'topic_tree_id');
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
        static $table;
        if( !isset($table) )
            $table = new CCTopicTree();
        return $table;
    }

}


class CCTopic
{
    function _verify_topic($topic_id,&$topics,&$record,$is_translate=false)
    {
        $topic_id = intval(CCUtil::Strip($topic_id));
        if( empty($topic_id) )
        {
            CCPage::Prompt(_('no topic specified.'));
            CCUtil::Send404(false,__FILE__,__LINE__); // just a hack
            return false;
        }

        $topics = CCTopics::GetTable();
        $record = $topics->GetRecordFromID($topic_id);

        if( !CCTopics::_can_reply($record) )
        {
            if( !$is_translate || empty($record['topic_can_xlat']) || !CCUser::IsLoggedIn() )
            {
                CCPage::Prompt(_('You are not authorized to post here.'));
                return false;
            }
        }

        if( empty($record) )
        {
            CCPage::Prompt(_('Cannot find that topic.') . ' ' . 
                           _('It might have been deleted by the author.'));
            CCUtil::Send404(false,__FILE__,__LINE__);
            return false;
        }

        return true;
    }

    function _list_translatable_topics()
    {
        CCPage::SetTitle(_('Topics for Translation'));

        $topics =& CCTopics::GetTable();
        $w['topic_can_xlat'] = 1;
        CCPage::AddPagingLinks($topics,$w);
        $results = $topics->GetRecords($w);
        $translations = CCTopicsi18n::GetTable();
        $keys = array_keys($results);
        foreach( $keys as $key )
        {
            $R =& $results[$key];
            $R['translations'] = $translations->GetTranslations($R['topic_id']);
        }

        CCPage::PageArg('str_none',_('(There are no translations for this topic)'));
        CCPage::PageArg('str_current_translations',_('Current translations') . ': ');
        CCPage::PageArg('topic_short_macro','topics.xml/xlat_list');
        CCPage::PageArg('topics',$results,'topic_short_macro');
        $this->AddLinks();

    }

    function Translate($topic_id='')
    {
        if( empty($topic_id) )
        {
            $this->_list_translatable_topics();
            return;
        }

        $topics = '';
        $record = array();

        if( !$this->_verify_topic($topic_id,$topics,$record,true) )
            return;

        CCPage::SetTitle( sprintf(_('Translation of "%s"'), $record['topic_name']) );

        require_once('ccextras/cc-topics-forms.inc');

        $form = new CCTopicTranslateForm($record['topic_name']);
        if( empty($_POST['topictranslate']) || !$form->ValidateFields() )
        {
            CCPage::PageArg('post_translation_macro','topics.xml/post_reply');
            CCPage::PageArg('topic',$record,'post_translation_macro');
            CCPage::AddForm( $form->GenerateForm() );
            $this->AddLinks();
        }
        else
        {
            $form->GetFormValues($values);
            $next_id = $topics->NextID();

            $xlat_args['topic_i18n_language'] = $values['topic_i18n_language'];
            $xlat_args['topic_i18n_topic'] = $record['topic_id'];
            $xlat_args['topic_i18n_xlat_topic'] = $next_id;
            $translations =& CCTopicsi18n::GetTable();
            $translations->Insert($xlat_args);
            
            unset($values['topic_i18n_language']);

            $values['topic_id']    = $next_id;
            $values['topic_date']  = date('Y-m-d H:i:s',time());
            $values['topic_user']  = CCUser::CurrentUser();
            $values['topic_type']  = 'xlat';
            $values['topic_forum'] = 
            $values['topic_thread'] = 0;
            $topics->Insert($values);

            CCUtil::SendBrowserTo( ccl('topics','view',$next_id) );
         }
    }

    function Reply($topic_id='',$is_quote = false)
    {
        $topics = null;
        $record = null;

        if( !$this->_verify_topic($topic_id,$topics,$record) )
            return;

        require_once('ccextras/cc-topics-forms.inc');

        // this is a round about way of doing form validation
        // but we do this in case we have to shove a 'quote' 
        // into the field, we only want to do that the very
        // first time the user comes into the form

        $form = new CCTopicReplyForm();
        $did_validation = false;
        $validated = false;
        if( !empty($_POST['topicreply']) )
        {
            $validated = $form->ValidateFields();
            $did_validation = true;
        }

        if( empty($_POST['topicreply']) || ($did_validation && !$validated) )
        {
            $title = CCTopics::GetReplyText($record);
            CCPage::SetTitle($title);

            if( $is_quote === true && !$did_validation)
            {
                // er, what if formatting is OFF ??

                $users =& CCUsers::GetTable();
                $user_real_name = $users->QueryItemFromKey('user_real_name',$record['topic_user']);
                $quote_text = '[quote=' . 
                              $user_real_name . 
                              ']' . 
                              $record['topic_text_plain'] .
                              '[/quote]';
                $form->SetFormValue( 'topic_text', $quote_text);
            }

            /*
                WRONG WAY:
            $form->GenerateForm();
            $args = array_merge($CC_GLOBALS,$form->GetTemplateVars());
            $args['root-url'] = cc_get_root_url();
            $args['topic'] = $record;

            $args['macro'] = 'post_reply';
            $tfile = CCTemplate::GetTemplate('topics .xml');
            $template = new CCTemplate($tfile);
            $html = $template->SetAllAndParse($args);
            CCPage::AddPrompt('body_text',$html);
            */

            CCPage::PageArg('post_reply_macro','topics.xml/post_reply');
            CCPage::PageArg('topic',$record,'post_reply_macro');
            CCPage::AddForm( $form->GenerateForm() );

            $this->AddLinks();
        }
        else
        {
            $replang = _('Reply');
            if( strstr($record['topic_name'],$replang) === false )
                $name = sprintf( '%s (%s)', $record['topic_name'], $replang );
            else
                $name = $record['topic_name'];
            $form->GetFormValues($values);
            $next_id = $topics->NextID();
            $values['topic_id']    = $next_id;
            $values['topic_date']  = date('Y-m-d H:i:s',time());
            $values['topic_user']  = CCUser::CurrentUser();
            $values['topic_type']  = 'reply';
            $values['topic_forum'] = $record['topic_forum'];
            $values['topic_thread'] = $record['topic_thread'];
            $values['topic_name']   = $name;
            // $values['topic_top']   = $record['topic_top'];
            $topics->Insert($values);
            $this->Sync($topic_id,$next_id);

            if( !empty($values['topic_thread']) )
            {
                // err, this should be in forums.inc no?
                require_once('ccextras/cc-forums.inc');
                $threads =& CCForumThreads::GetTable();
                $tvalues['forum_thread_id']   = $record['topic_thread'];
                $tvalues['forum_thread_newest'] = $next_id;
                $tvalues['forum_thread_date'] = $values['topic_date'];
                $threads->Update($tvalues);
            }

            CCEvents::Invoke( CC_EVENT_TOPIC_REPLY, array( &$values, &$record ) );

            CCUtil::SendBrowserTo();
        }
    }

    function Sync($parent_id,$child_id)
    {
        $topic_tree =& CCTopicTree::GetTable();
        $args['topic_tree_parent'] = $parent_id;
        $args['topic_tree_child'] = $child_id;
        $topic_tree->Insert($args);
    }

    function Quote($topic_id)
    {
        $this->Reply($topic_id,true);
    }

    function CheckTopicAccess($topic_id)
    {
        if( CCUser::IsLoggedIn() )
        {
            if( CCUser::IsAdmin() )
                return;
            $topics =& CCTopics::GetTable();
            $user_id = $topics->QueryItemFromKey('topic_user',$topic_id);
            if( $user_id == CCUser::CurrentUser() )
                return;
        }
        cc_exit();
    }

    function Delete($topic_id,$branch=false)
    {
        if( $branch && !CCUser::IsAdmin() )
            cc_exit();
        $this->CheckTopicAccess($topic_id);
        $topics =& CCTopics::GetTable();
        CCPage::SetTitle(_("Deleting Topic"));
        if( empty($_POST['confirmtopicdelete']) )
        {
            $topics =& CCTopics::GetTable();
            $pretty_name = $topics->QueryItemFromKey('topic_name',$topic_id);
            if( empty($pretty_name) )
                $pretty_name = _('Topic');
            require_once('ccextras/cc-topics-forms.inc');
            $form = new CCConfirmTopicDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->DeleteTopic($topic_id,$branch);
            //CCPage::Prompt(_("Topic has been deleted"));
            CCUtil::SendBrowserTo();
        }
    }

    function DeleteTopic($topic_id,$branch=false)
    {
        $topics =& CCTopics::GetTable();
        $tree =& CCTopicTree::GetTable();
        $arg1['topic_tree_child'] = $topic_id;
        $parent_id = $tree->QueryItem('topic_tree_parent',$arg1);
        if( $parent_id && !$branch )
        {
            $arg2['topic_tree_parent'] = $topic_id;
            $child_count = $tree->CountRows($arg2);
            if( $child_count < 1 )
            {
                // one parent, no children:
                // delete from tree
                $tree->DeleteWhere($arg1);
                CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_SHALLOW, 
                                                                $topic_id ));
                // delete from topics
                $topics->DeleteKey($topic_id);
                // If the parent is marked as deleted, nuke it
                $old_show_val = $topics->ShowDeleted(true);
                $mark = $topics->QueryItemFromKey('topic_deleted',$parent_id);
                $topics->ShowDeleted($old_show_val);
                if( $mark )
                    $this->DeleteTopic($parent_id); // recurse 'up'
            }
            else
            {
                // has children and a parent
                // mark as deleted
                $args4['topic_id'] = $topic_id;
                $args4['topic_deleted'] = 1;
                $topics->Update($args4);
                CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_MARK, 
                                                                $topic_id ));
            }
        }
        else
        {
            // top level topic or branch flag is set, wipe it's tree...
            CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_DEEP, 
                                                            $topic_id ));
            $this->_delete_tree($topic_id);
        }
    }

    function _delete_tree($parent_id)
    {
        $tree =& CCTopicTree::GetTable();
        $where['topic_tree_parent'] = $parent_id;
        $rows = $tree->QueryRows($where);
        foreach( $rows as $row )
            $this->_delete_tree($row['topic_tree_child']);
        $tree->DeleteWhere($where);
        $topics =& CCTopics::GetTable();
        $topics->DeleteKey($parent_id);
    }

    function AddLinks()
    {
        global $CC_GLOBALS;

        static $done;
        if( !isset($done) )
        {
            if( function_exists('_cc_format_links') )
            {
                _cc_format_links();
            }
            else
            {
                CCPage::AddScriptLink( ccd('cc_templates', 'dl_popup.js'), false );
            }
                
            CCPage::AddScriptLink( ccd('cctemplates', 'js', 'cc-topics.js'), false );
            $cname = 'skin-' . $CC_GLOBALS['skin'] . '-topics.css';
            $css_file = CCTemplate::GetTemplate($cname,false);
            CCPage::AddLink('head_links', 'stylesheet', 'text/css', 
                                ccd($css_file) , 'Default Style');

            CCPage::PageArg('topic_flag_tip',_('Flag this topic for possible violation of terms'));
            CCPage::PageArg('upload_flag_tip', _('Flag this upload for possible violation of terms'));

            $done = true;
        }
    }

    function GetText($topic_id='')
    {
        $topics =& CCTopics::GetTable();
        $topic_id = intval(CCUtil::Strip($topic_id));
        if( empty($topic_id) || (!($record = $topics->GetRecordFromId($topic_id))) )
        {
            print('no such topic');
            exit;
        }
        print( $record['topic_text_html'] );
        exit;
    }

    function Edit($topic_id)
    {
        $this->CheckTopicAccess($topic_id);
        global $CC_GLOBALS;

        require_once('ccextras/cc-topics-forms.inc');

        $form =  new CCTopicEditForm();
        $topics =& CCTopics::GetTable();
        $record = $topics->GetRecordFromID($topic_id);
        
        $inpost = !empty($_POST['topicedit']);

        if( !$inpost )
            $form->PopulateValues($record);

        if( !$inpost || !$form->ValidateFields() )
        {
            CCPage::SetTitle(sprintf(_("Edit topic: '%s'"),$record['topic_name']));
            /*
                WRONG WAY 
            $form->GenerateForm();
            $args = array_merge($CC_GLOBALS,$form->GetTemplateVars());
            $args['root-url'] = cc_get_root_url();
            $args['macro'] = 'post_edit';
            $tfile = CCTemplate::GetTemplate('topics .xml');
            $template = new CCTemplate($tfile);
            $html = $template->SetAllAndParse($args);
            CCPage::AddPrompt('body_text',$html);
            */
    
            CCPage::PageArg( 'post_edit_macro', 'topics.xml/post_edit' );
            CCPage::PageArg( '_bogus', true, 'post_edit_macro'); 
            CCPage::AddForm( $form->GenerateForm() );

            $this->AddLinks();
        }
        else
        {
            $form->GetFormValues($values);
            $values['topic_id']   = $topic_id;
            $values['topic_edited'] = date('Y-m-d H:i:s',time());
            $topics->Update($values);
            CCUtil::SendBrowserTo();
        }
    }

    function View($topic_id='')
    {
        global $CC_GLOBALS;

        CCPage::SetTitle(_('View Topic'));

        $topic_id = intval(CCUtil::Strip($topic_id));
        if( empty($topic_id) )
        {
            CCPage::Prompt(_('No topic specified'));
            CCUtil::Send404(false,__FILE__,__LINE__);
            return;
        }

        $topics =& CCTopics::GetTable();
        $topic = $topics->GetRecordFromID($topic_id);;

        if( empty($topic) )
        {
            CCPage::Prompt(_('Topic does not exist. (May have been deleted by author.)'));
            CCUtil::Send404(false,__FILE__,__LINE__);
            return;
        }

        /*
            WRONG WAY 
        $args = $CC_GLOBALS;
        $args['root-url']  = cc_get_root_url();
        $args['show_cmds'] = true;
        $args['macro']     = 'one_topic';
        $args['topic']     = $topics->GetRecordFromID($topic_id);
        $tfile = CCTemplate::GetTemplate('topics .xml');
        $template = new CCTemplate($tfile);
        $html = $template->SetAllAndParse($args);
        CCPage::AddPrompt('body_text',$html);
        */


        CCPage::PageArg( 'one_topic_macro','topics.xml/one_topic');
        CCPage::PageArg( 'show_cmds', true );
        CCPage::PageArg( 'topic', $topic, 'one_topic_macro' );

        $this->AddLinks();

        $name = $topic['topic_name'];
        $title = $name{0} == '"' ? $name : "$name";
        CCPage::SetTitle($title);
    }

    function Lock($topic_id)
    {
        CCPage::SetTitle(_('Locking and Unlocking Topic Replies'));

        $topics =& CCTopics::GetTable();
        $topic = $topics->QueryKeyRow($topic_id);
        if( empty($topic) )
        {
            CCPage::Prompt(_('Topic does not exist'));
            return;
        }

        $topics->GetTree($topic);
        $lock = !$topic['topic_locked'];
        $this->_lock_tree($topic,$topics,$lock);

        $prompt = $lock ? _('Topic is now locked') 
                        : _('Topic is now unlocked');

        if( !empty($_SERVER['HTTP_REFERER']) )
        {
            $prompt .= ' <a href="' . $_SERVER['HTTP_REFERER'] . '">' .
                       _('Return to previous page') . '</a>';   
        }

        CCPage::Prompt($prompt);
    }

    function _lock_tree(&$topic,$topics,$lock)
    {
        $args['topic_id']     = $topic['topic_id'];
        $args['topic_locked'] = $lock;
        $topics->Update($args);
        if( !empty($topic['topic_children']) )
        {
            $c = count($topic['topic_children']);
            for( $i = 0; $i < $c; $i++ )
            {
                $this->_lock_tree($topic['topic_children'][$i],$topics,$lock);
            }
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('topics','reply'),  array( 'CCTopic', 'Reply'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{topic_id}/[isquote]', 
            _('Display topic reply form') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','gettext'),  array( 'CCTopic', 'GetText'),   
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{topic_id}', 
            _('Ajax callback to get topic text') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','translate'),  array( 'CCTopic', 'Translate'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{topic_id}', 
            _('Display topic translate form') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','quote'),  array( 'CCTopic', 'Quote'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{topic_id}', 
            _('Display quote reply form') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','delete'), array( 'CCTopic', 'Delete'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{topic_id}', 
            _('Display delete topic form') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','edit'),   array( 'CCTopic', 'Edit'),    
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{topic_id}', 
            _('Display an edit topic form') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','view'),   array( 'CCTopic', 'View'),    
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{topicid}', 
            _('Display a topic thread') , CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('topics','lock'),   array( 'CCTopic', 'Lock'),    
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{topicid}', 
            _('Lock a topic thread from replies') , CC_AG_FORUMS );
    }

}
?>
