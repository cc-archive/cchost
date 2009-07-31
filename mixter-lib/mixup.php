<?php

define('CC_EVENT_FORMAT_MIXUP', 'fmtmixup' );

define('CC_MIXUP_MODE_DISABLED',  1 );
define('CC_MIXUP_MODE_SIGNUP',    2 );
define('CC_MIXUP_MODE_MIXING',    3 );
define('CC_MIXUP_MODE_REMINDER',  4 );
define('CC_MIXUP_MODE_UPLOADING', 5 );
define('CC_MIXUP_MODE_DONE',      6 );
define('CC_MIXUP_MODE_CUSTOM',    7 );

CCEvents::AddHandler(CC_EVENT_MAP_URLS,        'mixup_onmapurls');
CCEvents::AddHandler(CC_EVENT_FORMAT_MIXUP,    'mixup_onfiltermixup');
CCEvents::AddHandler(CC_EVENT_API_QUERY_SETUP, 'mixup_onqapiquerysetup' ); 
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,     'mixup_onuploaddone' );


function mixup_onuploaddone( $upload_id, $op )
{
    $user_id   = CCUser::CurrentUser();
    $mode_type = CC_MIXUP_MODE_UPLOADING;
    
    // is the current user signed up for a mixup that
    // is currently in 'uploading' mode?
    
    $sql =<<<EOF
        SELECT mixup_id, mixup_tag
            FROM cc_tbl_mixups
            JOIN cc_tbl_mixup_mode ON mixup_mode = mixup_mode_id
            JOIN cc_tbl_mixup_user ON mixup_id = mixup_user_mixup
            WHERE mixup_mode_type = {$mode_type} AND
            mixup_user_user = {$user_id}

EOF;

    $mixup_info = CCDatabase::QueryRows($sql);
    if( empty($mixup_info ) )
        return;

    $upload_tags = CCDatabase::QueryRow('SELECT upload_tags FROM cc_tbl_uploads WHERE upload_id='.$upload_id);
    $upload_tags['op'] = $op;
    $table = new CCTable( 'cc_tbl_mixup_user', 'mixup_user_user');
    
    foreach( $mixup_info as $MI )
    {
        // Is this upload intended for the mixup in question?
        if( CCUploads::InTags($MI['mixup_tag'],$upload_tags) )
        {
            $w['mixup_user_user']  = $user_id;
            $w['mixup_user_mixup'] = $MI['mixup_id'];
            $args['mixup_user_upload'] = $upload_id;
            $table->UpdateWhere($args,$w);
            break;
        }
        
    }
}

function mixup_onqapiquerysetup( &$args, &$queryObj, $validate)
{
    if( empty($args['datasource']) ||
       (
            ($args['datasource'] != 'mixups') &&
            ($args['datasource'] != 'mixup_users')
       )
      )
    {
        if( !empty($args['mixup'])) {
            $urlp = ccl('people') . '/';

            $queryObj->sql_p['joins'][] = 'cc_tbl_mixup_user ON mixup_user_upload=upload_id';
            $queryObj->sql_p['joins'][] = 'cc_tbl_user mixee ON mixup_user_other=mixee.user_id';
            $queryObj->where[] = 'mixup_user_mixup = ' . $args['mixup'];
            $queryObj->columns[] = "IF( mixup_user_other,  CONCAT( '{$urlp}', mixee.user_name ), '' ) as mixee_page_url";
            $queryObj->columns[] = cc_fancy_user_sql('mixee_name', 'mixee');
        }
        return;
    }

    // The query engine ignores the 'sort' field for datasources it
    // does not know, so we hack in the SQL for our table
    
    if( empty($args['sort'])) {
        $args['sort'] = 'date';
    }
    
    $is_user = $args['datasource'] == 'mixup_users';

    switch( $args['sort'] ) {
        case 'date':
            $queryObj->sql_p['order'] = $is_user ? 'mixup_user_date' : 'mixup_date';
            break;
        case 'name':
            $queryObj->sql_p['order'] = $is_user ? 'user_name' : 'mixup_display';
            break;
    }
    
    if( empty($args['ord'])) {
        $args['ord'] = 'DESC';
    }
    
    $queryObj->sql_p['order'] .= ' ' . $args['ord'];

    // our 'user' is actually a remixer in mixup_user table
    
    if( !empty($args['user']) )
    {
        $queryObj->where[] = 'mixer.user_name = \'' . $args['user'] . '\'';
        unset($args['user']);
    }
    
    // We add a 'mixup' parameter for queries
    
    if( !empty($args['mixup']) ) {
        $field = $is_user ? 'mixup_user_mixup' : 'mixup_id';
        $queryObj->where[] = $field . ' = ' . $args['mixup'];
    }
    
}

function mixup_helper_addpatt($x)
{
    return '%' . $x . '%';
}

