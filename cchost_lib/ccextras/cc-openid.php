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
* $id$
*
*/

/**
* @package cchost
* @subpackage user
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define( 'CC_OPENID_NONE', 0 );
define( 'CC_OPENID_AND_REG', 1 );
define( 'CC_OPENID_ONLY', 2 );


CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCOpenID' , 'OnGetConfigFields' ), 'cchost_lib/ccextras/cc-openid.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS, array( 'CCOpenID', 'OnMapUrls' ), 'cchost_lib/ccextras/cc-openid.inc' );

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCOpenID', 'OnFormFields'), 'cchost_lib/ccextras/cc-openid.inc' );

?>
