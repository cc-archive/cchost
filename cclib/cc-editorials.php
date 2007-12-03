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
        if( !empty($upload_id) )
        {
            $dv = new CCDataview();
            $args['where'] = 'upload_id = ' . $upload_id;
            $user_name = $dv->PerformFile('upload_owner',$args,CCDV_RET_ITEM);
            CCUtil::SendBrowserTo( ccl('files',$user_name,$upload_id) );
        }
    
        require_once('cclib/cc-query.php');
        $query = new CCQuery();
        $q = 't=ed_picks&tags=editorial_pick&title=str_editors_picks';
        $args = $query->ProcessAdminArgs($q);
        $query->Query($args);
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
            CCUtil::Send404();
            return;
        }

        CCPage::SetTitle('str_editorial_edit');
        $reviewer_user_name = CCUser::CurrentUsername();
        $reviewer_name      = CCUser::CurrentUserField('user_real_name');

        $uploads =& CCUploads::GetTable();
        $record  = $uploads->GetRecordFromID($upload_id);

        $editorials = $uploads->GetExtraField($record, 'edpicks');

        require_once('cclib/cc-editorials.inc');

        $form = new CCEditorialForm($reviewer_name);
        $showform = true;

        if( empty( $_POST['editorial']) )
        {
            $record['local_menu'] = CCUpload::GetRecordLocalMenu($record);
            $marg = array( $record );
            $form->CallFormMacro( 'records', 'list_files', $marg);
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

                require_once('cclib/cc-uploadapi.php');

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
                CCUtil::SendBrowserTo($url);
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
                     array(  'menu_text'  => _('Editorial'),
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
        CCEvents::MapUrl( 'editorial/submit',   array('CCEditorials','Submit'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{upload_id}', _('Display submit editorial form'), CC_AG_EDPICK );
        CCEvents::MapUrl( 'editorial/picks',    array('CCEditorials','ViewPicks'),    
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '[upload_id]', _('Display ed picks'), CC_AG_EDPICK );
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
               array( 'label'       => _('Editorial staff'),
                       'form_tip'   => _('Comma-separated list of login names for users with editorial privileges'),
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

    function _do_filter(&$records,$doformat)
    {
        $doformat = $doformat && function_exists('cc_format_text');

        $k = array_keys($records);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];
            if( is_string($R['upload_extra']) )
                $R['upload_extra'] = unserialize($R['upload_extra']);
            if( empty($R['upload_extra']['edpicks']) )
                continue;
            $ek = array_keys($R['upload_extra']['edpicks']);
            $R['edpick'] =& $R['upload_extra']['edpicks'][$ek[0]];
            if( $doformat )
            {
                $R['edpick']['review'] = cc_format_text($R['edpick']['review']);
            }
        }
    }

    function OnFilterEdPickDetail(&$records)
    {
        $this->_do_filter($records,true);
    }

    function OnFilterEdPick(&$records)
    {
        $this->_do_filter($records,false);
    }
}


?>
