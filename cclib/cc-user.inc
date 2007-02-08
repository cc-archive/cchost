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
* @subpackage user
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-form.php');
require_once('cclib/cc-seckeys.php');

/**
*/
class CCSecurityVerifierForm extends CCForm
{
    function CCSecurityVerifierForm()
    {
        $this->CCForm();
    }

    /**
     * Handles generation of &lt;img's and a hidden $id field
     *
     * The img tags are actually stuff with '/s/#' URLs that call back to
     * this module and return a bitmap corresponding to the security key's
     * id. The '#' is combination of id the index into the key in question
     * 
     * @see CCLogin::OnSecurityCallback()
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_securitykey($varname,$value='',$class='')
    {
        $label = $this->GetFormFieldItem($varname,'label');
        if( empty($label) )
            $this->SetFormFieldItem($varname,'label','&nbsp;');
        $keys =& CCSecurityKeys::GetTable();
        $hash = $keys->GenKey();
        $id = $keys->AddKey($hash);
        $len = strlen($hash);
        $html = "<table><tr><td>";
        for( $i = 0; $i < $len; $i++ )
        {
            $url = ccl('s', ($id * 100) + $i);
            $html .= "<img src=\"$url\" />";
        }
        $html .= "</td></tr></table><input type=\"hidden\" name=\"$varname\" id=\"$varname\" value=\"$id\" />";
        return($html);
    }

    /**
    * Handles validator for HTML field, called during ValidateFields()
    * 
    * Validates that the user typed in the proper security code.
    * 
    * @see CCForm::ValidateFields()
    * 
    * @param string $fieldname Name of the field will be passed in.
    * @returns bool $ok true means field validates, false means there were errors in user input
    */
    function validator_securitykey($fieldname)
    {
        $id = $this->GetFormValue('user_mask');
        $hash = CCUtil::StripText($_POST['user_confirm']);
        $keys =& CCSecurityKeys::GetTable();
        $retval = $keys->IsMatch( $hash, $id );
        if( !$retval )
        {
            $this->SetFieldError($fieldname,_('Security key does not match'));
        }
        return( $retval );
    }

    /**
    * Static function to return standard form tip for security field.
    */
    function GetSecurityTip()
    {
        return _('Type in the characters above. Valid characters are 0-9 and A-F. The zero (0) has a line through it, the D does not.');
    }
}

class CCUserForm extends CCSecurityVerifierForm
{
    var $record;

    function CCUserForm()
    {
        $this->CCSecurityVerifierForm();
    }

