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

require_once('cclib/cc-license.inc');

class CCLicensefiles
{

    function Licensefiles($name,$id)
    {
        $files =& CCFiles::GetTable();

        $uploads =& CCUploads::GetTable();
        $row = $uploads->QueryKeyRow($files->QueryItemFromKey('file_upload',$id));

        $people_dir = empty($CC_GLOBALS['user-upload-root']) ? 'content' :
                           $CC_GLOBALS['user-upload-root'];

        $file_name = $files->QueryItemFromKey('file_name',$id);
        $file_dir = $people_dir . '/' . $name . '/' . $file_name;

        $file_format_info = unserialize( $files->QueryItem('file_format_info','file_id = ' . $id) );

        $licenses =& CCLicenses::GetTable();
        $license = $licenses->QueryKeyRow($row['upload_license']);

        $rdf='<rdf:RDF
   xmlns="http://web.resource.org/cc/"
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
>
   <Work rdf:about="">
      <dc:title>'.$row['upload_name'].'</dc:title>
      <dc:description>'.$row['upload_description'].'</dc:description>
      <dc:subject>'.$row['upload_tags'].'</dc:subject>
      <dc:creator><Agent><dc:title>'.$row['user_real_name'].'</dc:title></Agent></dc:creator>
      <dc:rights><Agent><dc:title>'.$row['user_real_name'].'</dc:title></Agent></dc:rights>
      <dc:date>'.substr($row['upload_date'],0,10).'</dc:date>
      <dc:format>'.$file_format_info['mime_type'].'</dc:format>
      <dc:type></dc:type>
      <license rdf:resource="'.$license['license_url'].'" />
   </Work>
   <License rdf:about="'.$license['license_url'].'">';

    $permits = explode(',',$license['license_permits']);

    foreach( $permits as $pt )
    {
        if( !$pt )
            $rdf .= "\n" . '      <permits rdf:resource="http://web.resource.org/cc/'.$pt.'" />';
    }

    $required = explode(',',$license['license_required']);

    foreach( $required as $rd )
    {
        if( !$rd )
            $rdf .= "\n" . '      <permits rdf:resource="http://web.resource.org/cc/'.$rd.'" />';
    }

    $prohibits = explode(',',$license['license_prohibits']);

    foreach( $prohibits as $ph )
    {
        if( !$rdf )
            $rdf .= "\n" . '      <prohibits rdf:resource="http://web.resource.org/cc/'.$ph.'" />';
    }

   $rdf .= "\n" . '   </License>
</rdf:RDF>';

        return $rdf;
    }
}

?>
