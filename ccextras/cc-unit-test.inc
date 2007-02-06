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
* incomplete
* @package cchost
* @subpackage extras
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');




/**
*
*
*/
class CCUnitTest
{

    function RunTest()
    {
        print('running test');

        $map = CCEvents::GetUrlMap();

        //CCDebug::PrintVar($map,false);

        $urls = array_keys($map);
        foreach( $urls as $url )
        {
            $action = $map[$url];
            if( is_array($action->cb) )
                $cb = $action->cb[0] . '::' . $action->cb[1];
            else
                $cb = $action->cb;
            $path = empty($action->md) ? '(no mod)' : $action->md;
            print("\n<br />Performing: $url $cb - $path");
            CCPage::GetPage(true);
            CCEvents::PerformAction($action);
        }
        exit;
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','unittest'),  array( 'CCUnitTest', 'RunTest'), 
                          CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}



?>
