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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCBlogPing',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_DONE,    array( 'CCBlogPing',  'OnUploadDone'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,     array( 'CCBlogPing' , 'OnAdminMenu') );

/**
* Form for configuration the file format verification module
*
*/
class CCAdminBlogPingForm extends CCEditConfigForm
{
    /**
    * Constructor
    *
    */
    function CCAdminBlogPingForm()
    {
        $this->CCEditConfigForm('blog-ping');

        $fields = array(
                'pingurl' =>
                        array(  'label'      => "Ping URL:",
                               'form_tip'    => 'Starts with http://',
                               'formatter'   => 'textedit',
                               'flags'       => CCFF_POPULATE )
            );

        $this->AddFormFields( $fields );
    }
}

/**
*
*
*/
class CCBlogPing
{

    function Admin()
    {
        CCPage::SetTitle("Administer Blog Notification");
        $form = new CCAdminBlogPingForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Event handler for admin building
    *
    */
    function OnAdminMenu(&$items,$scope)
    {
        return; // not implemented yet.

        if( $scope != CC_GLOBAL_SCOPE )
        {
            $items += array(
                'blog-ping'   => array( 
                                 'menu_text'  => 'Blog ping',
                                 'menu_group' => 'configure',
                                 'help' => 'Ping your blog on new uploads',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 40,
                                 'action' =>  ccl('admin','ping')
                                 ),
                );
        }
    }


    /**
    * Event hander to clear the feed cache
    * 
    * @param integer $fileid Database ID of file
    */
    function OnUploadDone($upload_id, $type)
    {
        if( $type != CC_UF_NEW_UPLOAD )
            return;

        $configs =& CCConfigs::GetTable();
        $ping_settings = $configs->GetConfig('blog-ping');
        if( empty( $ping_settings ) || empty( $ping_settings['pingurl'] ) )
            return;

        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromKey($upload_id);

        if( empty($record) )
            return;

        $URL_to_ping = $ping_settings['pingurl'];

        // At this point the $record holds all the information
        // for the newly uploaded item, use 
        
        // CCDebug::PrintVar($record);

        // to dump the contents

        // also see http://beta.cmixter.org/cctools/apidoc/record_dump.html

        global $CC_GLOBALS;

        /*
        $template = new CCTemplate( $CC_GLOBALS['template-root'] . 'blog_ping.xml', false ); // false means XML
        $args = array( 'something' => 'value' );
        $xml = $template->SetAllAndParse($args);
        */

        // I use Snoopy because I was told not to trust curl (not
        // to mention curl is not compiled into many php installations)

        require_once('cclib/magpie/extlib/Snoopy.class.inc');
        $snoopy = new Snoopy();
        
        // This does a GET:

        // $ok = $snoopy->fetch($URL_to_ping);

        // This does a POST:

        // $post_vars = array( 'data' => $xml );
        // $ok = $snoopy->Submit($URL_to_ping, $post_vars);

        CCPage::Prompt("Blog ($URL_to_ping) pinged for {$record['upload_name']}");
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','ping'),  array( 'CCBlogPing', 'Admin'), CC_ADMIN_ONLY );
    }

}



?>