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
class v_3_0g extends CCUpdate
{
    function Update()
    {
        $this->_update_bpm();
    }

    function _update_bpm()
    {
        $sql = 'SELECT upload_id,upload_extra FROM cc_tbl_uploads';
        $qr = mysql_query($sql);
        $uploads =& CCUploads::GetTable();
        while( $row = mysql_fetch_assoc($qr) )
        {
            $ex = unserialize($row['upload_extra']);
            if( empty($ex['bpm']) || !intval($ex['bpm']) )
                continue;
            $tag = CCBPM::CalcBPMTag($ex['bpm']);
            if( $tag )
                CCUploadAPI::UpdateCCUD($row['upload_id'],$tag,'');
        }
        CCPage::Prompt("BPM info updated");
    }

}

?>
