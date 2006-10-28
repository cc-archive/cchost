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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCAccess' , 'OnMapUrls') );
//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCAccess' , 'OnGetConfigFields') );

/**
 *
 */
class CCAdminSuperForm extends CCEditConfigForm
{
    /**
     * Constructor
     *
     * @param string $config_type The name of the settings group (i.e. 'menu')
     * @param string $scope CC_GLOBAL_SCOPE or a specific vroot (blank means current)
     */
    function CCAdminSuperForm()
    {
        $this->CCEditConfigForm('config');

        $fields = array( 
                    'supers' =>
                       array(  'label'      => _('Super Admins'),
                               'form_tip'   => _('Comma separated list of super site admins'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED,
                            ),
            );

        $this->AddFormFields($fields);
    }

}

/**
* Displays global configuration options.
*
*/
class CCAccessEditForm extends CCForm
{
    /**
    * Constructor
    * 
    */
    function CCAccessEditForm()
    {
        $this->CCForm();
    
        CCPage::AddLink('head_links', 'stylesheet', 'text/css', 
            ccd('cctemplates/access.css'), 'Default Style');
        CCPage::PageArg('access_editor', 'access.xml/access_editor');

        $map = cc_get_url_map();
        
        $cg = count($map);
        $gkeys = array_keys($map);
        for( $i = 0; $i < $cg; $i++ )
        {
            $group =& $map[ $gkeys[$i] ];
            $cu = count($group);
            $cukeys = array_keys($group);
            for( $n = 0; $n < $cu; $n++ )
            {
                $ua =& $group[$cukeys[$n]];
                $ua->opts = $this->_gen_select($ua->pmu,$ua->url);
                //CCDebug::PrintVar($ua);
            }
        }

        $fields = array(
            'map' => array( 'label'      => '', // _('Access Map'),
                           'formatter'  => 'metalmacro',
                           'flags'      => CCFF_POPULATE,
                           'macro'      => 'access_editor',
                           'access_map' => $map,
                            )
            );

        $this->AddFormFields($fields);

        $help = _('Use this form to decide who has acces to which commands. (This does
                  not affect which menu items are shown, use the Manage Site/Menu
                  form for controlling that.)');
        
        $this->SetFormHelp($help);
    }

    function _gen_select($pm,$url)
    {
        static $roles;
        if( !isset($roles) )
            $roles = cc_get_roles();

        $html = "\n<select id=\"acc[$url]\" name=\"acc[$url]\">";
        foreach( $roles as $V => $T )
        {
            $sel = ( $V == $pm ) ? 'selected="selected"' : '';
            
            $html .= "<option $sel value=\"$V\">$T</option>";
        }
        $html .= '</select>';
        return $html;
    }
}


/**
* Basic admin access API and system event watcher.
* 
*/
class CCAccess
{
    function Super()
    {
        CCPage::SetTitle(_('Edit Super Admins'));
        $form = new CCAdminSuperForm();
        CCPage::AddForm($form->GenerateForm());
    }

    function Access()
    {
        CCPage::SetTitle(_('Edit Access Rights'));
        $form = new CCAccessEditForm();
        if( empty($_POST['accessedit']) )
        {
            CCPage::AddForm($form->GenerateForm());
        }
        else
        {
            $acc = $_POST['acc'];
            $map =& CCEvents::GetUrlMap();
            $accmap = array();
            foreach( $acc as $url => $pm )
            {
                if( $map[$url]->pm != $pm )
                    $accmap[$url] = $pm;
            }

            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig( 'accmap', $accmap, CC_GLOBAL_SCOPE, false );
            CCPage::Prompt(_('Access map changes saved'));
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/super',     array('CCAccess', 'Super'),       
            CC_SUPER_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( 'admin/access',     array('CCAccess', 'Access'), 
            CC_SUPER_ONLY, ccs(__FILE__) );
    }

}

function cc_get_url_map($doconly=1)
{
    $roles = cc_get_roles();
    $group_names = cc_get_access_groups();

    $configs =& CCConfigs::GetTable();
    $accmap = $configs->GetConfig('accmap');

    $map =& CCEvents::GetUrlMap();
    if( $doconly )
    {
        $groups = array();
        foreach( $map as $K => $V )
        {
            if( empty($V->dg) ) // no doc group?
                continue;       // never mind

            $pm = empty($accmap[$K]) ? $V->pm : $accmap[$K];
            $V->pmu = $pm;
            $V->url = $K;
            $V->pmd = $roles[ $pm ];
            if( array_key_exists($V->dg,$group_names) )
                $gn = $group_names[$V->dg];
            else
                $gn = $V->dg;
            $groups[ $gn ][$K] = $V;
        }
        ksort($groups);
        foreach( $groups as $G => $V )
        {
            ksort($groups[$G]);
        }
        return $groups;
    }
    return $map;
}

?>
