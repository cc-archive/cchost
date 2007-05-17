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

require_once('cclib/cc-form.php');

class CCQuotaAdminForm extends CCGridForm
{
    function CCQuotaAdminForm()
    {
        $this->CCGridForm();

        global $CC_GLOBALS;

        $default = empty($CC_GLOBALS['enable_quota']) ? '0' : $CC_GLOBALS['enable_quota'];
        $input = $this->generator_checkbox('enable_quota',$default);
        $html = sprintf(_('%s Enable Quota (Check this if you want to limit the total size of upload files.)'),$input);

        $this->SetFormHelp($html);

        $default = empty($CC_GLOBALS['default_quota']) ? '0' : $CC_GLOBALS['default_quota'];
        $input = $this->generator_textedit('default_quota',$default,'cc_form_edit_short');
        $html = sprintf(_('Configure default user quota is %sMB (0 is no limit).'),$input);

        $this->SetFormHelp($html);

        $heads = array(
                _('Delete'),
                _('Name'),
                _('Size (MB)') );
	    
        $this->SetColumnHeader($heads);

        $users = new CCUsers();
        $where = "user_quota > 0";
        $records =& $users->GetRecords($where);
        $count = 0;
        foreach( $records as $user  )
        {
            $S = 'S[' . ++$count . ']';
            $a = array(
                  array(
                    'element_name'  => $S . '[delete]',
                    'value'      => false,
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_POPULATE ),
                  array(
                    'element_name'  => $S . '[name]',
                    'value'      => $user['user_name'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
                  array(
                    'element_name'  => $S . '[quota]',
                    'value'      => $user['user_quota'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
                );

            $this->AddGridRow( $count, $a );
        }

        $S = 'new[%i%]';
        $a = array(
              array(
                'element_name'  => $S . '[delete]',
                'value'      => false,
                'formatter'  => 'checkbox',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[name]',
                'value'      => '',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
              array(
                'element_name'  => $S . '[quota]',
                'value'      => '',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
            );
	
         $this->AddMetaRow($a, _('Add User') );
    }	
}

class CCQuotaAPI
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp( 'admin', 'quota' ), array( 'CCQuotaAPI', 'Admin' ), CC_ADMIN_ONLY, ccs(__FILE__) );
    }

    function Admin()
    {
        CCPage::SetTitle('Configure User Quota');
        $form = new CCQuotaAdminForm();
        if( empty($_POST['quotaadmin']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $configs =& CCConfigs::GetTable();
            $b['enable_quota'] = empty($_POST['enable_quota']) ? '0' : '1';
            $b['default_quota'] = empty($_POST['default_quota']) ? '0' : $_POST['default_quota'];
            $configs->SaveConfig('config',$b,CC_GLOBAL_SCOPE,true);

            if( !empty($_POST['S']) )
                $this->UpdateQuota($_POST['S']);
            if( !empty($_POST['new']) )
                $this->UpdateQuota($_POST['new']);

            CCPage::Prompt(_('Quota saved'));
        }
	
    }

    function UpdateQuota($arr)
    {
        CCUtil::StripSlash($arr);
        foreach( $arr as $fields )
        {
            $users =& CCUsers::GetTable();
            $where['user_name'] = $fields['name'];
            $user_id = $users->QueryKey($where);
            $quota['user_id'] = $user_id;
        
            if( empty($fields['delete']) )
            {
                $quota['user_quota'] = $fields['quota'];
            }
            else
            {
                $quota['user_quota'] = 0; 
            }

            $users->Update($quota);   
        }
    }

    function OnAdminMenu(&$items, $scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
            return;

        $items += array(
            'quota'   => array( 'menu_text'  => _('Quota'),
                         'menu_group' => 'configure',
                         'access' => CC_ADMIN_ONLY,
                          'help'  => _('Configure user quota'),
                         'weight' => 1000,
                         'action' =>  ccl('admin','quota')
                         ),
            );
    }
}
?>
