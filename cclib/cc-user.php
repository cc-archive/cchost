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
* @subpackage user
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


class CCUser
{
    function IsLoggedIn()
    {
        global $CC_GLOBALS;

        return( !empty($CC_GLOBALS['user_name']) );
    }

    function IsSuper($name='')
    {
        if( !CCUtil::IsHTTP() )
            return true;

        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['supers']) )
            return false; // err...

        if( empty($name) )
            $name = CCUser::CurrentUserName();
        $ok = !empty($name) && (preg_match( "/(^|\W|,)$name(\W|,|$)/i",$CC_GLOBALS['supers']) > 0);

        return $ok;
    }

    function IsAdmin($name='')
    {
        static $checked;

        if( isset($checked) )
        {
            return $checked;
        }
        else
        {
            if( !CCUtil::IsHTTP() || CCUser::IsSuper($name) )
            {
                $checked = true;
                return true;
            }

            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $_admins = $settings['admins'];

            if( empty($name) )
                $name = CCUser::CurrentUserName();

            $checked = !empty($name) && (preg_match( "/(^|\W|,)$name(\W|,|$)/i",$_admins) > 0);

            return $checked;
        }
    }

    function CurrentUser()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? intval($CC_GLOBALS['user_id']) : -1 );
    }


    function CurrentUserName()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? $CC_GLOBALS['user_name'] : '' );
    }

    function CurrentUserField($field)
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? $CC_GLOBALS[$field] : '' );
    }

    function GetUserName($userid)
    {
        if( $userid == CCUser::CurrentUser() )
            return( CCUser::CurrentUserName() );

        $users =& CCUsers::GetTable();
        return( $users->QueryItemFromKey('user_name',$userid) );
    }


    function CheckCredentials($usernameorid)
    {
        $id     = CCUser::CurrentUser();
        $argid  = intval($usernameorid);
        $name   = CCUser::CurrentUserName();
        $bad = !$id || (($id !== $argid) && (strcmp($name,$usernameorid) != 0)) ;
        if( $bad )
        {
           CCUtil::AccessError();
        }
    }

    function IDFromName($username)
    {
        $users =& CCUsers::GetTable();
        $where = "user_name = '" . strtolower($username) . "'";
        return( $users->QueryKey($where) );
    }

    /**
    * Digs around the cookies looking for an auto-login. If succeeds, populate CC_GLOBALS with user data
    */
    function InitCurrentUser()
    {
        global $CC_GLOBALS;

        if( !empty($_COOKIE[CC_USER_COOKIE]) )
        {
            $users =& CCUsers::GetTable();
            $val = $_COOKIE[CC_USER_COOKIE];
            if( is_string($val) )
            {
                $val = unserialize(stripslashes($val));
                $record = CCDatabase::QueryRow( 'SELECT * FROM cc_tbl_user WHERE user_name = \'' . $val[0]  . '\'' );
                $record['user_extra'] = unserialize($record['user_extra']);
                if( !empty( $record ) && ($record['user_password'] == $val[1]) )
                {
                    $CC_GLOBALS = array_merge($CC_GLOBALS,$record);
                    $users->SaveKnownIP();
                }
            }
        }
    }

    function GetPeopleDir()
    {
        global $CC_GLOBALS;
        return( empty($CC_GLOBALS['user-upload-root']) ? 'content' : 
                            $CC_GLOBALS['user-upload-root'] );
    }

    function GetUploadDir($name_or_row)
    {
        if( is_array($name_or_row) )
            $name_or_row = $name_or_row['user_name'];

        return( CCUser::GetPeopleDir() . '/' . $name_or_row );
    }

    /**
    * Event handler for {@link CC_EVENT_PATCH_MENU}
    * 
    */
    function OnPatchMenu(&$menu)
    {
        $current_user_name = $this->CurrentUserName();

        // technically this isn't supposed to happen

        if( empty($menu['artist']['action']) )
        {
            CCPage::Prompt(_('Attention: Menus have been corrupted'));
            return;  
        }

        // fwiw, this whole thing is a heck, what really
        // should happen is that admins should be able
        // to access *any* CC_GLOBAL variable in any menu
        // item.

        $keys = array_keys($menu);
        $count = count($keys);
        for( $i = 0; $i < $count; $i++ )
        {
            $M =& $menu[$keys[$i]];
            $M['action'] = str_replace('%login_name%',$current_user_name,$M['action']);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow(&$row)
    {
        $users =& CCUsers::GetTable();
        $users->GetRecordFromRow($row,false);
    }

}

class CCUsers extends CCTable
{
    function CCUsers()
    {
        global $CC_SQL_DATE;

        $this->CCTable( 'cc_tbl_user','user_id');
        $this->AddExtraColumn("DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format");
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCUsers();
        return $_table;
    }

    function & GetRecordFromName($username)
    {
        $where = "user_name = '" . strtolower($username) . "'";
        $row = $this->QueryRow($where);
        if( empty($row) )
        {
            $row = null;
            return $row;
        }
        $r =& $this->GetRecordFromRow($row);
        return $r;
    }

    function & GetRecordFromID($userid)
    {
        $row = $this->QueryKeyRow($userid);
        if( empty($row) )
        {
            // this is a pretty bad state of affairs
            // the user account was deleted and the 
            // caller doesn't know it
            $a = array();
            return $a;
        }
        $r =& $this->GetRecordFromRow($row);
        return $r;
    }

    function & GetRecordFromRow(&$row,$expand = true)
    {
        print('User get record called');
        CCDebug::StackTrace();

        global $CC_GLOBALS;
        
        if( !empty($row['user_extra']) )
            $row['user_extra'] = unserialize($row['user_extra']);
        else
            $row['user_extra'] = array();

        // set the language to default (for visibility on the form only)
        if (empty($row['user_language']))
            $row['user_language'] = 'default';
    /*

        $row['artist_page_url']  = ccl('people' ,$row['user_name']);
        $row['user_emailurl']    = ccl('people', 'contact', $row['user_name'] );

        $row['user_is_admin'] = CCUser::IsAdmin($row['user_name']);
        $row['user_avatar_url'] = cc_get_user_avatar($row);
    */

        return $row;
    }

    function SaveKnownIP()
    {
        global $CC_GLOBALS;
    
        // we don't care about anon users
        if( empty($CC_GLOBALS['user_id']) )
            return;

        $ip    = CCUtil::EncodeIP($_SERVER['REMOTE_ADDR']);
        $dbip  = substr($CC_GLOBALS['user_last_known_ip'],0,8);
     
        if( empty($dbip) || ($ip != $dbip) )
        {
            $where['user_id'] = $CC_GLOBALS['user_id'];
            $where['user_last_known_ip'] = $ip . date('YmdHis');
            $this->Update($where);
        }
    }
}

?>
