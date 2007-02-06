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
* Class for interfacing with 3rd part web tools
*
*/
class CCSkinExport
{

    /**
    * Method called by cchost customized skins to output ccHost look/feel.
    *
    * The call to this method is made directly from the ccHost template
    * look in 3rd party web tools (like WordPress and phpBB2)
    *
    * To see what this method returns type: http://yourserver/media/skin/getheader
    * and http://yourserver/media/skin/getheader/simple into your browser.
    *
    * put a call to this between head tags
    *
    * @param string $simple tool may have a 'simple' version which means no adornments
    */
    function GetHeader($simple='')
    {
        $page =& CCPage::GetPage();
        $page->ShowHeaderFooter( !$simple, false );
        
        $html = $page->Show(false);
        //CCDebug::PrintVar($html,false);
        preg_match('%(<link.*)</head>%Usi',$html,$m);
        $html = $m[1];
        $this->_out_html_and_exit($html);
    }

    /**
    * Called by cchost customized skin 
    *
    * Put a call to this right after the <body> tag
    *
    */
    function GetBodyHeader($simple='')
    {
        $page =& CCPage::GetPage();
        $page->ShowHeaderFooter( !$simple, false );
        $naviator_api = new CCNavigator();
        $naviator_api->ShowTabs($page);
        $html = $page->Show(false);
        preg_match('%<body>(.*)<!-- CONTENT STARTS -->%si',$html,$m);
        $html = $m[1];
        $this->_out_html_and_exit($html);
    }

    /**
    * Method called by cchost customized skins to output ccHost look/feel.
    *
    * The call to this method is made directly from the ccHost templates
    * for 3rd party tools like WordPress and phpBB2
    *
    * To see what this method returns type: http://yourserver/media/skin/getfooter
    * and http://yourserver/media/skin/getfooter/simple into your browser.
    *
    * Put a call to this right before the </body> tag
    *
    * @param string $simple tool may have a 'simple' version which means no adornments
    */
    function GetFooter($simple='')
    {
        $page = new CCPage();
        $page->ShowHeaderFooter( false, !$simple );
        $page->AddScriptLink( ccd('cctemplates','dl_popup.js'), false );
        $html = $page->Show(false);
        preg_match('%<!-- CONTENT ENDS -->(.*)</body>%si',$html,$m);
        $html = $m[1];
        $this->_out_html_and_exit($html);
    }


    function _out_html_and_exit($html)
    {
        $html = addslashes($html);
        $lines = split("\n",$html);

        header("Content-type: text/plain");
        foreach($lines as $line)
        {
            $line = trim($line);
            if( $line )
                print( " document.writeln('$line');\n ");
        }

        exit;
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('skin','head'),   
                          array( 'CCSkinExport', 'GetHeader'),    
                          CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));

        CCEvents::MapUrl( ccp('skin','body_begin'),   
                          array( 'CCSkinExport', 'GetBodyHeader'),    
                          CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));

        CCEvents::MapUrl( ccp('skin','body_end'),   
                          array( 'CCSkinExport', 'GetFooter'),    
                          CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));
    }

}

?>