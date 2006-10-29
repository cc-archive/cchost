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

/*
* Just a quick hacky script to get a decent robots.txt for every
* virtual root in the installation
*/

error_reporting(E_ALL);

if( preg_match( '#[\\\\/]bin$#', getcwd() ) )
    chdir('..');

if( file_exists('robots.txt') )
    die('robots.txt already exists, I do not want to write over it so delete your current one.');

$disallows = array(
        '/tags',
        '/flag',
        '/search/people',
        '/people/contact',
        '/podcast',
        '/stream',
        '/files/stream',
        '/feed',
        '/publicize',
    );
define('IN_CC_HOST', 1);
include('cc-includes.php');
$configs =& CCConfigs::GetTable();
$vroots = $configs->GetConfigRoots();
$text = "User-agent: *\n";
foreach( $vroots as $VR )
{
    foreach( $disallows as $DA )
        $text .= "Disallow: /{$VR['config_scope']}{$DA}\n";
}

$f = fopen('robots.txt','w');
if( !$f )
    die('Can not open robots.txt for writing');
fwrite($f,$text);
fclose($f);
chmod('robots.txt', 0777);


?>