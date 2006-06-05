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
require_once('cclib/cc-feeds.php');

define('CC_EVENT_TOPIC_ROW', 'topicrow');
define('CC_EVENT_TOPIC_DELETE', 'topicdelete');
define('CC_EVENT_TOPIC_REPLY', 'topicreply');

// Topic Delete Flags
define('CCTDF_MARK', 'mark');
define('CCTDF_SHALLOW', 'shallow');
define('CCTDF_DEEP', 'deep');

define('CC_MAX_TOPIC_FEED_ITEMS', 30 );

CCEvents::AddHandler(CC_EVENT_MAP_URLS, array( 'CCTopic',  'OnMapUrls'));

class CCTopicsFeed extends CCFeed
{
    function Feed(&$topics,$title,$feed_type,$feed_url)
    {
        if( $feed_type == 'rss' )
            $template = 'rss_20_topics.xml';
        else
            die('unknown feed type'); // blaaa!

        $count = count($topics);
        for( $i = 0; $i < $count; $i++ )
        {
            $T =& $topics[$i];
            $T['topic_name']       = utf8_encode($this->_cct($T['topic_name']));
            $T['topic_text_plain'] = utf8_encode($this->_cct($T['topic_text_plain']));
            $T['user_real_name']   = utf8_encode($this->_cct($T['user_real_name']));
        }

        $this->_gen_feed_from_records($template,$topics,$title,$feed_url,$feed_type);
    }
}

class CCConfirmTopicDeleteForm extends CCForm
{
    function CCConfirmTopicDeleteForm($pretty_name)
    {
        $this->CCForm();
        $this->SetHelpText(cct("This action can not be reversed..."));
        $this->SetSubmitText(sprintf(cct("Delete \"%s\" ?"),$pretty_name));
    }
}

class CCTopicForm extends CCSecurityVerifierForm
{
    function CCTopicForm($label_text,$submit_text,$visible_title = false)
    {
        $this->CCSecurityVerifierForm();

        $fields = array();

        if( $visible_title )
        {
            $fields['topic_name'] = array(
                            'label'       => cct('Title'),
                            'formatter'   => 'textedit',
                            'flags'      => CCFF_REQUIRED | CCFF_POPULATE);
        }

        $fields += array( 
                    'topic_text' => array(
                            'label'       => $label_text,
                            'formatter'   => 'textarea',
                            'flags'      => CCFF_REQUIRED | CCFF_POPULATE),
                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => cct('Security Key'),
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip'   => cct('Type in characters above'),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE)
                    );

        $this->AddFormFields($fields);
        $this->SetSubmitText($submit_text);
    }
}

class CCTopicReplyForm extends CCTopicForm
{
    function CCTopicReplyForm()
    {
        $this->CCTopicForm(cct('Reply'),'Submit Reply');
    }
}

