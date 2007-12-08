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
        if( !$uploads->KeyExists($id) )
        {
            CCPage::Prompt(_("Don't know what upload to share!"));
            return;
        }
        $record = CCDatabase::QueryRow('SELECT upload_id, user_name, upload_name, user_real_name FROM cc_tbl_uploads JOIN ' .
                                       'cc_tbl_user ON upload_user=user_id WHERE upload_id=' . $id );
        $record['file_page_url'] = ccl('files',$record['user_name'],$id);
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

            $args['email_url'] = ccl( 'email', 'user', $arg1 );

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
        require_once('cclib/cc-template.inc');
        $fmts = CCTemplateAdmin::GetFormats('format');
        foreach( $fmts as $K => $F )
        {
            $opts['opts'][] = array( 'value' => $K, 'text' => $F, 'selected' => false );
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

        require_once('cclib/cc-page.php');
        CCPage::SetTitle( $title );
        CCPage::AddScriptLink( 'js/sharesites.js', true );
        CCPage::AddScriptLink( 'js/share.js', true );
        CCPage::SetStyleSheet('css/publicize.css');
        CCPage::PageArg('share', 'share.php/share_popup' );
        CCPage::PageArg('publicize', 'publicize.php/publicize');
        CCPage::PageArg('PUB', $args, 'share');
        CCPage::AddMacro('publicize');

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
                if( $ok )
                    $regarding = $record['user_name'];
                break;
            case 'upload':
                $uploads =& CCUploads::GetTable();
                if( !empty($arg1) )
                    $record = $uploads->GetRecordFromID($arg1);
                $ok = !empty($record);
                if( $ok )
                    $regarding = $record['upload_id'] . '/' . $record['upload_name'];
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

        if( empty($_POST['sharelink']) || !$form->ValidateFields())
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

            if( empty($fields['mail_from']) )
            {
                $users =& CCUsers::GetTable();
                $row = $users->QueryKeyRow($fields['mail_from_id']);
                $fields['mail_from'] = $row['user_email'];
            }
            $body = urldecode($fields['mail_stock_body']) . "\n\n" . $fields['mail_body'];
            $mailer->From( $fields['mail_from'] );
            $mailer->To( $fields['mail_to'] );
            $mailer->Subject( $fields['mail_subject'] );
            $mailer->Body( $body );
            @$mailer->Send();

            CCDebug::Enable(true);
            CCDebug::Log("Email share from {$fields['mail_from']} to {$fields['mail_to']} re: {$regarding}");

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