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
* $id$
*
*/

/**
* @package cchost
* @subpackage admin
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCSkinMaker' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCSkinMaker' , 'OnAdminMenu') );


class CCSkinMakerForm extends CCForm
{
    function CCSkinMakerForm()
    {
        $this->CCForm();

        $tdirs = CCTemplate::GetTemplatePath();

        // array_combine not in 4.2
        $template_dirs = array();
        foreach( $tdirs as $TD )
            $template_dirs[$TD] = $TD;

        $fields = array(

            'createin' => 
                       array( 'label'       => _('Create In'),
                               'form_tip'   => _('The new skin will be created in this directory'),
                               'formatter'  => 'select',
                               'options'    => $template_dirs,
                               'flags'      => CCFF_POPULATE),
            'name' => 
                       array( 'label'       => _('Name'),
                               'form_tip'   => _('Name of your new skin'),
                               'pattern_error'     => _('Invalid characters in name'), 
                               'formatter'  => 'skin',
                               'pattern'    => '@^[_a-zA-Z0-9]+$@',
                               'class'      => 'cc_form_input_short',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
                );

        $this->AddFormFields($fields);

        $help = _('Using this form you can create the basic structure of a new skin based
                   on skin-simple. 
                   Please consult the administrator\'s guide for editing skins.');

        $this->SetFormHelp($help);
    }

    function generator_skin($varname,$value='',$class='')
    {
        return $this->generator_pattern($varname,$value,$class) ;
    }

    function validator_skin($fieldname)
    {
        $ok = $this->validator_pattern($fieldname);
        if( $ok )
        {
            $name = $this->GetFormValue($fieldname);
            $dir = CCUtil::CheckTrailingSlash($this->GetFormValue('createin'),true);
            $fname = $dir . $name;
            $templates = CCTemplateAdmin::GetTemplates('skin','css');
            $ok = !in_array( $fname, $templates );
            if( !$ok )
            {
                $error_msg = _('That skin already exists');
                $this->SetFieldError($fieldname,$error_msg);
            }
        }

        return $ok;
    }

}


class CCSkinMaker
{

    function Create()
    {
        CCPage::SetTitle( _('Create a New Skin') );

        $form = new CCSkinMakerForm();

        if( !empty($_POST['skinmaker']) && $form->ValidateFields() )
        {
            $form->GetFormValues($values);

            $msg = $this->CreateSkin($values['name'],$values['createin']);

            CCPage::Prompt($msg);

        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function CreateSkin($newname,$template_dir)
    {
        $template_dir = CCUtil::CheckTrailingSlash($template_dir,true);
        $macros = @file_get_contents('ccextras/cc-skin-maker-template.txt');
        if( empty($macros) )
            return _('Error reading skin template file');

        $root_url = ccd();
        $macros = str_replace('%%root-url%%',$root_url,$macros);

        $ok = preg_match_all('/%%([^-]*)-begin%%(.*)%%end%%/sU', $macros, $m, PREG_SET_ORDER );
        if( !$ok )
            return _('Error parsing skin template file');


        foreach( $m as $t )
        {
            $text = trim(str_replace('%%name%%', $newname, $t[2]));
            $fname = $template_dir . 'skin-' . $newname;
            switch( $t[1] )
            {
                case 'css':
                    $fname .= '.css';
                    break;
                case 'map':
                    $fname .= '-map.xml';
                    break;
                case 'topics':
                    $fname .= '-topics.css';
                    break;
                default:
                    $fname .= '.xml';
                    break;
            }
            $f = fopen($fname,'w');
            if( !$f )
                return _('Error writing :' . $fname);
            fwrite($f,$text);
            fclose($f);
            chmod($fname,0777);
        }

        $graphics = array( 
            'button-left.gif', 'button-right-dl.gif',
            'button-right-play.gif', 'button-right.gif',
            'flat-button-pod.gif', 'flat-button-strm.gif',
            'footer-bl.gif', 'footer-br.gif',
            'footer-tl.gif', 'footer-nt.gif',
            'footer-tr.gif', 'header-bg.gif',
            'header-tl.gif', 'header-tr.gif',
            'header.gif', 'input.png', 'tab.gif' );

        CCUtil::MakeSubdirs($template_dir . $newname, 0777);

        foreach( $graphics as $g )
        {
            $src = 'ccextras/ccskin-graphics/' . $g;
            $dst = $template_dir . $newname . '/' . $g;

            copy( $src, $dst );
            chmod( $dst, 0777 );
        }

        $url = ccl('admin', 'settings');
        $link = sprintf( "<a href=\"$url\">%s</a>", _('Settings'));
        return sprintf(_('Skin "%s" written successfully. Use %s to select now.'),$newname,$link);
    }
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','skin','create'), array('CCSkinMaker','Create'), CC_ADMIN_ONLY);
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
                'skinmaker'   => array( 
                                 'menu_text'  => 'Skin Maker',
                                 'menu_group' => 'configure',
                                 'help' => 'Skin creation helper',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 2,
                                 'action' =>  ccl('admin','skin','create')
                                 ),
                );
        }
    }

}

?>