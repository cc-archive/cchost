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

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCNSFW', 'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCNSFW', 'OnFormPopulate') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCNSFW', 'OnUploadDone') );

/**
*
*
*/
class CCNSFW
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
        if( ($op == CC_UF_NEW_UPLOAD || $op == CC_UF_PROPERTIES_EDIT) )
        {
            $value =  array_key_exists('upload_nsfw',$_POST);
            $uploads =& CCUploads::GetTable();
            $uploads->SetExtraField($upload_id,'nsfw',$value);
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
        if( is_subclass_of($form,'CCUploadMediaForm') ||
                    is_subclass_of($form,'ccuploadmediaform') )
        {
            /*
            *  Add NSFW to file uploads
            */
            if( empty($fields['upload_nsfw']) )
                $fields['upload_nsfw'] = 
                            array( 'label'      => _('Not Safe For Work'),
                                   'form_tip'   => _('Mark this upload as <a ' . 
                                       'href="http://en.wikipedia.org/wiki/NSFW" target="_blank">NSFW</a> '.
                                       'if it contains questionable language.'),
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_NOUPDATE );
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
        $nsfw = !empty($values['upload_extra']['nsfw']);
        $form->SetFormValue('upload_nsfw',$nsfw);
    }
}



?>