function mixup_onfiltermixup(&$rows)
{
    /*
     Several of the fields have %-% style patterns that need replacing.
     
     The replacement values are found in other fields
    */
    
    $c = count($rows);
    if( $c > 0 )
    {
        // these are the fields that potentially need replacing:
        $fields = array( 'mixup_desc_html', 'mixup_desc_plain', 'mixup_mode_desc_html', 'mixup_mode_desc_plain');
        
        // here's all the columns in each row:
        $cols = array_keys($rows[0]);

        // these are cols in the row that (actually) need replacing
        $need_replacing = array_intersect($fields,$cols);
        
        if( empty($need_replacing)) {
            // there's nothing to replace
            return;
        }
        
        // these are fields that potentially have replacement values:
        $replace_keys = array_diff($cols,$fields);

        // these are the (potentially) replacement fields with '%' surrounding the names
        $replace_pats = array_map('mixup_helper_addpatt',$replace_keys);

        // loop through all rows:
        $keys = array_keys($rows);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $rows[$keys[$i]];
            
            // create an array of the potential replace values
            $values = array();
            foreach( $replace_keys as $K )
            {
                $values[] = $R[$K];
            }

            // loop through 
            foreach( $need_replacing as $NR )
            {
                $R[$NR] = str_replace($replace_pats,$values,$R[$NR]);
            }
        }    
    }
}

function mixup_onmapurls()
{
    CCEvents::MapUrl( 'mixup',  'mixup_view', 
        CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[mixup_name]', _('Main mixup display'),
        'mixup' );

    CCEvents::MapUrl( 'api/mixup',  'mixup_api', 
        CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{signup|remove|status}/mixup_id', _('ajax api for a mixup'),
        'mixup' );
    
    CCEvents::MapUrl( 'admin/mixup',  'mixup_admin', 
        CC_ADMIN_ONLY, ccs(__FILE__), '', _('admin mixup'),
        'mixup' );
    
    CCEvents::MapUrl( 'admin/mixup/massmail',  'mixup_admin_massmail', 
        CC_ADMIN_ONLY, ccs(__FILE__), '', _('admin mixup'),
        'mixup' );
}

function mixup_helper_get_default_modes($mixup_id)
{    
    if( empty($mixup_id) )
        $mixup_id = '0'; // make sure it's text
        
    $sql = 'SELECT * FROM cc_tbl_mixup_mode WHERE mixup_mode_mixup = ' . $mixup_id;
    $rows = CCDatabase::QueryRows($sql);
    if( !empty($rows) )
        return $rows;
    
    $baseurl = ccl('people') . '/';
    
    $fields = array(
         array( CC_MIXUP_MODE_DISABLED,             // mode_type
                $mixup_id,                          // mixup_id
                'Not active yet',                   // name
                'This mixup is not active yet.',    // desc
                '',                                 // e-mail
                'now'                              // date offset
                ),                            
         
         array( CC_MIXUP_MODE_SIGNUP,
                $mixup_id,                          // mixup_id
                'Signing Up',
                'This mixup is currently signing up participants. '.
                    'E-mail notifications will go out on %mixup_mode_date%',
                "DO NOT REPLY TO THIS EMAIL - IT WAS GENERATED AUTOMATICALLY\n\n" .
                    "Hello %mixer_full_name%,\n\n" .
                    "Thanks for signing up for %mixup_display%!\n\n" .
                    "The next step: On %mixup_mode_date% you will recieve an e-mail with " .
                    "the name of person you will remix.\n\n" .
                    "Don't do anything until you get that mail!\n\n" .
                    "If you have questions or problems: %mixup_admin_contact%\n\n" .
                    "More information: %mixup_url%\n\n" .
                    "Thanks,\n" .
                    "%mixup_name% Admin",
                'now + 2 weeks'
                ),
         
         array( CC_MIXUP_MODE_MIXING,
                $mixup_id,                          // mixup_id
                'Mixing',
                'The sign up period for this mixup is closed. ' .
                    'Participants are currently mixing it up. They will upload their remixes on: %mixup_mode_date%',
                "DO NOT REPLY TO THIS EMAIL - IT WAS GENERATED AUTOMATICALLY\n\n" .
                    "Hello %mixer_full_name%,\n\n" .
                    "Thanks for signing up for %mixup_display%!\n\n" .
                    "Your mixup assignment is: %mixee_full_name%\n\n" .
                    "Their home page: %mixee_page_url%\n\n" .
                    "(Remember this is a secret - don't tell anybody who were assigned!)\n\n" .
                    "The next step: Download their sources and get to remixing. " .
                    "Then, upload your remix on %mixup_mode_date% (NOT BEFORE!)\n\n" .
                    "If you have questions or problems: %mixup_admin_contact%\n\n" .
                    "More information: %mixup_url%\n\n" .
                    "Thanks,\n" .
                    "%mixup_name% Admin",
                'now + 4 weeks'
                ),
         
         array( CC_MIXUP_MODE_REMINDER,
                $mixup_id,                          // mixup_id
                'Reminder',
                'The sign up period for this mixup is closed. ' .
                    'Participants are currently mixing it up. They will upload their remixes on: %mixup_mode_date%',
                "DO NOT REPLY TO THIS EMAIL - IT WAS GENERATED AUTOMATICALLY\n\n" .
                    "Hello %mixer_full_name%,\n\n" .
                    "The time is getting close to remix %mixee_full_name% \n\n" .
                    "Here's that URL again: %mixee_page_url%\n\n" .
                    "The next step: Upload your remix on %mixup_mode_date% (NOT BEFORE!)\n\n" .
                    "If you have questions or problems: %mixup_admin_contact%\n\n" .
                    "More information: %mixup_url%\n\n" .
                    "Thanks,\n" .
                    "%mixup_name% Admin",
                'now + 4 weeks'
                ),
         
         array( CC_MIXUP_MODE_UPLOADING,
                $mixup_id,                          // mixup_id
                'Uploading',
                'The sign up period for this mixup is closed. ' .
                    'The mixup particpants should be uploading right now(!)',
                "DO NOT REPLY TO THIS EMAIL - IT WAS GENERATED AUTOMATICALLY\n\n" .
                    "Hello %mixer_full_name%,\n\n" .
                    "The remix period is over! (You're done with your remix of %mixee_full_name%, right?)\n\n" .
                    "The next step: Upload your remix as soon as you can!\n\n" .
                    "If you have questions or problems: %mixup_admin_contact%\n\n" .
                    "More information: %mixup_url%\n\n" .
                    "Thanks,\n" .
                    "%mixup_name% Admin",
                'now + 4 weeks'
                ),
         
         array( CC_MIXUP_MODE_DONE,
                $mixup_id,                          // mixup_id
               'Done',
                'This mixup is ancient history',
                '',
                'now'
                ),                            
         );
      
    $table = new CCTable('cc_tbl_mixup_mode', 'mixup_mode_id');
    $cols = array( 'mixup_mode_type', 'mixup_mode_mixup', 'mixup_mode_name', 'mixup_mode_desc',
                  'mixup_mode_mail', 'mixup_mode_date_offset' );
    $table->InsertBatch($cols, $fields);
    $rows = CCDatabase::QueryRows($sql);
    return $rows;
}


