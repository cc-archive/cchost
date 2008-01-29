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
* @subpackage extras
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/ccextras/cc-topics-forms.inc');


class CCContentPostForm extends CCTopicForm
{
    function CCContentPostForm()
    {
        $this->CCTopicForm(_('Content Entry'),_('Submit Content'),true,false);
        $this->EnableUploads();

        $types = CCDatabase::QueryItems('SELECT DISTINCT topic_type FROM cc_tbl_topics');

        $new_types[] = _('home');
        $new_types[] = _('sidebar_blurb');
        $new_types[] = _('blog');
        $types = array_filter(array_unique(array_merge($types,$new_types)));
        $types = array_combine( array_values($types), $types );
        $fields['topic_type'] =
                        array(  'label'      => _('Content Type'),
                               'form_tip'    => '',
                               'formatter'   => 'select',
                                'options'    => $types,
                               'flags'       => CCFF_POPULATE );

        $fields['new_topic_type'] =
                        array(  'label'      => _('New Content Type'),
                               'form_tip'    => '',
                               'formatter'   => 'textedit',
                               'flags'       => CCFF_POPULATE );

        $this->SetFormFieldItem('topic_text', 'show_img', true);
        $this->InsertFormFields( $fields, 'top' );
        $this->RemoveFormField('user_confirm');
        $this->RemoveFormField('user_mask');

        global $CC_GLOBALS;

        for( $i = 1; $i < 5; $i++ )
        {
            $images['images_' . $i] = 
                array( 'label' => _('Image' ) . ' ' . $i,
                        'formatter' => 'upload',
                        'upload_dir' => $CC_GLOBALS['image-upload-dir'],
                        'flags' => CCFF_NONE );
        }

        $this->AddFormFields($images);
        // | CCFF_NOSTRIP | CCFF_HTML
    }
}

/**
* Form for configuration the file format verification module
* !!incomplete!!
* @package cchost
* @subpackage admin
*
*/
class CCContentPageForm extends CCForm
{
    /**
    * Constructor
    *
    */
    function CCContentPageForm()
    {
        $this->CCForm();

        $fields['title'] =
                        array(  'label'      => _('Page Title'),
                               'form_tip'    => '',
                               'formatter'   => 'textedit',
                               'flags'       => CCFF_POPULATE | CCFF_REQUIRED );

        $types = CCDatabase::QueryItems('SELECT DISTINCT topic_type FROM cc_tbl_topics');
        $types = array_filter($types);
        $types = array_combine( array_values($types), $types );

        if( !empty($types) )
        {
            $fields['type'] =
                            array(  'label'      => _('Content Type'),
                                   'form_tip'    => _('You can specify a topic type and sort by date or another field...'),
                                    'value'       => 'home',
                                   'formatter'   => 'select',
                                    'options'    => $types,
                                   'flags'       => CCFF_POPULATE );
        }

        $fields['ids'] =
                        array(  'label'      => _('Topic IDs'),
                               'form_tip'    => _('...or you can specify a list of content (topic) ids, separated by commas'),
                               'formatter'   => 'textedit',
                               'flags'       => CCFF_POPULATE );

        require_once('cchost_lib/cc-template.inc');
        $formats = CCTemplateAdmin::GetContentTemplates();
        $fields['t'] =
                        array(  'label'      => _('Layout'),
                               'form_tip'    => '',
                               'formatter'   => 'select',
                               'options'     => $formats,
                               'flags'       => CCFF_POPULATE );


        $options =
            array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' );

        $fields['content_page_columns'] =
                        array(  'label'      => _('Number of columns'),
                               'form_tip'    => _('Some formats will ignore this.'),
                               'formatter'   => 'select',
                               'options'     => $options,
                               'flags'       => CCFF_POPULATE );

        $options =
            array( '' => _('Default page maximum'), '1' => '1', '4' => '4', '6' => '6', '10' => '10', '25' => '25', '40' => '40', );

        $fields['limit'] =
                        array(  'label'      => _('Limit number of topics'),
                               'form_tip'    => _('Some formats will ignore this.'),
                               'formatter'   => 'select',
                               'options'     => $options,
                               'flags'       => CCFF_POPULATE );

        $options =
            array( 'format' => 'Use formatted text (e.g. [b] style)',
                    'raw' => 'Do not format text (HTML)',
                   'text'  => 'Unformatted (remove [b] style formatting)', );

        $fields['content_page_textformat'] =
                        array(  'label'      => _('Text format'),
                               'form_tip'    => _('Some formats will ignore this.'),
                               'formatter'   => 'select',
                               'options'     => $options,
                               'flags'       => CCFF_POPULATE );

        $fields['content_page_paging'] =
                        array(  'label'      => _('Next/Prev Links'),
                               'form_tip'    => _('Allowing paging with next/prev links'),
                               'formatter'   => 'checkbox',
                               'value'       => 1,
                               'flags'       => CCFF_POPULATE );

        $fields['content_page_box'] =
                        array(  'label'      => _('Boxes'),
                               'form_tip'    => _('Use boxes around content'),
                               'formatter'   => 'checkbox',
                               'value'       => 1,
                               'flags'       => CCFF_POPULATE );

        $options = 
            array( 'date' => _('Date') );

        $fields['sort'] =
                        array(  'label'      => _('Sort by'),
                               'form_tip'    => '',
                               'formatter'   => 'select',
                               'options'     => $options,
                               'flags'       => CCFF_POPULATE );

        $options = 
            array( 'desc' => _('Newest first'), 'asc' => _('Oldest first'), );

        $fields['ord'] =
                        array(  'label'      => _('Order'),
                               'form_tip'    => '',
                               'formatter'   => 'select',
                               'options'     => $options,
                               'flags'       => CCFF_POPULATE );

        $this->AddFormFields( $fields );
    }
}

