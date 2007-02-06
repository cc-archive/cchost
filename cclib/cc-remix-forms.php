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
* $Id$
*
*/

/** 
* Module for handling Remix UI
*
* @package cchost
* @subpackage upload
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-upload-forms.php');

/**
 * Base class for uploading remixes form
 *
 * Note: derived classes must call SetHandler()
 * @access public
 */
class CCPostRemixForm extends CCNewUploadForm
{
    /**
     * Constructor
     *
     * Sets up form as a remix form. Initializes 'remix search' box.
     
     * @access public
     * @param integer $userid The remix will be 'owned' by owned by this user
     */
    function CCPostRemixForm($userid,$show_pools=false)
    {
        $this->CCNewUploadForm($userid,false);

        require_once('cclib/cc-remix.php');

        if( $show_pools )
            CCRemix::_add_pool_to_form($this);

        CCRemix::_setup_search_fields($this);
    }

    /**
     * Overrides the base class and only displays fields if search results is not empty.
     *
     */
    function GenerateForm()
    {
        if( $this->TemplateVarExists('remix_sources') || $this->TemplateVarExists('pool_sources')  )
        {
            parent::GenerateForm(false);
        }
        else
        {
            $this->EnableSubmitMessage(false);
            $this->SetSubmitText(null);
            parent::GenerateForm(true); // hiddenonly = true
        }

        return( $this );
    }

}


class CCEditRemixesForm extends CCForm
{
    /**
     * Constructor
     *
     * Sets up form as a remix editing form. Initializes 'remix search' box.
     *
     * @param bool $show_pools (reserved)
     */
    function CCEditRemixesForm($show_pools=false)
    {
        $this->CCForm();

        if( $show_pools )
            CCRemix::_add_pool_to_form($this);

        CCRemix::_setup_search_fields($this);

        $this->SetSubmitText(_('Done Editing'));
    }
}


?>
