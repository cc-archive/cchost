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

CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCPublicizeHV',  'OnUserRow') );
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCPublicizeHV',  'OnBuildUploadMenu') );
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPublicizeHV',  'OnUploadMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPublicize',  'OnMapUrls'),   'ccextras/cc-publicize.inc');
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPublicize' , 'OnGetConfigFields'),   'ccextras/cc-publicize.inc' );

/**
*/
class CCPublicizeHV
{
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
        $rurl = ccr('ccimages','shareicons') . '/';
        $menu['share_link'] = 
                     array(  'menu_text'  => '+', // _('Share'),
                             'weight'     => 10,
                             'group_name' => 'share',
                             'tip'        => _('Bookmark, share, embed...'),
                             'access'     => CC_DONT_CARE_LOGGED_IN,
                        );
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
        $url = ccl('share', $record['upload_id'] );
        $jscript = "window.open( '$url', 'cchostsharewin', 'status=1,toolbar=0,location=0,menubar=0,directories=0,resizable=1,scrollbars=1,height=480,width=550');";

        $menu['share_link']['id']      = 'sharecommand';
        $menu['share_link']['class']   = "cc_share_button";
        /*
        $menu['share_link']['action']  = "javascript://Share!";
        $menu['share_link']['onclick'] = $jscript;
        */
        $menu['share_link']['action']  = $url;
    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$row)
    {
        if( empty($row['artist_page']) )
            return;

        $itsme = CCUser::CurrentUser() == $row['user_id'];

        if( $this->_pub_wizard_allowd($itsme) )
        {
            $url = ccl('publicize',$row['user_name'] );
            $text = $itsme ? _('Publicize yourself')
                           : sprintf( _('Publicize %s'), $row['user_real_name'] );
                
            $row['user_fields'][] = array( 'label' => _('Publicize'), 
                                       'value' => "<a href=\"$url\">$text</a>" );
        }
    }

    function _pub_wizard_allowd($itsme)
    {
        global $CC_GLOBALS;

        return !empty($CC_GLOBALS['pubwiz']) &&
               (
                    $CC_GLOBALS['pubwiz'] == CC_DONT_CARE_LOGGED_IN ||
                    (
                        ($CC_GLOBALS['pubwiz'] == CC_MUST_BE_LOGGED_IN) && $itsme
                    )
               );
    }

} // end of class CCQueryFormats


?>
