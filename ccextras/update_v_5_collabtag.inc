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

class v_5_collabtag extends CCUpdate
{
    function Update()
    {
        $this->_update_collabtag();

        CCPage::Prompt(_('collabtag extensions installed'));
    }

    function _update_collabtag()
    {
        require_once('ccextras/cc-collab.inc');
        $uploads = new CCUploads();
        $uploads->AddJoin( new CCCollabUploads(), 'upload_id' );
        $uploads->SetTagFilter('remix');
        $rows = $uploads->QueryRows('','upload_id,collab_upload_collab');
        $c = count($rows);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $rows[$i];
            $uploads->SetExtraField( $R['upload_id'], 'collab', $R['collab_upload_collab'] );
        }
    }
}
?>
