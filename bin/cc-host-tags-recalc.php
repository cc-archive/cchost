<?
error_reporting(E_ALL);

chdir( dirname(__FILE__) . '/..' );
define('IN_CC_HOST',1);
require_once('cchost_lib/cc-includes.php');
if( !function_exists('gettext') )
   require_once('cchost_lib/ccextras/cc-no-gettext.inc');
$cc_extras_dirs = 'cchost_lib/ccextras';
include('cchost_lib/cc-inc-extras.php');
require_once('cchost_lib/cc-custom.php');
require_once('cchost_lib/cc-template-api.php');

if( empty($CC_GLOBALS) )
    CCConfigs::Init();

CCDebug::Enable(true);

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS tptemp';
$sql[] = 'CREATE TABLE tptemp (tag_pair varchar(255),tag_pair_tag varchar(255) )';
$sql[] = 'DROP TABLE IF EXISTS tagtemp';
$sql[] = 'CREATE TABLE tagtemp (tag varchar(255))';

CCDatabase::Query($sql);

$qr       = CCDatabase::Query('SELECT upload_tags FROM cc_tbl_uploads');
$table    = new CCTable('tptemp','tag_pair');
$subtypes = array('remix','sample','acappella','contest_source','contest_sample','original','extended_mix','site_promo');
$filt     = array_merge($subtypes, array('media','audio'));
$cols     = array( 'tag_pair', 'tag_pair_tag ');

$ttable   = new CCTable('tagtemp','tag');
$tcols    = array( 'tag' );

$count = 0;
$misses = array();
while( $row = mysql_fetch_array($qr) )
{
    $tags  = array_filter(split(',',$row[0]));

    $tarr = array();
    foreach($tags as $T)
        $tarr[] = array($T);
    $ttable->InsertBatch($tcols,$tarr);
    
    $types = array_intersect($tags,$subtypes);
    $tags  = array_diff( $tags, $filt );
    if( empty($types) )
    {
        $misses[] = $row[0];
       
    }
    foreach( $types as $type )
    {
        $batch = array();
        foreach( $tags as $tag )
        {
            $batch[] = array( $type, $tag );
        }
        $table->InsertBatch( $cols, $batch );
    }
    if( ++$count % 100 == 0 )
    {
        print '.';
    }
}

print "\nCleaning up pair\n";
$sql = array();
$sql[] = 'DELETE FROM cc_tbl_tag_pair';
$sql[] =<<<EOF
        INSERT INTO cc_tbl_tag_pair
          SELECT tag_pair, tag_pair_tag, COUNT(*) as tag_pair_count
          FROM tptemp
          GROUP BY tag_pair,tag_pair_tag
EOF;
$sql[] = 'DROP TABLE tptemp';
$sql[] = 'UPDATE cc_tbl_tags SET tags_count = 0';
CCDatabase::Query($sql);

print "recalculating tag counts\n";
$tagt = new CCTable('cc_tbl_tags','tags_tag');
$qr = CCDatabase::Query('SELECT COUNT(*) as tags_count, tag as tags_tag FROM tagtemp GROUP BY tag');
while( $row = mysql_fetch_assoc($qr) )
{
    $tagt->Update($row,true);
}
$sql = array();
$sql[] = 'DELETE FROM cc_tbl_tags WHERE tags_count < 1';
$sql[] = 'DROP TABLE tagtemp';
$sql[] = 'UPDATE cc_tbl_tags SET tags_tag = LOWER(tags_tag)'; // some tags ended up upper case which breaks INSERT
CCDatabase::Query($sql);

//print "missing: ";
//var_dump($misses);

print "done\n";

?>