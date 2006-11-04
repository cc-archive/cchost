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
* Copyright 2006, Creative Commons, www.creativecommons.org.
* Copyright 2006, Victor Stone.
* Copyright 2006, Jon Phillips, jon@rejon.org.
*/

error_reporting(E_ALL);

if( preg_match( '#[\\\\/]bin$#', getcwd() ) )
    chdir('..');

$no_ui = 1;
define('IN_CC_HOST',1);
require_once('ccextras/cc-export-settings.php');
require_once('cclib/cc-table.php');
require_once('cclib/cc-database.php');
require_once('cclib/cc-config.php');
require_once('cclib/cc-defines.php');
require_once('cclib/cc-debug.php');
require_once('cclib/cc-util.php');
if( !function_exists('gettext') )
    require_once('ccextras/cc-no-gettext.inc');


// I tried every combination of ob_* that I could think of
// and it still prints to screen so, that's that

$ex = new CCSettingsExporter();
$ex->Export();

?>