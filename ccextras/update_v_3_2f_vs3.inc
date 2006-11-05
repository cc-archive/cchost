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
class v_3_2f_vs3 extends CCUpdate
{
    function Update()
    {
        $this->_update_config_lang_map();
    }

    function _update_config_lang_map()
    {
        $configs =& CCConfigs::GetTable();

        // write the map so serialize will work 
        // properly

        $configs->SaveDefaultCfgStringMap();

        // now hash all the strings in config...

        $allrows = $configs->QueryRows('');
        foreach( $allrows as $R )
        {
            // by doing it this way, we asure that even
            // if someone runs this code twice (or more)
            // they won't be hashing the hashes that 
            // are already there...
            $data = $configs->GetConfig($R['config_type'],$R['config_scope']);
            $configs->SaveConfig($R['config_type'],$data,$R['config_scope'],false);
        }

        CCPage::Prompt( _('Configuration i18n Information Saved') );
    }


}

?>
