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

      $sql = array(
"INSERT INTO `cc_tbl_forums` VALUES(1, 8, 4, 1, 'str_forum_announcements', 'str_forum_messages_admins', 1);",
"INSERT INTO `cc_tbl_forums` VALUES(2, 1, 4, 2, 'Help', 'get aid', 2);",
"INSERT INTO `cc_tbl_forums` VALUES(3, 1, 4, 3, 'The Big OT', 'off topic stuff', 3);",
"INSERT INTO `cc_tbl_forums` VALUES(4, 1, 4, 4, 'Bugs', 'Report bugs here', 1);",
"INSERT INTO `cc_tbl_forum_groups` VALUES(1, 'str_forum_the_site', 1);",
"INSERT INTO `cc_tbl_forum_groups` VALUES(2, 'str_forum_the_content', 2);",
"INSERT INTO `cc_tbl_forum_groups` VALUES(3, 'str_forum_off_beats', 10);",
"INSERT INTO `cc_tbl_topics` VALUES(1, 0, 1, 0, 'news', '2008-07-14 17:12:00', '0000-00-00 00:00:00', 0, 'ccHost is up and running!', 'Congratulations on getting ccHost up and running. The ''news'' feature is part of the new [url=/admin/content]Content Manager[/url].', '', NULL, 0, 0, 0, 1, 2);",
"INSERT INTO `cc_tbl_topics` VALUES(2, 0, 1, 0, 'news', '2008-07-14 17:14:00', '0000-00-00 00:00:00', 0, 'Radiohead releases video sources under CC', 'Creative Commons is [url=http://creativecommons.org/weblog/entry/8476]reporting[/url] that Radiohead has released the sources to their video \"House of Cards\" under a CC license.', '', NULL, 0, 0, 0, 3, 4);",
"INSERT INTO `cc_tbl_topics` VALUES(3, 0, 1, 0, 'home', '2009-05-07 13:00:00', '0000-00-00 00:00:00', 0, 'Welcome to ccHost 5', 'Welcome to ccHost [define=CC_HOST_VERSION][/define] and congratulations on your installation of [b][var=site-title][/var][/b]\r\n\r\nHere are some helpful documentation links:\r\n\r\n[big][url=http://wiki.creativecommons.org/Cchost/Documentation]ccHost Documentaton Wiki[/url]\r\n\r\n[url=http://wiki.creativecommons.org/Cchost/guide/Customize]Customizing your installation[/url]\r\n\r\n[url=http://wiki.creativecommons.org/Cchost/guide/Troubleshooting]Troubleshooting[/url]\r\n\r\n[url=http://wiki.creativecommons.org/Cchost#Contacting]Contact the team[/url][/big]', '', NULL, 0, 0, 0, 5, 6);",
"INSERT INTO `cc_tbl_topics` VALUES(4, 0, 1, 0, 'home', '2009-05-07 21:40:00', '0000-00-00 00:00:00', 0, 'New Skin Engine', '[right][skinimg=layouts/images/layout005.gif][/skinimg]\r\n[skinimg=layouts/images/layout023.gif][/skinimg]\r\n[skinimg=layouts/images/layout036.gif][/skinimg][/right]\r\n\r\n[indent=15]The new skin engine allows for easy customization for admins and web developers. Shipping in the box are 40 layouts, 3 string profiles (generic media sites, music sites and image sites), configurable tab layouts, form layouts, etc. [b][cmd=admin/skins]Start here...[/cmd][/b].', '', NULL, 0, 0, 0, 7, 8);",
"INSERT INTO `cc_tbl_topics` VALUES(5, 0, 1, 0, 'home', '2009-05-07 22:01:00', '0000-00-00 00:00:00', 0, 'Content Manager', 'Admins can create pages in the system without any knowledge of HTML or coding. This page and the content on it was created using it. You can see how it was by [b][cmd=admin/content]poking around here[/cmd][/b].\r\n\r\nSee the ccHost Wiki [b][url=http://wiki.creativecommons.org/Cchost/concepts/Content]documentation[/url][/b] for a [b][url=http://wiki.creativecommons.org/Cchost/admin/Content_Manager]step by step tutorial[/url][/b] on how to create content topics and a page to display them (like this page).\r\n\r\nYou don''t have to use the content manager to create pages in the system. Read about how to [url=http://wiki.creativecommons.org/Cchost/Static_HTML_Pages]add HTML/PHP files directly[/url].\r\n\r\nFor a slightly more technical discussion of how all content (including user uploads) is handled, see the general discussion on content at the wiki.', '', NULL, 0, 0, 0, 9, 10);",
"INSERT INTO `cc_tbl_topics` VALUES(8, 0, 1, 0, 'welcome', '2009-05-08 14:27:00', '0000-00-00 00:00:00', 0, 'Welcome New Member', '(for admins: use the [cmd=admin/content]Content Manager[/cmd] to edit this welcome screen)\r\n\r\nHello [b][var=user_real_name][/var][/b] and welcome to [b][var=site-title][/var][/b]!\r\n\r\nYour profile address is: [cmdurl=people/[var=user_name][/var]][/cmdurl]\r\n\r\nHere''s some stuff you might want to do:\r\n\r\n[big][cmd=people/profile]Edit your profile[/cmd][/big] to change your password, upload an avatar, etc.\r\n\r\n[big][cmd=preferences]Customize the site[/cmd][/big] decide what you see every time you log in.\r\n\r\n[big][cmd=people/notify/edit]Edit your notifications[/cmd][/big] tells us when you want to be notified in email.\r\n\r\n[big][cmd=submit]Submit files[/cmd][/big] Start uploading your content!', '', NULL, 0, 0, 0, 11, 12);",
"INSERT INTO `cc_tbl_topics` VALUES(9, 0, 1, 0, 'home', '2009-05-07 17:00:00', '0000-00-00 00:00:00', 0, 'Site News', '[query=t=news&type=news&limit=4][/query]', '', NULL, 0, 0, 0, 13, 14);",
"INSERT INTO `cc_tbl_topics` VALUES(12, 0, 1, 0, 'sidebar_blurb', '2009-05-13 17:51:00', '0000-00-00 00:00:00', 0, 'a blurb', 'A sidebar blurb is an excellent way to dispense site news. Edit blurbs using the [cmd=admin/content]Content Manager[/cmd]. Change their placement (or remove them) with [cmd=admin/extras]Sidebar Extras[/cmd]', '', NULL, 0, 0, 0, 19, 20);",
"INSERT INTO `cc_tbl_topics` VALUES(10, 0, 1, 0, 'news', '2009-05-10 00:31:00', '0000-00-00 00:00:00', 0, 'RiP: Movie Making the Rounds', 'The movie [url=http://www.ripremix.com/]RiP: A Remix Manifesto[/url] is tearing up the festivals and brings to light the dangers of treating creativity as property.', '', NULL, 0, 0, 0, 15, 16);",
);
    CCDatabase::Query($sql);

    return(true);
}

?>
