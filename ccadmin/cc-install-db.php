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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

if( !defined('CC_MAIL_THROTTLED') )
    define('CC_MAIL_THROTTLED', 8); // see ccextras/cc-mail.php

function d(&$obj)
{
    print '<pre>';
    print_r($obj);
    print '</pre>';
    exit;
}

function cc_install_tables(&$vars,&$msg,$local_base_dir)
{
    $new_tables_text = file_get_contents( dirname(__FILE__) . '/cchost_tables.sql');

    preg_match_all( '/CREATE TABLE ([^\s]+) \((.*\))\s+\) ENGINE[^;]+;/msU', $new_tables_text, $m );

    /* DROP PREVIOUS TABLES */

    $tables = CCDatabase::ShowTables();

    if( !empty($tables) )
    {
        foreach( $m[1] as $drop_table )
        {
            if( in_array($drop_table,$tables) )
            {
                mysql_query( "DROP TABLE $drop_table" );
                $msg = mysql_error();
                if( $msg )
                   return(false);
            }
        }
    }


    /* INSTALL TABLES */
    
    foreach( $m[0] as $s )
    {
       mysql_query($s);
       $msg = mysql_error();
       if( $msg )
           return(false);
    }


    $configs =& CCConfigs::GetTable();

    require_once('cc-install-settings.php');

    foreach( $install_settings as $S )
    {
        $configs->SaveConfig($S['config_type'],$S['config_data'],$S['config_scope']);
    }

    // ----------------- default forums and forum groups ----------------------------

        $sql = array();
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (1, 'The Site', 1)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (2, 'The Content', 2)";
        $sql[] = "INSERT INTO `cc_tbl_forum_groups` VALUES (3, 'Off Beats', 10)";
        
        $ad = CC_ADMIN_ONLY;
        $dc = CC_DONT_CARE_LOGGED_IN;
        $ru = CC_MUST_BE_LOGGED_IN;

        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (1, $ad, $dc, 1, 'Announcements', 'Messages from the admins', 1)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (2, $ru, $dc, 2, 'Help', 'get aid', 2)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (3, $ru, $dc, 3, 'The Big OT', 'off topic stuff', 3)";
        $sql[] = "INSERT INTO `cc_tbl_forums` VALUES (4, $ru, $dc, 4, 'Bugs', 'Report bugs here', 1)";

        CCDatabase::Query($sql);

    return(true);
}

?>
