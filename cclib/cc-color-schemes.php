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
* Base classes and general user admin interface
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-form.php');
require_once('cclib/cc-skin-admin.php');

/**
 *
 */
class CCAdminColorSchemesForm extends CCGridForm
{
    /**
     * Constructor
     */
    function CCAdminColorSchemesForm($schemes)
    {
        $this->CCGridForm();

        $heads = array( _('Display'), _('Internal'), _('Scheme') );
        $this->SetColumnHeader($heads);


        foreach( $schemes['properties'] as $scheme)
        {
            $keyname = $scheme['id'];
            $a = array(
                  array(
                    'element_name'  => "grp[$keyname][caption]",
                    'value'      => $scheme['caption'],
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][id]",
                    'value'      => $scheme['id'],
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][css]",
                    'value'      => $scheme['css'],
                    'formatter'  => 'textarea',
                    'expanded'   => true,
                    'flags'      => CCFF_REQUIRED ),
                );

            $this->AddGridRow( $keyname, $a );
        }

        $S = 'new[%i%]';
        $a = array(
              array(
                'element_name'  => $S . '[caption]',
                'value'      => 'Friendly name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
              array(
                'element_name'  => $S . '[id]',
                'value'      => 'system_name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[css]',
                'value'      => '',
                'expanded'   => true,
                'formatter'  => 'textarea',
                'flags'      => CCFF_POPULATE ),
            );

        $this->AddMetaRow($a, _('Add Scheme') );
        $this->SetSubmitText(_('Submit Scheme Changes'));
    }

}

/**
* Edit and maintain color schemes
* 
*/
class CCColorSchemes
{
    function Admin()
    {
        CCPage::SetTitle(_('Edit Color Schemes'));
        $config =& CCConfigs::GetTable();
        $schemes = $config->GetConfig('skin-design');
        $form = new CCAdminColorSchemesForm($schemes['color-scheme']);
        if( empty($_POST['admincolorschemes']) || !$form->ValidateFields())
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            /* POST ---------------
                [grp] => Array
                        (
                            [mono] => Array
                                (
                                    [css] => .light_bg { background-color: #FFFFFF; }
                                             .light_border { border-colo

                $schemes-----------
                [color-scheme] => Array
                    (
                        [properties] => Array
                            (
                                [0] => Array
                                    (
                                        [caption] => Black and White
                                        [id] => mono
                                        [css] => .light_bg { background-color: #FFFFFF; }
                                                 .light_border { border-color: #FFFFFF; }
            */
            CCUtil::Strip($_POST);

            foreach( $schemes['color-scheme']['properties'] as $k => $v )
            {
                if( !empty($_POST['grp'][$v['id']]) )
                    $schemes['color-scheme']['properties'][$k]['css'] = $_POST['grp'][$v['id']]['css'];
            }

            if( !empty($_POST['new']) )
            {
                $schemes['color-scheme']['properties'] += $_POST['new'];
            }
            
            $config->SaveConfig('color-design',$schemes,CC_GLOBAL_SCOPE,false);

            CCPage::Prompt(_('Color scheme changes saved'));
        }
    }

    function OnAdminMenu( &$items, $scope )
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'colorschemes'   => array( 'menu_text'  => _('Skin Color Schemes'),
                             'menu_group' => 'configure',
                             'help'      => _('Manage color schemes'),
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 120,
                             'action' =>  ccl('admin','colors')
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
        CCEvents::MapUrl( 'admin/colors',     array('CCColorSchemes', 'Admin'),       
            CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}


?>
