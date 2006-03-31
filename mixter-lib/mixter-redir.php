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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMixterRedir', 'OnMapUrls'));

class CCMixterRedir
{
    function Redir($base,$arg1='',$arg2='',$arg3='',$arg4='')
    {
    }

    function File($person,$dtitle)
    {
        $uploads =& CCUploads::GetTable();
        $where['upload_user'] = CCUser::IDFromName($person);
        $ok = !empty($where['upload_user']);
        if( $ok )
        {
            $where['old_dtitle'] = $dtitle;
            $row = $uploads->QueryRow($where);
            $ok = !empty($row);
        }
        if( $ok )
        {
            CCUtil::SendBrowserTo( ccl( 'files', $person, $row['upload_id'] ) );
        }
        else
        {
            CCPage::SystemError("That file can not be found, sorry");
        }
    }

    function Contrib($person,$filename)
    {
        $uploads =& CCUploads::GetTable();
        $where['upload_user'] = CCUser::IDFromName($person);
        $ok = !empty($where['upload_user']);
        if( $ok )
        {
            $where['old_fname'] = $filename;
            $records = $uploads->GetRecords($where);
            $ok = !empty($records[0]);
        }
        if( $ok )
        {
            $url = $records[0]['files'][0]['download_url'];
            CCUtil::SendBrowserTo( $url );
        }
        else
        {
            CCPage::SystemError("That file can not be found, sorry");
        }
    }

    function Stream($person,$dtitle)
    {
        $uploads =& CCUploads::GetTable();
        $where['upload_user'] = CCUser::IDFromName($person);
        $ok = !empty($where['upload_user']);
        if( $ok )
        {
            $where['old_dtitle'] = $dtitle;
            $upload_id = $uploads->QueryKey($where);
            $ok = !empty($upload_id);
        }
        if( $ok )
        {
            $ra = new CCRenderAudio();
            $ra->StreamFiles('', $upload_id . '.m3u' );
        }
        else
        {
            // yea, die
            die("That file can not be found, sorry");
        }
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( 'redir',         array('CCMixterRedir', 'Redir'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'redir/file',    array('CCMixterRedir', 'File'),     CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'redir/contrib', array('CCMixterRedir', 'Contrib'),  CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'redir/m3utree', array('CCMixterRedir', 'Stream'),   CC_DONT_CARE_LOGGED_IN);
    }

}

?>