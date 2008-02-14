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
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-form.php');
require_once('cchost_lib/ccextras/cc-forums.inc');

class CCForumAddForm extends CCForm
{
    function CCForumAddForm($groups)
    {
        $this->CCForm();

        $fields = array(
            'forum_name' => array( 
                    'label'     => _('Forum Name'),
                    'formatter' => 'textedit',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_description' => array( 
                    'label'     => _('Description'),
                    'formatter' => 'textedit',
                    'flags'     => CCFF_POPULATE,
                     ),
            'forum_weight' => array( 
                    'label'     => _('Weight'),
                    'formatter' => 'textedit',
                    'form_tip'  => _('Lower number places forum higher in listing'),
                    'class'     => 'cc_form_input_short',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_post_access'  => array(
                    'label'     => _('Post Access'),
                    'formatter' => 'select',
                    'options'   => array(
                             CC_MUST_BE_LOGGED_IN   => _('Registered Users'),
                             CC_ADMIN_ONLY          => _('Admins Only')
                             ),
                    'flags'     =>  CCFF_POPULATE,
                    ),
            'forum_read_access'  => array(
                    'label'     => _('Read Access'),
                    'formatter' => 'select',
                    'options'   => array(
                             CC_DONT_CARE_LOGGED_IN => _('Everyone'),
                             CC_MUST_BE_LOGGED_IN   => _('Registered Users'),
                             CC_ADMIN_ONLY          => _('Admins Only')
                             ),
                    'flags'     =>  CCFF_POPULATE,
                    ),
            'forum_group' => array(
                    'label'     => _('Forum Group'),
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
                    'label'     => _('Forum Group Name'),
                    'formatter' => 'textedit',
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'forum_group_weight' => array( 
                    'label'     => _('Weight'),
                    'formatter' => 'textedit',
                    'form_tip'  => _('A lower number places forum group higher in listing.'),
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
        $help = _('This action will delete <b>ALL</b> topics ever posted in the forum. There is <b>NO UNDO</b>. Once you delete the forum, <b>ALL</b> messages will be gone forever.');

        $this->CCForm();
        $this->SetHelpText($help);
        $this->SetSubmitText(sprintf(_("Delete Forum, '%s' ?"), $pretty_name));
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
                    'label'     => _('Forum') . ':',
                    'formatter' => 'select',
                    'form_tip'  => _('Move thread to this fourm'),
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
                'forumadmin'   => array( 'menu_text'  => _('Forum'),
                                 'menu_group' => 'configure',
                                 'help'      => _('Configure forums access, groups, etc.'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 5,
                                 'action' =>  ccl('admin','forums') ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('thread'),              array( 'CCForumAPI', 'ViewThread'),  
                CC_DONT_CARE_LOGGED_IN, 'cchost_lib/ccextras/cc-forums.inc', '{thread_id}', 
                _('View a forum thread'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums'),              array( 'CCForumAPI', 'Index'),   
                CC_DONT_CARE_LOGGED_IN, 'cchost_lib/ccextras/cc-forums.inc', '[forum_id]', 
                _('View forums index or specific forum'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums','post'),       array( 'CCForumAPI', 'PostNew'), 
                CC_DONT_CARE_LOGGED_IN, 'cchost_lib/ccextras/cc-forums.inc', '{forum_id}', 
                _('Post a new topic'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('forums','people'),     array( 'CCForumAPI', 'User'),    
                CC_DONT_CARE_LOGGED_IN, 'cchost_lib/ccextras/cc-forums.inc', '{user_name}', 
                _('Display forum topics for user'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('feed','rss','forums'), array( 'CCForumAPI', 'RssFeed'), 
                CC_DONT_CARE_LOGGED_IN, 'cchost_lib/ccextras/cc-forums.inc', 
                array(  'feed/rss/forums', 
                        'feed/rss/forums/user/{user_name}',
                        'feed/rss/forums/thread/{thread_id}' ), 
                _('Various forum feeds'), CC_AG_FEEDS );

        CCEvents::MapUrl( ccp('admin','forums'),      array( 'CCForumsAdmin', 'Admin'),   
                CC_ADMIN_ONLY, ccs(__FILE__), '', 
                _('Configure forums'), CC_AG_FORUMS );

        CCEvents::MapUrl( ccp('admin','forums','move'), array( 'CCForumsAdmin', 'MoveThread'),   
                CC_ADMIN_ONLY, ccs(__FILE__), '{thread_id}', 
                _("Displays 'Move Thread' form"), CC_AG_FORUMS );
    }

    function MoveThread($thread_id)
    {
        $api = new CCForums();
        $threads =& CCForumThreads::GetTable();
        $threads->AddJoin( new CCTopics(), 'forum_thread_oldest' );
        $row = $threads->QueryKeyRow($thread_id);

        $form = new CCForumMoveThreadForm();

        if( empty($_POST['forummovethread']) || !$form->ValidateFields() )
        {
            $title = sprintf( _('Move topic: %s'), $row['topic_name'] );
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

    function _stick_thread($thread_id)
    {
        $threads =& CCForumThreads::GetTable();
        $a['forum_thread_id'] = $thread_id;
        $a['forum_thread_sticky'] = 'forum_thread_sticky ^ 1';
        $threads->Update($a,false);
        CCPage::SetTitle(_('Change Thread Sticky Flag'));
        $url = ccl('thread',$thread_id);
        $link1 = "<a href=\"$url\">";
        $link2 = '</a>';
        CCPage::Prompt(
            sprintf(_('Sticky status changed for thread. %sGo back to thread%s'),$link1,$link2) );
    }

    function _close_thread($thread_id)
    {   
        $threads =& CCForumThreads::GetTable();
        $a['forum_thread_id'] = $thread_id;
        $a['forum_thread_closed'] = 'forum_thread_closed ^ 1';
        $threads->Update($a,false);

        $islocked = $threads->QueryItemFromKey('forum_thread_closed',$thread_id);

        $topics =& CCTopics::GetTable();
        $where['topic_thread'] = $thread_id;
        $fields['topic_locked'] = $islocked;
        $topics->UpdateWhere($fields,$where);

        CCPage::SetTitle(_('Change Thread Open Status'));
        $url = ccl('thread',$thread_id);
        $link1 = "<a href=\"$url\">";
        $link2 = '</a>';
        CCPage::Prompt( 
            sprintf( _('Open status changed for thread. %sGo back to thread%s'), $link1, $link2 ) );
    }


    function Admin( $param1='', $param2='', $param3='' )
    {
        if( $param1 == 'stick' )
        {
            $this->_stick_thread($param2);
            return;
        }

        if( $param1 == 'close' )
        {
            $this->_close_thread($param2);
            return;
        }

        $forums = new CCForum();
        $groups = $this->_groups_options();

        if( $param1 == 'newforum' )
        {
            CCPage::SetTitle('Add Forum');

            if( empty($groups) )
            {
                $href = ccl('admin','forums','newgroup');
                $link1 = "<a href=\"$href\">";
                $link2 = '</a>';
                CCPage::Prompt(
                    sprintf( _('You must %sadd groups%s before you can add forums'), $link1, $link2 ) );
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
            CCPage::SetTitle( _('Edit Forum Properties') );

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
            CCPage::SetTitle( _('Delete Forum') );

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
            CCPage::SetTitle( _('Add Forum Group') );
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
            CCPage::SetTitle( _('Edit Forum Group Properties') );

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
            CCPage::SetTitle( _('Delete Forum Group') );

            $where['forum_group'] = $param2;
            $forums_in_group = $forums->CountRows($where);
            $href = ccl('admin','forums');
            if( $forums_in_group > 0 )
            {
                $link1 = "<a href=\"$href\">";
                $link2 = '</a>';
                CCPage::Prompt(
                    sprintf( _('Group is not empty. %sReturn to forums.%s'), $link1, $link2 ) );
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
            $args['add_forum_text'] = _('Add Forum');
            $args['edit_forum_link'] = ccl('admin','forums','edit');
            $args['edit_forum_text'] = _('Edit Forum');
            $args['del_forum_link'] = ccl('admin','forums','delete');
            $args['del_forum_text'] = _('Delete Forum');

            $args['add_forum_group_link'] = ccl('admin','forums','newgroup');
            $args['add_forum_group_text'] = _('Add Group');
            $args['edit_forum_group_link'] = ccl('admin','forums','editgroup');
            $args['edit_forum_group_text'] = _('Edit Group');
            $args['del_forum_group_link'] = ccl('admin','forums','delgroup');
            $args['del_forum_group_text'] = _('Delete Group');

            $args['forums'] = $forum_items;
            $args['forum_groups'] = $groups;

            CCPage::SetTitle( _('Forum Admin') );
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
        $link1 = "<a href=\"$href\">";
        $link2 = '</a>';
        CCPage::Prompt(
            sprintf( _('Forum has been permanantly deleted. %sReturn to forums.%s'), $link1, $link2 ) );
    }
}

?>