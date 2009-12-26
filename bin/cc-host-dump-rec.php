<?
/* $Id */

define('CC_HOST_CMD_LINE', 1 );
$admin_id = 9;
chdir( dirname(__FILE__) . '/..');
require_once('cc-cmd-line.inc');

dump_row('SELECT * FROM cc_tbl_uploads WHERE upload_id = ' . $argv[1] );
dump_rows('SELECT * FROM cc_tbl_files WHERE file_upload = ' . $argv[1] );

function dump_rows($sql)
{
    $rows = CCDatabase::QueryRows($sql);
    foreach( $rows as $R )
        _dump_row($R);
}

function dump_row($sql)
{
    
    $row = CCDatabase::QueryRow($sql);
    _dump_row($row);
}

function _dump_row($row)
{
    if( empty($row) )
        die('No record there');
        
    foreach( $row as $K => $V )
    {
        print "[{$K}] $V\n";
    }
}
?>