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
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCPseudoVerifyAPI', 'OnMapUrls' )  , 'cchost_lib/ccextras/cc-pseudo-verify.inc' );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,       array( 'CCPseudoVerifyAPI', 'OnGetSysTags'), 'cchost_lib/ccextras/cc-pseudo-verify.inc' );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,        array( 'CCPseudoVerifyAPI', 'OnAdminMenu') , 'cchost_lib/ccextras/cc-pseudo-verify.inc' );

?>