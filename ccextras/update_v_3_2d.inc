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

/**
*/
class v_3_2d extends CCUpdate
{
    function Update()
    {
        $this->_update_access();
        $this->_update_null_fields();
    }

    function _update_access()
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['supers']) )
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $newcfg['supers'] = $settings['admins'];
            $configs->SaveConfig('config',$newcfg,'',true);
        }

        CCPage::Prompt(_("Super admins created from admins"));
    }

    function _update_null_fields()
    {
       $user = array(
      'user_num_remixes' => 'INT(7) unsigned NOT NULL default 0',
      'user_num_remixed' => 'INT(7) unsigned NOT NULL default 0',
      'user_num_uploads' => 'INT(7) unsigned NOT NULL default 0',
      
      'user_score' => 'INT(11) unsigned NOT NULL default 0',
      'user_num_scores' => 'INT(11) unsigned NOT NULL default 0',
      'user_rank' => 'INT(11) unsigned NOT NULL default 0',

      'user_num_reviews' => 'INT(7) unsigned NOT NULL default 0',
      'user_num_reviewed' => 'INT(7) unsigned NOT NULL default 0',

      'user_num_posts' => 'INT(11) unsigned NOT NULL default 0',
          );

       $this->_update_table('cc_tbl_user',$user);

      $uploads = array(
      'upload_num_remixes' => 'INT(7) unsigned NOT NULL default 0',
      'upload_num_pool_remixes' => 'INT(7) unsigned NOT NULL default 0',
      'upload_num_sources' => 'INT(7) unsigned NOT NULL default 0',
      'upload_num_pool_sources' => 'INT(7) unsigned NOT NULL default 0',

      'upload_score' => 'INT(11) unsigned NOT NULL default 0',
      'upload_num_scores' => 'INT(11) unsigned NOT NULL default 0',
      'upload_rank' => 'INT(11) unsigned NOT NULL default 0',
        );

      $this->_update_table('cc_tbl_uploads',$uploads);
      CCPage::Prompt(_('Null table columns updated'));
    }

    function _update_table($table,$defs)
    {
        $sql = "ALTER TABLE $table ";
        $comma = '';
        foreach( $defs as $C => $def )
        {
            $sql .= $comma . "CHANGE $C $C $def ";
            $comma = ', ';
        }
        //CCDebug::PrintVar($sql,false);
        CCDatabase::Query($sql);
    }
}

?>
