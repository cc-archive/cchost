<?
/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

global $DIG_ROOT_URL;
global $MIXTER_ROOT_DIR;
global $QUERY_ROOT_URL;
global $QUERY_PROXY_URL;

$page = empty($_REQUEST['page']) ? 'home' : $_REQUEST['page'];
    
require_once('pages/'.$page.'.php');

?>
