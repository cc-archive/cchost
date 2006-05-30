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
* $Id: cc-reviews.php 3291 2006-05-11 06:27:20Z fourstones $
*
*/

/**
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


class CCForumAddForm extends CCForm
{
    function CCForumAddForm($groups)
    {
        $this->CCForm();

        $fields = array(
            'forum_name' => array( 
                    'label'     => 'Forum Name',
                    'formatter' => 'textedit',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_description' => array( 
                    'label'     => 'Description',
                    'formatter' => 'textedit',
                    'flags'     => CCFF_POPULATE,
                     ),
            'forum_weight' => array( 
                    'label'     => 'Weight',
                    'formatter' => 'textedit',
                    'form_tip'  => 'Lower number places forum higher in listing',
                    'class'     => 'cc_form_input_short',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_post_access'  => array(
                    'label'     => 'Post Access',
                    'formatter' => 'select',
                    'options'   => array(
                             CC_DONT_CARE_LOGGED_IN => 'Everyone',
                             CC_MUST_BE_LOGGED_IN   => 'Registered Users',
                             CC_ADMIN_ONLY          => 'Admins Only'
                             ),
                    'flags'     =>  CCFF_POPULATE,
                    ),
            'forum_read_access'  => array(
                    'label'     => 'Read Access',
                    'formatter' => 'select',
                    'options'   => array(
                             CC_DONT_CARE_LOGGED_IN => 'Everyone',
                             CC_MUST_BE_LOGGED_IN   => 'Registered Users',
                             CC_ADMIN_ONLY          => 'Admins Only'
                             ),
                    'flags'     =>  CCFF_POPULATE,
                    ),
            'forum_group' => array(
                    'label'     => 'Forum Group',
                    'formatter' => 'select',
                    'options'   => $groups,
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            );

        $this->AddFormFields($fields);
    }
}

class CCForumAddGroupForm extends CCForm
{
    function CCForumAddGroupForm()
    {
        $this->CCForm();

        $fields = array(
            'forum_group_name' => array( 
                    'label'     => 'Forum Group Name',
                    'formatter' => 'textedit',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_group_weight' => array( 
                    'label'     => 'Weight',
                    'formatter' => 'textedit',
                    'form_tip'  => 'Lower number places forum group higher in listing',
                    'class'     => 'cc_form_input_short',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            );

        $this->AddFormFields($fields);
    }
}

class CCForumConfirmDeleteForm extends CCForm
{
    function CCForumConfirmDeleteForm($pretty_name)
    {
        $help =<<<END
This action will delete <b>ALL</b> topics ever posted in the forum. There is <b>NO UNDO</b>
so once you delete the forum <b>ALL</b> messages will be gone forever.
END;
        $this->CCForm();
        $this->SetHelpText(cct($help));
        $this->SetSubmitText(sprintf(cct("Delete \"%s\" ?"),$pretty_name));
    }
}

class CCForumMoveThreadForm extends CCForm
{
    function CCForumMoveThreadForm()
    {
        $this->CCForm();

        $forums =& CCForum::GetTable();
        $forums->SetSort('forum_name','ASC');
        $rows = $forums->QueryRows('');
        $options = array();
        foreach( $rows as $row )
        {
            $options[ $row['forum_id'] ] = $row['forum_name'];
        }

        $fields = array(
            'forum_id' => array( 
                    'label'     => 'Forum:',
                    'formatter' => 'select',
                    'form_tip'  => 'Move thread to this fourm',
                    'options'   => $options,
                    'flags'     => CCFF_POPULATE,
                     ),
            );

        $this->AddFormFields($fields);
    }

}

/**
* Forums Admin API
*
*/
class CCForumsAdmin
{
    function MoveThread($api,$thread_id)
    {
        $threads =& CCForumThreads::GetTable();
        $threads->AddJoin( new CCTopics(), 'forum_thread_oldest' );
        $row = $threads->QueryKeyRow($thread_id);

        $form = new CCForumMoveThreadForm();

        if( empty($_POST['forummovethread']) || !$form->ValidateFields() )
        {
            $title = sprintf( cct('Move topic: %s'), $row['topic_name'] );
            CCPage::SetTitle($title);

            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $new_forum = $values['forum_id'];

            // we have to update all the topics in the threads manually
            $topics =& CCTopics::GetTable();
            $where['topic_thread'] = $thread_id;
            $fields['topic_forum'] = $new_forum;
            $topics->UpdateWhere($fields,$where);
            $where2['forum_thread_forum'] = $new_forum;
            $where2['forum_thread_id'] = $thread_id;
            $threads->Update($where2);

            $url = ccl('thread',$thread_id);
            CCUtil::SendBrowserTo($url);
        }

    }

    function _groups_options()
    {
        $groups =& CCForumGroups::GetTable();
        $rows = $groups->QueryRows('');
        $options = array();
        foreach( $rows as $row )
        {
            $options[ $row['forum_group_id'] ] = $row[ 'forum_group_name'];
        }

        return $options;
    }