function mixup_helper_get_modes($mixup_id='')
{
    if( empty($mixup_id) )
      $mixup_id = "0";
      
    $sql = 'SELECT * FROM cc_tbl_mixup_mode WHERE mixup_mode_mixup = ' . $mixup_id;
    $rows = CCDatabase::QueryRows($sql);
    if( empty($rows) ) {
        $recs = mixup_helper_get_default_modes(0);
        if( empty($mixup_id) )
            return $recs;
        $keys = array_keys($recs);
        $c = count($keys);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $recs[ $keys[$i]];
            $R['mixup_mode_mixup'] = $mixup_id;
            $R['mixup_mode_date'] = date( 'Y-m-d H:i:s', strtotime( $R['mixup_mode_date_offset'] ));
            unset($R['mixup_mode_id']);
        }        
        $cols = array_keys( $recs[0] );
        $table = new CCTable('cc_tbl_mixup_mode', 'mixup_mode_id');
        $table->InsertBatch($cols, $recs );
        $rows = CCDatabase::QueryRows($sql);
    }
    
    return $rows;
}

function mixup_helper_setup_page($text,$mixup_id=0)
{        
    require_once('cchost_lib/cc-admin.php');
    $admin = new CCAdmin();
    $title = _('Admin Mixups');
    
    if( $mixup_id )
    {
        $name = CCDatabase::QueryItem('SELECT mixup_display FROM cc_tbl_mixups WHERE mixup_id = '.$mixup_id);
        $trail1 = array( 'url' => ccl('admin','mixup'), 'text' => $title );
        $trail2 = array( 'url' => ccl('admin','mixup','edit', $mixup_id), 'text' => $name );
        $trail3 = array( 'url' => '', 'text' => $text );
        $admin->BreadCrumbs(true,$trail1,$trail2,$trail3);
        $text .= " \"{$name}\" ";
        
    }
    else if( $text )
    {
        $trail1 = array( 'url' => ccl('admin','mixup'), 'text' => $title );
        $trail2 = array( 'url' => '', 'text' => $text );
        $admin->BreadCrumbs(true,$trail1,$trail2);
    }
    else
    {
        $text = $title;
        $trail2 = array( 'url' => '', 'text' => $title );
        $admin->BreadCrumbs(true,$trail2);
    }
    
    require_once('cchost_lib/cc-page.php');
    $page =& CCPage::GetPage();
    $page->SetTitle($text);
}    

function mixup_helper_get_mixup_options($mixup_id)
{
    $modes = mixup_helper_get_modes($mixup_id);
    $mod_opts = array();
    foreach( $modes as $M ) {
        $mod_opts[ $M['mixup_mode_id'] ] = $M['mixup_mode_name'];
    }
    return $mod_opts;
}