    /**
     * Handles generation of &lt;input type='password' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_matchpassword($varname,$value='',$class='')
    {
        return( $this->generator_password($varname,$value,$class) );
    }

    function validator_matchpassword($fieldname)
    {
        if( !empty($this->record) )
        {
            $value = $this->GetFormValue($fieldname);

            $password = md5( $value );

            if( $this->record['user_password'] != $password )
            {
                $this->SetFieldError($fieldname,_("Password does not match login name."));
                return(false);
            }

            return( true );
        }

        return( false );
    }


    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_username($varname,$value='',$class='')
    {
        return( $this-> generator_textedit($varname,$value,$class) );
    }

    function validator_username($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            if( empty($value) )
                return(true);

            $users =& CCUsers::GetTable();
            $this->record = $users->GetRecordFromName( $value );

            if( empty($this->record) )
            {
                $this->SetFieldError($fieldname,_("Cannot find that username"));
                return(false);
            }

            return( true );
        }

        return( false );
    }

}


class CCUserProfileForm extends CCUploadForm
{
    var $record;

    function CCUserProfileForm($userid,$avatar_dir)
    {
        global $CC_GLOBALS;

        $this->CCUploadForm();
        $users =& CCUsers::GetTable();
        $this->record = $users->GetRecordFromID($userid);

        // print_r( $CC_GLOBALS['language']->GetPossibleLanguages() );

        $fields = array( 
                    'user_real_name' =>
                        array( 'label'      => _('Full Name'),
                               'form_tip'   => _('Your display name for the site (not to be confused with your login name).'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_password' =>
                       array( 'label'       => _('Password'),
                               'formatter'  => 'password',
                               'flags'      => CCFF_SKIPIFNULL ),

                    'user_email' =>
                       array(  'label'      => _('e-mail'),
                               'form_tip'   => _('This address will never show on the site but is required for creating a new account and password recovery in case you forget it.'),
                               'formatter'  => 'email',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'user_image' =>
                       array(  'label'      => _('Image'),
                               'formatter'  => 'avatar',
                               'form_tip'   => _('Image file (cannot be bigger than 93x93)'),
                               'upload_dir' => $avatar_dir,
                               'maxwidth'   => 93,
                               'maxheight'  => 94,
                               'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL  ),

                    'user_description' =>
                        array( 'label'      => _('About You'),
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'user_homepage' =>
                       array(  'label'      => _('Home Page URL'),
                               'form_tip'   => _('Make sure it starts with http://'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
            );

        if( !empty($CC_GLOBALS['lang_per_user']) )
        {
            $fields += array( 
                    'user_language' => 
                        array(  'label'      => _("Default Language:"),
                            'formatter'  => 'select',
                            'options'    => $CC_GLOBALS['language']->GetPossibleLanguages(), 
                            'flags'      => CCFF_POPULATE ),
                );
        }

        $fields += array( 
                    'user_whatido' =>
                        array( 'label'      => _('What I Pound On'),
                               'form_tip'   => _('(e.g. vinyl, guitar, ACID Pro, vocals, beat slicer)'),
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_whatilike' =>
                        array( 'label'      => _('What I Like:'),
                               'form_tip'   => _('(e.g. Django, Old Skool, Miles Davis, Acid House)'),
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_lookinfor' =>
                        array( 'label'      => _("What I'm Looking For:"),
                               'form_tip'   => _("List attributes of musicians you'd like to hook up with ".
                                               '(e.g. Producer, singer, drummer)'),
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),


                        );

        $this->AddFormFields( $fields );
        $this->EnableSubmitMessage(false);
    }

}


class CCUserAPI
{
    function EditProfile($username='')
    {
        global $CC_GLOBALS;

        if(!CCUser::IsLoggedIn())
            return;

        if( !empty($username) )
            $this->CheckCredentials($username);
        else
            $username = CCUser::CurrentUserName();

        if( empty($CC_GLOBALS['avatar-dir']) )
            $upload_dir = $this->GetUploadDir($username);
        else
            $upload_dir = $CC_GLOBALS['avatar-dir'];

        CCPage::SetTitle(_("Edit Your Settings"));
        $id    = CCUser::IDFromName($username);
        $form  = new CCUserProfileForm($id, $upload_dir );
        $ok    = false;

        if( empty($_POST['userprofile']) )
        {
            $form->PopulateValues( $form->record );
        }
        else
        {
            if( $form->ValidateFields() )
            {

                $form->FinalizeAvatarUpload('user_image', $upload_dir);
                $form->GetFormValues($fields);

                $users =& CCUsers::GetTable();
                $old_info = $users->GetRecordFromID($id);
                $fields['user_id'] = $id;
                if( empty($fields['user_real_name']) )
                    $fields['user_real_name'] = $username;
                $users->Update($fields);
                
                CCEvents::Invoke(CC_EVENT_USER_PROFILE_CHANGED, 
                                 array( $id, &$old_info));

                if( !empty($CC_GLOBALS['lang_per_user']) )
                    CCEvents::Invoke(CC_EVENT_TRANSLATE);

                // this sends us to the save page
                // I added because problem with getting the new language
                // visible as soon as changed upon save
                CCUtil::SendBrowserTo(ccl('people','profile','save'));
            }
        }

        if( !$ok )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    *
    * This just displays a message about the changes being saved.
    *
    * This is really a stub for moving the save profile to this method, but for
    * now it is just a quick display on the page saying changes were saved
    * and is ncessary to display this message in the newly selected language.
    *
    * @access   public
    */
    function SaveProfile()
    {
        CCPage::SetTitle(_("Edit Your Settings"));
        CCPage::Prompt(_("Changes were saved"));
    }

    function ListRecords($sql_where = '')
    {
        $users = new CCUsers(); // getting a new one so we can safely do the join
        if( empty($sql_where) )
        {
            CCPage::SetTitle(_("People"));
            // only show those with uploads
            $users->SetOrder('user_registered','DESC');
            $users->AddJoin( new CCTable('cc_tbl_uploads','upload_user'), 'user_id' );
            $users->GroupOnKey();
            $sql_where = 'upload_id > 0';
        }
        CCPage::AddPagingLinks($users,$sql_where);
        $records =& $users->GetRecords($sql_where);
        CCPage::PageArg( 'user_record', $records, 'user_listings' );
        //CCDebug::PrintVar($sql_where);
    }