    function Admin($param1,$param2,$param3)
    {
        $forums = new CCForum();
        $groups = $this->_groups_options();

        if( $param1 == 'newforum' )
        {
            CCPage::SetTitle('Add Forum');

            if( empty($groups) )
            {
                $href = ccl('admin','forums','newgroup');
                CCPage::Prompt("You must <a href=\"$href\">add groups</a> before you can add forums");
                $param1 = '';
            }
            else
            {
                $form = new CCForumAddForm($groups);
                if( empty($_POST['forumadd']) || !$form->ValidateFields() )
                {
                    CCPage::AddForm( $form->GenerateForm() );
                }
                else
                {
                    $form->GetFormValues($values);
                    $forums->Insert($values);
                    CCUtil::SendBrowserTo( ccl('admin','forums') );
                }
            }
        }
        

        if( ($param1 == 'edit') && !empty($param2) )
        {
            CCPage::SetTitle('Edit Forum Properties');

            $row = $forums->QueryKeyRow($param2);
            if( empty($row) )
            {
                $param1 = '';
            }
            else
            {
                $form = new CCForumAddForm($groups);
                if( empty($_POST['forumadd']) )
                {
                    $form->PopulateValues($row);
                }
                elseif( $form->ValidateFields() )
                {
                    $form->GetFormValues($values);
                    $values['forum_id'] = $param2;
                    $forums->Update($values);
                    CCUtil::SendBrowserTo( ccl('admin','forums') );
                }

                CCPage::AddForm( $form->GenerateForm() );
            }
        }

        if( ($param1 == 'delete') && !empty($param2) )
        {
            CCPage::SetTitle('Delete Forum');

            $row = $forums->QueryKeyRow($param2);
            if( empty($row) )
            {
                $param1 = '';
            }
            else
            {
                if( empty($_POST['forumconfirmdelete']) )
                {
                    $form = new CCForumConfirmDeleteForm($row['forum_name']);
                    CCPage::AddForm( $form->GenerateForm() );
                }
                else
                {
                    $this->_delete_forum($row['forum_id']);
                }

            }
        }

        $fgroups =& CCForumGroups::GetTable();
        
        if( $param1 == 'newgroup' )
        {
            CCPage::SetTitle('Add Forum Group');
            $form = new CCForumAddGroupForm();
            if( empty($_POST['forumaddgroup']) || !$form->ValidateFields() )
            {
                CCPage::AddForm( $form->GenerateForm() );
            }
            else
            {
                $form->GetFormValues($values);
                $fgroups->Insert($values);
                CCUtil::SendBrowserTo( ccl('admin','forums') );
            }
        }

        if( ($param1 == 'editgroup') && !empty($param2) )
        {
            CCPage::SetTitle('Edit Forum Group Properties');

            $row = $fgroups->QueryKeyRow($param2);
            if( empty($row) )
            {
                $param1 = '';
            }
            else
            {
                $form = new CCForumAddGroupForm();
                if( empty($_POST['forumaddgroup']) )
                {
                    $form->PopulateValues($row);
                }
                elseif( $form->ValidateFields() )
                {
                    $form->GetFormValues($values);
                    $values['forum_group_id'] = $param2;
                    $fgroups->Update($values);
                    CCUtil::SendBrowserTo( ccl('admin','forums') );
                }

                CCPage::AddForm( $form->GenerateForm() );
            }
        }

        if( ($param1 == 'delgroup') && !empty($param2) )
        {
            CCPage::SetTitle('Delete Forum Group');

            $where['forum_group'] = $param2;
            $forums_in_group = $forums->CountRows($where);
            $href = ccl('admin','forums');
            if( $forums_in_group > 0 )
            {
                CCPage::Prompt("Group is not empty. <a href=\"$href\">Return to forums.</a>");
            }
            else
            {
                $where2['forum_group_id'] = $param2;
                $fgroups->DeleteWhere($where2);
                CCUtil::SendBrowserTo($href);                
            }
        }

        $forum_items = $forums->QueryRows('');

        if( empty($param1) )
        {
            $args['add_forum_link'] = ccl('admin','forums','newforum');
            $args['add_forum_text'] = 'Add Forum';
            $args['edit_forum_link'] = ccl('admin','forums','edit');
            $args['edit_forum_text'] = 'Edit Forum';
            $args['del_forum_link'] = ccl('admin','forums','delete');
            $args['del_forum_text'] = 'Delete Forum';

            $args['add_forum_group_link'] = ccl('admin','forums','newgroup');
            $args['add_forum_group_text'] = 'Add Group';
            $args['edit_forum_group_link'] = ccl('admin','forums','editgroup');
            $args['edit_forum_group_text'] = 'Edit Group';
            $args['del_forum_group_link'] = ccl('admin','forums','delgroup');
            $args['del_forum_group_text'] = 'Delete Group';

            $args['forums'] = $forum_items;
            $args['forum_groups'] = $groups;

            CCPage::SetTitle('Forum Admin');
            CCPage::PageArg('fadmin',$args,'forum_admin');
        }
    }

    function _empty_forum($forum_id)
    {
        $topics =& CCTopics::GetTable();
        $where['topic_forum'] = $forum_id;
        $topics->DeleteWhere($where);
    }

    function _delete_forum($forum_id)
    {
        $this->_empty_forum($forum_id);
        $where2['forum_id'] = $forum_id;
        $forums =& CCForum::GetTable();
        $forums->DeleteWhere($where2);
        $href = ccl('admin','forums');
        CCPage::Prompt("Forum has been permanantly deleted. <a href=\"$href\">Return to forums.</a>");
    }
}

?>