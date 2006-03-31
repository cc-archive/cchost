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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCHowIDidIt',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCHowIDidIt',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCHowIDidIt',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCHowIDidIt',  'OnMapUrls'));

class CCHowIDidItForm extends CCForm
{
    function CCHowIDidItForm()
    {
        $this->CCForm();
        $this->AddFormFields(CCHowIDidIt::_get_fields());
    }
}

/**
* Main API for media blogging
*/
class CCHowIDidIt
{
    /*-----------------------------
        MAPPED TO URLS
    -------------------------------*/

    function HowIDidIt($upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        if( empty($record['upload_extra']['howididit']) )
            return;
        $record['local_menu'] = CCUpload::GetRecordLocalMenu($record);
        $arg = array( $record );
        CCUpload::ListRecords( $arg );
        $fields = $this->_get_fields();
        $data = $record['upload_extra']['howididit'];
        $keys = array_keys($data);
        foreach( $keys as $key )
        {
            if( !empty($data[$key]) )
                $data[$key] = nl2br($data[$key]);
        }
        CCPage::SetTitle(cct("How I Did It"));
        CCPage::PageArg('howididit_fields',$fields);
        CCPage::PageArg('howididit_info',$data,'howididit');

    }

    function nlfix($str)
    {
        return( preg_replace( "/[\n\r]+/","<br />",$str) );
    }

    function EditHowIDidIt($upload_id)
    {
        CCUpload::CheckFileAccess(CCUser::CurrentUser(),$upload_id);
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);
        if( empty($record) )
            return;
        CCPage::SetTitle( cct("Edit 'How I Did It' for ") . $record['upload_name']);
        $form = new CCHowIDidItForm();
        $is_post = !empty($_POST['howididit']);
        if(  !$is_post && !empty($record['upload_extra']['howididit']) )
            $form->PopulateValues($record['upload_extra']['howididit']);
        if( !$is_post || !$form->ValidateFields() )
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $form->GetFormValues($values);
            $record['upload_extra']['howididit'] = $values;
            $args['upload_extra'] = serialize($record['upload_extra']);
            $args['upload_id'] = $upload_id;
            $uploads->Update($args);
            $url = ccl('howididit',$upload_id);
            CCPage::Prompt(sprintf(cct("Changes saved. Click <a href=\"%s\">here</a> to see results"),$url));
        }
    }

    function _get_fields()
    {
        $fields = array(
            'tools' => array(
                'label'     => cct('Tools I Used'),
                'form_tip'  => cct('What software, hardware, plug-ins, etc. did you use?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'samples' => array(
                'label'     => cct('Samples I Used'),
                'form_tip'  => cct('Where did you find your samples? What kind of license are they under?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'origial' => array(
                'label'     => cct('Original Samples'),
                'form_tip'  => cct('What material did you create just for this work?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'process' => array(
                'label'     => cct('Process'),
                'form_tip'  => cct('How did you put all the pieces together?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'other' => array(
                'label'     => cct('Other Notes'),
                'form_tip'  => cct('Share your feelings about the experience of creating this work'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE)
            );

        return($fields);
    }

    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow( &$record )
    {
        if( !empty($record['upload_extra']['howididit']) )
        {
            $record['howididit_link'] = array( 'action' => ccl('howididit',$record['upload_id']),
                                               'text'  => cct('How I Did It'));
            if( empty($record['file_macros']) )
                $record['file_macros'][] = 'howididit_link';
            else
                array_unshift($record['file_macros'],'howididit_link');
        }

    }

    /**
    * Event handler for CC_EVENT_BUILD_UPLOAD_MENU
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['howididit'] = 
                     array(  'menu_text'  => cct('Edit "How I Did It"'),
                             'weight'     => 110,
                             'group_name' => 'owner',
                             'id'         => 'editcommand',
                             'access'     => CC_MUST_BE_LOGGED_IN );
    }

    /**
    * Event handler for CC_EVENT_UPLOAD_MENU
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu
    */
    function OnUploadMenu(&$menu,&$record)
    {
        $isowner = CCUser::CurrentUser() == $record['user_id'];
        $isadmin = CCUser::IsAdmin();

        if( $isadmin || $isowner && !$record['upload_banned'] )
        {
            $menu['howididit']['action'] = ccl( 'edithowididit', $record['upload_id'] );
            
            if( $isadmin && !$isowner ) // geez, it's me!
                $menu['howididit']['group_name']  = 'admin';
        }
        else
        {
            $menu['howididit']['access'] = CC_DISABLED_MENU_ITEM;
        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('howididit'),      array('CCHowIDidIt','HowIDidIt'),     CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( ccp('edithowididit'),  array('CCHowIDidIt','EditHowIDidIt'),   CC_MUST_BE_LOGGED_IN );
    }

}


?>