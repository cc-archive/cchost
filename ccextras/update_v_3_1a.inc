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
class v_3_1a extends CCUpdate
{
    function Update()
    {
        $this->_update_sidebar_content();

        // we have to kill the cache for
        // skin maker:
        CCMenu::KillCache();
    }

    function _update_sidebar_content()
    {
        global $CC_GLOBALS;

        $troot = $CC_GLOBALS['template-root'];

        $sb_src  = $troot . 'custom.xml';
        $sb_dest = $troot . 'sidebar.xml';
        if( !file_exists($sb_src) )
        {
            CCPage::Prompt('Warning: The default Sidebar content file (custom.xml) was not
                            found so no copy was made');
        }
        elseif( !file_exists($sb_dest) )
        {
            copy( $sb_src, $sb_dest );
            chmod( $sb_dest, 0777 );
            CCPage::Prompt('custom.xml was copied to sidebar.xml');
        }
        else
        {
            CCPage::Prompt('sidebar.xml alrady exists so custom.xml was not copied');
        }

    }

}

?>
