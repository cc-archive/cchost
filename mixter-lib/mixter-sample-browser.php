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
* $Id: mixter.php 3245 2006-04-06 07:21:19Z fourstones $
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCSampleBrowser' , 'OnMapUrls') );

class CCSampleBrowser
{
    function _delegate($method,$arg='')
    {
        require_once('mixter-lib/mixter-sample-browser.inc');
        $api = new CCSampleBrowserAPI();
        $api->$method($arg);
    }

    function SamplesList()
    {
        $this->_delegate('SamplesList');
    }

    function SamplesBrowse()
    {
        $this->_delegate('SamplesBrowse');
    }

    function SamplesStream($file_id)
    {
        $this->_delegate('SamplesStream',$file_id);
    }

    function SamplesSearch()
    {
        $this->_delegate('SamplesSearch');
    }

    function OnMapUrls()
    {

        CCEvents::MapUrl( ccp('samples'),   array('CCMixter', 'Samples'),  
                            CC_DONT_CARE_LOGGED_IN );

        CCEvents::MapUrl( ccp('samples','list'),   array('CCSampleBrowser', 'SamplesList'),  
                            CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( ccp('samples','browse'),   array('CCSampleBrowser', 'SamplesBrowse'),  
                            CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( ccp('samples','stream'),   array('CCSampleBrowser', 'SamplesStream'),  
                            CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( ccp('samples','search'),   array('CCSampleBrowser', 'SamplesSearch'),  
                            CC_DONT_CARE_LOGGED_IN );
    }
}

?>