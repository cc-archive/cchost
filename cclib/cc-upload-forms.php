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
* @subpackage upload
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-form.php');

/**
 * Base class for forms that uplaod media files.
 * 
 */
class CCUploadMediaForm extends CCUploadForm 
{
    /**
     * Constructor.
     * 
     * Sets up basic editing fields for name, tags, description and the
     * file upload itself. Invokes the CC_UPLOAD_VALIDATOR 
     * to get a list of valid file types allowed for upload.
     *
     * @access public
     * @param integer $user_id This id represents the 'owner' of the media
     */
    function CCUploadMediaForm($user_id,$file_field = true)
    {
        global $CC_CFG_ROOT;

        $this->CCUploadForm();
        $this->SetSubmitText(_('Upload'));
        $this->SetHiddenField('upload_user', $user_id);
        $this->SetHiddenField('upload_config', $CC_CFG_ROOT);

        $fields['upload_name'] =
                        array( 'label'      => _('Name'),
                               'formatter'  => 'textedit',
                               'form_tip'   => _('Display name for file'),
                               'flags'      => CCFF_POPULATE );

        if( $file_field )
        {
            require_once('cclib/cc-upload.php');
            CCUpload::GetUploadField($fields,'upload_file_name');
        }

        require_once('cclib/cc-tags.inc');
        $tags =& CCTags::GetTable();
        $where['tags_type'] = CCTT_USER;
        $tags->SetOffsetAndLimit(0,'25');
        $tags->SetOrder('tags_count','DESC');
        $pop_tags = $tags->QueryKeys($where);

        $fields['upload_tags'] =
                        array( 'label'      => _('Tags'),
                               'formatter'  => 'tagsedit',
                               'form_tip'   => _('Comma separated list of terms'),
                               'flags'      => CCFF_NONE );

        $fields['popular_tags'] =
                        array( 'label'      => _('Popular Tags'),
                               'target'     => 'upload_tags',
                               'tags'       => $pop_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => _('Click on these to automatically add to your upload.'),
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE );

        $fields['upload_description'] =
                        array( 'label'      => _('Description'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE );
        
        $this->AddFormFields( $fields );

        $this->_extra = array();

        // no need to call special, now in default scripts
        // CCPage::AddScriptBlock('popular_tags_script'); 
    }

    function AddSuggestedTags($suggested_tags)
    {
        if( empty($suggested_tags) )
            return;

        if( !is_array($suggested_tags) )
            $suggested_tags = CCTag::TagSplit($suggested_tags);

        $fields['suggested_tags'] =
                        array( 'label'      => _('Suggested Tags'),
                               'target'     => 'upload_tags',
                               'tags'       => $suggested_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => _('Click on these to automatically add to your upload.'),
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE );

        $this->InsertFormFields( $fields, 'before', 'popular_tags' );
    }

}

/**
 * Extend this class for forms that upload new media to the system.
 *
 */
class CCNewUploadForm extends CCUploadMediaForm
{
    /**
     * Constructor.
     *
     * Tweaks the bass class state to be in line with
     * new uploads, original or remixes.
     *
     * @access public
     * @param integer $userid The upload will be 'owned' by this user
     * @param integer $show_lic Set this to display license choices
     */
    function CCNewUploadForm($userid, $show_lic = true)
    {
        $this->CCUploadMediaForm($userid);

        $this->SetHiddenField('upload_date', date( 'Y-m-d H:i:s' ) );

        if( $show_lic )
        {
            require_once('cclib/cc-license.php');
            $licenses =& CCLicenses::GetTable();
            $lics     = $licenses->GetEnabled();
            $count    = count($lics);
            if( $count == 1 )
            {
                $this->SetHiddenField('upload_license',$lics[0]['license_id']);
            }
            elseif( $count > 1 )
            {
                $fields = array( 
                    'upload_license' =>
                                array( 'label'      => _('License'),
                                       'formatter'  => 'metalmacro',
                                       'flags'      => CCFF_POPULATE,
                                       'macro'      => 'license_choice',
                                       'license_choice' => $lics
                                )
                            );
                
                $this->AddFormFields( $fields );
            }
        }
        
    }

}

class CCConfirmDeleteForm extends CCForm
{
    function CCConfirmDeleteForm($pretty_name)
    {
        $this->CCForm();
        $this->SetHelpText(_('This action can not be reversed...'));
        $this->SetSubmitText(sprintf(_("Are you sure you want to delete '%s'?"),$pretty_name));
    }
}

/**
* @package cchost
* @subpackage admin
*/
class CCAdminUploadForm extends CCForm
{
    function CCAdminUploadForm(&$record)
    {
        $this->CCForm();

        require_once('cclib/cc-tags.inc');
        $tags =& CCTags::GetTable();
        $where['tags_type'] = CCTT_SYSTEM;
        $tags->SetOrder('tags_tag','ASC');
        $sys_tags = $tags->QueryKeys($where);

        $fields = array(
            'ccud' => array(
                'label'     => _('Internal Tags'),
                'form_tip'  => _("Be careful when editing these, it is easy to confuse the system"),
                'value'     => $record['upload_extra']['ccud'],
                'formatter' => 'textedit',
                'flags'     => CCFF_REQUIRED | CCFF_POPULATE
                ),
            'popular_tags'  =>
                        array( 'label'      => _('System Tags'),
                               'target'     => 'ccud',
                               'tags'       => $sys_tags,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'popular_tags',
                               'form_tip'   => _('Click on these to automatically add them.'),
                               'flags'      => CCFF_STATIC | CCFF_NOUPDATE 
                ),
            );

        $this->AddFormFields($fields);
        //CCPage::AddScriptBlock('popular_tags_script');

    }
}

?>
