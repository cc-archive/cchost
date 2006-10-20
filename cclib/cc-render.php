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
* $Id: cc-renderimage.php 3560 2006-06-26 16:56:38Z fourstones $
*
*/

/**
* @package cchost
* @subpackage render
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* @package cchost
* @subpackage render
*/
class CCRender
{
    function Show($username,$upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        CCUpload::EnsureFiles($record,true);
        $url = $record['files'][0]['download_url'];

        /*
        $html =<<< END
<html>
<body>
<img src="$url" />
</body>
</html>
END;
        print($html);
        exit; */
    }

} // end of CCRender class

?>
