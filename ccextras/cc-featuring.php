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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCFeaturing', 'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCFeaturing', 'OnFormPopulate') );
CCEvents::AddHandler(CC_EVENT_FORM_VERIFY,    array( 'CCFeaturing', 'OnFormVerify') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCFeaturing', 'OnUploadDone') );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,     array( 'CCFeaturing', 'OnGetMacros'));

/**
*
*
*/
class CCFeaturing
{

    /**
    * Event handler for {@link CC_EVENT_GET_MACROS}
    *
    * @param array &$record Upload record we're getting macros for (if null returns documentation)
    * @param array &$file File record we're getting macros for
    * @param array &$patterns Substituion pattern to be used when renaming/tagging
    * @param array &$mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros( &$record, &$file, &$patterns, &$mask )
    {
        require_once('ccextras/cc-featuring.inc');
        $api = new CCFeaturingAPI();
        $api->OnGetMacros( $record, $file, $patterns, $mask );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_DONE}
    * 
    * @param integer $upload_id ID of upload row
    * @param string $op One of {@link CC_UF_NEW_UPLOAD}, {@link CC_UF_FILE_REPLACE}, {@link CC_UF_FILE_ADD}, {@link CC_UF_PROPERTIES_EDIT'} 
    * @param array &$parents Array of remix sources
    */
    function OnUploadDone($upload_id, $op)
    {
        require_once('ccextras/cc-featuring.inc');
        $api = new CCFeaturingAPI();
        $api->OnUploadDone($upload_id,$op);
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        require_once('ccextras/cc-featuring.inc');
        $api = new CCFeaturingAPI();
        $api->OnFormFields($form,$fields);
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

        if( !empty($values['upload_extra']['featuring']) )
            $form->SetFormValue('upload_featuring',$values['upload_extra']['featuring']);
    }

    /**
    * Event handler for {@link CC_EVENT_FORM_VERIFY}
    * 
    * @param object &$form CCForm object
    * @param boolean &$retval Set this to false if fields fail to verify
    */
    function OnFormVerify(&$form,&$retval)
    {
        require_once('ccextras/cc-featuring.inc');
        $api = new CCFeaturingAPI();
        $api->OnFormVerify($form,$retval);
    }
}

?>