function mixup_helper_get_mixup_fields($mixup_id)
{
    $urlm = ccl('mixup') . '/';
    $fields = array(
            'mixup_name' =>
                array( 'label'      => 'URL',
                    'form_tip' => 'Last part of mixup URL',
                    'prefix' => $urlm,
                    'class' => 'form_input_short',
                   'formatter'  => 'textedit',
                   'flags'      => CCFF_REQUIRED|CCFF_POPULATE,
                     ),
            'mixup_display' =>
                array( 'label' => 'Display name',
                    'formatter' => 'textedit',
                    'flags' => CCFF_POPULATE,
                    ),
            'mixup_tag' =>
                array( 'label' => 'Upload Tag',
                        'formatter' => 'textedit',
                        'flags' => CCFF_POPULATE,
                        'form_tip' => 'Tells the system to watch for this tag on uploads'),
                
            'mixup_desc' =>
                array( 'label' => 'Description',
                    'formatter' => 'textarea',
                    'want_formatting' => 1,
                    'flags' => CCFF_POPULATE,
                    ),
            'mixup_admin' =>
                array( 'label' => 'Contact Person',
                    'formatter' => 'username',
                    'class' => 'form_input_short',
                    'value' => CCUser::CurrentUser(),
                    'flags' => CCFF_REQUIRED | CCFF_POPULATE | CCFF_POPULATE_WITH_DEFAULT,
                    ),
            );

    if( !empty($mixup_id) )
    {
        $mod_opts = mixup_helper_get_mixup_options($mixup_id);
        $fields['mixup_mode'] = 
                array( 'label' => 'Current Mode',
                        'formatter' => 'select',
                        'flags' => CCFF_POPULATE,
                        'options' => $mod_opts);
                

        $purl = ccl('admin','mixup','edit',$mixup_id);

        $fields['mixup_playlist'] = 
                array( 'label' => 'Playlist',
                        'formatter' => 'textedit',
                        'class' => 'form_input_short',
                        'form_tip' => 'This can be generated automatically <a href="'.$purl.'">here</a>',
                        'flags' => CCFF_POPULATE );
                
        
        $fields['mixup_thread'] = 
                array( 'label' => 'Forum Thread',
                        'formatter' => 'textedit',
                        'class' => 'form_input_short',
                        'form_tip' => '',
                        'flags' => CCFF_POPULATE );
    }
    return $fields;
}

function mixup_helper_get_edit_form_help($mixup)
{
    $mixup_id = $mixup['mixup_id'];
    $mode_row = CCDatabase::QueryRow('SELECT * FROM cc_tbl_mixup_mode WHERE mixup_mode_id = '.$mixup['mixup_mode']);
    $type = $mode_row['mixup_mode_type'];

    // actions:
    // - do mixup
    // - send global email (mixing assignments, time to upload)
    // - create submit form (?)

    $prop_url    = ccl('admin','mixup', 'properties', $mixup_id ); 
    $mixup_url   = ccl('admin','mixup', 'assign',$mixup_id);
    $mail_url    = ccl('admin','mixup', 'massmail',$mixup_id);
    $mode_url    = ccl('admin','mixup', 'editmodes',$mixup_id);
    $makefrm_url = ccl('admin','submit'); // ccl('admin','mixup', 'makeform', $mixup_id);
    $playlist_url= ccl('api','mixup','playlist', $mixup_id);
    
    $txt =<<<EOF
    <p>Here's some stuff you can do with this mixup:</p>
    <ul>
        <li><a href="{$prop_url}">Edit Properties</a></li>
        <li><a href="{$mixup_url}">Generate/Edit Mixup Assignments</a></li>
        <li><a href="{$mail_url}">Send mail to everybody</a></li>
        <li><a href="{$playlist_url}">Create/Browse Dynamic Playlist</a></li>
        <li><a href="{$makefrm_url}">Edit submit forms</a></li>
        <li><a href="{$mode_url}">Edit mixup modes</a> </li>
    </ul>
EOF;

    return $txt;
}