    function _build_bread_crumb_trail($username,$tagfilter)
    {
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );
        $trail[] = array( 'url' => ccl('people'), 'text' => _('People') );
        if( !empty($username) )
        {
            $users =& CCUsers::GetTable();
            $user_real_name = $users->QueryItem('user_real_name',"user_name = '$username'");
            if( !empty($user_real_name) )
            {
                $trail[] = array( 'url' => ccl('people',$username), 'text' => $user_real_name );

                if( !empty($tagfilter) )
                {
  //                $trail[] = array( 'url' => ccl('people',$username,$tagfilter), 'text' => $tagfilter );
                    $trail[] = array( 'url' => '', 'text' => '');
                }
            }
            
            require_once('cclib/cc-upload-table.php');
            $uploads = new CCUploads();
            $where['user_name'] = $username;
            $tagrows = $uploads->QueryRows($where,'upload_tags');
            if( !empty($tagrows) )
            {
                $all_tags = array();
                foreach( $tagrows as $tagrow )
                    $all_tags = array_merge($all_tags,CCTag::TagSplit($tagrow['upload_tags']));
                $all_tags = array_unique($all_tags);
                sort($all_tags);
                $taglinks = array();
                $taglinks[] = array( 'url' => ccl('people',$username),
                                     'text' => empty($tagfilter) ? _('(select tag)') : _('(no tag)'),
                                     'selected' => empty($tagfilter) );
                foreach($all_tags as $tag)
                    $taglinks[] = array( 'url' => ccl('people',$username,$tag),
                                         'text' => $tag,
                                         'selected' => $tag == $tagfilter );

                CCPage::PageArg('crumb_tags',$taglinks);
            }
        }

        
        CCPage::AddBreadCrumbs($trail);
    }

    function BrowseUsers()
    {
        $users = new CCUsers();

        $alpha = '';
        $where = 'user_num_uploads > 0';
        if( !isset($_GET['p']) )
        {
            $users->SetOrder('user_registered','DESC');
        }
        else
        {
            $alpha = CCUtil::StripText($_GET['p']);
            $where = "($where) AND (user_name LIKE '{$alpha}%')";
            $users->SetOrder('user_name','ASC');
        }

        CCPage::AddPagingLinks($users,$where);
        $records =& $users->GetRecords($where);
        CCPage::PageArg('user_record',$records,'user_listings');

        $sql =<<<END
                SELECT DISTINCT LOWER(SUBSTRING(user_name,1,1)) c
                   FROM `cc_tbl_user` 
                   WHERE user_num_uploads > 0
                ORDER BY c
END;

        $burl = ccl('people');
        $chars = CCDatabase::QueryItems($sql);
        $len = count($chars);
        $alinks = array();
        for( $i = 0; $i < $len; $i++ )
        {
            $c = $chars[$i];
            if( $c == $alpha )
            {
                $alinks[] = array( 
                                'url' => '', 
                                'text' => "<b>$c</b>" );
            }
            else
            {
                $alinks[] = array( 
                                'url' => $burl . '?p=' . $c, 
                                'text' => $c );
            }
        }

        CCPage::PageArg('user_index',$alinks);
    }

    function UserPage($username = '',$tagfilter='')
    {
        $this->_build_bread_crumb_trail($username,$tagfilter);

        if( empty($username) )
        {
            $this->BrowseUsers();
        }
        else
        {
            $users = new CCUsers();

            $where['user_name'] = $username;
            $users->AddExtraColumn('1 as artist_page');
            $records  = $users->GetRecords($where);
            if( empty($records) )
            {
                CCPage::Prompt(_("The system does not know that user."));
                CCUtil::Send404(false);
                return;
            }
            $itsme = $username == CCUser::CurrentUserName();
            $R =& $records[0];
            $name = $R['user_real_name'];

            if( !empty($tagfilter ) )
                $tagfilter = CCUtil::StripText($tagfilter);

            if( !empty($tagfilter ) )
            {
                require_once('cclib/cc-upload.php');
                CCUpload::ListMultipleFiles($where,$tagfilter);
                $this->_show_feed_links($username);
            
                CCPage::SetTitle($name . ' :: ' . $tagfilter );

                /// EARLY EXIT 
                return;
            }

            CCPage::SetTitle($name);

            $uploads =& CCUploads::GetTable();
            $show_user = true;
            $show_records = false;
            if( $uploads->CountRows($where) > 0 )
            {
                $show_records = true;
            }
            else
            {
                if( $itsme )
                {
                    if( empty($R['user_extra']['seen_welcome']) )
                    {
                        CCPage::ViewFile('welcome.xml');
                        $show_user = false;
                        $extra = $R['user_extra'];
                        $extra['seen_welcome'] = true;
                        $uargs['user_extra'] = serialize($extra);
                        $uargs['user_id'] = $R['user_id'];
                        $users =& CCUsers::GetTable();
                        $users->Update($uargs);
                    }
                    else
                    {
                        $msg = _("You've registered an account and logged in, but you haven't uploaded any remixes.") . '<br /><br />' . 
                               _("Use the 'Submit Files' menu items on the left to start uploading.");

                         $R['user_fields'][] = array( 'label' => '', 
                                                      'value' => _($msg) );
                    }
                }
                else
                {
                        $msg = sprintf(_('%s has not uploaded any remixes.'), $name);                         
                        $R['user_fields'][] = array( 'label' => '', 
                                                      'value' => $msg );
                }
            }
            if( $show_user )
                CCPage::PageArg( 'user_record', $records, 'user_listing' );

            if( $show_records )
            {
                require_once('cclib/cc-upload.php');
                CCUpload::ListMultipleFiles($where,$tagfilter);
            }

            $this->_show_feed_links($username);
        }
    }

    function _show_feed_links($username)
    {
        require_once('cclib/cc-feeds.php');
        CCPage::PageArg('artist_page',$username);
        CCFeeds::AddFeedLinks($username,'',sprintf(_('Uploads by %s'), $username) );
        CCFeeds::AddFeedLinks('','remixesof=' .$username, sprintf(_('Remixes of %s'), $username) );
        CCFeeds::AddFeedLinks('','remixedby=' .$username, sprintf(_('Remixed by %s'), $username) );
    }

    function AddToFavs($user_to_add_or_remove)
    {
        $current_favs = CCUser::CurrentUserField('user_favorites');
        $favs = CCTag::TagSplit($current_favs);

        $msg = '';
        if( in_array( $user_to_add_or_remove, $favs ) )
        {
            $favs = array_diff($favs,array($user_to_add_or_remove));
            $msg = sprintf(_("User, %s, has been removed from your list of favorites."), $user_to_add_or_remove);
        }
        else
        {
            $favs[] = $user_to_add_or_remove;
            $msg = sprintf(_("User, %s, has been added to your list of favorites."), $user_to_add_or_remove);
        }
        $new_favs = implode(',',$favs);
        $users =& CCUsers::GetTable();
        $args['user_id'] = CCUser::CurrentUser();
        $args['user_favorites'] = $new_favs;
        $users->Update($args);
        CCPage::Prompt($msg);
        $this->UserPage($this->CurrentUserName());
    }


    /**
    * Event handler for {@link CC_EVENT_GET_MACROS}
    *
    * @param array &$record Upload record we're getting macros for (if null returns documentation)
    * @param array &$file File record we're getting macros for
    * @param array &$patterns Substituion pattern to be used when renaming/tagging
    * @param array &$mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$record,&$file,&$patterns,&$mask)
    {
        if( empty($record) )
        {
            $patterns['%artist%']       = _("Artist name");
            $patterns['%login%']        = _("Artist login name");
            $patterns['%artist_page%']  = _("Artist page URL");
        }
        else
        {
            $patterns['%artist%']       = $record['user_real_name'];
            $patterns['%login%']        = $record['user_name'];

            if( !empty($record['artist_page_url']) )
                $patterns['%artist_page%']   = $record['artist_page_url'];
            else
                $patterns['%artist_page%'] = '';
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAIN_MENU}
    * 
    * @see CCMenu::AddItems()
    */
    function OnBuildMenu()
    {
        $items = array( 
            'artist'   => array( 'menu_text'  => _('Your Page'),
                             'menu_group' => 'artist',
                             'weight' => 10,
                             'action' =>  ccp( 'people' ,'%login_name%' ),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
                               
            'editprofile'  => array( 'menu_text'  => _('Edit Your Profile'),
                             'menu_group' => 'artist',
                             'weight' => 11,
                             'action' =>  ccp( 'people' ,'profile' ),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
                );

        CCMenu::AddItems($items);

        $groups = array(
                    'visitor' => array( 'group_name' => _('Visitors'),
                                          'weight'    => 1 ),
                    'artist'  => array( 'group_name' => _('Artists'),
                                          'weight'   => 2 )
                    );

        CCMenu::AddGroups($groups);

    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'people',           array('CCUserPage','People'),
            CC_DONT_CARE_LOGGED_IN, 'cclib/cc-user-page.php', '[username]', 
            _('Display people page or user profile'), CC_AG_USER );

        CCEvents::MapUrl( 'people/profile',   array('CCUserAPI','EditProfile'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{username}', 
            _('Edit user profile'), CC_AG_USER );

        CCEvents::MapUrl( 'people/profile/save', array('CCUserAPI','SaveProfile'),    
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__) );

        CCEvents::MapUrl( 'people/addtofavs', array('CCUserAPI','AddToFavs'),  
            CC_MUST_BE_LOGGED_IN, ccs(__FILE__), '{username}', 
            _('Add/Remove favorite users'), CC_AG_USER );
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
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['admins'] =
               array(  'label'      => _('Site Administrators'),
                       'form_tip'   => _('List login names of site admins. (e.g. admin, fightmaster, sally)'),
                       'value'      => 'Admin',
                       'formatter'  => 'tagsedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

            $fields['newuserpage'] =
               array(  'label'      => _('Use Subtabs on User Page'),
                       'form_tip'   => _('Uncheck this for skins that use legacy user profile page'),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
        }

    }

}
?>
