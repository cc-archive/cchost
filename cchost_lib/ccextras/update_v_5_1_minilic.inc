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
* $Id: update_v_5_1_paging.inc 12334 2009-04-22 06:54:31Z fourstones $
*
*/

/**
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
class v_5_1_minilic extends CCUpdate
{
    function Update()
    {
        require_once('cchost_lib/cc-page.php');
        $cfg = new CCConfigs();
        $lics = $cfg->GetConfig('licenses');
        if( array_search('on',$lics) === false )
        {
            $keys = array_keys($lics);
            foreach( $keys as $k )
                $lics[$k] = 'on';
            $cfg->SaveConfig('licenses',$lics,'',false);
        }
        CCPage::Prompt( sprintf(_('Internal license format change') ) );
    }
}

?>
