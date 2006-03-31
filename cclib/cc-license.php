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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_FILE_DONE,    array( 'CCLicense',  'OnFileDone'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCLicense' , 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,  array( 'CCLicense',  'OnGetSysTags'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCLicense',  'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCLicense',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,   array( 'CCLicense',  'OnUploadRow'));

/**
 * Form class for configuring licenses.
 *
 * @access public
 */
class CCAdminLicenseForm extends CCForm
{
    /**
     * Constructor.
     *
     * Sets up fields as read from the license database.
     *
     * @access public
     * @param  boolean $populate True if fields are to be populated with values in the database
     */
    function CCAdminLicenseForm($populate)
    {
        $this->CCForm();
        $config =& CCConfigs::GetTable();
        $config_lics = $config->GetConfig('licenses');
        if( empty($config_lics) )
            $config_lics = array();
        $licenses =& CCLicenses::GetTable();
        $rows = $licenses->QueryRows('');
        $fields = array();
        foreach($rows as $row)
        {
            $value = $populate && in_array( $row['license_id'], $config_lics ) ? 'checked' : '';

            $fields[ $row['license_id'] ] = 
                       array( 'label'       => 'Enabled:',
                               'value'      => $value,
                               'license'    => $row,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'license_enable',
                               'flags'      => CCFF_NONE );
        }
        $this->AddFormFields($fields);
    }

}

/**
* Wrapper class for license information table
*
* This is just syntantic sugar on to of CCTable
*/
class CCLicenses extends CCTable
{
    /**
    * Constructor
    *
    */
    function CCLicenses()
    {
        $this->CCTable('cc_tbl_licenses','license_id');
        $this->AddExtraColumn('0 as license_checked');
    }

    /**
    * Returns static singleton of configs table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCLicenses();
        return( $_table );
    }

    /**
    * Get rows with enabled flag turned on (this is bogus and will change)
    *
    * @returns array $rows Returns CCLicense table object rows
    */
    function GetEnabled($looser_than = -1)
    {
        $configs =& CCConfigs::GetTable();
        $licenses = $configs->GetConfig('licenses');
        if( empty($licenses) )
        {
            $rows = $this->QueryRows("license_id = 'attribution'");
            return($rows);
        }
        $where = array();
        foreach($licenses as $lic)
            $where[] = "(license_id = '$lic')";
        $where = implode(' OR ' ,$where);
        if( $looser_than != -1 )
            $where = "($where) AND (license_strict <= $looser_than)";
        $rows = $this->QueryRows($where);
        if( empty($rows) )
            $rows = $this->QueryRows("license_id = 'attribution'");
        if( !empty($rows) )
            $rows[0]['license_checked'] = true;
        return( $rows );
    }
}

/**
* License event handlers and API
*
*/
class CCLicense
{
    /**
    * Event handler for getting id3 tagging macros
    *
    * @param array $record Upload record we're getting macros for (if null returns documentation)
    * @param array $file File record we're getting macros for 
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $masks Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$record, &$file, &$patterns, &$masks)
    {
        if( empty($record) )
        {
            $patterns['%license_url%'] = "License URL";
            $patterns['%license%']     = "License name";
        }
        else
        {
            if( !empty($record['license_url']) )
            {
                $patterns['%license_url%'] = $record['license_url'];
                $patterns['%license%'] = $record['license_name'];
            }

        }
    }

    /**
    * Event handler for CC_EVENT_GET_SYSTAGS 
    *
    * @param array $record Record we're getting tags for 
    * @param array $tags Place to put the appropriate tags.
    */
    function OnGetSysTags(&$record,&$file,&$tags)
    {
        if( !empty($record['upload_license']) )
        {
            $licenses =& CCLicenses::GetTable();
            $where['license_id'] = $record['upload_license'];
            $tags[] = $licenses->QueryItem('license_tag',$where);
        }
    }

    function OnFileDone(&$file)
    {
        if( !defined('IN_MIXTER_PORT') )
        {
            $sha1 = sha1_file( $file['local_path'] ) ;
            $file['file_extra']['sha1'] = $this->_hex_to_base32($sha1);
        }
    }

    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow( &$record )
    {
        if( empty($record['upload_license']) || empty($record['works_page']) )
            return;

        $record['year'] = substr($record['upload_date'],0,4);
        $record['start_comm'] = "<!--";
        $record['end_comm'] = "-->";

        $record['file_macros'][] = 'license_rdf';

    }

    /**
    * Event handler for building menus
    *
    */
    function OnAdminMenu(&$items, $scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'licenses'=> array('menu_text'  => 'License',
                                 'menu_group' => 'configure',
                                 'access'     => CC_ADMIN_ONLY,
                                 'help'   => 'Pick which licenses a user is allowed to select from',
                                 'weight'     => 16,
                                 'action'     => ccl('admin','license') )
                        );
    }

    /**
    * Show and process a form for admins to pick which licenses work on the site
    */
    function Admin()
    {
        CCPage::SetTitle("Configure Licenses");
        if( empty($_POST['adminlicense']) )
        {
            $form = new CCAdminLicenseForm(true);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form = new CCAdminLicenseForm(false);
            if( $form->ValidateFields() )
            {
                $form->GetFormValues($values);
                foreach( $values as $name => $value )
                {
                    if( !empty($value) )
                        $config_lics[] = $name;
                }
                $configs =& CCConfigs::GetTable();
                $configs->SaveConfig('licenses',$config_lics,'',false);
                CCPage::Prompt("Licenses Updated");
            }
        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/license',  array('CCLicense', 'Admin'), CC_ADMIN_ONLY  );
    }

    /**
    * Internal: used for hashing files
    */
    function _hex_to_base32($hex) 
    {
      $b32_alpha_to_rfc3548_chars = array(
        '0' => 'A',
        '1' => 'B',
        '2' => 'C',
        '3' => 'D',
        '4' => 'E',
        '5' => 'F',
        '6' => 'G',
        '7' => 'H',
        '8' => 'I',
        '9' => 'J',
        'a' => 'K',
        'b' => 'L',
        'c' => 'M',
        'd' => 'N',
        'e' => 'O',
        'f' => 'P',
        'g' => 'Q',
        'h' => 'R',
        'i' => 'S',
        'j' => 'T',
        'k' => 'U',
        'l' => 'V',
        'm' => 'W',
        'n' => 'X',
        'o' => 'Y',
        'p' => 'Z',
        'q' => '2',
        'r' => '3',
        's' => '4',
        't' => '5',
        'u' => '6',
        'v' => '7'
      );
      $b32_alpha = '';
      for ($pos = 0; $pos < strlen($hex); $pos += 10) {
        $hs = substr($hex,$pos,10);
        $b32_alpha_part = base_convert($hs,16,32);
        $expected_b32_len = strlen($hs) * 0.8;
        $actual_b32_len = strlen($b32_alpha_part);
        $b32_padding_needed = $expected_b32_len - $actual_b32_len;
        for ($i = $b32_padding_needed; $i > 0; $i--) {
          $b32_alpha_part = '0' . $b32_alpha_part;
        }
        $b32_alpha .= $b32_alpha_part;
      }
      $b32_rfc3548 = '';
      for ($i = 0; $i < strlen($b32_alpha); $i++) {
        $b32_rfc3548 .= $b32_alpha_to_rfc3548_chars[$b32_alpha[$i]];
      }
      return $b32_rfc3548;
    }
}



?>