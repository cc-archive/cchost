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

error_reporting(E_ALL & ~E_NOTICE);

if( !file_exists('cc-config-db.php') )
    die('<html><body>ccHost has not been properly installed. Please <a href="ccadmin/">
        follow these steps</a> for a successful
        setup.</body></html>');

if( file_exists('ccadmin') )
    die('<html><body>ccHost Installation is not complete -- for security reasons you 
         should rename "ccadmin". </body></html>');


define('IN_CC_HOST', true);

if( file_exists('.cc-ban.txt') )        // this file is written by doing
    require_once('.cc-ban.txt');        // per-user account management...

require_once('cclib/cc-debug.php');

CCDebug::Enable(false);                 // set this to 'true' if you are a
                                        // developer or otherwise customizing
                                        // the code. 
                                        //
                                        // Hint: You can also create a one line
                                        // module where you set it true and drop
                                        // that module with a .PHP extension into
                                        // ccextras

CCDebug::LogErrors( E_ALL & ~E_NOTICE );  // Log errors to a file during beta
                                          // this will help ccHost developers
                                          // when things go wrong on your site

CCDebug::InstallErrorHandler(true);     

if( !function_exists('gettext') )
   require_once('ccextras/cc-no-gettext.inc');

require_once('cc-includes.php');
require_once('cc-custom.php');


CCConfigs::Init();                      // config settings established here
CCLogin::InitCurrentUser();             // user logged in 

CCEvents::Invoke(CC_EVENT_APP_INIT);    // Let all modules know it's safe to 
                                        // get in the waters

CCEvents::PerformAction();              // process incoming url 

CCPage::Show();                         // show the resulting page

CCDebug::InstallErrorHandler(false); 

CCEvents::Invoke(CC_EVENT_APP_DONE);    

?>
