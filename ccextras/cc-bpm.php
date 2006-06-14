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
* @subpackage audio
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCBPM', 'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCBPM', 'OnFormPopulate') );
CCEvents::AddHandler(CC_EVENT_FORM_VERIFY,    array( 'CCBPM', 'OnFormVerify') );

CCEvents::AddHandler(CC_EVENT_DO_SEARCH,      array( 'CCBPM', 'OnDoSearch') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCBPM', 'OnUploadDone') );

/**
*
*
*/
class CCBPM
{

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    * 
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'} 
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id, $op)
    {
        if( ($op == CC_UF_NEW_UPLOAD || $op == CC_UF_PROPERTIES_EDIT) &&
            array_key_exists('upload_bpm',$_POST)
          )
        {
            $uploads =& CCUploads::GetTable();
            $uploads->SetExtraField($upload_id,'bpm',$_POST['upload_bpm']);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        if( strtolower( get_class($form) ) == 'ccsearchform' )
        {
            /*
            *  Add BPM to search
            */
            $options = $fields['search_in']['options'];
            $sorted = $options;
            ksort($sorted);
            $nextbit = 1;
            foreach( $sorted as $key => $value )
            {
                if( $key & $nextbit )
                {
                    $nextbit <<= 1;
                }
            }
            $options[$nextbit] = _('BPM (use \'-\' for range: 90-100)');
            $fields['search_in']['options'] = $options;
            $form->SetHiddenField('bpm_search',$nextbit);
        }
        elseif( is_subclass_of($form,'CCUploadMediaForm') ||
                    is_subclass_of($form,'ccuploadmediaform') )
        {
            /*
            *  Add BPM to file uploads
            */
            if( empty($fields['upload_bpm']) )
                $fields['upload_bpm'] = 
                            array( 'label'      => _('BPM'),
                                   'form_tip'   => _('Tempo'),
                                    'class'     => 'form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_NOUPDATE);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_POPULATE}
    * 
    * @param object &$form CCForm object
    * @param array &$values Current values being applied to form fields
    */
    function OnFormPopulate(&$form,&$values)
    {
        if( !is_subclass_of($form,'CCUploadMediaForm') &&
                    !is_subclass_of($form,'ccuploadmediaform') )
        {
            return;
        }
        if( !empty($values['upload_extra']['bpm']) )
            $form->SetFormValue('upload_bpm',$values['upload_extra']['bpm']);
    }


    /**
    * Event handler for {@link CC_EVENT_FORM_VERIFY}
    * 
    * @param object &$form CCForm object
    * @param boolean &$retval Set this to false if fields fail to verify
    */
    function OnFormVerify(&$form,&$retval)
    {
        if( !is_subclass_of($form,'CCUploadMediaForm') &&
                    !is_subclass_of($form,'ccuploadmediaform') )
        {
            return;
        }
        
        if( !array_key_exists('upload_bpm', $_POST) )
            return;

        $bpm = CCUtil::StripText($_POST['upload_bpm']);
        if( empty($bpm) )
            return;

        if( !intval($bpm) || $bpm < 10 || $bpm > 300 )
            $bpm = 0;

        $form->SetFormValue('upload_bpm',$bpm );

        return true;
    }

    /**
    * Event handler for {@link CC_EVENT_DO_SEARCH}
    * 
    * @param boolean &$done_search Set this to true if you handle the search
    */
    function OnDoSearch(&$done_search)
    {
        if( empty($_POST['bpm_search']) )
            return;

        $bpm_field = CCUtil::StripText($_POST['bpm_search']);
        if( !intval($bpm_field) )
            return;

        if( $_POST['search_in'] == $bpm_field )
        {
            if( !empty($_POST['search_text']) )
            {
                $q = trim($_POST['search_text']);
                if(!empty($q) )
                    $q = split('-',$q);
            }

            if( !empty($q) )
            {
                $uploads =& CCUploads::GetTable();
                if( count($q) == 1 )
                {
                    if( !intval($q[0]) || $q[0] < 1 )
                        return;

                    list( , $where ) = $uploads->WhereForSerializedField('upload_extra', 'bpm', $q[0]);
                }
                else
                {
                    if( !intval($q[0]) || $q[0] < 1 || !intval($q[1]) || $q[0] >= $q[1] )
                        return;
                    list( $field, $regexp ) = $uploads->WhereForSerializedField('upload_extra', 'bpm', '[1-9]+');
                    $where = "$regexp AND ($field >= {$q[0]}) AND ($field <= {$q[1]})";
                }

                $records = $uploads->GetRecords($where);
                if( empty($records) )
                {
                    $url = ccl('search');
                    CCPage::Prompt(sprintf(_("No records match that BPM. Go back to <a href=\"%s\">search again</a>"),$url));
                }
                else
                {
                    $count = count($records);
                    for( $i = 0; $i < $count; $i++ )
                    {
                        $records[$i]['result_info'] = 'BPM: <span>' . $records[$i]['upload_extra']['bpm'] . '</span>';
                    }
            
                    CCUpload::ListRecords($records);

                    $done_search = true;
                }
            }
        }
    }
}



?>