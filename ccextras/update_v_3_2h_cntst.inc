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
class v_3_2h_cntst extends CCUpdate
{
    function Update()
    {
        $this->_update_contest_source_field();

        CCPage::Prompt(_('Contest entry dates updated'));
   }


    function _update_contest_source_field()
    {
        if( !$this->_check_for_field('cc_tbl_contests', 'contest_entries_accept', 
                        'datetime NOT NULL') )
        {
            $sql = 'UPDATE cc_tbl_contests SET contest_entries_accept = contest_open';
            CCDatabase::Query($sql);
        }

    }

}

?>