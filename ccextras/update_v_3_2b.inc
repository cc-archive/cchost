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
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
class v_3_2b extends CCUpdate
{
    function Update()
    {
        $this->_update_ratings();

        CCPage::Prompt('ratings installed');
   }

    function _update_ratings()
    {
        global $CC_GLOBALS;

        if( !empty($CC_GLOBALS['v_3_2a']) ) // this is victor's machine
            return;

        $configs =& CCConfigs::GetTable();
        $roots = $configs->GetConfigRoots();
        $is_on = false;
        foreach( $roots as $root )
        {
            $name = $root['config_scope'];
            $settings = $configs->GetConfig('settings',$name);
            if( isset($settings['ratings']) )
            {
                $is_on |= $settings['ratings'];
            }
        }

        $args['ratings'] = $is_on;
        $args['ratings_ban'] = isset($CC_GLOBALS['ratings_ban']) ? 
                                 $CC_GLOBALS['ratings_ban'] : '';
        $configs->SaveConfig('chart',$args,CC_GLOBAL_SCOPE,true);
        $globals = $configs->GetConfig('config');
        if( isset($globals['ratings_ban']) )
        {
            unset($globals['ratings_ban']);
            $configs->SaveConfig('config',$globals,CC_GLOBAL_SCOPE,false);
        }
    }

}

?>
