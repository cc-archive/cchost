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

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCFeaturing', 'OnFormFields')   , 'ccextras/cc-featuring.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_POPULATE,  array( 'CCFeaturing', 'OnFormPopulate') , 'ccextras/cc-featuring.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_VERIFY,    array( 'CCFeaturing', 'OnFormVerify')   , 'ccextras/cc-featuring.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCFeaturing', 'OnUploadDone')   , 'ccextras/cc-featuring.inc' );
CCEvents::AddHandler(CC_EVENT_GET_MACROS,     array( 'CCFeaturing', 'OnGetMacros')    , 'ccextras/cc-featuring.inc' );

?>