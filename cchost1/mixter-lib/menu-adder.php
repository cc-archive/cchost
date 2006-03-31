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

CCEvents::AddHandler(CC_EVENT_APP_INIT, 'menu_adder_install');

function menu_adder_install()
{
    global $CC_GLOBALS;

    if( empty($_REQUEST['stuffmenu']) )
        return;

    $m = CCMenu::_menu_items();
    $i = 0;
    $key = 'dummy0';
    //CCDebug::PrintVar($m);
    for( $n = 0; $n < 4; $n++ )
    {
        while( array_key_exists($key,$m) )
        {
            $key = 'dummy' . $i++;
        }
        $items[$key] = array(
                 'menu_text'  => 'Dummy' . $i,
                 'menu_group' => 'extra1',
                 'access' => CC_DONT_CARE_LOGGED_IN,
                 'weight' => 3,
                 'action' =>  ccl()
                );
        $m[$key] = true;
    }

    CCMenu::AddItems($items,true);

    CCUtil::SendBrowserTo(ccl('admin/menu'));
} 
?>