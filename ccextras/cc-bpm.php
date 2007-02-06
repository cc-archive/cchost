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

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCBPM' , 'OnGetConfigFields' ), 'ccextras/cc-bpm.inc' );

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCBPM', 'OnFormFields'), 'ccextras/cc-bpm.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCBPM', 'OnFormPopulate'), 'ccextras/cc-bpm.inc'  );
CCEvents::AddHandler(CC_EVENT_FORM_VERIFY,    array( 'CCBPM', 'OnFormVerify'), 'ccextras/cc-bpm.inc'  );

CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCBPM', 'OnUploadDone'), 'ccextras/cc-bpm.inc'  );


?>
