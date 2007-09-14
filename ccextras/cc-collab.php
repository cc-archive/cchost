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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCCollab',  'OnMapUrls')         , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,        array( 'CCCollab', 'OnFormFields')      , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,        array( 'CCCollab', 'OnUploadDone')      , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,      array( 'CCCollab',  'OnUploadDelete')    , 'ccextras/cc-collab.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCCollabHV',  'OnUploadRow') );

class CCCollabHV 
{
    function OnUploadRow(&$record)
    {
        if( empty($record['upload_extra']['collab']) )
            return;

        $collab_id = $record['upload_extra']['collab'];
        require_once('ccextras/cc-collab.inc');
        $collabs = new CCCollabs();
        $record['collab'] = $collabs->QueryKeyRow($collab_id);
        $api = new CCCollab();
        $record['collab']['users'] = $api->_get_collab_users($collab_id);
        $record['collab']['base_purl'] = ccl('people');
    }
}

?>
