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
* Module for managing Creative Commons licenses
*
* @package cchost
* @subpackage feature
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-admin.php');
require_once('cchost_lib/cc-license.php');

/**
 * Form class for configuring licenses.
 *
* @package cchost
* @subpackage admin
 */
class CCAdminLicenseForm extends CCEditConfigForm
{
    /**
     * Constructor.
     *
     * Sets up fields as read from the license database.
     *
     * @access public
     * @param  boolean $populate True if fields are to be populated with values in the database
     */
    function CCAdminLicenseForm()
    {
        $this->CCEditConfigForm('licenses','',false);
        $this->SetModule(ccs(__FILE__));
 
        $licenses =& CCLicenses::GetTable();
        $licenses->SetOrder('license_name');
        $rows = $licenses->QueryRows('');
        $fields = array();
        foreach($rows as $row)
        {
            $fields[ $row['license_id'] ] = 
                       array( 'label'       => _('Enabled:'),
                               'license'    => $row,
                               'formatter'  => 'lic',
                               'flags'      => CCFF_POPULATE );
        }
        $this->AddFormFields($fields);
    }

    function generator_lic($name,$value='')
    {
        $args = func_get_args();
        $row = $this->GetFormFieldItem( $name, 'license' );
        $page =& CCPage::GetPage();
        $logourl = $page->URL('images/lics/'.$row['license_logo']);
        $checked = empty($value) ? '' : 'checked="checked"';
        $html =<<<EOF
<input type="checkbox" id="{$name}" name="{$name}" {$checked} />
        {$row['license_name']}<br />
<img src="{$logourl}" />
EOF;
        return $html;
    }

    function validator_lic()
    {
        return true;
    }
}


/**
* License event handlers and API
*
*/
class CCLicense
{
    /**
    * Event handler for {@link CC_EVENT_GET_MACROS}
    *
    * @param array &$record Upload record we're getting macros for (if null returns documentation)
    * @param array &$file File record we're getting macros for
    * @param array &$patterns Substituion pattern to be used when renaming/tagging
    * @param array &$mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$record, &$file, &$patterns, &$masks)
    {
        if( empty($record) )
        {
            $patterns['%license_url%'] = _("License URL");
            $patterns['%license%']     = _("License name");
        }
        else
        {
            if( empty($record['license_url']) )
            {
                if( !empty($record['upload_license']) )
                {
                    $licrow = CCDatabase::QueryRow(
                                'SELECT license_url, license_name FROM cc_tbl_licenses WHERE license_id="'.
                                $record['upload_license'] . '"');
                    $patterns['%license_url%'] = $licrow['license_url'];
                    $patterns['%license%'] = $licrow['license_name'];
                }
            }
            else
            {
                $patterns['%license_url%'] = $record['license_url'];
                $patterns['%license%'] = $record['license_name'];
            }

        }
    }

    /**
    * Event handler for {@link CC_EVENT_GET_SYSTAGS}
    *
    * @param array $record Record we're getting tags for 
    * @param array $file Specific file record we're getting tags for
    * @param array $tags Place to put the appropriate tags.
    */
    function OnGetSysTags(&$record,&$file,&$tags)
    {
        if( !isset($record) )
            return;

        if( empty($record['upload_license']) )
            $record['upload_license'] = CCDatabase::QueryItem('SELECT upload_license FROM cc_tbl_uploads WHERE upload_id='.$record['upload_id']);
        $licenses =& CCLicenses::GetTable();
        $where['license_id'] = $record['upload_license'];
        $tags[] = $licenses->QueryItem('license_tag',$where);
    }

    function OnFileDone(&$file)
    {
        if( !defined('IN_MIXTER_PORT') )
        {
            $sha1 = @sha1_file( $file['local_path'] ) ;
            $file['file_extra']['sha1'] = $this->_hex_to_base32($sha1);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items, $scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'licenses'=> array( 'menu_text'  => _('License'),
                                'menu_group' => 'configure',
                                'access'     => CC_ADMIN_ONLY,
                                'help'       => _('Pick which licenses a user is allowed to select from'),
                                'weight'     => 16,
                                'action'     => ccl('admin','license') )
                        );
    }

    /**
    * Show and process a form for admins to pick which licenses work on the site
    */
    function Admin()
    {
        require_once('cchost_lib/cc-page.php');
        $page =& CCPage::GetPage();
        $admin = new CCAdmin();
        $title = _("Configure Licenses");
        $page->SetTitle($title);
        $admin->BreadCrumbs(false,array( 'url' => '', 'text' => $title ));
        $page->SetTitle($title);
        $form = new CCAdminLicenseForm(true);
        $page->AddForm( $form->GenerateForm() );
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/license',  array('CCLicense', 'Admin'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Show admin license form'), CC_AG_MISC_ADMIN   );
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
