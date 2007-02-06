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
class v_4_uphide extends CCUpdate
{
    function Update()
    {
        $this->_update_upload_hidden();
    }

    function _update_upload_hidden()
    {
        $sql = 'UPDATE cc_tbl_uploads SET upload_published = 1 WHERE upload_contest > 0';
        CCDatabase::Query($sql);
        CCPage::Prompt(_('Database optimization installed'));
    }


}

?>
