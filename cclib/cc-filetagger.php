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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCID3Tagger', 'OnAdminMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCID3Tagger', 'OnMapUrls') );

$CC_ID3_TAGGER = new CCID3Tagger();

/**
* Admin form for upload ID3 tagging rules
*/
class CCAdminTaggerForm  extends CCEditConfigForm
{
    /**
    * Constructor
    *
    * Every module in the system has the opportunity to participate in the ID3 tagging
    * rules by responding to CC_EVENT_GET_MACROS event (triggered by this method).
    * In this case the $record field will be blank and therefore the documentation
    * for each tagging macro is expected back.
    *
    */
    function CCAdminTaggerForm()
    {
        $this->CCEditConfigForm('id3-tag-masks');

        $fields = array();

        $standard_tags =& CCID3Tagger::_get_standard_tags();
        $this->AddFormFields( $standard_tags );

        // Help...
        $patterns['%title%'] = "Title";
        $patterns['%site%']  = "Site name";
        $dummy = array();
        CCEvents::Invoke( CC_EVENT_GET_MACROS, array( $dummy, $dummy, &$patterns, $dummy ) );
        ksort($patterns);
        $this->CallFormMacro('macro_patterns','show_macro_patterns',$patterns);
    }

}

/**
* ID3 Tagging policy API
*
*/
class CCID3Tagger 
{
    /**
    * Method that does the ID3 tagging according to rules set by user
    *
    * Every module in the system has the opportunity to participate in the ID3 tagging
    * rules by responding to CC_EVENT_GET_MACROS event (triggered by this method).
    * All respondents are responsible for retuning the macros as
    * well as the value associated with the upload record.
    *
    * This method is called by checking for the global '$CC_ID3_TAGGER' and then
    * calling $CC_ID3_TAGGER->TagFile($record).
    *
    *
    * <code>
        
    // get $record from database or user filled out form...

    if( isset($CC_ID3_TAGGER) )
    {
        $errors = $CC_ID3_TAGGER->TagFile($record,$local_path);
        if( !empty($errors) )
        {
            $error_text = implode('<br />',$errors);
            print($error_text);
        }
    }

    * </code>
    *
    * @see CCUploadAPI::PostProcessNewUpload
    * @param array $record Database record of upload
    * @returns array $errors Array of errors found, otherwise null on success
    */
    function TagFile(&$record,$local_path)
    {
        global $CC_GLOBALS;

        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');

        $patterns['%title%'] = $record['upload_name'];
        $patterns['%site%']  = $ttags['site-title'];
        $dummy = '';
        CCEvents::Invoke( CC_EVENT_GET_MACROS, array( &$record, $dummy, &$patterns, $dummy ) );

        $tagmasks = $configs->GetConfig('id3-tag-masks');
        $tags = array();

        foreach( $tagmasks as $name => $mask )
        {
            $value     = CCMacro::TranslateMask($patterns,$mask);
            if( !empty($value) )
                $tags[$name] = array( $value );
        }

        if( count($tags) > 0 )
        {
            CCDebug::QuietErrors();
            $debug = CCDebug::Enable(false);

            $id3 =& CCGetID3::InitID3Obj();
            getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

            $tagwriter = new getid3_writetags;
            $tagwriter->filename       = $local_path;
            if( $CC_GLOBALS[CCGETID3_ENABLED_ID3V1] )
                $tagwriter->tagformats = array( "id3v1", "id3v2.3" );
            else
                $tagwriter->tagformats = array( "id3v2.3"  );
            $tagwriter->overwrite_tags = true;
            $tagwriter->tag_data = $tags;

            $res = $tagwriter->WriteTags();

            CCDebug::Enable($debug);
            CCDebug::RestoreErrors();
            return($res);
        }

        return(null);
    }

    /**
    * Internal goody
    */
    function & _get_standard_tags()
    {
        $standard_tags = array();

        $standard_tags['title'] =
                   array(  'label'       => "Title",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => '%title%');

        $standard_tags['artist'] =
                   array(  'label'       => "Artist",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => '%artist%');

        $standard_tags['copyright'] =
                   array(  'label'       => "Copyright",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%Y% %artist% Licensed to the public under ".
                                            "%license_url% Verify at %song_page%");

        $standard_tags['original_artist'] =
                   array(  'label'       => "Original Artist",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%source_artist%");

        $standard_tags['remixer'] =
                   array(  'label'       => "Remixer",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%artist%");

        $standard_tags['year'] =
                   array(  'label'       => "Year",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%Y%");

        $standard_tags['album'] =
                   array(  'label'       => "Album",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%site%");
/*
        $standard_tags['url_file'] =
                   array(  'label'       => "File URL",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => '%url%' ); // bug in getid3 makes this a pain
*/
        $standard_tags['url_user'] =
                   array(  'label'       => "Artist's URL",
                           'flags'       => CCFF_POPULATE,
                           'formatter'   => 'textedit',
                           'value'       => "%artist_page%");

        return( $standard_tags );
    }


    /**
    * Event handler for building admin menus
    *
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
            return;

        $items += array( 
            'id3-tag-masks'   => array( 'menu_text'  => 'ID3 Tagger',
                             'menu_group' => 'configure',
                             'help' => 'Configure how to tag ID3 compatible files (e.g. MP3s)',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 60,
                             'action' =>  ccl('admin','id3tags')
                             ),
            );

    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/id3tags',  array('CCID3Tagger', 'AdminTagger'), CC_ADMIN_ONLY );
    }

    /**
    * Handler for admin/id3tags - puts up form
    *
    * @see CCAdminTaggerForm::CCAdminTaggerForm
    */
    function AdminTagger()
    {
        CCPage::SetTitle("Configure ID3 Tagger");
        $form = new CCAdminTaggerForm($this);
        CCPage::AddForm( $form->GenerateForm() );
    }

}

?>