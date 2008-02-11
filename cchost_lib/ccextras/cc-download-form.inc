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
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-form.php');
require_once('cchost_lib/cc-admin.php');

class CCDownloadAdminForm extends CCEditConfigForm
{
    /**
    * Constructor
    */
    function CCDownloadAdminForm()
    {
        $this->CCEditConfigForm('config',CC_GLOBAL_SCOPE);

        $fields['counter'] =
           array(  'label'      => _('Download counter'),
                   'form_tip'   => _('File download counter.'),
                   'value'      => 0,
                   'formatter'  => 'checkbox',
                   'flags'      => CCFF_POPULATE );

        $fields['checksum'] =
           array(  'label'      => _('Checksum'),
                   'form_tip'   => _('Download files with checksum. (Raps download in ZIP archive)'),
                   'value'      => 0,
                   'formatter'  => 'checkbox',
                   'flags'      => CCFF_POPULATE );

        $fields['license'] =
           array(  'label'      => _('License'),
                   'form_tip'   => _('Download files with license RDF file.  (Raps download in ZIP archive)'),
                   'value'      => 0,
                   'formatter'  => 'checkbox',
                   'flags'      => CCFF_POPULATE );

        $this->AddFormFields($fields);
        $this->SetModule( ccs(__FILE__) );
    }
}

?>