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
* $Id: cc-mail.inc 5180 2007-02-06 01:16:33Z fourstones $
*
*/

/**
* @package cchost
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-license.inc');

class CCLicensefiles
{

    function Licensefiles($name,$id)
    {
        $files =& CCFiles::GetTable();

        $uploads =& CCUploads::GetTable();
        $row = $uploads->QueryKeyRow($files->QueryItemFromKey('file_upload',$id));

        $people_dir = empty($CC_GLOBALS['user-upload-root']) ? 'people' :
                           $CC_GLOBALS['user-upload-root'];

        $file_name = $files->QueryItemFromKey('file_name',$id);
        $file_dir = $people_dir . '/' . $name . '/' . $file_name;

        $licenses =& CCLicenses::GetTable();
        $license = $licenses->QueryKeyRow($row['upload_license']);

        $rdf='<rdf:RDF
   xmlns="http://web.resource.org/cc/"
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
>
   <Work rdf:about="http://www.openclipart.org/incoming/2004/04/contents/barcode_ean13.svg">
      <dc:title>'.$row['upload_name'].'</dc:title>
      <dc:date>'.substr($row['upload_date'],0,4).'</dc:date>
      <dc:description>'.$row['upload_description'].'</dc:description>
      <dc:creator><Agent>
          <dc:title>'.$row['user_real_name'].'</dc:title>
      </Agent></dc:creator>
      <dc:rights><Agent>
          <dc:title>'.$row['user_real_name'].'</dc:title>
      </Agent></dc:rights>
      <license rdf:resource="'.$license['license_url'].'" />
   </Work>
       <Work rdf:about="urn:sha1:'.CCLicense::_hex_to_base32( sha1_file( $file_dir ) ).'">
          <license rdf:resource="'.$license['license_url'].'" />
       </Work>
   <License rdf:about="'.$license['license_url'].'">';

    $permits = explode(',',$license['license_permits']);

    foreach( $permits as $pt )
    {
        $rdf .= "\n" . '      <permits rdf:resource="http://web.resource.org/cc/'.$pt.'" />';
    }

    $required = explode(',',$license['license_required']);

    foreach( $required as $rd )
    {
        $rdf .= "\n" . '      <permits rdf:resource="http://web.resource.org/cc/'.$rd.'" />';
    }

    $prohibits = explode(',',$license['license_prohibits']);

    foreach( $prohibits as $ph )
    {
        $rdf .= "\n" . '      <prohibits rdf:resource="http://web.resource.org/cc/'.$ph.'" />';
    }

   $rdf .= "\n" . '   </License>
</rdf:RDF>';

        return $rdf;
    }

    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['license'] =
               array(  'label'      => _('License'),
                       'form_tip'   => _('Download files with license.'),
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
        }
    }

}

?>
