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
class v_5_fixplaylists extends CCUpdate
{
    function Update()
    {
        $this->_update_fixpl();
    }

    function _update_fixpl()
    {
        $sql =<<<EOF
UPDATE cc_tbl_cart SET cart_num_items = (
SELECT count( * )
FROM cc_tbl_cart_items
WHERE cart_item_cart = cart_id
)
EOF;

        CCDatabase::Query($sql);
        CCPage::Prompt(_('Playlist counts fixed'));
    }


}

?>