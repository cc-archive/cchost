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

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCUser', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_PATCH_MENU,   array( 'CCUser', 'OnPatchMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCUser', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCUser', 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCUser' , 'OnGetConfigFields') );

// yes, the next two were meant to map to the same method...
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,   array( 'CCUser', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_CONTEST_ROW,  array( 'CCUser', 'OnUploadRow'));

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
                $this->SetFieldError($fieldname,_("Can't find that username"));
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
        $this->CCUploadForm();
        $users =& CCUsers::GetTable();
        $this->record = $users->GetRecordFromID($userid);

        $fields = array( 
                    'user_real_name' =>
                        array( 'label'      => _('Full Name'),
                               'form_tip'   => _('Your display name for the site (not to be confused with' .
                                                ' your login name).'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_password' =>
                       array( 'label'       => _('Password'),
                               'formatter'  => 'password',
                               'flags'      => CCFF_SKIPIFNULL ),

                    'user_email' =>
                       array(  'label'      => _('e-mail'),
                               'form_tip'   => _('This address will never show on the site but is '.
                                                'required for creating a new account and password '.
                                                'recovery in case you forget it.'),
                               'formatter'  => 'email',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'user_image' =>
                       array(  'label'      => _('Image'),
                               'formatter'  => 'avatar',
                               'form_tip'   => _('Image file (can not be bigger than 93x93)'),
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


class CCUsers extends CCTable
{
    function CCUsers()
    {
        global $CC_SQL_DATE;

        $this->CCTable( 'cc_tbl_user','user_id');
        $this->AddExtraColumn("DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format");
        $this->AddExtraColumn("LOWER(user_name) as user_lower");
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCUsers();
        return $_table;
    }

    // -----------------------------------
    //  For turning vanilla db 'rows' into
    //        app-usable 'records'
    // ------------------------------------
    function & GetRecords($where)
    {
        $qr = $this->Query($where);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
            $records[] = $this->GetRecordFromRow($row);

        return $records;
    }

    function & GetRecordFromName($username)
    {
        $where = "LOWER(user_name) = '" . strtolower($username) . "'";
        $row = $this->QueryRow($where);
        if( empty($row) )
        {
            $row = null;
            return $row;
        }
        $r =& $this->GetRecordFromRow($row);
        return $r;
    }

    function & GetRecordFromID($userid)
    {
        $row = $this->QueryKeyRow($userid);
        $r =& $this->GetRecordFromRow($row);
        return $r;
    }

    function & GetRecordFromRow(&$row,$expand = true)
    {
        global $CC_GLOBALS;

        $row['artist_page_url']  = ccl('people' ,$row['user_name']);
        $row['user_emailurl']    = ccl('people', 'contact', $row['user_name'] );

        $row['user_is_admin'] = CCUser::IsAdmin($row['user_name']);
        
        if( !empty($row['user_extra']) )
            $row['user_extra'] = unserialize($row['user_extra']);
        else
            $row['user_extra'] = array();


        if( $row['user_image'] )
        {
            if( empty($CC_GLOBALS['avatar-dir']) )
            {
                $avatar_dir = CCUser::GetUploadDir( $row );
            }
            else
            {
                $avatar_dir = $CC_GLOBALS['avatar-dir'];
            }

            $row['user_avatar_url'] = ccd( $avatar_dir , $row['user_image'] );
        }

        $row['user_homepage_html'] = '';
        if( !empty($row['user_homepage']) )
        {
            $row['user_homepage_html'] = "<a href=\"{$row['user_homepage']}\">{$row['user_homepage']}</a>";
        }

        // todo: collapse these into the db
        $user_fields = array( _('Home Page') => 'user_homepage_html',
                              _('About Me')  => 'user_description' );

        $row['user_fields'] = array();
        foreach( $user_fields as $name => $uf  )
        {
            if( empty($row[$uf]) )
                continue;
            $row['user_fields'][] = array( 'label' => $name, 'value' => $row[$uf], 'id' => $uf );
        }


        if( CCUser::IsAdmin() )
        {
            $url = ccl('admin','user',$row['user_id']);
            $row['user_fields'][] = array( 'label' => '', 'value' => "<a href=\"$url\" class=\"cc_user_admin_link\">Account Management</a>" );
        }

        if( $expand )
        {
            $row['user_tag_links'] = array();

            $favs = CCTag::TagSplit($row['user_favorites']);
            if( !empty($favs) )
            {
                $links = array();
                foreach( $favs as $fav )
                    $links[] = "(user_name = '$fav')";
                $where = join(' OR ' ,$links);
                $baseurl = ccl('people') . '/';
                $sql =<<<END
                    SELECT user_real_name as tag, 
                           CONCAT('$baseurl',user_name) as tagurl
                    FROM cc_tbl_user
                    WHERE $where
END;
                $links = CCDatabase::QueryRows($sql);
                $row['user_tag_links']['links0'] = array( 'label' => _('Favorite people'),
                                                  'value' => $links );
            //CCDebug::PrintVar($row);
            }

            $tags =& CCTags::GetTable();
            $tags->ExpandOnRow($row,'user_whatilike',ccl('search/people', 'whatilike'), 'user_tag_links',
                                    _('What I Like'));
            $tags->ExpandOnRow($row,'user_whatido',  ccl('search/people', 'whatido'),'user_tag_links', 
                                    _('What I Pound On'));
            $tags->ExpandOnRow($row,'user_lookinfor',ccl('search/people', 'whatido'),'user_tag_links',
                                    _('What I Look For'));

            CCEvents::Invoke( CC_EVENT_USER_ROW, array( &$row ) );
        }

        return $row;
    }

    function SaveKnownIP()
    {
        global $CC_GLOBALS;
    
        // we don't care about anon users
        if( empty($CC_GLOBALS['user_id']) )
            return;

        $ip    = CCUtil::EncodeIP($_SERVER['REMOTE_ADDR']);
        $dbip  = substr($CC_GLOBALS['user_last_known_ip'],0,8);
     
        if( empty($dbip) || ($ip != $dbip) )
        {
            $where['user_id'] = $CC_GLOBALS['user_id'];
            $where['user_last_known_ip'] = $ip . date('YmdHis');
            $this->Update($where);
        }
    }
}

class CCUser
{
    function IsLoggedIn()
    {
        global $CC_GLOBALS;

        return( !empty($CC_GLOBALS['user_name']) );
    }

    function IsAdmin($name='')
    {
        if( !CCUtil::IsHTTP() )
            return true;

        static $_admins;
        if( !isset($_admins) )
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $_admins = $settings['admins'];
        }

        if( empty($name) )
            $name = CCUser::CurrentUserName();
        $ok = !empty($name) && (preg_match( "/(^|\W|,)$name(\W|,|$)/i",$_admins) > 0);

        return $ok;
    }

    function CurrentUser()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? intval($CC_GLOBALS['user_id']) : -1 );
    }


    function CurrentUserName()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? $CC_GLOBALS['user_name'] : '' );
    }

    function CurrentUserField($field)
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? $CC_GLOBALS[$field] : '' );
    }

    function GetUserName($userid)
    {
        if( $userid == CCUser::CurrentUser() )
            return( CCUser::CurrentUserName() );

        $users =& CCUsers::GetTable();
        return( $users->QueryItemFromKey('user_name',$userid) );
    }


    function CheckCredentials($usernameorid)
    {
        $id     = CCUser::CurrentUser();
        $argid  = intval($usernameorid);
        $name   = CCUser::CurrentUserName();
        if( !$id || (($id !== $argid) && ($name != $usernameorid)) )
        {
           CCUtil::AccessError();
        }
    }

    function IDFromName($username)
    {
        $users =& CCUsers::GetTable();
        $where = "LOWER(user_name) = '" . strtolower($username) . "'";
        return( $users->QueryKey($where) );
    }

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
        $id    = $this->IDFromName($username);
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
                
                CCEvents::Invoke(CC_EVENT_USER_PROFILE_CHANGED, array( $id, &$old_info));

                CCPage::Prompt(_("Changes were saved"));
                $ok = true;
            }
        }

        if( !$ok )
            CCPage::AddForm( $form->GenerateForm() );
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

    function UserPage($username = '',$tagfilter='')
    {
        $this->_build_bread_crumb_trail($username,$tagfilter);

        if( empty($username) )
        {
            $uploads = new CCUploads();
            $uploads->_key_field = 'user_id'; // cheese alert!
            $uploads->GroupOnKey();
            $uploads->SetOrder('user_registered','DESC');
            CCPage::AddPagingLinks($uploads,'');
            $records =& $uploads->GetRecords('');
            CCPage::PageArg('user_record',$records,'user_listings');
        }
        else
        {
            $where['user_name'] = $username;
            $users  = new CCUsers(); // we'll be slamming
            $users->AddExtraColumn('1 as artist_page');
            $records  = $users->GetRecords($where);
            $itsme = $username == $this->CurrentUserName();
            $R =& $records[0];
            $name = $R['user_real_name'];

            if( !empty($tagfilter ) )
                $tagfilter = CCUtil::StripText($tagfilter);

            if( !empty($tagfilter ) )
            {
                CCUpload::ListMultipleFiles($where,$tagfilter);
                $this->_show_feed_links($username);
            
                CCPage::SetTitle($name . ' :: ' . $tagfilter );

                /// EARLY EXIT 
                return;
            }

            CCPage::SetTitle($name);

            if( !$itsme && $this->IsLoggedIn() )
            {
                $current_favs = strtolower($this->CurrentUserField('user_favorites'));
                $favs = CCTag::TagSplit($current_favs);
                if( in_array( strtolower($R['user_name']), $favs ) )
                    $msg = sprintf(_("Remove %s from my favorites"),$name );
                else
                    $msg = sprintf(_("Add %s to my favorites"),$name);
                $R['user_favs_link'] = array( 'text' => $msg,
                                             'link' => ccl('people','addtofavs',$username) );
            }

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
                        $msg =<<<END
You've registered an account and logged in, but you haven't uploaded any remixes yet.

Use the 'Submit Files' menu items on the left to start uploading your sampled tracks and sample libraries.
END;

                         $R['user_fields'][] = array( 'label' => '', 
                                                      'value' => _($msg) );

                    }
                }
                else
                {
                        $msg = $name . _(' has not uploaded any remixes yet.');                         $R['user_fields'][] = array( 'label' => '', 
                                                      'value' => $msg );
                }
            }
            if( $show_user )
                CCPage::PageArg( 'user_record', $records, 'user_listing' );

            if( $show_records )
            {
                CCUpload::ListMultipleFiles($where,$tagfilter);
            }

            $this->_show_feed_links($username);
        }
    }

    function _show_feed_links($username)
    {
        CCPage::PageArg('artist_page',$username);
        CCFeeds::AddFeedLinks($username,'',_('Uploads by ').$username);
        CCFeeds::AddFeedLinks('','remixesof=' .$username,_('Remixes of ').$username);
        CCFeeds::AddFeedLinks('','remixedby=' .$username,_('Remixed by ').$username);
    }

    function AddToFavs($user_to_add_or_remove)
    {
        $current_favs = $this->CurrentUserField('user_favorites');
        $favs = CCTag::TagSplit($current_favs);

        $msg = '';
        if( in_array( $user_to_add_or_remove, $favs ) )
        {
            $favs = array_diff($favs,array($user_to_add_or_remove));
            $msg = sprintf(_("%s has been removed from your list of favorites"),$user_to_add_or_remove);
        }
        else
        {
            $favs[] = $user_to_add_or_remove;
            $msg = sprintf(_("%s has been added to your list of favorites"),$user_to_add_or_remove);
        }
        $new_favs = implode(',',$favs);
        $users =& CCUsers::GetTable();
        $args['user_id'] = $this->CurrentUser();
        $args['user_favorites'] = $new_favs;
        $users->Update($args);
        CCPage::Prompt($msg);
        $this->UserPage($this->CurrentUserName());
    }

    function GetPeopleDir()
    {
        global $CC_GLOBALS;
        return( empty($CC_GLOBALS['user-upload-root']) ? 'people' : 
                            $CC_GLOBALS['user-upload-root'] );
    }

    function GetUploadDir($name_or_row)
    {
        if( is_array($name_or_row) )
            $name_or_row = $name_or_row['user_name'];

        return( CCUser::GetPeopleDir() . '/' . $name_or_row );
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow(&$row)
    {
        $users =& CCUsers::GetTable();
        $users->GetRecordFromRow($row,false);
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
            $patterns['%artist%'] = "Artist name";
            $patterns['%login%']  = "Artist login name";
            $patterns['%artist_page%']   = "Artist page URL";
        }
        else
        {
            $patterns['%artist%']        = $record['user_real_name'];
            $patterns['%login%']         = $record['user_name'];

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
    * Event handler for {@link CC_EVENT_PATCH_MENU}
    * 
    */
    function OnPatchMenu(&$menu)
    {
        $current_user_name = $this->CurrentUserName();
        $menu['artist']['action']  =  str_replace('%login_name%',$current_user_name,$menu['artist']['action']);
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'people',           array('CCUser','UserPage'),     CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'people/profile',   array('CCUser','EditProfile'),  CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( 'people/addtofavs', array('CCUser','AddToFavs'),  CC_MUST_BE_LOGGED_IN );
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
               array(  'label'      => 'Site Administrators',
                       'form_tip'   => 'List login names of site admins.<br /> (e.g. admin, fightmaster, sally)',
                       'value'      => 'Admin',
                       'formatter'  => 'tagsedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }

        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['avatar-dir'] =
               array(  'label'      => 'Avatar Directory',
                       'form_tip'   => 'If blank then avatars are assumed to be in the user\'s upload directory.',
                       'value'      => 'Admin',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

}
?>
