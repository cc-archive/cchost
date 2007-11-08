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

require_once('cclib/cc-admin.php');

/**
 *
 */
class CCSkinAdminForm extends CCEditConfigForm
{
    /**
     * Constructor
     */
    function CCSkinAdminForm()
    {
        $this->CCEditConfigForm('skin-properties');

        $props = CCSkinAdmin::_read_properties();

        $fields = array();
        foreach( $props as $id => $value )
        {
            $fields[$id] = array(
                    'label' => $value['label'],
                    'formatter' => 'skin_prop',
                    'macro'   => $value['editor'],
                    'props'     => $value['properties'],
                    'flags'    => CCFF_POPULATE,
                );

        }

        $this->SetHiddenField( 'properties', '' );
        $this->AddFormFields($fields);
        $this->SetSubmitText(_('Submit Skin Changes'));
        $this->SetModule(ccs(__FILE__));

        CCPage::AddScriptLink('js/skin_editor.js',true);
    }

    function generator_skin_prop($varname,$value,$class='')
    {
        return $this->generator_metalmacro($varname,$value,$class);
    }

    function validator_skin_prop($fieldname)
    {
        $props = $this->GetFormFieldItem($fieldname,'props');
        $valname = empty($_POST[$fieldname]) ? $props[0]['id'] : $_POST[$fieldname];
        $propval = null;
        foreach( $props as $P )
        {
            if( $P['id'] == $valname )
            {
                $propval = $P;
                break;
            }
        }
        $config_props = $this->GetFormValue('properties');
        $config_props[$propval['id']] = $propval;
        $this->SetFormValue('properties',$config_props);
        return true;
    }
}


/**
* Edit and maintain color schemes
* 
*/
class CCSkinAdmin
{

    function & _read_properties()
    {
        $file = CCTemplate::Search('properties.xml',true) or die('Can\'t find properties.xml');
        $text = file_get_contents($file);
        $map = array( '/<properties>/' => '$sections = array(' . "\n",
                      '/<section type="([^"]+)"\s+label="([^"]+)"\s+editor="([^"]+)">/U' => 
                                "  '$1' => array(\n   'label' => '$2',\n   'editor' => '$3',\n'properties' => array( \n",
                      '/<property>/' => '    array(' . "\n",
                      '#<caption>(.*)</caption>#U' => "   'caption' => '$1',\n",
                      '#<image>(.*)</image>#U' => "   'img' => '$1',\n",
                      '#<id>(.*)</id>#U' => "   'id' => '$1',\n",
                      '#<markup type="([^"]+)">(.*)</markup>#Ums'=> "   'markup_type' => '$1',\n      'markup' => '$2',\n",
                      '#</property>#' => ' ), ',
                      '#</section>#' => ' ), ), ',
                      '#</properties>#' => ' ); '
                    );

        $php = preg_replace(array_keys($map),array_values($map),$text);
        eval($php);
        //CCDebug::PrintVar($php);
        return $sections;
    }

    function Admin()
    {
        CCPage::SetTitle(_('Configure Skins'));
        $form = new CCSkinAdminForm();
        CCPage::AddForm($form->GenerateForm());
    }

    function OnAdminMenu( &$items, $scope )
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'skinadmin'   => array( 'menu_text'  => _('Skins'),
                             'menu_group' => 'configure',
                             'help'      => _('Configure skins'),
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 120,
                             'action' =>  ccl('admin','skins')
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
        CCEvents::MapUrl( 'admin/skins',     array('CCSkinAdmin', 'Admin'),       
            CC_ADMIN_ONLY, ccs(__FILE__) );
    }

}


?>
