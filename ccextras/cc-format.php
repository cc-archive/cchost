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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCFormat', 'OnFormFields'), 'ccextras/cc-format.inc' );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCFormat', 'OnMapUrls'), 'ccextras/cc-format.inc' );
CCEvents::AddHandler(CC_EVENT_USER_ROW,       array( 'CCFormat', 'OnUserRow'), 'ccextras/cc-format.inc' );
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,     array( 'CCFormat', 'OnUploadRow'), 'ccextras/cc-format.inc' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCFormat' , 'OnGetConfigFields'), 'ccextras/cc-format.inc'  );
CCEvents::AddHandler(CC_EVENT_TOPIC_ROW,     array( 'CCFormat' , 'OnTopicRow'), 'ccextras/cc-format.inc'  );


function generator_cc_format($form, $fieldname, $value, $class )
{
    require_once('ccextras/cc-format.inc');
    return _generator_cc_format($form, $fieldname, $value, $class );
}

/**
* Called from templates to test/convert bbCode-lite text to HTML formatted
*
*/
function cc_format_text($text)
{
    if( _cc_is_formatting_on() )
        return _cc_format_format($text);
    return $text;
}

function validator_cc_format($form, $fieldname)
{
    return $form->validator_textarea($fieldname);
}

function _cc_can_format_edit()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['format']) ||
           (!empty($CC_GLOBALS['adminformat']) && CCUser::IsAdmin());
}

function _cc_is_formatting_on()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['format']) ||
           !empty($CC_GLOBALS['adminformat']);
}

?>
