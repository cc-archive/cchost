<?php
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
* $id$
*
*/

/** 
* @package cchost
* @subpackage upload
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-admin.php');

class CCAdminLicWaiverForm extends CCEditConfigForm
{
    function CCAdminLicWaiverForm()
    {
        $this->CCEditConfigForm('lic-waiver',CC_GLOBAL_SCOPE);
        $this->SetModule(ccs(__FILE__));

        $lics = CCDatabase::QueryRows('SELECT license_id,license_name FROM cc_tbl_licenses ORDER by license_name');
        $licx = array();
        foreach( $lics as $L )
            $licx[$L['license_id']] = $L['license_name'];

        $fields = array(
                    'waivers' =>
                        array(
                                'label' => _('Waivers'),
                                'form_tip' => _('When a remix would default to one of these...') ,
                                'formatter' => 'template',
                                'macro' => 'multi_checkbox',
                                'options' => $licx,
                                'cols' => 2,
                                'flags' => CCFF_POPULATE ),
                    'licenses' =>
                        array(
                                'label' => _('Alternatives'),
                                'form_tip' => _('...then offer one of these...') ,
                                'formatter' => 'template',
                                'macro' => 'multi_checkbox',
                                'options' => $licx,
                                'cols' => 2,
                                'flags' => CCFF_POPULATE ),
            );

        $this->AddFormFields($fields);
    }

    function PopulateValues(&$vals)
    {
        $vals['licenses'] = join(',',array_keys( $vals['licenses']));
        $vals['waivers'] = join(',',array_keys( $vals['waivers']));
        parent::PopulateValues($vals);
    }
}

class CCLicWaiver
{
    function Admin()
    {
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-admin.php');
        $title = _('Configure Waiver Alternatives');
        CCAdmin::BreadCrumbs(true,array('url'=>'','text'=>$title));
        CCPage::SetTitle($title);
        $form = new CCAdminLicWaiverForm();
        CCPage::AddForm( $form->GenerateForm() );
    }
    
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','waiver'),   array('CCLicWaiver','Admin'),
            CC_ADMIN_ONLY, ccs(__FILE__), '', 
            _('Manage license waiver alternatives') , CC_AG_UPLOAD );
    }
}

?>
