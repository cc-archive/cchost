<?
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCChecksum
{

    function Checksum($path)
    {
        return (md5_file($path));
    }

    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['checksum'] =
               array(  'label'      => _('Checksum'),
                       'form_tip'   => _('Download files with checksum.'),
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
        }
    }

}
?>
