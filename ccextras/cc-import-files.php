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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,     array( 'CCImportFiles' , 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCImportFiles',  'OnMapUrls'));

/**
*/
class CCImportForm extends CCNewUploadForm
{
    function CCImportForm($uid)
    {
        $this->CCNewUploadForm($uid);

        $this->SetFormFieldItem( 'upload_file_name', 'formatter', 'importer');
        $this->SetFormFieldItem( 'upload_file_name', 'label', _('Path to file on server') );
    }

    function generator_importer($varname,$value='',$class='')
    {
        return $this->generator_textedit($varname,$value,$class);
    }

    function validator_importer($fieldname)
    {
        if( $this->validator_textedit($fieldname) )
        {
            $name = $this->GetFormValue($fieldname);
            if( !file_exists($name) || !is_file($name) )
            {
                $this->SetFieldError($fieldname,_('Can not file a file by that name'));
            }
            else
            {
                return true;
            }
        }

        return false ;
    }

}
/**
*
*
*/
class CCImportFiles
{
    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array(
                'importer'   => array( 
                                 'menu_text'  => 'Import Files',
                                 'menu_group' => 'configure',
                                 'help' => 'Import files from your server into ccHost',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 40,
                                 'action' =>  ccl('admin','import')
                                 ),
                );

            global $CC_GLOBALS;
            if( empty($CC_GLOBALS['importer-installed']) )
            {
                CCEvents::GetUrlMap(true);
                $flag['importer-installed'] = true;
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('config',$flag);
            }
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','import'),  array( 'CCImportFiles', 'Import'), CC_ADMIN_ONLY );
    }

    /**
    * Generic handler for submitting original works
    *
    * Displays and process new submission form and assign tags to upload
    *
    * @param string $page_title Caption for page
    * @param string $username Login name of user doing the upload
    */
    function Import($username='', $extra='')
    {
        $page_title = _('Import files');
        $tags = CCUD_ORIGINAL;
        $form_help = _('Use this form to import files already on your server into the ccHost system. WARNING: This form will <b>MOVE</b> your file from its original location.');

        CCPage::SetTitle($page_title);
        if( empty($username) )
        {
            $uid = CCUser::CurrentUser();
            $username = CCUser::CurrentUserName();
        }
        else
        {
            CCUser::CheckCredentials($username);
            $uid = CCUser::IDFromName($username);
        }
        $form = new CCImportForm($uid);

        $media_host = new CCMediaHost();
        if( !empty($_POST['import']) )
        {
            if( $form->ValidateFields() )
            {
                $form->GetFormValues($values);
                $current_path = $values['upload_file_name'];
                $new_name     = 'import_' . basename($current_path);
                $user_tags    = $values['upload_tags'];

                // All fields here that start with 'upload_' are 
                // considered to be fields in the CCUploads table
                // so....
                // Destroy the $_FILES object so it doesn't get
                // confused with that 

                unset($values['upload_file_name']);

                $ret = CCUploadAPI::PostProcessNewUpload(   $values, 
                                                            $current_path,
                                                            $new_name,
                                                            array( CCUD_ORIGINAL, 'media'),
                                                            $user_tags,
                                                            $media_host->_get_upload_dir($username),
                                                            null );

                if( is_string($ret) )
                {
                    $form->SetFieldError('upload_file_name',$ret);
                }
                else
                {
                    $upload_id = $ret;
                    $uploads =& CCUploads::GetTable();
                    $record = $uploads->GetRecordFromID($upload_id);
                    $url = $media_host->_get_file_page_url($record);
                    CCPage::Prompt(sprintf(_("Import succeeded. Click <a href=\"%s\">here</a> to see results."),$url));
                    return;
                }
            }
        }
        
        if( !empty($form_help) )
            $form->SetFormHelp($form_help);

        CCPage::AddForm( $form->GenerateForm() );
    }

}



?>