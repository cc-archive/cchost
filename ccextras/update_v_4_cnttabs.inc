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
class v_4_cnttabs extends CCUpdate
{
    function Update()
    {
        $this->_update_tabs();

        CCPage::Prompt(_('Contest tab pages updated'));
   }

    function _update_tabs()
    {
        global $CC_GLOBALS;

        $configs =& CCConfigs::GetTable();
        //error_reporting(E_STRICT | E_ALL)
        $roots = $configs->GetConfigRoots();
        foreach( $roots as $root )
        {
            $name = $root['config_scope'];
            $pages = $configs->GetConfig('tab_pages',$name);
            $write = false;
            $fpages = $pages;
            foreach( $fpages as $page_name => $page )
            {
                if( !empty($page['handler']) )
                {
                    if( empty($page['handler']['method'][0]) || 
                          $page['handler']['method'][0] != 'CCContest' )
                    {
                        CCPage::Prompt("Warning: unknown handler for tabs: {$page}::{$page_name}");
                    }
                    else
                    {
                        $pages[$page_name]['handler']['method'][0] = 'CCContestHV';
                        $pages[$page_name]['handler']['module'] = 'cclib/cc-contest.php';
                        $write = true;
                    }
                }
            }
            if( $write )
            {
                $configs->SaveConfig('tab_pages',$pages,$name,false);
            }
        }
    }

}

?>
