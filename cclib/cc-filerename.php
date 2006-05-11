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
* Fancy macro macro-based file renaming module
*
* @package cchost
* @subpackage io
*/


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,       array( 'CCFileRename', 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,        array( 'CCFileRename', 'OnMapUrls') );

/**
*/
$CC_RENAMER = new CCFileRename();

/**
* File renaming policy API
*
*/
class CCFileRename
{
    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu($items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'name-masks' => array( 'menu_text'  => 'Upload Renaming',
                             'menu_group' => 'configure',
                             'help'   => 'Configure how uploads are automatically renamed',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 30,
                             'action' =>  ccl('admin','renaming'),
                             ),
            );

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/renaming',  array( 'CCFileRename', 'AdminRenaming'), CC_ADMIN_ONLY );
    }

    /**
    * Handler for admin/renaming - put up form
    *
    * @see CCAdminRenameForm::CCAdminRenameForm()
    */
    function AdminRenaming()
    {
        CCPage::SetTitle("Edit Upload Renaming Rules");

        require_once('cclib/cc-filerename-admin.inc');
        $form = new CCAdminRenameForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Method that does the upload renaming according to rules set by user
    *
    * Every module in the system has the opportunity to participate in the renaming
    * rules by responding to CC_EVENT_GET_MACROS event (triggered by this method).
    * If the handler thinks it 'owns' the upload it should return the 'mask' to 
    * use. All respondents are responsible for retuning the macro in the mask as
    * well as the value associated with the upload record.
    *
    * This method is called by checking for the global '$CC_RENAMER' and then
    * calling $CC_RENAMER->Rename($record,$newname).
    *
    * If everything works out OK, this method will populate the $newname arg
    *
    * <code>
    *        
    * // get $file record from CCFiles table 
    * // $relative_dir is determined by owner module (media blog, contest, etc.)
    *
    * if( isset($CC_RENAMER) )
    * {
    *    if( $CC_RENAMER->Rename($record,$newname )
    *    {
    *        $oldname = $file['file_name'];
    *        rename( cca($relative_dir,$oldname), cca($relative_dir,$newname) );
    *        $file['file_name'] = $newname;
    *        $files->Update($file);
    *    }
    * }
    *
    * </code>
    *
    * @see CCUploadAPI::PostProcessNewUpload()
    * @param array $record Database record of upload
    * @returns boolean $renamed true if file was replaced
    */
    function Rename(&$record,&$file,&$newname)
    {
        $configs             =& CCConfigs::GetTable();
        $template_tags       = $configs->GetConfig('ttag');
        $settings            = $configs->GetConfig('name-masks');

        $patterns['%title%'] = $record['upload_name'];
        $patterns['%site%']  = $template_tags['site-title'];
        $mask                = '';
        $args                = array( &$record, &$file, &$patterns, &$mask );

        CCEvents::Invoke( CC_EVENT_GET_MACROS, $args );
        
        if( !empty($mask) )
        {
            $newname = CCMacro::TranslateMask($patterns,$mask,$settings['upload-replace-sp']);
            if( !empty($newname) )
            {
                return( true );
            }
        }
        
        return( false );
    }


}




?>