function mixup_admin($cmd='',$arg='')
{
    require_once('cchost_lib/cc-page.php');
    $page =& CCPage::GetPage();
    
    switch( $cmd )
    {
        case 'create':
            {
                mixup_helper_setup_page(_('Create a New Mixup'));
                require_once('cchost_lib/cc-form.php');
                $form = new CCForm();
                $fields = mixup_helper_get_mixup_fields(0);
                $form->AddFormFields($fields);
                if( empty($_POST) || !$form->ValidateFields() ) {
                    $page->AddForm( $form->GenerateForm() );
                }
                else {
                    $form->GetFormValues($values);
                    $table = new CCTable('cc_tbl_mixups','mixup_id');
                    $values['mixup_id'] = $table->NextID();
                    $modes = mixup_helper_get_modes($values['mixup_id']);
                    $mode = 0;
                    foreach($modes as $M) {
                        if( $M['mixup_mode_type'] == CC_MIXUP_MODE_DISABLED ) {
                            $mode = $M['mixup_mode_id'];
                            break;
                        }
                    }
                    $values['mixup_mode'] = $mode;
                    $values['mixup_date'] = date( 'Y-m-d H:i:s');
                    $table->Insert($values);
                    $url = ccl('mixup', $values['mixup_name'] );
                    CCUtil::SendBrowserTo($url);
                }
            }
            break;
            
        case 'edit':
            {
                $mixup_id = $arg;
                mixup_helper_setup_page(_('Actions'), $mixup_id );
                $row = CCDatabase::QueryRow('SELECT * FROM cc_tbl_mixups WHERE mixup_id=' . $mixup_id);
                $html = '<div class="box" style="width:60%">' . mixup_helper_get_edit_form_help($row) . '</div>';
                $page =& CCPage::GetPage();
                $page->AddContent($html);
            }
            break;
        
        case 'properties':
            {
                $mixup_id = $arg;
                mixup_helper_setup_page(_('Mixup Properties'), $mixup_id );
                require_once('cchost_lib/cc-form.php');
                $form = new CCForm();
                $fields = mixup_helper_get_mixup_fields($mixup_id);
                $form->AddFormFields($fields);
                if( empty($_POST) )
                {
                    $populate = true;
                    $show = true;
                }
                else if( $form->ValidateFields() )
                {
                    $populate = false;
                    $show = false;
                    $form->GetFormValues($values);
                    $table = new CCTable('cc_tbl_mixups','mixup_id');
                    $values['mixup_id'] = $mixup_id;
                    $table->Update($values);
                    $url = ccl('mixup', $values['mixup_name'] );
                    CCUtil::SendBrowserTo($url);
                }
                else {
                    $populate = false;
                    $show = true;
                }
                
                if( $populate ) {
                    $row = CCDatabase::QueryRow('SELECT * FROM cc_tbl_mixups WHERE mixup_id=' . $mixup_id);
                    $form->PopulateValues($row);
                }
                
                if( $show ) {
                    $page->AddForm( $form->GenerateForm() );
                }
            }
            break;
            
        case 'editmodes':
            {
                $mixup_id = $arg;
                if( empty($mixup_id) ) {
                    mixup_helper_setup_page(_('Edit Default Mixup Modes'));
                }
                else {
                    mixup_helper_setup_page(_('Edit Mixup Modes'),$mixup_id);
                }
                $rows = mixup_helper_get_modes($mixup_id);
                $args = array();
                foreach( $rows as $mode_row )
                {
                    $args[] = array(
                             'actions' =>
                                array( 
                                    array(
                                        'action'    => ccl( 'admin', 'mixup', 'editmode', $mode_row['mixup_mode_id'] ),
                                        'menu_text' =>  _('Edit')
                                       ),
                                     ),
                               'help' => '"' . $mode_row['mixup_mode_name'] . '"',
                             );
                }
                // $args[] = array( 'action' => ccl('admin','mixup','newmode'), 'menu_text' => _('Create new mode'));
                $page->PageArg('use_buttons', 1 );
                $page->PageArg('client_menu',$args,'print_client_menu');                
            }
            break;
            
        case 'editmode':
            {
                $mode_id = $arg;
                $row = CCDatabase::QueryRow('SELECT mixup_mode_name, mixup_mode_mixup FROM cc_tbl_mixup_mode WHERE mixup_mode_id='.$mode_id);
                if( empty($row['mixup_mode_mixup']) ) {
                    mixup_helper_setup_page(_('Edit Default Mixup Mode: ') . $row['mixup_mode_name']);
                }
                else {
                    mixup_helper_setup_page(_('Edit Mixup Mode: ' . $row['mixup_mode_name']),$row['mixup_mode_mixup']);
                }
                require_once('cchost_lib/cc-form.php');
                $form = new CCForm();
                $fields = array(
                        'mixup_mode_name' =>
                            array( 'label'      => 'Name',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_REQUIRED|CCFF_POPULATE,
                                 ),
                        'mixup_mode_date' =>
                            array( 'label' => 'Date',
                                'formatter' => 'date',
                                'form_tip' => _('A date that is significant to this mode.'),
                                'flags' => CCFF_POPULATE,
                                ),
                        'mixup_mode_desc' =>
                            array( 'label' => 'Description',
                                'formatter' => 'textarea',
                                'want_formatting' => 1,
                                'flags' => CCFF_POPULATE,
                                ),
                        'mixup_mode_mail' =>
                            array( 'label' => 'Mail Template',
                                'formatter' => 'textarea',
                                'form_tip' => _('Mail that might be relevant to this mode. (Leave blank for n/a)'),
                                'flags' => CCFF_POPULATE,
                                ),
                        );
                $form->AddFormFields($fields);
                $populate = false;
                $show = false;
                if( empty($_POST) ) {
                    $populate = true;
                    $show = true;
                }
                else
                {
                    if( $form->ValidateFields() ) {
                        $form->GetFormValues($values);
                        $values['mixup_mode_id'] = $mode_id;
                        $table = new CCTable('cc_tbl_mixup_mode','mixup_mode_id');
                        $table->Update($values);
                        $url = ccl('admin','mixup','editmodes',$row['mixup_mode_mixup']);
                        CCUtil::SendBrowserTo($url);
                    } else {
                        $populate = false;
                        $show = true;
                    }
                }
                
                if( $populate ) {
                    $row = CCDatabase::QueryRow("SELECT * FROM cc_tbl_mixup_mode WHERE mixup_mode_id = {$mode_id}");
                    $form->PopulateValues($row);
                }
                
                if( $show ) {
                    $page->AddForm( $form->GenerateForm() );                    
                }
                
                
            }
            break;
            
        case 'newmode':
            {
                
            }
            break;
        
        case 'assign':
            {
                mixup_helper_assignments($arg);
            }
            break;
        
        default:
            {
                mixup_helper_setup_page('');
                $args = array( 
                    array(
                        'action'    => ccl( 'admin', 'mixup', 'create' ),
                        'menu_text' =>  _('Create a New Mixup')
                       ),
                    array(
                        'action'    => ccl( 'admin', 'mixup', 'editmodes' ),
                        'menu_text' =>  _('Edit Default Modes')
                       ),
                    );
                $page->PageArg('client_menu',$args,'print_client_menu');                
            }   
    }
}


