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

require_once('cclib/cc-admin.php');

class CCPathAdminForm extends CCEditConfigForm
{
    function CCPathAdminForm()
    {
        $this->CCEditConfigForm('config');

        $wflag = '<span style="color:red">**</span>';

        $fields['files-root'] =
               array( 'label'       => 'viewfile ' . _('Path'),
                       'form_tip'   => _('ccHost will look here for files used with the "viewfile" command before it looks in ccfiles'),
                       'value'      => 'ccfiles/',
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE  );

        $fields['template-root'] =
               array( 'label'       => _('Skins Path'),
                       'form_tip'   => _('Directory for template engine to find skins'),
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE  );

        $fields['logfile-dir'] =
               array( 'label'       => $wflag . _('Logfile Directory'),
                       'form_tip'   => _('Log files and error files will be written here.'),
                       'writable'   => true,
                       'slash'      => true,
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE );

        $fields['php-tal-cache-dir'] =
               array( 'label'       => $wflag . _('Cache Directory'),
                       'form_tip'   => _('Used by the system to write cached page templates and other temporary files.'),
                       'value'      => 'cclib/phptal/phptal_cache',
                       'writable'   => true,
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

        $fields['php-tal-dir'] =
               array( 'label'       => _('PHPTal Libs Directory'),
                       'form_tip'   => _('It is a bad idea to change this.'),
                       'value'      => 'cclib/phptal/libs',
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

        $fields['extra-lib'] =
               array( 'label'       => _('Plugins Path'),
                       'form_tip'   => _('ccHost will look here for extra PHP modules after it looks in ccextras.'),
                       'value'      => 'extra-lib',
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE );

        $fields['avatar-dir'] =
               array(  'label'      => $wflag . _('Avatar Directory'),
                       'form_tip'   => _("If blank, avatars are assumed to be in the user's upload directory."),
                       'writable'   => true,
                       'value'      => 'localdir',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );

        $fields['error-txt'] =
               array( 'label'       => _('System Error Message'),
                       'form_tip'   => _('This file is displayed when the system encounters an error.'),
                       'value'      => 'error-msg.txt',
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

        $fields['disabled-msg'] =
               array( 'label'       => _('Site Disabled Message'),
                       'form_tip'   => _('This file is displayed when the admins have temporarily disabled the site.'),
                       'value'      => 'disabled-msg.txt',
                       'formatter'  => 'sysdir',
                       'flags'      => CCFF_POPULATE  );

        $fields['user-upload-root'] =
               array( 'label'       => $wflag . _('Media Upload Directory'),
                       'form_tip'   => _('Files will be uploaded to and downloaded from here.'),
                       'value'      => 'content',
                       'writable'   => true,
                       'formatter'  => 'sysdir',
                       'slash'      => false,
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

        $fields['contest-upload-root'] =
               array( 'label'       => $wflag . _('Contest Upload Directory'),
                       'form_tip'   => _('Contest sources and entries will be uploaded and downloaded here.'),
                       'value'      => 'contests',
                       'writable'   => true,
                       'formatter'  => 'sysdir',
                       'slash'      => false,
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

        CCEvents::Invoke( CC_EVENT_SYSPATHS, array( &$fields ) );

        $this->AddFormFields($fields);

        $help = _('NOTE:') . ' '. _('Changing the values here will not move any files around. You are responsible for that and the system will not work until the directories match these values.');
        $this->SetHelpText($help);
        $help = _('NOTE:') . ' ' . _(sprintf('Fields with %s need to be set up so that PHP scripts have full write access.', $wflag));
        $this->SetHelpText($help);
        $this->SetModule( ccs(__FILE__) );
    }

    function generator_sysdir($fname,$value='',$class='')
    {
        if( !empty($value) && strlen($value) > 80 )
            return $this->generator_textarea($fname,$value,$class);
        return $this->generator_textedit($fname,$value,$class);
    }

    function validator_sysdir($fieldname)
    {
        if( !$this->validator_textedit($fieldname) )
            return false;

        $dir = $this->GetFormValue($fieldname);
        if( $dir )
        {
            $mustexist = $this->GetFormFieldItem($fieldname,'mustexist');

            if( $mustexist && !file_exists($dir) )
            {
                $this->SetFieldError($fieldname, _('This directory or file does not exist.'));
                return false;
            }

            $writable = $this->GetFormFieldItem($fieldname,'writable');

            if( file_exists($dir) && !empty($writable) )
            {
                if( !is_writable($dir) )
                {
                    $this->SetFieldError($fieldname, _('This file or directory is not writable.'));
                    return false;
                }
            }
    
            $slash_required = $this->GetFormFieldItem($fieldname,'slash');
            $dir = CCUtil::CheckTrailingSlash($dir,$slash_required);
        }

        $this->SetFormValue($fieldname,$dir);

        return true;
    }
}

/**
*
*
*/
class CCPathAdmin
{
    function Admin()
    {
        CCPage::SetTitle(_('Paths and Files'));
        $form = new CCPathAdminForm();
        CCPage::AddForm($form->GenerateForm());
    }

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
            global $CC_GLOBALS;

            $items += array(
                'pathman'   => array( 
                                 'menu_text'    => _('Paths'),
                                 'menu_group'   => 'configure',
                                 'help'         => _('Configure where ccHost looks for stuff'),
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 3,
                                 'action' =>  ccl('admin', 'paths')
                                 ),
                );
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','paths'), array('CCPathAdmin','Admin'), CC_ADMIN_ONLY
            , ccs(__FILE__), '', 
            _('Display path settings form.'), CC_AG_CONFIG );
    }


}



?>
