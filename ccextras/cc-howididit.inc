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

require_once('cclib/cc-form.php');

/**
*/
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

    function Browse()
    {
        require_once('cclib/cc-upload-table.php');
        CCPage::SetTitle(_("Browse 'How I Did It'"));
        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter('how_i_did_it');
        $ord = 'ASC';
        if( empty($_GET['sort']) )
        {
            $sort = 'upload_name';
        }
        else
        {
            switch( $_GET['sort'])
            {
                case 'name';
                    $sort = 'upload_name';
                    break;
                case 'date';
                    $sort = 'upload_date';
                    $ord = 'DESC';
                    break;
                case 'user';
                    $sort = 'user_real_name';
                    break;
            }
        }
        $uploads->SetOrder($sort,$ord);
        $rows = $uploads->QueryRows('');
        $uploads->SetTagFilter('');
        $uploads->SetOrder('');
        $url = ccl('howididit','detail');
        $current_url = cc_calling_url();
        $sorts = array(
                    array( 'url' => url_args($current_url,'sort=user'),
                           'text' => _('Artist'),
                           'selected' => $sort == 'user_real_name' ),
                    array( 'url' => url_args($current_url,'sort=date'),
                           'text' => _('Date'),
                           'selected' => $sort == 'upload_date' ),
                    array( 'url' => url_args($current_url,'sort=name'),
                           'text' => _('Upload Name'),
                           'selected' => $sort == 'upload_name' ),
            );

        $help = _("Remixers are encouraged to specify the tools and process they used to create submissions to this site. Below is a list of submissions that the author has annotated with these special notes. Click on any submission to see the 'author's notes.");

        CCPage::PageArg('howididit_sort_cap', _('Sort by:'));
        CCPage::PageArg('howididit_sorts', $sorts );
        CCPage::PageArg('howididit_help', _($help) );
        CCPage::PageArg('howididit_url',$url);
        CCPage::PageArg('howididit_records',$rows,'howididit_browse');
    }

    function Detail($upload_id)
    {
        require_once('cclib/cc-upload-table.php');
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($upload_id);
        if( empty($record['upload_extra']['howididit']) )
            return;
        global $CC_GLOBALS;
        $args = $CC_GLOBALS;
        $args['root-url'] = ccd();
        $args['record'] = $record;
        $args['howididit_fields'] = $this->_get_fields();
        $args['howididit_info'] = $this->_get_data($record);
        $template = new CCTemplateMacro( '', 'howididit_detail' );
        print( $template->SetAllAndParse($args) );
        exit;
    }

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
        $data = $this->_get_data($record);
        CCPage::SetTitle(_("How I Did It"));
        CCPage::PageArg('howididit_fields',$fields);
        CCPage::PageArg('howididit_info',$data,'howididit');

    }

    function _get_data(&$record)
    {
        $data = $record['upload_extra']['howididit'];
        $keys = array_keys($data);
        foreach( $keys as $key )
        {
            if( !empty($data[$key]) )
                $data[$key] = nl2br($data[$key]);
        }
        return $data;
    }

    function nlfix($str)
    {
        return( preg_replace( "/[\n\r]+/","<br />",$str) );
    }

    function EditHowIDidIt($upload_id)
    {
        require_once('cclib/cc-upload.php');
        require_once('cclib/cc-uploadapi.php');

        CCUpload::CheckFileAccess(CCUser::CurrentUser(),$upload_id);
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);
        if( empty($record) )
            return;
        CCPage::SetTitle( _("Edit 'How I Did It' for ") . $record['upload_name']);
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
            //CCDebug::PrintVar($values);
            $has_values = false;
            foreach( $values as $name => $val )
            {
                if( !empty($val) )
                {
                    $has_values = true;
                    break;
                }
            }
            if( $has_values )
            {
                $record['upload_extra']['howididit'] = $values;
                $args['upload_extra'] = serialize($record['upload_extra']);
                $args['upload_id'] = $upload_id;
                $uploads->Update($args);
                CCUploadAPI::UpdateCCUD($upload_id,'how_i_did_it','');
                $url = ccl('howididit',$upload_id);
                CCPage::Prompt(sprintf(_("Changes saved. Click %s to see results"), "<a href=\"$url\">here</a>"));
            }
            else
            {
                if( !empty($record['upload_extra']['howididit']) )
                {
                    unset($record['upload_extra']['howididit']);
                    $args['upload_extra'] = serialize($record['upload_extra']);
                    $args['upload_id'] = $upload_id;
                    $uploads->Update($args);
                }

                CCUploadAPI::UpdateCCUD($upload_id,'','how_i_did_it');

                $url = $record['file_page_url'];
                $msg = _("No 'How I Did It' for this record. Click %s to go back to the upload's page");
                CCPage::Prompt(sprintf($msg,"<a href=\"$url\">here</a>"));
            }
                    
        }
    }

    function _get_fields()
    {
        $fields = array(
            'tools' => array(
                'label'     => _('Tools I Used'),
                'form_tip'  => _('What software, hardware, and plug-ins did you use?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'samples' => array(
                'label'     => _('Samples I Used'),
                'form_tip'  => _('Where did you find your samples? What kind of license are they under?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'origial' => array(
                'label'     => _('Original Samples'),
                'form_tip'  => _('What material did you create just for this work?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'process' => array(
                'label'     => _('Process'),
                'form_tip'  => _('How did you put all the pieces together?'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE),
            'other' => array(
                'label'     => _('Other Notes'),
                'form_tip'  => _('Share your feelings about the experience of creating this work.'),
                'formatter' => 'textarea',
                'flags'     => CCFF_POPULATE)
            );

        return $fields;
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('howididit'),      array('CCHowIDidIt','HowIDidIt'),   
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{upload_id}', 
            _('Show How I Did It Page for an upload'), CC_AG_HIDI );

        CCEvents::MapUrl( ccp('howididit','browse'), array('CCHowIDidIt','Browse'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), 
            _('Show How I Did It browser'), CC_AG_HIDI  );

        CCEvents::MapUrl( ccp('howididit','detail'), array('CCHowIDidIt','Detail'),  
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__)  );

        CCEvents::MapUrl( ccp('edithowididit'),  array('CCHowIDidIt','EditHowIDidIt'),   
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{upload_id}', 
            _('Edit How I Did It Page'), CC_AG_HIDI );
    }

}


?>
