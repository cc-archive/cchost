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
class v_4_carts extends CCUpdate
{
    function Update()
    {
        $this->_update_carts();

        CCPage::Prompt(_('Carts installed'));
   }


    function _update_carts()
    {
        if( $this->_table_exists('cc_tbl_cart') )
            return;

        $sql[] =<<<END

CREATE TABLE `cc_tbl_cart` (
  `cart_id` int(11) unsigned NOT NULL auto_increment,
  `cart_user` int(11) UNSIGNED NOT NULL,
  `cart_name` varchar(255) NOT NULL,
  `cart_desc` text NOT NULL,
  `cart_tags` text NOT NULL,
  `cart_type` varchar(20) NOT NULL,
  `cart_subtype` varchar(20) NOT NULL,
  `cart_rating` int(4) NOT NULL,
  `cart_date` datetime NOT NULL,
   cart_dynamic text NOT NULL,
  `cart_num_items` INT( 11 ) UNSIGNED NOT NULL,
    
   PRIMARY KEY  (`cart_id`)
)

END;

        $sql[] =<<<END

CREATE TABLE `cc_tbl_cart_items` (
  `cart_item_id` int(11) NOT NULL auto_increment,
  `cart_item_cart` int(11) NOT NULL,
  `cart_item_upload` int(11) NOT NULL,
  `cart_item_order` int(4) NOT NULL,
  PRIMARY KEY  (`cart_item_id`)
)

END;

        CCDatabase::Query($sql);

        $this->_check_for_field('cc_tbl_uploads', 'upload_num_playlists', 
                        'INT(5) unsigned NOT NULL default 0');

    }

}

?>
