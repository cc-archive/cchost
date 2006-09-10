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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCSkinMaker' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,  array( 'CCSkinMaker' , 'OnAdminMenu') );


class CCSkinMakerForm extends CCForm
{
    function CCSkinMakerForm()
    {
        $this->CCForm();

        $templates = CCTemplateAdmin::GetTemplates('skin','css');

        $Ts = array();
        foreach( $templates as $T )
            $Ts[] = $T . '$';
        $tor = join('|',$Ts);
        $regex = '/^(?!' . $tor . ')[a-zA-Z0-9]+$/';
        
        $tname = join('|', $templates );

        $fields = array(

            'name' => 
                       array( 'label'       => _('Name'),
                               'form_tip'   => _('Name of your new skin (alpha-numeric only)'),
                               'pattern_error'     => _('That skin already exists or the name is invalid.'),
                               'formatter'  => 'cc_pattern',
                               'pattern'    => $regex,
                               'class'      => 'cc_form_input_short',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
/*
            'based-on' => 
                       array( 'label'       => _('Based on'),
                               'form_tip'   => _('Your new skin will be derived from this'),
                               'formatter'  => 'select',
                               'options'    => CCTemplateAdmin::GetTemplates('skin','css'),
                               'flags'      => CCFF_POPULATE ),
            'type' => 
                       array( 'label'       => _('Type of copy:'),
                               'form_tip'   => _('You might not think so, but you probably want derivation'),
                               'formatter'  => 'radio',
                               'value'      => 'derive',
                               'options'    => array(
                                                   'full' => _('Perform full copy'),
                                                   'derive' => _('Derivation only')
                                                     ),
                               'flags'      => CCFF_POPULATE ),
*/
                );

        $this->AddFormFields($fields);

        $help = _('Using this form you can create the basic structure of a new skin based
                   on skin-simple. 
                   Please consult the administrator\'s guide for editing skins.');

        $this->SetFormHelp($help);
    }
}

function generator_cc_pattern($form,$varname,$value='',$class='')
{
    return $form->generator_textedit($varname,$value,$class) ;
}

function validator_cc_pattern($form,$fieldname)
{
    $pattern = $form->GetFormFieldItem( $fieldname, 'pattern' );

    $ok = $form->validator_textedit($fieldname);

    if( $ok )
    {
        if( empty($pattern) )
            return true;  // hmmmmm

        $value = $form->GetFormValue($fieldname);

        $ok = preg_match( $pattern, $value );

        if( !$ok )
        {
            $errmsg = $form->GetFormFieldItem( $fieldname, 'pattern_error' );
            if( empty($errmsg) )
            {
                $errmsg = _('Does match proper pattern');
            }
            $form->SetFieldError( $fieldname, $errmsg );
        }
    }

    return $ok;
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

            $msg = $this->CreateSkin($values['name']);

            CCPage::Prompt($msg);

        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

    function CreateSkin($newname)
    {
        $macros = @file_get_contents('ccextras/cc-skin-maker-template.txt');
        if( empty($macros) )
            return _('Error reading skin template file');
        $ok = preg_match_all('/%%([^-]*)-begin%%(.*)%%end%%/sU', $macros, $m, PREG_SET_ORDER );
        if( !$ok )
            return _('Error parsing skin template file');

        foreach( $m as $t )
        {
            $text = trim(str_replace('%%name%%', $newname, $t[2]));
            $fname = 'cctemplates/skin-' . $newname;
            switch( $t[1] )
            {
                case 'css':
                    $fname .= '.css';
                    break;
                case 'map':
                    $fname .= '-map.xml';
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

        CCUtil::MakeSubdirs('cctemplates/' . $newname, 0777);

        foreach( $graphics as $g )
        {
            $src = 'ccextras/ccskin-graphics/' . $g;
            $dst = 'cctemplates/' . $newname . '/' . $g;

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