function mixup_helper_send_mail($msg,$user_id,$subject)
{
    $user_email = CCDatabase::QueryItem('SELECT user_email FROM cc_tbl_user WHERE user_id = ' . $user_id);
    require_once('cchost_lib/ccextras/cc-mail.inc');
    $mailer = new CCMailer();
    $mailer->Body($msg);
    $mailer->To($user_email);
    $mailer->Subject($subject);
    $mailer->Send();
}


function generator_mixup_mode_nav(&$form,$fieldname,$value,$class)
{
    $html     = $form->generator_select($fieldname,$value,$class);
    $mixup_id = $form->GetFormFieldItem($fieldname,'mixup');
    $urlb     = ccl('admin','mixup','massmail',$mixup_id) . '/';

    $js =<<<EOF
<script>
function do_nav() {
    var box = $('{$fieldname}');
    var mode = box.options[ box.selectedIndex ].value;
    window.location.href = '{$urlb}' + mode;
}
Event.observe( '{$fieldname}', 'change', do_nav );
</script>
EOF;
    return $html . $js;
}

function validator_mixup_mode_nav(&$form,$fieldname)
{
    return $form->validator_select($form,$fieldname);
}

function mixup_admin_massmail($mixup_id,$mode='')
{
    mixup_helper_setup_page(_('Send Mixup Mass Mail'),$mixup_id);
    
    require_once('cchost_lib/cc-page.php');
    $page =& CCPage::GetPage();

    require_once('cchost_lib/cc-form.php');
    $form = new CCForm();

    $mod_opts = mixup_helper_get_mixup_options($mixup_id);

    if( empty($_POST) && empty($mode) )
        $mode = CCDatabase::QueryItem('SELECT mixup_mode FROM cc_tbl_mixups WHERE mixup_id = ' . $mixup_id);

    $fields = array(
            'mode_nav' =>
                array(
                    'label'      => 'Base message on mode:',
                    'form_tip'   => 'Select a mode to use as a template for the message',
                    'formatter'  => 'mixup_mode_nav',
                    'options'    => $mod_opts,
                    'value'      => $mode,
                    'mixup'      => $mixup_id,
                    'flags'      => CCFF_NONE,
                     ),
            'body_template' =>
                array(
                    'label'    => 'Message template',
                    'formatter' => 'textarea',
                    'flags'     => CCFF_REQUIRED
                )
    );
    $form->AddFormFields($fields);
    $form->SetSubmitText('Send Mail Now');
    if( empty($_POST)  || !$form->ValidateFields() ) {
        
        /* Show macro documentation */
        if( empty($_POST ) )
        {
            $msg = CCDatabase::QueryItem('SELECT mixup_mode_mail FROM cc_tbl_mixup_mode WHERE mixup_mode_id = '.$mode);
            $form->SetFormValue('body_template',$msg);
        }
        
        /* get the macro sub fields: */
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs('f=php&dataview=mixup_mail&limit=1&mixup='.$mixup_id);
        $results = $query->Query($args);
        if( !empty($results[0][0]) ) {
            $help = mixup_helper_get_macro_help($results[0][0]);
            $form->SetFormHelp($help);
        }
        
        $page->AddForm( $form->GenerateForm() );
    }
    else {
        $form->GetFormValues($values);
        $query_str = 'f=php&dataview=mixup_mail&mixup='.$mixup_id;
        $org_text = $values['body_template'];
        if( mixup_helper_mail_merge($org_text,$query_str) )
        {
            $page->Prompt('OK, sent out all the mail');            
        }
        else
        {
            $page->Prompt('wups, nobody to send mail to!');            
        }
    }
}

function mixup_helper_get_macro_help(&$R)
{
    $html = '<table><tr><th>Macro</th><th>Example values</th>';
    foreach( $R as $K => $V )
    {
        if( $K == 'mixup_mode_mail ')
            continue;
        
        $html .= '<tr><td style="text-align:right"><span style="color:#AAA">%</span>' . $K . '<span style="color:#AAA">%</span></td>' .
                     '<td style="padding-left: 6px;color:#686;">  ' . $V . '</td></tr>';
    }
    $html .= '</table>';
    
    return $html;
}

