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

CCDebug::PrintVar($schemes);

        $heads = array( _('Display'), _('Internal'), _('Scheme') );
        $this->SetColumnHeader($heads, 1);

        foreach( $schemes as $keyname => $scheme)
        {
            $a = array(
                  array(
                    'element_name'  => "grp[$keyname][display_name]",
                    'value'      => $scheme['display_name'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][name]",
                    'value'      => $scheme['name'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][scheme]",
                    'value'      => $scheme['scheme'],
                    'formatter'  => 'textarea',
                    'expanded'   => false,
                    'flags'      => CCFF_REQUIRED ),
                );

            $this->AddGridRow( $keyname, $a );
        }

        $empty_scheme = CCColorSchemes::GetEmptyScheme();
        $S = 'new[%i%]';
        $a = array(
              array(
                'element_name'  => $S . '[display_name]',
                'value'      => 'Friendly name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
              array(
                'element_name'  => $S . '[name]',
                'value'      => 'system_name',
                'formatter'  => 'textedit',
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => $S . '[scheme]',
                'value'      => $empty_scheme,
                'expanded'   => false,
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
    function GetDefaultColorSchemes()
    {
        $mono =<<<EOF
\$bg = '#FFFFFF';
\$color = '#000000';
\$dark = '#555555';
\$med = '#888888';
\$light = '#DDDDDD';
\$highlight = '#CCCCCC';
\$highlight_bg = '#BBBBBB';
\$link = \$dark;
\$link_visited = \$dark;
EOF;
        $blue =<<<EOF
\$bg = '#EEEEFF';
\$color = '#0000FF';
\$dark = '#555577';
\$med = '#8888FF';
\$light = '#DDDDFF';
\$highlight = '#CCCCFF';
\$highlight_bg = '#BBBBBB';
\$link = \$dark;
\$link_visited = \$dark;
EOF;
        $green =<<<EOF
\$bg = '#EEFFEE';
\$color = '#00FF00';
\$dark = '#557755';
\$med = '#88FF88';
\$light = '#DDFFDD';
\$highlight = '#CCFFCC';
\$highlight_bg = '#BBBBBB';
\$link = \$dark;
\$link_visited = \$dark;
EOF;

        return array(
            array( 'name' => 'mono' , 'display_name' => 'Black and White', 'scheme' => $mono ), 
            array( 'name' => 'blue' , 'display_name' => 'Blue', 'scheme' => $blue ), 
            array( 'name' => 'green' , 'display_name' => 'Green', 'scheme' =>$green) 
        );
    }

    function GetEmptyScheme()
    {
        $empty_scheme =<<<EOF
\$bg = '#FFFFFF';
\$color = '#000000';
\$dark = '#000000';
\$med = '#888888';
\$light = '#FFFFFF';
\$highlight = '#666666';
\$highlight_bg = '#BBBBBB';
\$link = \$dark;
\$link_visited = \$dark;
EOF;
        return $empty_scheme;
    }

    function Admin()
    {
        CCPage::SetTitle(_('Edit Color Schemes'));
        if( empty($_POST['admincolorschemes']) )
        {
            $config =& CCConfigs::GetTable();
            $schemes = $config->GetConfig('color-schemes');
            if( empty($schemes) )
                $schemes = $this->GetDefaultColorSchemes();
            $form = new CCAdminColorSchemesForm($schemes);
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $form = new CCAdminColorSchemesForm(array());
            if( !$form->ValidateFields() )
            {
                CCPage::AddForm($form->GenerateForm());
            }
            else
            {
                CCUtil::Strip($_POST);
                if( empty($_POST['new']) )
                    $grp = $_POST['grp'];
                else
                    $grp = array_merge($_POST['grp'],$_POST['new']);
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig( 'color-schemes', $grp, '', false);
                CCPage::Prompt(_('Color scheme changes saved'));
            }
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