class CCTopicEditForm extends CCTopicForm
{
    function CCTopicEditForm($static_title=true)
    {
        $this->CCTopicForm(cct('Text'),'Submit Changes');
        if( $static_title )
        {
            $flags = CCFF_STATIC | CCFF_NOUPDATE | CCFF_POPULATE;
            $type = 'statictext';
        }
        else
        {
            $flags = CCFF_REQUIRED | CCFF_POPULATE;
            $type = 'edittext';
        }
        $f['topic_name'] = array(
                            'label'       => cct('Title'),
                            'formatter'   => $type,
                            'flags'      => $flags
                            );

        //ugh -- don't do this
        $this->_form_fields = array_merge($f,$this->_form_fields);
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

        if( $row['topic_thread'] )
        {
            // these are overrwritten by reviews
            $row['topic_permalink'] = ccl('thread', $row['topic_thread'] ) . 
                                        '#' . $row['topic_id'];
        }

        if( $row['user_id'] )
        {
            // these are overrwritten by reviews
            $row['user_post_count'] = $row['user_num_posts'];
            $row['user_post_text']  = cct('Posts');
            $row['user_post_url']   = ccl( 'forums', 'people', $row['user_name'] );
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

            if( CCUser::IsAdmin() || (CCUser::CurrentUser() == $row['topic_user']) )
            {
                $row['commands']['delete'] = array( 'url' => ccl('topics','delete',$row['topic_id']),
                                            'script' => '',
                                            'text' => cct('Delete') );
                $row['commands']['edit'] = array( 'url' => ccl('topics','edit',$row['topic_id']),
                                            'script' => '',
                                            'text' => cct('Edit') );

            }

            if( $this->_can_reply() )
            {
                $text = $this->GetReplyText($row);
                
                $row['commands']['quote'] = array( 'url' => ccl('topics','quote',$row['topic_id']),
                                            'script' => '',
                                            'text' => cct('Reply with quote') );
                
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
            $text = cct('Reply to yourself');
        }
        elseif( !empty($row['user_real_name']) )
        {
            $text = sprintf(cct('Reply to %s'),$row['user_real_name']);
        }
        else
        {
            $text = cct('Reply');
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

    function GetTree(&$record,$sort='')
    {
        // this will return recs marked as deleted

        if( !empty($sort) )
            $order = "ORDER BY children.topic_date $sort";
        else
            $order = '';

        $parent_id = $record['topic_id'];
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

    function _can_reply()
    {
        return CCUser::IsLoggedIn();
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
    function Reply($topic_id,$is_quote = false)
    {
        if( !CCTopics::_can_reply() )
            exit; // for now...

        global $CC_GLOBALS;

        $form =  new CCTopicReplyForm();
        $topics =& CCTopics::GetTable();
        $record = $topics->GetRecordFromID($topic_id);

        if( empty($record) )
        {
            CCPage::Prompt(cct('Can not find that topic, it might have been deleted by the author'));
            return;
        }

        // this is a round about way of doing form validation
        // but we do this in case we have to shove a 'quote' 
        // into the field, we only want to do that the very
        // first time the user comes into the form

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

            $form->GenerateForm();
            $args = array_merge($CC_GLOBALS,$form->GetTemplateVars());
            $args['root-url'] = cc_get_root_url();
            $args['topic'] = $record;
            $args['macro'] = 'post_reply';
            $template = new CCTemplate($CC_GLOBALS['template-root'] . 'topics.xml');
            $html = $template->SetAllAndParse($args);
            CCPage::AddPrompt('body_text',$html);
            $this->AddLinks();
        }
        else
        {
            $replang = cct('Reply');
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

    function Delete($topic_id)
    {
        $this->CheckTopicAccess($topic_id);
        $topics =& CCTopics::GetTable();
        CCPage::SetTitle(cct("Deleting Topic"));
        if( empty($_POST['confirmtopicdelete']) )
        {
            $topics =& CCTopics::GetTable();
            $pretty_name = $topics->QueryItemFromKey('topic_name',$topic_id);
            if( empty($pretty_name) )
                $pretty_name = 'Topic';
            $form = new CCConfirmTopicDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $this->DeleteTopic($topic_id);
            //CCPage::Prompt(cct("Topic has been deleted"));
            CCUtil::SendBrowserTo();
        }
    }

    function DeleteTopic($topic_id)
    {
        $topics =& CCTopics::GetTable();
        $tree =& CCTopicTree::GetTable();
        $arg1['topic_tree_child'] = $topic_id;
        $parent_id = $tree->QueryItem('topic_tree_parent',$arg1);
        if( $parent_id )
        {
            $arg2['topic_tree_parent'] = $topic_id;
            $child_count = $tree->CountRows($arg2);
            if( $child_count < 1 )
            {
                // one parent, no children:
                // delete from tree
                $tree->DeleteWhere($arg1);
                CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_SHALLOW, $topic_id ));
                // delete from topics
                $topics->DeleteKey($topic_id);
            }
            else
            {
                // has children and a parent
                // mark as deleted
                $args4['topic_id'] = $topic_id;
                $args4['topic_deleted'] = 1;
                $topics->Update($args4);
                CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_MARK, $topic_id ));
            }
        }
        else
        {
            // top level topic, wipe it's tree...
            CCEvents::Invoke( CC_EVENT_TOPIC_DELETE, array( CCTDF_DEEP, $topic_id ));
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
                CCPage::AddScriptBlock('ajax_block');
                CCPage::AddScriptBlock('dl_popup_script',true);
            }
                
            CCPage::AddScriptLink( ccd('ccextras','cc-topics.js') );

            $css_file = $CC_GLOBALS['template-root'] . 'skin-' . $CC_GLOBALS['skin'] . '-topics.css';
            CCPage::AddLink('head_links', 'stylesheet', 'text/css', 
                                ccd($css_file) , 'Default Style');
            $done = true;
        }
    }

    function Edit($topic_id)
    {
        $this->CheckTopicAccess($topic_id);
        global $CC_GLOBALS;

        $form =  new CCTopicEditForm();
        $topics =& CCTopics::GetTable();
        $record = $topics->GetRecordFromID($topic_id);

        
        $inpost = !empty($_POST['topicedit']);

        if( !$inpost )
            $form->PopulateValues($record);

        if( !$inpost || !$form->ValidateFields() )
        {
            CCPage::SetTitle(sprintf(cct("Edit topic: '%s'"),$record['topic_name']));
            $form->GenerateForm();
            $args = array_merge($CC_GLOBALS,$form->GetTemplateVars());
            $args['root-url'] = cc_get_root_url();
            $args['macro'] = 'post_edit';
            $template = new CCTemplate($CC_GLOBALS['template-root'] . 'topics.xml');
            $html = $template->SetAllAndParse($args);
            CCPage::AddPrompt('body_text',$html);
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

    function View($topic_id)
    {
        global $CC_GLOBALS;

        $topics =& CCTopics::GetTable();
        $args = $CC_GLOBALS;
        $args['root-url']  = cc_get_root_url();
        $args['show_cmds'] = true;
        $args['macro']     = 'one_topic';
        $args['topic']     = $topics->GetRecordFromID($topic_id);
        $template = new CCTemplate($CC_GLOBALS['template-root'] . 'topics.xml');
        $html = $template->SetAllAndParse($args);
        CCPage::AddPrompt('body_text',$html);
        $this->AddLinks();

        $name = $args['topic']['topic_name'];
        $title = sprintf('"%s"',$name);
        CCPage::SetTitle($title);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('topics','reply'),  array( 'CCTopic', 'Reply'),   CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('topics','quote'),  array( 'CCTopic', 'Quote'),   CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('topics','delete'), array( 'CCTopic', 'Delete'),  CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('topics','edit'),   array( 'CCTopic', 'Edit'),    CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('topics','view'),   array( 'CCTopic', 'View'),    CC_DONT_CARE_LOGGED_IN);
    }

}
?>