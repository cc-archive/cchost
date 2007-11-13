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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-form.php');

class CCDataViewForm extends CCForm
{
    function CCDataViewForm()
    {
        $this->CCForm();

        $flag_map = CCDataview::_get_flag_map();

        $fields = array(
            'dataview_id' => array(
                    'label'     => _('Dataview ID'),
                    'formatter' => 'textedit',
                    'tip'       => _('Internal name for this dataview'),
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            'dataview_query' => array(
                    'label'     => _('SQL Query'),
                    'formatter' => 'textarea',
                    'expanded'  => true,
                    'flags'     => CCFF_REQUIRED | CCFF_POPULATE,
                     ),
            );

        foreach( $flag_map as $field => $flag )
        {
            $fields[$field] = array(
                    'label'     => $flag[1],
                    'formatter' => 'checkbox',
                    'flags'     => CCFF_POPULATE,
                     );
        }

        $this->AddFormFields($fields);
    }
}
/**
*
*
*/
class CCDataview
{
    function _get_flag_map()
    {
       return array(
                'include_menus'           => array( CC_DV_MENUS, _('Include menus') ),
                'include_remix_history'   => array( CC_DV_REMIXES, _('Include full remix history') ),
                'include_remix_history_3' => array( CC_DV_REMIXES_3, _('Include remix history (max 3)')),
                'include_files'           => array( CC_DV_FILES, _('Include file details')),
                'make_tag_links'          => array( CC_DV_TAGLINKS, _('Make tag links') ),
                );
    }

    function Browse($ui=true)
    {
        if( $ui )
            CCPage::SetTitle(_('Browse DataViews'));
        $table = new CCTable('cc_tbl_dataview','dataview_id');
        $url = ccl('dataview','edit') . '/';
        $rows = $table->QueryItems("CONCAT('<a href=\"{$url}',dataview_id,'\">',dataview_id,'</a>') as edit_url",'1' );
        $html = join('<br />',$rows);
        CCPage::AddContent($html);
    }

    function Create()
    {
        CCPage::SetTitle(_('Create a new dataview'));
        $form = new CCDataViewForm();
        if( empty($_POST['dataview']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $flags = 0;
            $flag_map = $this->_get_flag_map();
            foreach( $flag_map as $field => $flag )
            {
                if( !empty($values[$field]) )
                    $flags |= $flag[0];
                unset($values[$field]);
            }
            $values['dataview_flags'] = $flags;
            $table = new CCTable('cc_tbl_dataview','dataview_id');
            $table->Insert($values);
            CCPage::Prompt(_('Dataview Saved'));
            $this->Browse(false);
        }
    }

    function Edit($dv_id)
    {
        CCPage::SetTitle(_('Edit Dataview'));
        $form = new CCDataViewForm();
        $table = new CCTable('cc_tbl_dataview','dataview_id');
        $row = $table->QueryKeyRow($dv_id);
        $flag_map = $this->_get_flag_map();
        foreach( $flag_map as $field => $flag )
            $row[$field] = ($row['dataview_flags'] & $flag[0]) != 0 ? 'on' : '';
        unset($row['dataview_flags']);
        $form->PopulateValues($row);
        if( empty($_POST['dataview']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($values);
            $flags = 0;
            $flag_map = $this->_get_flag_map();
            foreach( $flag_map as $field => $flag )
            {
                if( !empty($values[$field]) )
                    $flags |= $flag[0];
                unset($values[$field]);
            }
            $values['dataview_flags'] = $flags;
            $table->Update($values);
            CCPage::Prompt(_('Dataview Updated'));
            $this->Browse(false);
        }

    }

    function Delete($dv_id)
    {
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('dataview'),            array( 'CCDataview', 'Browse'),  
                CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('dataview','create'),   array( 'CCDataview', 'Create'),  
                CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('dataview','edit'),   array( 'CCDataview', 'Edit'),  
                CC_ADMIN_ONLY, ccs(__FILE__) );
        CCEvents::MapUrl( ccp('dataview','delete'),   array( 'CCDataview', 'Delete'),  
                CC_ADMIN_ONLY, ccs(__FILE__) );
    }
}

?>
