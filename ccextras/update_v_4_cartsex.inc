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
class v_4_cartsex extends CCUpdate
{
    function Update()
    {
        $this->_update_cartsex();

        CCPage::Prompt(_('Carts extensions installed'));
   }


    function _update_cartsex()
    {
        if( !$this->_table_exists('cc_tbl_cart') )
        {
            // updates are coming in out of order
            require_once('ccextras/update_v_4_carts.inc');
            $api = new v_4_carts();
            $api->_update_carts();
        }

        $this->_check_for_field('cc_tbl_uploads', 'upload_num_plays', 
                        'INT(5) unsigned NOT NULL default 0');

        $this->_check_for_field('cc_tbl_cart', 'cart_num_plays', 
                        'INT(5) unsigned NOT NULL default 0');
    }

}

?>
