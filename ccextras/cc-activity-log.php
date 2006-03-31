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

CCEvents::AddHandler(CC_EVENT_APP_INIT,   array( 'CCActivityLogAPI' , 'OnAppInit') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,   array( 'CCActivityLogAPI' , 'OnMapUrls') );

/*
define('CC_EVENT_APP_INIT',            'init');
define('CC_EVENT_MAIN_MENU',           'mainmenu');
define('CC_EVENT_PATCH_MENU',          'patchmenu');

define('CC_EVENT_UPLOAD_MENU',         'uploadmenu');
define('CC_EVENT_BUILD_UPLOAD_MENU',   'builduploadmenu');
define('CC_EVENT_ADMIN_MENU',          'buildadminmenu');
define('CC_EVENT_UPLOAD_ROW',          'uploadrow' );
define('CC_EVENT_UPLOAD_LISTING',      'uploadlisting' );
define('CC_EVENT_LISTING_RECORDS',     'listingrecs' );
define('CC_EVENT_GET_SYSTAGS',         'getsystags' );
define('CC_EVENT_USER_ROW',            'userrow' );
define('CC_EVENT_MAP_URLS',            'mapurls');
define('CC_EVENT_UPLOAD_ALLOWED',      'throttle');
define('CC_EVENT_CONTEST_ROW',         'contestrow' );
define('CC_EVENT_GET_MACROS',          'getmacros' );
define('CC_EVENT_SUBMIT_FORM_TYPES',   'submitformtypes' );

define('CC_EVENT_GET_UPLOAD_FIELDS',   'getupflds' );
define('CC_EVENT_GET_USER_FIELDS',     'getuserflds' );
define('CC_EVENT_GET_CONFIG_FIELDS',   'getcfgflds' );

define('CC_EVENT_DELETE_UPLOAD',       'delete' );
define('CC_EVENT_DELETE_FILE',         'deletefile' );

define('CC_EVENT_USER_DELETED',         'userdel' );
define('CC_EVENT_USER_REGISTERED',      'userreg' );
define('CC_EVENT_USER_PROFILE_CHANGED', 'userprof' );
define('CC_EVENT_LOGIN_FORM',           'loginform' );
define('CC_EVENT_LOGOUT',               'logout' );
*/

class CCActivityLog extends CCTable
{
    function CCActivityLog()
    {
        $this->CCTable('cc_tbl_activity_log','activity_log_id');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCActivityLog();
        return($_table);
    }
}

class CCActivityLogAPI
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('activity'),  array('CCActivityLogAPI','ViewLog'), CC_ADMIN_ONLY );
    }

    function ViewLog($arg='')
    {
        CCPage::SetTitle('View Activity Log');
        $logs =& CCActivityLog::GetTable();

        if( $arg == 'clear' )
        {
            $logs->DeleteWhere('1');
            CCPage::Prompt("Log Cleared");
            return;
        }

        $where = array();
        if( !empty($_GET['user']) )
        {
            $name = CCUtil::StripText($_GET['user']);
            if( !empty($name) )
                $where['activity_log_user_name'] = $name;
        }
        if( !empty($_GET['ip']) )
        {
            $ip = CCUtil::StripText($_GET['ip']);
            if( !empty($ip) )
                $where['activity_log_ip'] = $ip;
        }

        CCPage::AddPagingLinks($logs,'',50);
        $logs->SetOrder('activity_log_date','DESC');
        $rows = $logs->QueryRows($where);
        CCPage::PageArg('activity_log',$rows,'show_activity_log');
    }

    function OnAppInit()
    {
        if( defined('IN_MIXTER_PORT') )
            return;

        $configs =& CCConfigs::GetTable();
        $logging = $configs->GetConfig('logging',CC_GLOBAL_SCOPE);
        if( empty($logging) )
            return;
        global $CC_GLOBALS;
        $CC_GLOBALS['logging'] = $logging;
        CCEvents::AddHook( array( &$this, 'Hook' ) );
    }

    function Hook($args)
    {
        $event_name = $args[0];
        $args = empty($args[1]) ? array() : $args[1];

        //if( $event_name != 'uploadrow' )
        //    CCDebug::Log($event_name);

        global $CC_GLOBALS;
        if( !in_array( $event_name, $CC_GLOBALS['logging']  ) )
            return;
        $logs =& CCActivityLog::GetTable();
        $uargs['activity_log_event'] = $event_name;
        $uargs['activity_log_ip'] = $_SERVER['REMOTE_ADDR'];
        $uargs['activity_log_date'] = date( 'Y-m-d H:i:s' );
        $uargs['activity_log_user_name'] = CCUser::CurrentUserName();

        $ufs[CC_UF_NEW_UPLOAD] = 'NEW UPLOAD';
        $ufs[CC_UF_FILE_REPLACE] = 'FILE REPLACE';
        $ufs[CC_UF_FILE_ADD] = 'FILE ADD';
        $ufs[CC_UF_PROPERTIES_EDIT] = 'PROPERTIES EDIT';

        switch( $event_name )
        {
            case CC_EVENT_UPLOAD_DONE:
                $uargs['activity_log_param_2'] = $ufs[$args[1]];
                $upload_id = $args[0];
                break;

            case CC_EVENT_FILE_DONE:
                $uargs['activity_log_param_1'] = $args[0]['file_name'];
                break;

            case CC_EVENT_DELETE_UPLOAD:
                $uargs['activity_log_param_1'] = $args[0]['upload_name'];
                break;

            case CC_EVENT_DELETE_FILE:
                $files =& CCFiles::GetTable();
                $upload_id = $files->QueryItem('file_upload','file_id = ' . $args[0]);
                break;

            case CC_EVENT_USER_REGISTERED:
                $uargs['activity_log_param_1'] = $args[0]['user_name'];
                $uargs['activity_log_param_2'] = $args[0]['user_email'];
                break;
        }

        if( !empty($upload_id) )
        {
            $uploads =& CCUploads::GetTable();
            $uargs['activity_log_param_1'] = $uploads->QueryItem('upload_name','upload_id = ' . $upload_id);
        }

        $logs->Insert($uargs);

    }

}

?>