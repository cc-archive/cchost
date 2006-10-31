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
    }

    function _update_sidebar_content()
    {
        global $CC_GLOBALS;

        $troot = 'cctemplates/'; // 

        $sb_src  = $troot . 'custom.xml';
        $sb_dest = $troot . 'sidebar.xml';
        if( !file_exists($sb_src) )
        {
            CCPage::Prompt(_('WARNING') . ': ' . _('The default Sidebar content file (custom.xml) was not found. So, no copy was made.'));
        }
        elseif( !file_exists($sb_dest) )
        {
            if( is_writable($sb_dest) )
            {
                copy( $sb_src, $sb_dest );
                chmod( $sb_dest, 0777 );
                CCPage::Prompt(('custom.xml was copied to sidebar.xml'));
            }
            else
            {
                CCPage::Prompt(sprintf(_("Tried to write to %s, but it is not writable."), $sb_dest) );
            }
        }
        else
        {
            CCPage::Prompt(_('sidebar.xml already exists so custom.xml was not copied'));
        }

    }

}

?>
