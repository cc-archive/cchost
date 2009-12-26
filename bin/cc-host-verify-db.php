<?

/* $Id$ */

define('CC_HOST_CMD_LINE', 1 );
$admin_id = 9;
chdir( dirname(__FILE__) . '/..');
require_once('cc-cmd-line.inc');
require_once('cchost_lib/cc-uploadapi.php');

//-------------------------------------
// Check for files that are owned by 
// users that have been deleted
//-------------------------------------
$sql = "SELECT upload_id,upload_user FROM cc_tbl_uploads LEFT OUTER JOIN cc_tbl_user ON upload_user = user_id WHERE ISNULL(user_id) ORDER BY upload_id";
$rows = CCDatabase::QueryRows($sql);

if( empty($rows) )
{
    print "No orphaned uploads\n";
}
else
{
    $ids = array();
    foreach( $rows as $row )
    {
        $ids[] = $row['upload_user'];
    }
    $ids = array_unique($ids);
    print "Orphaned uploads found(!)\n";
    delete_orphaned_files_for_user_ids($ids);
}
        
function delete_orphaned_files_for_user_ids($ids)
{
    foreach( $ids as $user_id )
    {
        print "\nDeleting uploads for user_id: {$user_id}\n";
        $upload_ids = CCDatabase::QueryItems('SELECT upload_id FROM cc_tbl_uploads WHERE upload_user = ' . $user_id );
        foreach( $upload_ids as $upload_id )
        {
            $upload_name = CCDatabase::QueryItem('SELECT upload_name FROM cc_tbl_uploads WHERE upload_id = ' . $upload_id);
            $files = CCDatabase::QueryRows('SELECT * FROM cc_tbl_files WHERE file_upload = ' . $upload_id);
            if( empty($files) )
            {
                print "No files found for [{$upload_id}]: {$upload_name}\n";
            }
            else
            {
                print "Deleting files found for [{$upload_id}]: {$upload_name}\n";
                foreach( $files as $F )
                {
                    $fname = $F['file_name'];
                    preg_match('/^([^_]+)_/',$fname,$m);
                    $user_name = $m[1];
                    $path = 'content/' . $user_name . '/' . $fname;
                    print "  file: {$path}\n";
                    @unlink($path);
                }
                CCDatabase::Query('DELETE FROM cc_tbl_files WHERE file_upload = ' . $upload_id );
            }
        }
        delete_playlist_entries_for_orphaned_uploads($upload_ids);
        delete_reviews_for_orphaned_uploads($upload_ids);
        delete_trackbacks_for_orphaned_uploads($upload_ids);
        $inset = join( ', ', $upload_ids);
        $sql = "DELETE FROM cc_tbl_uploads WHERE upload_id IN ({$inset})";
        CCDatabase::Query($sql);
    }
    
}

function delete_reviews_for_orphaned_uploads($ids)
{
    
}

function delete_playlist_entries_for_orphaned_uploads($ids)
{
    
}

function delete_trackbacks_for_orphaned_uploads($ids)
{
    
}

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