<?

/*
  $Id$
*/

CCEvents::AddAlias('lostpassword','atmlostpassword');
CCEvents::AddHandler( CC_EVENT_MAP_URLS, array('ATMLogin','OnMapUrls'), 'mixter-lib/mixter-lost-password.inc' );

?>