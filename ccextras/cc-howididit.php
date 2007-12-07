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
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCHowIDidItHV',  'OnBuildUploadMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCHowIDidItHV',  'OnUploadMenu')      );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCHowIDidItHV',  'OnUploadRow')      );
CCEvents::AddHandler(CC_EVENT_FILTER_MACROS,      array( 'CCHowIDidItHV',  'OnFilterMacros')      );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCHowIDidIt',  'OnMapUrls')        , 'ccextras/cc-howididit.inc' );

class CCHowIDidItHV
{
    function OnFilterMacros(&$records)
    {
        $k = array_keys($records);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];

            if( empty($R['upload_extra']['howididit']) )
                continue;

            if( empty($R['file_macros']) )
                $R['file_macros'][] = 'print_howididit_link';
            else
                array_unshift($R['file_macros'],'print_howididit_link');
        }
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow( &$record )
    {
        if( !empty($record['upload_extra']['howididit']) )
        {
            $record['howididit_link'] = array( 'action' => ccl('howididit',$record['upload_id']),
                                               'text'  => _('How I Did It'));
            if( empty($record['file_macros']) )
                $record['file_macros'][] = 'print_howididit_link';
            else
                array_unshift($record['file_macros'],'print_howididit_link');
        }

    }

    /**
    * Event handler for {@link CC_EVENT_BUILD_UPLOAD_MENU}
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu()
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['howididit'] = 
                     array(  'menu_text'  => _('Edit "How I Did It"'),
                             'weight'     => 110,
                             'group_name' => 'owner',
                             'id'         => 'editcommand',
                             'access'     => CC_MUST_BE_LOGGED_IN );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_MENU}
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu()
    */
    function OnUploadMenu(&$menu,&$record)
    {
        $isowner = CCUser::CurrentUser() == $record['user_id'];
        $isadmin = CCUser::IsAdmin();

        if( ($isadmin || $isowner) && !$record['upload_banned']) 
        {
            $menu['howididit']['action'] = ccl( 'edithowididit', $record['upload_id'] );
            
            if( $isadmin && !$isowner ) // geez, it's me!
                $menu['howididit']['group_name']  = 'admin';
        }
        else
        {
            $menu['howididit']['access'] = CC_DISABLED_MENU_ITEM;
        }
    }

}
?>
