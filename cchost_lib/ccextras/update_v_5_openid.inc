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
* $Id: update_v_5_oh1_bug.inc 11065 2008-10-29 09:19:17Z fourstones $
*
*/

/**
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
class v_5_openid extends CCUpdate
{
    function Update()
    {
        $sql =<<<EOF
            CREATE TABLE cc_tbl_openids (
              openid varchar(255) default NULL,
              openid_user int(11) NOT NULL default '0',
              openid_confirmed int(1) NOT NULL default '0',
              openid_session int(11) NOT NULL default '0',
              openid_extra mediumtext,
              PRIMARY KEY openid (openid)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
EOF;
        CCDatabase::Query($sql);
        CCPage::Prompt( _('OpenID extensions installed') );

    }
}

?>