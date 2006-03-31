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

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCNSFW', 'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCNSFW', 'OnFormPopulate') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCNSFW', 'OnUploadDone') );

/**
*
*
*/
class CCNSFW
{
    function OnUploadDone($upload_id, $uf_type)
    {
        if( ($uf_type == CC_UF_NEW_UPLOAD || $uf_type == CC_UF_PROPERTIES_EDIT) )
        {
            $value =  array_key_exists('upload_nsfw',$_POST);
            $uploads =& CCUploads::GetTable();
            $uploads->SetExtraField($upload_id,'nsfw',$value);
        }
    }

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
                            array( 'label'      => cct('Not Safe For Work'),
                                   'form_tip'   => cct('Mark this upload as <a ' . 
                                       'href="http://en.wikipedia.org/wiki/NSFW" target="_blank">NSFW</a> '.
                                       'if it contains questionable language.'),
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_NOUPDATE );
        }
    }

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