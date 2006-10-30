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

CCEvents::AddHandler(CC_EVENT_USER_ROW,           array( 'CCPublicize', 'OnUserRow') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPublicize', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPublicize' , 'OnGetConfigFields') );

/**
*/
class CCPublicize
{
    function Publicize($user='')
    {
        global $CC_GLOBALS;

        if( empty($user) )
        {
            if( !($user = CCUser::CurrentUserName()) )
            {
                CCPage::Prompt(_('Don\'t know what user to publicize!'));
                return;
            }
        }
    
        $itsme  = $user == CCUser::CurrentUserName();

        if( !$this->_pub_wizard_allowd($itsme) )
        {
            CCPage::Prompt(_('This feature is not enabled here'));
            return;
        }

        $users  =& CCUsers::GetTable();
        $record =& $users->GetRecordFromName($user);
        $args = $record;
        if( $itsme )
        {
            $args['intro'] = _('Do you have a blog or web page? You can display a list of up-to-the-minute links to your latest remixes directly on your page.');
            $args['yourremixes'] = _('Your remixes');
            $args['othersremixes'] = _('Other peoples\'s remixes of you.');
            $args['allyourups'] = _('All your uploads');
            $title = _('Publicize Yourself');
        }
        else
        {
            $args['intro'] = sprintf( _('Do you have a blog or web page? You can display a list of up-to-the-minute links to %s\'s latest remixes directly on your page.'), 
                            '<b>' . $record['user_real_name'] . '</b>' );
            $args['yourremixes'] = sprintf( _('%s\'s remixes'), $record['user_real_name'] );
            $args['othersremixes'] = sprintf( _('Other peoples\'s remixes of %s'),
                                         $record['user_real_name'] );
            $args['allyourups'] = sprintf( _('All %s\'s uploads'), $record['user_real_name'] );
            $title = sprintf( _('Publicize %s'), $record['user_real_name'] );
        }

        $args['step1'] = _('1. Select from these options:');
        $args['step2'] = _('2. Then copy the text from this field and paste into your page:');

        $args['typeoflinks'] = _('Type of links:');
        $args['numlinks']    = _('Number of links:');
        $args['format']      = _('Format:');
        
        $args['justone'] = _('Just the very latest one');
        $args['last5']   = _('The 5 latest');
        $args['last10']  = _('The 10 latest');
        $args['abunch']  = _('A whole bunch (up to a 50)');
        

        $args['plainlinks']   = _('Plain links');
        $args['linkswithby']  = _('Links with attribution');
        $args['linkswstream'] = _('Links with a stream link');
        $args['linkswdl']     = _('Links with a download link');
        $args['linksmed']     = _('Verbose (!)');
        $args['chophelp']     = _('Cut off links if they are larger than:');
        
        $args['chars10']     = _('10 characters');
        $args['chars20']     = _('20 characters');     
        $args['chars25']     = _('25 characters');     
        $args['dontchop']    = _('Don\'t do any chopping');

        $args['seehtml']       = _('Show raw HTML');
        $args['showformatted'] = _('Show Formatted');
        
        $args['preview'] = _('Preview');
        $args['previewwarn'] = _('This preview has been pre-formatted, how this will actually 
                                  look on your web page will change depending on your style settings.');
        $args['htmlwarn'] = _('Make sure to copy from the box above, not what is showing below because 
                               the actual content of this HTML will change based on the upload activity. 
                               You can still get an idea of what of the formatting will look
                               like here:');

        $args['extra_formats'] = array();
        if( !empty($CC_GLOBALS['pubwizex']) )
        {
            $exformats = preg_split('/\s*,\s*/',$CC_GLOBALS['pubwizex']);
            foreach($exformats as $exformat )
            {
                $file = $this->_find_fmt_template($exformat);
                if( $file )
                {
                    $text = file_get_contents($file);
                    if( preg_match('/FORMAT_NAME\s+_\([\'"](.*)[\'"]\);/U',$text,$m) )
                    {
                        $name = $m[1];
                    }
                    else
                    {
                        $name = $text;
                    }
                    $args['extra_formats'][] = array( 'format' => $exformat, 'name' => _($name) );
                }
            }
        }

        CCPage::SetTitle( $title );
        CCPage::AddLink('head_links', 'stylesheet', 'text/css', 
            ccd('cctemplates/publicize.css'), 'Default Style');
        CCPage::PageArg('publicize', 'publicize.xml/publicize');
        CCPage::PageArg('PUB', $args, 'publicize');
    }

    function _find_fmt_template($name)
    {
        $trythese = array( $name, 
                           'formats/' . $name . '.xml',
                           $name . '.xml',
                           'formats/' . $name );

        foreach( $trythese as $trythis )
            if( ($file = CCTemplate::GetTemplate($trythis)) )
                return $file;

        return false;
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
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
            $options = array(
                CC_DONT_CARE_LOGGED_IN => _('Everybody'),
                CC_MUST_BE_LOGGED_IN   => _('Current User Only'),
                0                      => _('Nobody (Turn this feature off)'),
                );

            $fields['pubwiz'] =
               array(  'label'      => _('Show "Publicize Wizard" to'),
                       'form_tip'   => _('Allows visitors to create HTML snippets for their blogs'),
                       'weight'      => 600,
                       'options'    => $options,
                       'formatter'  => 'select',
                       'flags'      => CCFF_POPULATE );

            $fields['pubwizex'] =
               array(  'label'      => _('Extra publicize formats'),
                       'form_tip'   => _('Comma separated format templates. These can be in a \'formats\' directory in your Skins path. (e.g. mplayer, my_links, my_big_links)'),
                       'weight'      => 601,
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE );
        }

    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$row)
    {
        if( empty($row['artist_page']) )
            return;

        $itsme = CCUser::CurrentUser() == $row['user_id'];

        if( $this->_pub_wizard_allowd($itsme) )
        {
            $url = ccl('publicize',$row['user_name'] );
            $text = $itsme ? _('Publicize yourself')
                           : sprintf( _('Publicize %s'), $row['user_real_name'] );
                
            $row['user_fields'][] = array( 'label' => _('Publicize'), 
                                       'value' => "<a href=\"$url\">$text</a>" );
        }
    }

    function _pub_wizard_allowd($itsme)
    {
        global $CC_GLOBALS;

        return !empty($CC_GLOBALS['pubwiz']) &&
               (
                    $CC_GLOBALS['pubwiz'] == CC_DONT_CARE_LOGGED_IN ||
                    (
                        ($CC_GLOBALS['pubwiz'] == CC_MUST_BE_LOGGED_IN) && $itsme
                    )
               );
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('publicize'),  array( 'CCPublicize', 'Publicize'), 
                CC_DONT_CARE_LOGGED_IN);
    }

} // end of class CCQueryFormats


?>
