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

class CCAdminThumbnailForm extends CCEditConfigForm
{
    function CCAdminThumbnailForm()
    {
        $this->CCEditConfigForm('settings');

        $fields['thumbnail-on'] = 
           array( 'label'       => _('Display Thumbnails'),
                   'formatter'  => 'checkbox',
                   'form_tip'   => _('Display thumbnails for image uploads'),
                   'flags'      => CCFF_POPULATE);

        $fields['thumbnail-constrain-y'] = 
           array( 'label'       => _('Constrain Thumbnail Proportion'),
                   'formatter'  => 'checkbox',
                   'form_tip'   => _('Constrain proportion of image to the original image\'s height (y value)'),
                   'flags'      => CCFF_POPULATE);

        $fields['thumbnail-x'] = 
           array( 'label'       => _('Max Thumb X'),
                   'formatter'  => 'textedit',
                   'form_tip'   => _('Leave this blank or 0 (zero) to use the image\'s natural size'),
                   'class'      => 'cc_form_input_short',
                   'flags'      => CCFF_POPULATE);

        $fields['thumbnail-y'] =
           array( 'label'       => _('Max Thumb Y'),
                   'formatter'  => 'textedit',
                   'class'      => 'cc_form_input_short',
                   'flags'      => CCFF_POPULATE );

        $this->AddFormFields($fields);
        $this->SetModule(ccs(__FILE__));
    }
}


?>
