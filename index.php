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

/*
*  The code was originally written for PHP 4 and while we 
*  would rather run under E_ALL it seems impossible without 
*  100s of changes, so we push it as far as possible for 5
*/
if( version_compare(phpversion(),'4.4.3') < 0 )
    $cc_error_level = E_ALL;
else
    $cc_error_level = E_ALL & ~E_NOTICE;

error_reporting($cc_error_level); 

/*
*  ccHost can't connect to the database without this file. 
*  If not present, it probably means we haven't installed yet.
*/
if( !file_exists('cc-config-db.php') )
{
    die('<html><body>ccHost has not been properly installed. 
        Please <a href="ccadmin/">
        follow these steps</a> for a successful
        setup.</body></html>');
}

/*
*  All ccHost includes require this define to prevent direct 
*  web access to them.
*/
define('IN_CC_HOST', true);

/*
*  The .cc-ban.txt file is written by doing 'Account Management' 
*  from the user's profile. We don't assume that ccHost is 
*  running under Apache, otherwise we would do this through 
*  Deny in .htaccess
*/
if( file_exists('.cc-ban.txt') )        
    require_once('.cc-ban.txt');        

/*
* We make a special include for debug so that modules can turn 
* it on as quickly as possible.
*/
require_once('cclib/cc-debug.php');

/*
* Logging errors to a file this will help ccHost developers
* when things go wrong on your site
*/
CCDebug::LogErrors( $cc_error_level );

/*
*  We catch errors and handle them according log file settings
*/
CCDebug::InstallErrorHandler(true);     

/*
*  Internaitionalization requires (for now) that gettext be 
*  compiled into PHP
*/
if( !function_exists('gettext') )
   require_once('ccextras/cc-no-gettext.inc');

/*
*  Include core modules and extras that come with the 
*  ccHost package
*/
require_once('cc-includes.php');
$cc_extras_dirs = 'ccextras';
include('cc-inc-extras.php');
require_once('cc-custom.php');

/*
* Configuration initialized here
*/
CCConfigs::Init();

/*
*  We don't want to encourage ccHost installations to have 
*  their installation directories open to the public. We 
*  check it here after Configs::Init because the admin can 
*  disable the site while doing other work (like a SVN 
*  update)
*/
if( file_exists('ccadmin') )
    die('<html><body>ccHost Installation is not complete -- for security reasons you 
         should rename "ccadmin". </body></html>');

/*
*  Pick up 3rd party PHP modules
*/
if( !empty($CC_GLOBALS['extra-lib']) )
{
    $cc_extras_dirs = $CC_GLOBALS['extra-lib'];
    include('cc-inc-extras.php');
}

/*
*  User is logged in after this call
*/
CCLogin::InitCurrentUser();             

/*
*  Let all the modules know that config is set
*  and user has been logged in.
*/
CCEvents::Invoke(CC_EVENT_APP_INIT);

/*
*  Process incoming URL
*/
CCEvents::PerformAction();

/*
*  Show the resulting page
*/
CCPage::Show();           

/*
*  Shut down the session
*/
CCDebug::InstallErrorHandler(false); 
CCEvents::Invoke(CC_EVENT_APP_DONE);    

?>
