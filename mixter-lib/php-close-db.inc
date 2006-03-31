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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

//CCEvents::AddHandler(CC_EVENT_APP_INIT, 'cc_phpbb_ensure_closedb');

function cc_phpbb_ensure_closedb()
{
    global $CC_GLOBALS;

    // don't run this code on ccMixter.org

    if( empty($CC_GLOBALS['phpbb2_closedb']) )
    {
        $CC_GLOBALS['phpbb2_closedb'] = true;
        $configs =& CCConfigs::GetTable();
        $configs->SetValue('config','phpbb2_closedb', true);
    }

}
?>