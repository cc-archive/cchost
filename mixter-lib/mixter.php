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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMixter',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMixter' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_APP_INIT,     array( 'CCMixter' , 'OnAppInit') );

class CCMixter
{

    function OnAppInit()
    {
        global $CC_GLOBALS;

        if( CCUser::IsAdmin() && !empty($_REQUEST['closeccc']) )
        {
            if( file_exists('mixter-lib/close-criminals.inc') )
            {
                require_once('mixter-lib/close-criminals.inc');
                _kill_ccc_contest($_REQUEST['closeccc']);
            }
            else
            {
                CCPage::Prompt('cound not find .inc file');
            }
        }
    }

    function Samples()
    {
        $sample_link = $song_link = $all_link = false;

        if( !empty($_REQUEST['tags']) )
            $tags = CCUtil::StripText($_REQUEST['tags']);

        if( empty($tags) )
            $tags = 'sample';

        $base = ccl('view','media','samples');

        $args['sample']   = array( 'text' => 'samples only',
                                    'normal' => true,
                                    'selected' => false,
                                    'action' => url_args( $base, 'tags=sample' ) ) ;

        $args['sample original']   = array( 'text' => 'samples &amp; fully mixed tracks', 
                                    'selected' => false,
                                    'normal' => true,
                                    'action' => url_args( $base, 'tags=sample+original' ) );

        $args['original']   = array( 'text' => 'fully mixed tracks only',
                                    'selected' => false,
                                    'normal' => true,
                                    'action' => url_args( $base, 'tags=original' ) );

        if( !empty($args[$tags]) )
        {
            $args[$tags]['selected'] = true;
            $args[$tags]['normal'] = false;
        }


        $show_plugs = empty($_REQUEST['offset']);

        CCPage::PageArg('show_plugs',$show_plugs);
        CCPage::PageArg('samples_links',$args);

        $tagapi = new CCTag();
        $tagapi->BrowseTags($tags,'any');

            CCPage::ViewFile('samples.xml');
    }

    function OnBuildMenu()
    {
        $items = array(
            'mixterfaq' => array (
                'menu_text' => 'Is It Legal? (FAQ)',
                'access'    => CC_DONT_CARE_LOGGED_IN,
                'menu_group'=> 'visitor',
                'weight'    => 100,
                'action'    => ccl('viewfile','isitlegal.xml')
                )
            );

        CCMenu::AddItems($items);
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('samples'),   array('CCMixter', 'Samples'),  CC_DONT_CARE_LOGGED_IN );
    }


}

?>