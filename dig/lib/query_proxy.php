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


require_once('../config.php');

define('CC_HOST_CMD_LINE', 1 );      // define this exact way
chdir( $MIXTER_ROOT_DIR );           // must be run from the cchost install root dir
$NO_EXTRANEOUS_OUTPUT = 1;           // supress tagline of this file (OPTIONAL)
require_once( 'cc-cmd-line.inc' );  

require_once( 'cchost_lib/cc-query.php');

$query = new CCQuery();

$args = $query->ProcessUriArgs();

if( $args['format'] != 'page' )
{
    $query->ProcessUri(); // this will exit
}

chdir( dirname(__FILE__) . '/..' ); // chdir back to dig


?>