function mixup_api($action,$mixup_id=0,$arg='')
{
    if( $action == 'remove' )
    {
        if( !CCUser::IsAdmin() )
            exit;
            
        $table  = new CCTable('cc_tbl_mixup_user','mixup_user_mixup');
        $args['mixup_user_mixup'] = $mixup_id;
        $args['mixup_user_user']  = $arg;
        $table->DeleteWhere($args);
        $url = ccl('mixup',$mixup_id);
        CCUtil::SendBrowserTo($url);
        exit;
    }
    
    if( $action == 'playlist' )
    {
        if( !CCUser::IsAdmin() )
            exit;
            
        $playlist = CCDatabase::QueryItem('SELECT mixup_playlist FROM cc_tbl_mixups WHERE mixup_id ='.$mixup_id);
        if( empty($playlist) ) {
            // we do this to ensure all the proper macro and bbCode substitutions in the desc field
            // (very heavy weight, done exactly ONCE in the lifetime of any given mixup)
            require_once('cchost_lib/cc-query.php');
            $query = new CCQuery();
            $args = $query->ProcessAdminArgs('f=php&dataview=mixups&mixup='.$mixup_id);
            list( list( $row ) ) = $query->Query($args);

            // create the playlist
            require_once('cchost_lib/ccextras/cc-playlist.inc');
            $playlist_api = new CCPlaylists();
            $prow = $playlist_api->_create_playlist('playlist',
                                                    $row['mixup_display'],
                                                    $row['mixup_desc_plain'],
                                                    0,  // current user
                                                    'tags=' . $row['mixup_tag']
                                                    );
            $playlist = $prow['cart_id'];
            
            $table = new CCTable('cc_tbl_mixups','mixup_id');
            $dargs['mixup_id'] = $mixup_id;
            $dargs['mixup_playlist'] = $playlist;
            $table->Update($dargs);
        }

        $url = ccl('playlist','browse',$playlist);
        CCUtil::SendBrowserTo($url);
        exit;
    }
    
    if( $action == 'faq' )
    {
        $topic_id = CCDatabase::QueryItem('SELECT topic_id FROM cc_tbl_topics WHERE topic_type = \'secret_faq\'');
        if( empty($topic_id) )
        {
            require_once('cchost_lib/cc-page.php');
            $page =& CCPage::GetPage();
            $page->Prompt('Hey, tell your admin to create a topic with the type "secret_faq"');
            return;
        }
        $url = ccl('topics','view',$topic_id);
        CCUtil::SendBrowserTo($url);
        exit;
    }
    
    $mode_type = CCDatabase::QueryItem('SELECT mixup_mode_type FROM cc_tbl_mixups JOIN cc_tbl_mixup_mode on mixup_mode = mixup_mode_id WHERE mixup_id = ' .$mixup_id);    
    if( $mode_type != CC_MIXUP_MODE_SIGNUP )
    {
        $args['msg'] = 'This mixup is not signing up';
    }
    else {
    
        if( !CCUser::IsLoggedIn() )
        {
            $args['msg'] = 'You must be logged in to sign up for a mixup';
        }
        else
        {
            
            $user = CCDatabase::QueryItem('SELECT mixup_user_user FROM cc_tbl_mixup_user WHERE mixup_user_mixup =' . $mixup_id .
                                              ' AND mixup_user_user = ' . CCUser::CurrentUser() );
            switch($action) {
                case 'status':
                    {
                        if( $user )
                        {
                            $args['signedUp'] = true;
                        }
                        else
                        {
                            $args['notSignedUp'] = true;
                        }
                    }
                    break;
                
                case 'signup':
                    {
                        if( !$user )
                        {
                            // insert the new user info...
                            $table  = new CCTable('cc_tbl_mixup_user','mixup_user_mixup');
                            $dargs['mixup_user_mixup'] = $mixup_id;
                            $dargs['mixup_user_user']  = CCUser::CurrentUser();
                            $dargs['mixup_user_date'] = date( 'Y-m-d H:i:s');
                            $table->Insert($dargs);

                            // notify the new user...
                            
                            $org_text = CCDatabase::QueryItem('SELECT mixup_mode_mail FROM cc_tbl_mixups ' .
                                                              'JOIN cc_tbl_mixup_mode on mixup_mode=mixup_mode_id ' .
                                                              'WHERE mixup_id ='.$mixup_id);
                            
                            $query_str = 'f=php&dataview=mixup_mail&mixup='.$mixup_id .
                                                              '&user=' . CCUser::CurrentUserName();
                            
                            mixup_helper_mail_merge( $org_text, $query_str );
                        }
                        $args['signedUp'] = true;
                    }
                    break;
                
                case 'remove':
                    {
                        if( $user )
                        {
                            $table  = new CCTable('cc_tbl_mixup_user','mixup_user_mixup');
                            $args['mixup_user_mixup'] = $mixup_id;
                            $args['mixup_user_user']  = CCUser::CurrentUser();
                            $table->DeleteWhere($args);
                        }
                        $args['notSignedUp'] = true;                    
                    }
                    break;
                
            }
        }
    }
    CCUtil::ReturnAjaxData($args);
    exit;
}


function mixup_helper_mail_merge($org_text,$query_str,$merge_with = array())
{
    if( empty($merge_with) )
    {
        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $args = $query->ProcessAdminArgs($query_str);
        $results = $query->Query($args);
        if( empty($results[0][0]) ) {
            return false;
        }
        $merge_with = $results[0][0];
    }
     
    $replace_pats = array_map('mixup_helper_addpatt',array_keys($merge_with));
    foreach( $results[0] as $R ) {
        $text = str_replace( $replace_pats, $R, $org_text );
        mixup_helper_send_mail($text,$R['mixer_user_id'],$R['mixup_display']);
    }

    return $merge_with;
}

