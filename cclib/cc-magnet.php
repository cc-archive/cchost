<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCMagnetLink' , 'OnGetConfigFields' ));
CCEvents::AddHandler(CC_EVENT_APP_INIT,           array( 'CCMagnetLink',  'OnAppInit'         ));
CCEvents::AddHandler(CC_EVENT_FILE_DONE,          array( 'CCMagnetLink',  'OnUploadDone'      ));
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCMagnetLink',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCMagnetLink',  'OnUploadMenu'));

/**
* Support for Magnet linking API
*/
class CCMagnetLink
{
    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['bitcollider'] =
               array(  'label'      => 'Full path to Bitcollider',
                       'form_tip'   => 'Used to make <a href="http://www.magnetlinks.org/">Magnet Links</a>.',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE);
        }
    }

    function OnUploadDone( &$file )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['bitcollider']) )
            return;

        $b = new Bitcollider();
        $prog_path =  $CC_GLOBALS['bitcollider'];
        $b->set_program_location($prog_path);
        $b->set_calculate_md5(true);
        $b->analyze_file( $file['local_path'] );
        $file['file_extra']['magnet'] = $b->get_magnetlink( $file['download_url'] );
    }

    /**
    * Event handler for CC_EVENT_BUILD_UPLOAD_MENU
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['magnetlinks'] = 
                 array(  'menu_text'      => '',
                         'weight'         => 90,
                          'group_name'    => 'download_share',
                         'id'             => 'magnetlinks',
                         'access'         => CC_DISABLED_MENU_ITEM );
    }

    /**
    * Event handler for CC_EVENT_UPLOAD_MENU
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu
    */
    function OnUploadMenu(&$menu,&$record)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['bitcollider']) )
            return;

        $downloads = array();
        foreach( $record['files'] as $file )
        {
            if( empty($file['file_extra']['magnet']) )
                continue;

            $downloads[] = array(
                            'action'    => $file['file_extra']['magnet'],
                            'menu_text' => $file['file_nicname'],
                            'group_name' => 'download_share',
                            'tip'       => 'P2P Download and share',
                            'id'        => 'magnetbutton',
                            );
        }

        if( !empty($downloads) )
        {
            $menu['magnetlinks']['repeataction']  = $downloads;
            $menu['magnetlinks']['access'] = CC_DONT_CARE_LOGGED_IN;
        }
    }

    /**
    * Called in response to application initialization
    *
    * Includes bitcollider API if user requested magnet support
    */
    function OnAppInit()
    {
        global $CC_GLOBALS;

        if( !empty($CC_GLOBALS['bitcollider']) )
        {
            require_once('cclib/bitcollider/Bitcollider.php');
        }
    }
}
 







?>