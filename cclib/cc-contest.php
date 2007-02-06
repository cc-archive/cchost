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
* Implements the user interface and database management for contests
*
* @package cchost
* @subpackage contest
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

//-------------------------------------------------------------------

/**
* Contest High Volume API and event callbacks
*
*/
class CCContestHV
{
    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        if( empty($record['upload_contest']) )
            return;

        $systags = CCUploads::SplitTags($record);

        $isentry  = in_array( CCUD_CONTEST_ENTRY, $systags);
        $issource = !$isentry && (in_array( CCUD_CONTEST_MAIN_SOURCE, $systags ) ||
                                 in_array( CCUD_CONTEST_SAMPLE_SOURCE, $systags) );

        if( $issource  )
            $relative = $this->_get_upload_dir($record);
        elseif( $isentry )
            $relative = $this->_get_user_upload_dir($record);
        else
            return;
        
        $record['relative_dir']  = $relative;
        for( $i = 0; $i < count($record['files']); $i++ )
        {
            $record['files'][$i]['download_url']  = ccd( $relative, $record['files'][$i]['file_name'] );
            $record['files'][$i]['local_path']    = cca( $relative, $record['files'][$i]['file_name']);
        }

        $record['file_page_url'] = $this->_get_file_page_url($record);
    }


    /**
    * Internal helper for getting the upload page's URL
    *
    * @param array $record Upload database record (may be incomplete)
    */
    function _get_file_page_url(&$record)
    {
        return( ccl('files',$record['user_name'],$record['upload_id']) );
    }

    /**
    * Internal helper method
    *
    * Returns the upload directory associated with the username (or one
    * found in the record
    *
    * @param array $record Database record with contest name in it
    * @param string $username For this user (if blank, $record is assumed to have a username in it)
    * @returns string $dir User's upload directory for this contest
    */
    function _get_user_upload_dir( $record, $username='' )
    {
        $basedir = CCContestHV::_get_upload_dir($record);
        if( empty($username) )
            $username = $record['user_name'];

        return( $basedir . '/' . $username );
    }

    /**
    * Internal helper method
    *
    * Returns the base upload directory associated with a contest
    *
    * @param mixter $name_or_row Either short contest name or database record with contest name in it
    */
    function _get_upload_dir($name_or_row)
    {
        global $CC_GLOBALS;

        $name = is_string($name_or_row) ? $name_or_row : CCContestHV::GetContestNameFromRecord($name_or_row);
        $base_dir = empty($CC_GLOBALS['contest-upload-root']) ? 'contests' : 
                            $CC_GLOBALS['contest-upload-root'];

        return( $base_dir . '/' . $name );
    }

    function GetContestNameFromRecord($row) 
    {
        global $CC_GLOBALS;

        if( !empty($row['contest_short_name']) )
            return $row['contest_short_name'];

        if( empty($CC_GLOBALS['contests']) )
        {
            // this will happen one time when upgrading to 4

            require_once('cclib/cc-contest-admin.inc');
            $CC_GLOBALS['contests'] = CCContestAdmin::UpgradeGlobals();
        }

        $name = '';

        if( empty($row['contest_short_name']) )
        {
            if( !empty($row['upload_contest']) )
            {
                $name = $CC_GLOBALS['contests'][ $row['upload_contest'] ];
            }
        }
        else
        {
            $name = $name['contest_short_name'];
        }

        return $name;
    }

    /**
    * Callback for Navigation tab display
    *
    * @see CCNavigator::View()
    * @param array $page Array of tabs to be manipulated before display
    */
    function OnTabDisplay( &$page )
    {
        require_once('cclib/cc-contest-table.inc');
        $contests =& CCContests::GetTable();
        $short_name = $page['handler']['args']['contest'];
        $contest = $contests->GetRecordFromShortName($short_name);

        if( !empty($page['winners']) )
        {
            if( $page['winners']['function'] != 'url' )
            {
                $uploads =& CCUploads::GetTable();
                $uploads->SetTagFilter('winner,' . $short_name,'all');
                $num_winners = $uploads->CountRows();
                $uploads->SetTagFilter('');

                if( !$num_winners )
                {
                    unset($page['winners']);
                }
            }
        }

        if( !$contest['contest_taking_submissions'] )
        {
            unset($page['submit']);
        }

        if( !CCUser::IsAdmin() && !$contest['contest_can_browse_entries'] )
        {
            unset($page['entries']);
        }
    }

}
?>
