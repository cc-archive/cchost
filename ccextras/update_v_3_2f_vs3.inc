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
        // grab all current rows
        // these have non-hashed strings
        // in them

        $configs =& CCConfigs::GetTable();
        $allrows = $configs->QueryRows('');

        // write the map so serialize will work 
        // properly

        $config_lang_map = $this->_get_lang_map();

        $configs->SaveConfig('clangmap', $config_lang_map,
                               CC_GLOBAL_SCOPE, false );

        // now hash all the strings in config...

        foreach( $allrows as $R )
        {
            // by doing it this way, we asure that even
            // if someone runs this code twice (or more)
            // they won't be hashing the hashes that 
            // are already there...
            $data = $configs->GetConfig($R['config_type'],$R['config_scope']);
            $configs->SaveConfig($R['config_type'],$data,$R['config_scope'],false);


            $configs->GetConfig($R['config_type'],$R['config_scope']);
        }

        CCPage::Prompt( _('Configuration i18n Information Saved') );
    }

    function _get_lang_map()
    {
        return array ( 

            'tab_pages' =>
            array( 
                    '*' => array (
                        '*' => array (
                            'text' => true,
                            'help'=> true,
                            ),
                        ),
             ),

            'config' =>  // config_type
             array( 
                    'ban-message'=> true,
                    'flag_msg' => true,
                    'ban-email' => true,
                 ),

            'channels' =>
                array(
                    '*' => array(
                        'text' => true,
                        )
                     ),

            'id3-tag-masks' =>
            array( 
                    'copyright'=> true,
             ),

            'throttle' =>
            array( 
                    'quota-msg'=> true,
             ),

            'ttag' =>
            array( 
                    'site-title'=> true,
                    'banner-html'=> true,
                    'site-description'=> true,
                    'footer'=> true,
                    'site-license'=> true,
                    'site-meta-description'=> true,
                    'site-meta-keywords'=> true,
                    'beta_message'=> true,
             ),

            'submit_forms' =>
            array( 
                    '*' => array (
                        'text'=> true,
                        'submit_type'=> true,
                        'help'=> true,
                        'form_help'=> true,
                        ),
             ),

            'groups' =>
            array( 
                    '*' => array (
                        'group_name'=> true,
                        ),
             ),

            'menu' =>
            array( 
                    '*' => array (
                        'menu_text'=> true,
                        ),
             ),
        ); 

    }
}

?>
