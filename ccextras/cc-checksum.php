<?
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS, array( 'CCChecksum' , 'OnGetConfigFields') , 'ccextras/cc-checksum.inc' );
?>
