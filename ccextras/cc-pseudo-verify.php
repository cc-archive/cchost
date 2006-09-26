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

CCEvents::AddHandler(CC_EVENT_APP_INIT,    array( 'CCPseudoVerify', 'Install' ));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCPseudoVerify', 'OnMapUrls' ));
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS, array( 'CCPseudoVerify', 'OnGetSysTags'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCPseudoVerify', 'OnAdminMenu'));

$old_validator = null;

class CCPseudoVerify
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','pverify'), array( 'CCPseudoVerify', 'Admin' ),
                          CC_ADMIN_ONLY );
    }

    function Admin()
    {
        require_once('ccextras/cc-pseudo-verify.inc');
        $api = new CCPseudoVerifyAPI();
        $api->Admin();
    }

   function Install()
   {  
       global $old_validator, $CC_UPLOAD_VALIDATOR;

       $old_validator = $CC_UPLOAD_VALIDATOR;
       $CC_UPLOAD_VALIDATOR = $this;
   }

   function GetValidFileTypes(&$types)
   {
        require_once('ccextras/cc-pseudo-verify.inc');
        $api = new CCPseudoVerifyAPI();
        return $api->GetValidFileTypes($types);
   }

    function FileValidate(&$formatinfo)
    {
        require_once('ccextras/cc-pseudo-verify.inc');
        $api = new CCPseudoVerifyAPI();
        return $api->FileValidate(&$formatinfo);
    }

    /**
    * Event handler for {@link CC_EVENT_GET_SYSTAGS}
    *
    * @param array $record Record we're getting tags for 
    * @param array $file Specific file record we're getting tags for
    * @param array $tags Place to put the appropriate tags.
    */
    function OnGetSysTags(&$record,&$file,&$tags)
    {
        if( empty($file['file_format_info']['tags']) )
            return;

        $newtags = CCTag::TagSplit($file['file_format_info']['tags']);
    
        $tags = array_merge($tags,$newtags);
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items, $scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'pverify'   => array( 'menu_text'  => _('Pseudo Verify'),
                         'menu_group' => 'configure',
                         'access' => CC_ADMIN_ONLY,
                          'help'  => _('Configure usage of exotic and other dangerous file types'),
                         'weight' => 1000,
                         'action' =>  ccl('admin','pverify')
                         ),
            );
    }

}