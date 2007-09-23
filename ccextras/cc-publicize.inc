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
                CCPage::Prompt(_("Don't know what user to publicize!"));
                return;
            }
        }

        $itsme  = $user == CCUser::CurrentUserName();
        if( !$this->_pub_wizard_allowd($itsme) )
        {
            CCPage::Prompt(_('This feature is not enabled here'));
            return;
        }

        $this->_share('user',$user,$itsme);
    }

    function Share($id)
    {
        require_once('cclib/cc-page.php');
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($id);
        if( empty($record) )
        {
            CCPage::Prompt(_("Don't know what upload to share!"));
            return;
        }
        $this->_share('upload',$record);
    }

    
    function _share($type,$arg1,$arg2='')
    {
        global $CC_GLOBALS;

        $args['intro'] = '';
        $args['step1'] = _('1. Select from the following options:');
        $args['step2'] = _('2. Then copy the text from this field and paste it into your page:');
        $args['hiddens'] = array();

        $combos = array();

        if( $type == 'user' )
        {
            $users  =& CCUsers::GetTable();
            $record =& $users->GetRecordFromName($arg1);
            
            $args['bookmark_url'] = $record['artist_page_url'];
            $configs =& CCConfigs::GetTable();
            $template_tags = $configs->GetConfig('ttag');
            $args['bookmark_title'] = $record['user_real_name'] . ' @ ' . $template_tags['site-title'];

            $args['email_url'] = ccl( 'email', 'user', $user );

            $args = array_merge( $args, $record );
            
            if( $arg2 ) // $itsme 
            {
                $args['intro'] = _('Do you have a blog or web page? You can display a list of up-to-the-minute links to your latest remixes directly on your page.');
                $yourremixes = _('Your remixes');
                $othersremixes = _("Other peoples's remixes of you.");
                $allyourups = _('All of your uploads');
                $title = _('Publicize Yourself');
            }
            else
            {
                $args['intro'] = sprintf( _("Do you have a blog or web page? You can display a list of up-to-the-minute links to latest remixes of %s directly on your page."), '<b>' . $record['user_real_name'] . '</b>' ) . ' ' .
                    sprintf(_('Use the links above to share %s\'s with your social network or follow the instructions below to embed a link in your web page, blog, MySpace page, etc.'), '<b>' . $record['user_real_name'] . '</b>' );
                $yourremixes = sprintf( _("%s's remixes"), $record['user_real_name'] );
                $othersremixes = sprintf( _("Other peoples's remixes of %s"),
                                             $record['user_real_name'] );
                $allyourups = sprintf( _('All %s\'s uploads'), $record['user_real_name'] );
                $title = sprintf( _('Publicize %s'), $record['user_real_name'] );
            }
            $opts['title'] = _('Type of links:');
            $opts['name']  = '';
            $opts['class']  =
            $opts['id']    = 'usertypechanger';
            $opts['help']  = '';
            $opts['opts'] = array( 
                    array( 'value'    => 'remix',
                           'selected' => true,
                           'text'     => $yourremixes ),
                    array( 'value'    => $record['user_name'],
                           'selected' => false,
                           'text'     => $othersremixes ),
                    array( 'value'    => 'all',
                           'selected' => false,
                           'text'     => $allyourups )
                    );
            $combos[] = $opts;

            $do_num_links = true;
            $do_chop = true;
        }
        else
        {
            if( $type == 'upload' )
            {

                $record =& $arg1;
                
                $args['intro'] = _('Use the links above to share this upload with your social network or follow the instructions below to embed a link in your web page, blog, MySpace page, etc.');

                $args['bookmark_url'] = $record['file_page_url'];
                $args['bookmark_title'] = $record['upload_name'];
                $args['email_url'] = ccl( 'email', 'upload', $record['upload_id'] );


                $args = array_merge( $args, $record );
                $title = sprintf( _('Share \'%s\''), $record['upload_name'] );
            }

            $args['hiddens'][] = array( 'value' => $record['upload_id'], 'name' => 'ids' );
            $do_num_links = false;
            $do_chop = false;
            $args['user_name'] = '';
        }

        if( $do_num_links )
        {
            $opts = array();
            $opts['title'] = _('Number of links:');
            $opts['name']  =
            $opts['id']    = 'limit';
            $opts['class'] = 'queryparam';
            $opts['help']  = '';
            $opts['opts']  = array(
                    array( 'value'     => '1',
                           'selected'  => false,
                           'text'      => _('Just the very latest one') ),
                    array( 'value'     => '5',
                           'selected'  => true,
                           'text'      => _('The 5 latest')),
                    array( 'value'     => '10',
                           'selected'  => false,
                           'text'      => _('The 10 latest')),
                    array( 'value'     => '50',
                           'selected'  => false,
                           'text'      => _('A whole bunch (up to a 50)'))
                );
            $combos[] = $opts;

            $s = 's';
        }
        else
        {
            $s = '';
        }

        /*
            Formats
        */
        $opts = array();
        $opts['title'] = _('Format:');
        $opts['help']  = '';
        $opts['class'] = 'queryparam';
        $opts['name']  =
        $opts['id']    = 'template';
        $opts['opts']  = array(
                array( 'value'  => 'links',
                       'selected'  => true,
                       'text'   => $s ? _("Plain links") : _("Plain link")),
                array( 'value'  => 'links_by',
                       'selected'  => false,
                       'text'   => $s ? _("Links with attribution") : _("Link with attribution")),
                array( 'value'  => 'links_stream',
                       'selected'  => false,
                       'text'   => $s ? _('Links with a stream link') : _('Link with a stream link')),
                array( 'value'  => 'links_dl',
                       'selected'  => false,
                       'text'   => $s ? _('Links with a download link') : _('Link with a download link')),
                array( 'value'  => 'med',
                       'selected'  => false,
                       'text'   => _('Verbose (!)')),
            );

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
                    $opts['opts'][] = array( 'value' => $exformat, 'selected' => false, 'text' => $name );
                }
            }
        }

        $combos[] = $opts;

        /*
            Chop
        */
        if( $do_chop )
        {
            $opts = array();
            $opts['title'] = _('Chop:');
            $opts['class'] = 'queryparam';
            $opts['name']  =
            $opts['id']    = 'chop';
            $opts['help']  = _('Cut off links larger than:');
            $opts['opts']  = array(
                    array( 'value'  => '10',
                           'selected'  => true,
                           'text'   => _("10 characters")),
                    array( 'value'  => '20',
                           'selected'  => false,
                           'text'   => _("20 characters")),
                    array( 'value'  => '25',
                           'selected'  => false,
                           'text'   => _("25 characters")),
                    array( 'value'  => '0',
                           'selected'  => false,
                           'text'   => _("Don't do any chopping")),
                );
            $combos[] = $opts;
        }

        $args['combos'] =& $combos;

        $args['seehtml']       = _('Show raw HTML');
        $args['showformatted'] = _('Show Formatted');
        
        $args['preview'] = _('Preview');
        $args['previewwarn'] = _('This preview is pre-formatted. How this will actually look on your web page will change depending on your stylesheet settings.');
        $args['htmlwarn'] = _('Make sure to copy from the box above, not what is showing below because the actual content of this HTML will change based on the upload activity. You can still get an idea of what of the formatting will look like here:');

        CCPage::SetTitle( $title );
        $sites = CCPage::GetViewFile('sharesites.js',false);

        CCPage::AddScriptLink( ccd($sites) );
        CCPage::AddLink('head_links', 'stylesheet', 'text/css', ccd('cctemplates/publicize.css'), 'Default Style');
        CCPage::PageArg('share', 'share.xml/share_popup' );
        CCPage::PageArg('publicize', 'publicize.xml/publicize');
        CCPage::PageArg('PUB', $args, 'share');
        CCPage::PageArg('dummy', array(), 'publicize');

        if( !empty($record) )
        {
            $this->_build_bread_crumb_trail($record['user_name'], empty($record['upload_id']) ? '' : $record['upload_id']);
        }
    }

    /**
    * @access private
    */
    function _build_bread_crumb_trail($username,$upload_id)
    {
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );
        
        $trail[] = array( 'url' => ccl('people'), 
                          'text' => _('People') );
        $users =& CCUsers::GetTable();
        $user_real_name = $users->QueryItem('user_real_name',
                                            "user_name = '$username'");
        if( !empty($user_real_name) )
        {
            $trail[] = array( 'url' => ccl('people',$username), 
                                       'text' => $user_real_name );
            if( !empty($upload_id) )
            {
                $uploads =& CCUploads::GetTable();
                $upload_name = $uploads->QueryItemFromKey('upload_name',
                                                          $upload_id);
                if( !empty($upload_name) )
                {
                    $upload_name = '"' . $upload_name . '"';
                    $trail[] = array( 'url' => ccl('files',$username,
                                                   $upload_id), 
                                       'text' => $upload_name );
                }
            }
        }

        $trail[] = array( 'url' => '', 'text' => _('Share') );

        CCPage::AddBreadCrumbs($trail);
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
                CC_MUST_BE_LOGGED_IN   => _('Registered User Only'),
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

    function Email($type='',$arg1='')
    {
        require_once('cclib/cc-page.php');
        CCPage::SetTitle(_('Share This Link'));

        switch( $type )
        {
            case 'user':
                $users =& CCUsers::GetUsers();
                if( !empty($arg1) )
                    $record = $users->GetRecordFromName($arg1);
                $ok = !empty($record);
                break;
            case 'upload':
                $uploads =& CCUploads::GetTable();
                if( !empty($arg1) )
                    $record = $uploads->GetRecordFromID($arg1);
                $ok = !empty($record);
                break;
            default:
                $ok = false;
        }
        if( !$ok )
            CCUtil::Send404(true);

        if( !empty($record) )
        {
            $this->_build_bread_crumb_trail($record['user_name'], empty($record['upload_id']) ? '' : $record['upload_id']);
        }

        require_once('ccextras/cc-publicize-forms.inc');
        $form = new CCShareLinkForm($type,$record);

        if( empty($_POST['emaillink']) || !$form->ValidateFields())
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($fields);
            require_once('ccextras/cc-mail.inc');
            global $CC_MAILER;

            if( empty($CC_MAILER) )
                $mailer = new CCMailer();
            else
                $mailer = $CC_MAILER;

            $mailer->From( $fields['mail_from'] );
            $mailer->To( $user_to['user_email'] );
            $mailer->Subject( $fields['mail_subject'] );
            $mailer->Body( $fields['mail_body'] );
            $mailer->Send();

            CCPage::Prompt( _('Mail sent') );
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
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__));

        CCEvents::MapUrl( ccp('share'), array( 'CCPublicize', 'Share' ),  
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{id}', _('Displays Share/Embed PopupWindow'), CC_AG_UPLOADS  );

        CCEvents::MapUrl( ccp('email'), array( 'CCPublicize', 'Email' ),  
                CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{type},{id}', _('Email link to a friend'), CC_AG_UPLOADS  );

    }

}


?>
