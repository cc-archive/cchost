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
class v_3_1e extends CCUpdate
{
    function Update()
    {
        $this->_update_local_path_struct();

   }


    function _update_local_path_struct()
    {
        if( file_exists('local_files') )
        {
            global $CC_GLOBALS;

            $msg = 'local_files installed';
            $fields['files-root'] = $CC_GLOBALS['files-root'] = 'local_files/viewfile;ccfiles';
            $fields['template-root'] = $CC_GLOBALS['template-root'] = 'local_files/skins;cctemplates';
            $fields['php-tal-cache-dir'] = $CC_GLOBALS['php-tal-cache-dir'] = 'local_files/temp';
            $fields['extra-lib'] = $CC_GLOBALS['extra-lib'] = 'local_files/lib';
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig( 'config', $fields, CC_GLOBAL_SCOPE, true );

        }
        else
        {
            $msg = 'no changes were made';
        }
        CCPage::Prompt(_('Path structures checked:') . " $msg");
    }

}

?>
