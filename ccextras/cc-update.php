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


CCEvents::AddHandler(CC_EVENT_APP_INIT, array( 'CCUpdate', 'UpdateSite') );

/**
*/
class CCUpdate
{
    function UpdateSite()
    {
        global $CC_GLOBALS;

        if( !CCUser::IsAdmin() || empty($_REQUEST['update']) )
            return;

        require_once('cclib/cc-page.php');
        $updates = array();
        if ($cc_dh = opendir('ccextras')) 
        {
           while (($cc_file = readdir($cc_dh)) !== false) 
           {
               if( preg_match('/update_([^\.]+)\.inc$/',$cc_file,$m) )
                   $updates[] = $m[1];
           }
           closedir($cc_dh);
        }

        $prompts = array();
        foreach( $updates as $update )
        {
            if( empty($CC_GLOBALS[$update])  )
                $this->_do_update($update);
            else
                $prompts[] = $update;
        }

        $prompts = join(', ',$prompts);
        CCPage::Prompt(_('Updates already installed:') . ' ' . $prompts);

        CCMenu::KillCache();
        CCTemplate::ClearCache();
    }


    function _do_update($name)
    {
        require_once('update_' . $name . '.inc');
        $updater = new $name;
        $updater->Update();
        $this->_write_config_flag($name);
    }

    function _write_config_flag($flag)
    {
        global $CC_GLOBALS;
        $CC_GLOBALS[$flag] = 1;
        $configs =& CCConfigs::GetTable();
        $args[$flag] = 1;
        $configs->SaveConfig('config', $args, CC_GLOBAL_SCOPE, true );
    }

    function _table_exists($tablename)
    {
        $tables = CCDatabase::ShowTables();
        return in_array( $tablename, $tables );
    }

    /**
    * Check for existance of a database column and create one if it doesn't exist.
    *
    * For a tutorial on using this method see {@tutorial cchost.pkg#new Create a new database column}
    *
    */
    function _check_for_field($tablename,$fieldname, $desc)
    {
        if( is_object($tablename) )
            $tablename = $tablename->_table_name;

        $fields = CCDatabase::QueryRows('DESCRIBE ' . $tablename);
        $found = false;
        foreach( $fields as $field )
        {
            if( $field['Field'] == $fieldname )
            {
                $found = true;
                break;
            }
        }

        if( !$found )
        {
            $sql = "ALTER TABLE `$tablename` ADD `$fieldname` $desc";
            CCDatabase::Query($sql);
        }

        return($found);
    }

}
?>
