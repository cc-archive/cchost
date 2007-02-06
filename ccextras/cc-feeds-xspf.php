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
* XSPF Module feed generator
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCFeedsXSPF', 'OnMapUrls'), 'ccextras/cc-feeds-xspf.inc' );

// implemented in the base class
CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCFeedsXSPF', 'OnApiQueryFormat'), 'ccextras/cc-feeds-xspf.inc' ); 

?>