/**
*
*
*/
class CCContentPage
{
    function TDelete($topic_id='')
    {
        $this->_build_bread_crumb_trail(_('Delete a Content Entry'));
        CCPage::SetTitle(_('Delete a Content Entry'));
        if( empty($topic_id) )
            CCUtil::Send404();
        $form = new CCGenericForm();
        if( empty($_POST['generic']) )
        {
            $name = CCDatabase::QueryItem('SELECT topic_name FROM cc_tbl_topics WHERE topic_id='.$topic_id);
            $form->SetFormHelp(_('This is permanantly erase this content. There is <b>NO UNDO</b>'));
            $fname = basename($name);
            $form->SetSubmitText(sprintf("Delete '%s' Now",$name));
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            require_once('cchost_lib/ccextras/cc-topics.inc');
            $topics = new CCTopics();
            $topics->DeleteKeyRow($topic_id);
            CCUtil::SendBrowserTo( ccl('admin','content','manage') );
        }
    }

    function Edit($topic_id)
    {
        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail(_('Edit Content'));
        CCPage::SetTitle(_('Edit Content'));
        $form = new CCContentPostForm();
        if( empty($_POST['contentpost']) )
        {
            $row = CCDatabase::QueryRow('SELECT * FROM cc_tbl_topics WHERE topic_id='.$topic_id);
            $form->PopulateValues($row);
        }
        elseif( $form->ValidateFields() )
        {
            global $CC_GLOBALS;
            $form->GetFormValues($values);
            for($i = 1; $i < 5; $i++ )
            {
                $name = 'images_' . $i;
                if( !empty($values[$name]['name']) )
                {
                    move_uploaded_file($values[$name]['tmp_name'],$CC_GLOBALS['image-upload-dir'] . $values[$name]['name']);
                }
                unset($values[$name]);
            }
            if( !empty($values['new_topic_type']) )
                $values['topic_type'] = $values['new_topic_type'];
            unset($values['new_topic_type']);
            $topics = new CCTopics();
            $values['topic_id'] = $topic_id;
            $topics->Update($values);
            $url = ccl('admin','content','manage');
            if( !empty($_GET['offset']) )
                $url = url_args( $url, 'offset='.$_GET['offset']);
            CCUtil::SendBrowserTo($url);
        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    function Post()
    {
        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail(_('Post Content'));
        CCPage::SetTitle(_('Post Content'));
        $form = new CCContentPostForm();
        if( empty($_POST['contentpost']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            global $CC_GLOBALS;
            $form->GetFormValues($values);
            for($i = 1; $i < 5; $i++ )
            {
                $name = 'images_' . $i;
                if( !empty($values[$name]['name']) )
                {
                    move_uploaded_file($values[$name]['tmp_name'],$CC_GLOBALS['image-upload-dir'] . $values[$name]['name']);
                }
                unset($values[$name]);
            }
            if( !empty($values['new_topic_type']) )
                $values['topic_type'] = $values['new_topic_type'];
            unset($values['new_topic_type']);
            $topics = new CCTopics();
            $values['topic_id'] = $topics->NextID();
            $values['topic_upload'] = 0;
            $values['topic_thread'] = 0;
            $values['topic_date'] = date('Y-m-d H:i:s',time());
            $values['topic_user'] = CCUser::CurrentUser();
            //$values['topic_type'] = $values['topic_type'];
            $topics->Insert($values,0);
            $title = _('New Content Preview');
            $q="title={$title}&datasource=topics&t=content_page&ids=".$values['topic_id'];
            $url = url_args( ccl('api','query'), $q );
            CCUtil::SendBrowserTo($url);
        }
    }

    function PageDelete()
    {
        $this->_build_bread_crumb_trail(_('Delete a Content Page'));
        CCPage::SetTitle(_('Delete a Content Page'));
        $page = CCUtil::Strip($_GET['page']);
        if( empty($page) )
            CCUtil::Send404();
        $form = new CCGenericForm();
        if( empty($_POST['generic']) )
        {
            $form->SetFormHelp(_('This is permanantly erase this page. There is <b>NO UNDO</b>'));
            $fname = basename($page);
            $form->SetSubmitText(sprintf("Delete '%s' Now",$fname));
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            unlink($page);
            CCUtil::SendBrowserTo( ccl('admin','content','manage') );
        }
    }

    function PageEdit()
    {
        $this->_build_bread_crumb_trail(_('Edit a Content Page'));
        CCPage::SetTitle(_('Edit a Content Page'));
        $page = CCUtil::Strip($_GET['page']);
        if( empty($page) )
            CCUtil::Send404();
        require_once('cchost_lib/cc-file-props.php');
        $fp = new CCFileProps();
        $props = $fp->GetFileProps($page);
        $props['type'] = $props['topic_type'];
        $props['title'] = $props['desc'];

        $form = new CCContentPageForm();
        if( empty($_POST['contentpage']) )
        {
            $form->PopulateValues($props);                
        }
        elseif( $form->ValidateFields() && $this->_valid_target_dir($form,$target) )
        {
            $this->_save_page($form,false,$target);
            CCUtil::SendBrowserTo( ccl('admin','content','manage') );
        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    function Page($cmd='')
    {
        require_once('cchost_lib/cc-page.php');

        if( $cmd == 'edit' )
            return $this->PageEdit();
        if( $cmd == 'delete' )
            return $this->PageDelete();

        $this->_build_bread_crumb_trail(_('Create a Content Page'));
        CCPage::SetTitle(_('Create a Content Page'));
        $form = new CCContentPageForm();
        if( empty($_POST['contentpage']) || !$form->ValidateFields() || !$this->_valid_target_dir($form,$target) )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $fname = $this->_save_page($form,true,$target);
            CCUtil::SendBrowserTo( ccl('docs',$fname) );
        }
    }

    function _valid_target_dir(&$form,&$target_dir) 
    {
        global $CC_GLOBALS;
        list( $target_dir ) = array_filter(split( ';', $CC_GLOBALS['files-root'] ));
        if( !empty($target_dir) )
            $target_dir = trim($target_dir);
        if( empty($target_dir) || !file_exists($target_dir) )
        {
            $form->SetHelpText(sprintf(_("Can't find a suitable place to write the new 'docs' content. Fix this in %sPath Configure%"),
                            '<a href="' . ccl('admin','paths') . '">', '</a>' ) );
            return false;
        }
        return true;
    }

    function _save_page(&$form,$new,$target_dir)
    {
        $form->GetFormValues($values);
        $fname = trim(preg_replace('/[^0-9a-z_-]+/', '-', strtolower($values['title'])),'-');
        if( empty($fname) )
            $fname = 'topicpage';
        $path = $target_dir . '/' . $fname . '.php';
        if( $new )
        {
            CCUtil::MakeSubdirs($target_dir);
            $i = 0;
            $root = $fname;
            while( file_exists($path) )
            {
                $fname = $root . '_' . ++$i;
                $path = $target_dir . '/' . $fname . '.php';
            }
        }
        $values['title'] = addslashes($values['title']);

        $meta = '';
        foreach( $values as $K => $V )
        {
            if( ($K == 'title') || ($K == 'type') )
                continue;
            $meta .= '      ' . $K . ' = ' . $V . "\n";
        }

        $meta .= '      topic_type = ' . $values['type'] . "\n";

        $q = 'datasource=topics&f=page';
        foreach( array('t','sort','ord','type','limit','ids') as $K )
        {
            $q .= '&' . $K . '=' . $values[$K];
        }

        $vars = '';
        foreach( array( 'content_page_box', 'content_page_paging', 
                        'content_page_textformat', 'content_page_columns' ) as $K  )
        {
            if( isset($values[$K]) )
                $vars .= "\$A['{$K}'] = '" . $values[$K] . "';\n";
        }

        $vars .= "\$A['topic_url'] = '" . url_args( ccl('api','query'), 'datasource=topics&t=' . $values['t'] ) . "&ids=';";

        $text = '<?/*' . "\n[meta]\n";
        $text .=<<<EOF
     type = dynamic_content_page
     desc = _('{$values['title']}')
{$meta}
[/meta]
*/
{$vars}
print "<h1>{$values['title']}</h1>";
 cc_query_fmt('{$q}');
 \$A['macro_names'][] = 'prev_next_links';
?>
EOF;

        $f = fopen($path,'w');
        fwrite($f,$text);
        fclose($f);
        chmod($path,cc_default_file_perms());
        return $fname;
    }

    function Manage()
    {
        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail(_('Manage Content'));
        CCPage::SetTitle(_('Manage Content'));

        require_once('cchost_lib/cc-template.inc');
        $pages = CCTemplateAdmin::GetContentPages();
        CCPage::PageArg('content_pages',$pages);
        
        $types = CCDatabase::QueryItems("SELECT DISTINCT topic_type FROM cc_tbl_topics WHERE topic_type > ''");
        CCPage::PageArg('topic_types',$types);
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        if( empty($_GET['topic_type']) )
            $_GET['topic_type'] = $types[0];
        $args = $query->ProcessAdminArgs('t=content_manage&datasource=topics&type='.$_GET['topic_type']);
        $query->Query($args);
    }

    function Admin()
    {
        require_once('cchost_lib/cc-page.php');
        $this->_build_bread_crumb_trail($cmd='');
        CCPage::SetTitle(_('Content Admin'));

        $args[] = array( 'action'    => ccl('admin','content','manage'),
                         'menu_text' => _('Manage Content'),
                         'help'      => _('Edit, delete and organize page content') );

        $args[] = array( 'action'    => ccl('admin','content','post'),
                         'menu_text' => _('Post Content'),
                         'help'      => _('Create new content for pages at your site.') );

        $args[] = array( 'action'    => ccl('admin','content','page'),
                         'menu_text' => _('New Content Page'),
                         'help'      => _('Create new content page layouts') );

        CCPage::PageArg('client_menu',$args,'print_client_menu');
    }

    /**
    * @access private
    */
    function _build_bread_crumb_trail($cmd='')
    {
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );
        
        $trail[] = array( 'url' => ccl('admin','site'), 
                          'text' => _('Settings') );

        if( $cmd )
        {
            $trail[] = array( 'url' => ccl('admin','content'), 'text' => _('Content') );
            $trail[] = array( 'url' => '', 'text' => $cmd );
        }
        else
        {
            $trail[] = array( 'url' => '', 'text' => _('Content') );
        }

        CCPage::AddBreadCrumbs($trail);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','content','page'),   array( 'CCContentPage', 'Page'),       CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content','post'),   array( 'CCContentPage', 'Post'),       CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content','edit'),   array( 'CCContentPage', 'Edit'),       CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content','delete'), array( 'CCContentPage', 'TDelete'),    CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content','manage'), array( 'CCContentPage', 'Manage'),     CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content','pages'),  array( 'CCContentPage', 'Pages'),      CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('admin','content'),          array( 'CCContentPage', 'Admin'),      CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}


?>