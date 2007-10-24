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


require_once('cclib/cc-user.inc');

class CCConfirmTopicDeleteForm extends CCForm
{
    function CCConfirmTopicDeleteForm($pretty_name)
    {
        $this->CCForm();
        $this->SetHelpText(_("This action cannot be reversed...") );
        // this line was different in svn:
        $this->SetSubmitText(sprintf(_('Are you sure you want to delete "%s"?'),
                                     $pretty_name));
    }
}

class CCTopicForm extends CCSecurityVerifierForm
{
    function CCTopicForm($label_text,$submit_text,$visible_title = false,$show_xlat = true)
    {
        $this->CCSecurityVerifierForm();

        $fields = array();

        if( $visible_title )
        {
            $fields['topic_name'] = array(
                            'label'         => _('Title'),
                            'formatter'     => 'textedit',
                            'flags'         => CCFF_REQUIRED | CCFF_POPULATE);
        }

        $fields += array( 
                    'topic_text' => array(
                            'label'         => $label_text,
                            'formatter'     => 'textarea',
                            'flags'         => CCFF_REQUIRED | CCFF_POPULATE),
                    );

        if( CCUser::IsAdmin() && $show_xlat )
        {
            $fields += array( 
                        'topic_can_xlat' => array(
                                'label'         => _('Allow Translations'),
                                'formatter'     => 'checkbox',
                                'flags'         => CCFF_POPULATE)
                        );
        }

        $fields += array( 
                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => _('Security Key'),
                               'formatter'  => 'textedit',
                               'class'      => 'cc_form_input_short',
                               'form_tip_str'   => CCSecurityVerifierForm::GetSecurityTipStr(),
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE)
                    );


        $this->AddFormFields($fields);
        $this->SetSubmitText($submit_text);
    }
}

class CCTopicReplyForm extends CCTopicForm
{
    function CCTopicReplyForm()
    {
        $this->CCTopicForm(_('Reply'), _('Submit Reply'));
    }
}

class CCTopicTranslateForm extends CCTopicForm
{
    function CCTopicTranslateForm($title)
    {
        $this->CCTopicForm(_('Translate'), _('Submit Translation'), true, false );

        $fields = array( 

        'topic_i18n_language' => array(
                'label'         => _('Language'),
                'form_tip'      => _('Your language, in your language (e.g. Italiano, Magyar, etc.)'),
                'formatter'     => 'textedit',
                'flags'         => CCFF_REQUIRED | CCFF_POPULATE),
            );

        $this->InsertFormFields( $fields, 'after', 'topic_name' );
        $this->SetFormValue('topic_name',htmlentities($title));
    }
}

class CCTopicEditForm extends CCTopicForm
{
    function CCTopicEditForm($static_title=true)
    {
        $this->CCTopicForm(_('Text'), 'Submit Changes');
        if( $static_title )
        {
            $flags = CCFF_STATIC | CCFF_NOUPDATE | CCFF_POPULATE;
            $type = 'statictext';
        }
        else
        {
            $flags = CCFF_REQUIRED | CCFF_POPULATE;
            $type = 'edittext';
        }
        $f['topic_name'] = array(
                            'label'       => _('Title'),
                            'formatter'   => $type,
                            'flags'       => $flags
                            );

        //ugh -- don't do this
        $this->_form_fields = array_merge($f,$this->_form_fields);
    }
}


?>