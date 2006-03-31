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


CCEvents::AddHandler(CC_EVENT_APP_INIT, array( 'CCUpdate', 'UpdateSite') );

class CCUpdate
{
    function UpdateSite()
    {
        // 0.4.0 requires a reinstall
    }

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