function mixup_helper_do_shuffle( $first_half, $org_second_half, $rejects  = array() )
{
    $second_half = $org_second_half;
    $num_half = count($first_half);
    shuffle($second_half);
    $pairs = array();
    
    for( $i = 0; $i < $num_half; $i++ )
    {
        $f = $first_half[$i];
        $s = $second_half[$i];
        if( $f == $s ) // || ( in_array($f,$rejects) && in_array($s, $rejects) ) )
        {
            dlog("\nFORCING reshuffle on {$f}/{$s}\n");
            return mixup_helper_do_shuffle($first_half, $org_second_half);
        }
        
        $pairs[] = array( $first_half[$i], $second_half[$i] );
    }
    
    return $pairs;
}

function mixup_helper_gen_assignments($mixup_id)
{
    $users = CCDatabase::QueryItems('SELECT mixup_user_user FROM cc_tbl_mixup_user WHERE mixup_user_mixup =' . $mixup_id);
    $pairs = mixup_helper_do_shuffle($users,$users);
    $table = new CCTable('cc_tbl_mixup_user','mixup_user_user');
    foreach( $pairs as $P )
    {
        $a['mixup_user_user']  = $P[0];
        $a['mixup_user_other'] = $P[1];
        $table->Update($a);
    }
}

function mixup_helper_assignments($mixup_id)
{
    require_once('cchost_lib/cc-form.php');
    require_once('cchost_lib/cc-page.php');
    
    $page =& CCPage::GetPage();
    mixup_helper_setup_page('Edit Assignments',$mixup_id);
    
    if( empty($_POST) && !empty($_GET['gen_assignments']) ) 
        mixup_helper_gen_assignments($mixup_id);
        
    $form = new CCGridForm();
    $cols = array( 'Remixer', 'Assignment', 'Upload' );
    $form->SetColumnHeader($cols);
    $rows = CCDatabase::QueryRows('SELECT * FROM cc_tbl_mixup_user WHERE mixup_user_mixup = ' . $mixup_id);
    $count = 0;
    foreach( $rows as $R )
    {
        $S = 'S[' . ++$count . ']';
        $a = array(
              array(
                'element_name'  => $S . '[mixup_user_user]',
                'value'      => $R['mixup_user_user'],
                'formatter'  => 'username',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[mixup_user_other]',
                'value'      => $R['mixup_user_other'],
                'formatter'  => 'username',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[mixup_user_upload]',
                'value'      => $R['mixup_user_upload'],
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
            );

        $form->AddGridRow( $count, $a );
    }
    
    if( empty($_POST) || !$form->ValidateFields() )
    {
        $url = url_args( ccl('admin','mixup','assign',$mixup_id), 'gen_assignments=1');
        $help =<<<EOF
        <a class="small_button" href="{$url}">Auto-generate Assignment</a>
        <p><b>NOTE</b>: Auto-generating will re-assign all values below.</p>
        <p><b>NOTE</b>: Auto-generating assignments will <b>not</b> send assignment emails.
EOF;
        $form->SetFormHelp($help);
        CCPage::AddForm( $form->GenerateForm() );
    }
    else
    {
        // sigh.
        // The structure of POST is perfect, but the values
        // are only translated to user_ids in the $form obj.
        
        $form->GetFormValues($values);
        $s = $_POST['S'];
        $c = count($s);
        $table = new CCTable('cc_tbl_mixup_user','mixup_user_user');
        for( $i = 0; $i < $c; $i++ )
        {
            $k = 'S[' . ($i+1) . ']';
            $da['mixup_user_user']   = $values[ $k . '[mixup_user_user]' ];
            $da['mixup_user_other']  = $values[ $k . '[mixup_user_other]' ];
            $da['mixup_user_upload'] = $values[ $k . '[mixup_user_upload]' ];
            $table->Update($da);
        }
        
        $url = ccl('admin', 'mixup', 'edit', $mixup_id );
        CCUtil::SendBrowserTo($url);
        
    }

}

/*
function mixup_helper_submit_form($mixup_id)
{
    -*
        [enabled] => 0
        [submit_type] => The Label
        [text] => The Caption
        [help] => The Description
        [tags] => The_tags (ARRAY!)
        [suggested_tags] => The_Suggested_Tags
        [weight] => 1
        [form_help] => The Form Help Message
        [isremix] => 1
        [licenses] => 
        [action] => 
        [delete] => 0
        [logo] => chut.jpg
        [type_key] => secretmixter
        [licenses] => attribution_3,noncommercial_3

    *-
    
}
*/

function mixup_view($mixup=null)
{
    require_once('cchost_lib/cc-query.php');
    $query = new CCQuery();
 
    if( isset($mixup)) {
        
        if( !is_int($mixup)) {
            $mixup = CCDatabase::QueryItem('SELECT mixup_id FROM cc_tbl_mixups WHERE mixup_name="'.$mixup.'"');
        }
        $args = $query->ProcessAdminArgs('t=mixups&limit=1&mixup='.$mixup);
    }
    else {
        $args = $query->ProcessAdminArgs('t=mixups&limit=1&ord=asc&sort=date&paging=on');
    }
    
    $query->Query($args);
    require_once('cchost_lib/cc-page.php');
    $page =& CCPage::GetPage();
    $page->AddPagingLinks($query->dataview,'',1);
    
}

?>