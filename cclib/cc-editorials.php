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
* Implements Editor's Picks
*
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCEditorials',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCEditorials',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCEditorials',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCEditorials',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCEditorials' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,        array( 'CCEditorials',  'OnGetSysTags'));

/**
* Form for writing editorial picks
*
*/
class CCEditorialForm extends CCForm
{
    /** 
    * Constructor
    *
    */
    function CCEditorialForm($reviewer)
    {
        $this->CCForm();

        $fields = array( 
            'reviewer' =>
                        array( 'label'      => cct('Reviewer'),
                               'formatter'  => 'statictext',
                               'flags'      => CCFF_NOUPDATE | CCFF_STATIC,
                               'value'      => $reviewer,
                        ),
            'editorial_review' =>
                        array( 'label'      => cct('Review'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_NONE,
                        ),
            'editorial_delete' =>
                        array( 'label'      => cct('Delete'),
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_NONE,
                        ),
                    );
        
        $this->AddFormFields( $fields );
    }
}

/**
* Editorial Picks API
*
*/
class CCEditorials
{
    /*-----------------------------
        MAPPED TO URLS
    -------------------------------*/

    /**
    * Handler for editorial/picks
    *
    * Displays a list of eidtorial picks
    *
    * @param integer $upload_id OPTIONAL if set only displays the one file and it's editorial
    */
    function ViewPicks($upload_id = '')
    {
        CCPage::SetTitle( cct("Editors' Picks") );
        $tag   = empty($upload_id) ? 'editorial_pick' : '';
        $where = empty($upload_id) ? '' : array( 'upload_id' => $upload_id );
        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter($tag);
        CCPage::AddPagingLinks($uploads,$where);
        $uploads->SetOrder('upload_date','DESC');
        $records =& $uploads->GetRecords($where);
        if( !empty($_REQUEST['dump_rec']) && CCUser::IsAdmin() )
            CCDebug::PrintVar($records[0],false);
        CCPage::PageArg('editorial_picks',$records,'show_editor_picks');
        if( count($records) == 1 )
            CCPage::PageArg( 'all_picks_link', ccl('editorial','picks') );
    }

    /**
    * Handler for editorial/submit
    *
    * Display a form for editors to write/edit/delete editorials
    *
    * @param integer $upload_id Upload database id of file to write up
    */
    function Submit($upload_id)
    {
        if( !$this->_is_editor() )
        {
            CCPage::Prompt(cct("Invalid path"));
            return;
        }

        CCPage::SetTitle(cct("Edit Editorial"));
        $reviewer_user_name = CCUser::CurrentUsername();
        $reviewer_name      = CCUser::CurrentUserField('user_real_name');

        $uploads =& CCUploads::GetTable();
        $record  = $uploads->GetRecordFromID($upload_id);

        $editorials = $uploads->GetExtraField($record, 'edpicks');

        $form = new CCEditorialForm($reviewer_name);
        $showform = true;

        if( empty( $_POST['editorial']) )
        {
            $record['local_menu'] = CCUpload::GetRecordLocalMenu($record);
            $marg = array( $record );
            $form->CallFormMacro( 'file_records', 'list_files', $marg);
            if( !empty($editorials[$reviewer_user_name]) )
            {
                $form->SetFormValue('editorial_review', $editorials[$reviewer_user_name]['review'] );
            }
        }
        else
        {
            if( $form->ValidateFields() )
            {
                $form->GetFormValues($values);
                if( empty( $values['editorial_delete'] ) )
                {
                    $editorials[$reviewer_user_name] = array( 'reviewer' => $reviewer_name,
                                                            'review' => $values['editorial_review'],
                                                             'edited' => date('Y-m-d H:i:s')
                                                            );
                }
                else
                {
                    unset($editorials[$reviewer_user_name]);
                }

                // use upload id to force commits at each stage

                $uploads->SetExtraField($upload_id,'edpicks',$editorials);

                if( empty($editorials) )
                {
                    CCUploadAPI::UpdateCCUD($upload_id,'','editorial_pick');
                }
                else
                {
                    CCUploadAPI::UpdateCCUD($upload_id,'editorial_pick','');
                }

                CCEvents::Invoke( CC_EVENT_UPLOAD_DONE, 
                                    array( $upload_id, CC_UF_PROPERTIES_EDIT, array(&$record) ) );
                CCEvents::Invoke( CC_EVENT_ED_PICK, array( $upload_id ) );

                $showform = false;

                $url = ccl('editorial','picks');
                CCPage::Prompt(sprintf(cct("Editorial saved. See <a href=\"%s\">here</a> for results."),$url));
            }
        }

        if( $showform )
            CCPage::AddForm( $form->GenerateForm() );
    }


    /*-----------------------------
        HELPERS
    -------------------------------*/

    /**
    * Internal helper to determine if current user is has eidtor status
    * @access private
    */
    function _is_editor()
    {
        if( CCUser::IsAdmin() )
            return(true);
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        return( !empty($settings['editors']) && in_array( CCUser::CurrentUserName(), CCTag::TagSplit($settings['editors']) ) );
    }


    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        $uploads =& CCUploads::GetTable();
        $editorials = $uploads->GetExtraField($record,'edpicks');
        if( empty($editorials) )
            return;

        $count = count($editorials);
        $keys = array_keys($editorials);
        for( $i = 0; $i < $count; $i++ )
        {
            // hmmm
            $pick =& $record['upload_extra']['edpicks'][ $keys[$i] ];
            $pick['review_html'] = CCUtil::TextToHTML( $pick['review'] );
            $pick['review_short'] = substr($pick['review'],0,25) . '...';
            $pick['review_url'] = ccl( 'editorial','picks',$record['upload_id'] );
        }
    }


    /**
    * Event handler for {@link CC_EVENT_BUILD_UPLOAD_MENU}
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu()
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['editorial'] = 
                     array(  'menu_text'  => cct('Editorial'),
                             'weight'     => 300,
                             'group_name' => 'editorial',
                             'id'         => 'editorialcommand',
                             'access'     => CC_DYNAMIC_MENU_ITEM );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu()
    */
    function OnUploadMenu(&$menu,&$record)
    {
        if( $this->_is_editor() && empty($record['upload_banned']) )
        {
            $menu['editorial']['action'] = ccl( 'editorial', 'submit', $record['upload_id'] );
            $menu['editorial']['access']  |= CC_MUST_BE_LOGGED_IN;
            if( CCUser::IsAdmin() )
                $menu['editorial']['group_name'] = 'admin';
        }
        else
        {
            $menu['editorial']['access'] = CC_DISABLED_MENU_ITEM;
        }

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'editorial/submit',   array('CCEditorials','Submit'),   CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( 'editorial/picks',    array('CCEditorials','ViewPicks'),    CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['editors'] =
               array( 'label'       => 'Editorial staff',
                       'form_tip'   => 'Comma separated list of login names for users with Editorial privelages',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_GET_SYSTAGS}
    *
    * @param array $record Record we're getting tags for 
    * @param array $file Specific file record we're getting tags for
    * @param array $tags Place to put the appropriate tags.
    */
    function OnGetSysTags(&$record,&$file,&$tags)
    {
        if( !empty($record['upload_extra']['edpicks']) )
        {
            $tags[] = 'editorial_pick';
        }
    }

}


?>