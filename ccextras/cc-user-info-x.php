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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCUserInfoX' , 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,   array( 'CCUserInfoX', 'OnFormFields'));

/*
*  Certain contest jurisdictions require that the following 
*  data be collected for every entrant in a contest where
*  the prize is over a particular threshold (e.g. $300 USD)
*
*   - Legal name
*   - Phone number
*   - Country 
*   - Date of birth
*
*  This module can be used to collect that data from entrants
*  before allowing them access to the contest submit forms.
*
*  To direct users here all menu items and navigation tabs
*  should point to contest/userinfo/[contest_name] instead
*  of contest/submit/[contest_name] however, this will not
*  prevent users who know the system to circumvent this
*  and call the contest/submit form of the url anyway.
*
*  N.B. AddAlias will not work as a redirect because of 
*       recursion (and other) issues so to ensure
*       that users can't directly access the contest/submit
*       url it is safest to use ModRerwrite to map the
*       contest/submit form to contest/userinfo
*/

class CCUserInfoForm extends CCForm
{
    function CCUserInfoForm()
    {
        $this->CCForm();

        $fifteen_yo = strtotime('15 years ago');

        $fields = array(
            'ux_name' => array(
                'label'     => cct('Name'),
                'form_tip'  => cct('Your legal name'),
                'formatter' => 'textedit',
                'flags'     => CCFF_POPULATE | CCFF_REQUIRED),
            'ux_phone' => array(
                'label'     => cct('Phone Number'),
                'form_tip'  => cct('A phone number to reach you'),
                'formatter' => 'textedit',
                'flags'     => CCFF_POPULATE | CCFF_REQUIRED),
            'ux_country' => array(
                'label'     => cct('Country'),
                'form_tip'  => cct('The country of your legal residence'),
                'formatter' => 'textedit',
                'flags'     => CCFF_POPULATE | CCFF_REQUIRED),
            'ux_birthdate' => array(
                'label'     => cct('Date of birth'),
                'form_tip'  => cct('You must be 15 or older to be elligable'),
                'formatter' => 'date',
                'day_only'  => true,
                'value'     => date('Y-m-d', $fifteen_yo),
                'year_end'  => date('Y') - 15,
                'year_begin' => date('Y') - 90,
                'flags'     => CCFF_POPULATE),
            );

        $this->AddFormFields($fields);

        $help =<<<END
The information on this screen will be never appear on the site but is necessary in order to
officially enter you into the contest and make sure the contest is fair as well as give us 
a way to reach you if you win. All the fields must be filled in with the proper information or
your entry will be disqualified.<br /><br />
You must be <b>at least 15 years old</b> to participate in this contest.<br /><br />
You will be able to edit this information if it changes before the contest closes by
clicking on the '<b>Edit Your Profile</b>' link on the left.
END;
        $this->SetFormHelp(cct($help));
    }
}

class CCUserInfoX
{
    function UserInfo($contest,$cmd='')
    {
        global $CC_GLOBALS;

        $info = '';

        if( $cmd == 'dump' && CCUser::IsAdmin() )
        {
            CCDebug::PrintVar($CC_GLOBALS['user_extra']);
        }

        if( $cmd == 'clear' && CCUser::IsAdmin() )
        {
            $this->_write_info($contest,array());
            unset($CC_GLOBALS['user_extra']['user_info'][$contest]);
        }

        if( !empty($CC_GLOBALS['user_extra']['user_info'][$contest]) )
        {
            $fields = array( 'ux_birthdate', 'ux_name', 'ux_phone', 'ux_country' );

            $info =& $CC_GLOBALS['user_extra']['user_info'][$contest];
            $ok = true;
            if( $cmd == 'edit' )
            {
                $ok = false; // not really
            }
            else
            {
                $ok = true; // not necessarily
                foreach( $fields as $field )
                    $ok = $ok && !empty($info[$field]);
            }
        }
        else
        {
            $ok = false;
        }

        if( !$ok )
        {
            CCPage::SetTitle(cct('Contest Entry User Information'));
            $form = new CCUserInfoForm();
            if( !empty($info) )
                $form->PopulateValues($info);
            if( empty($_POST['userinfo']) || !$form->ValidateFields() )
            {
                CCPage::AddForm ( $form->GenerateForm() );
            }
            else
            {
                $form->GetFormValues($values);
                $this->_write_info($contest,$values);
                $ok = true;
            }
        }

        if( $ok )
        {
            if( $cmd == 'edit' )
            {
                CCUtil::SendBrowserTo();
            }
            else
            {
                $action = CCEvents::ResolveUrl( '/contest/submit/' . $contest );
                CCEvents::PerformAction($action);
            }
        }
    }

    function _write_info($contest,$values)
    {
        global $CC_GLOBALS;

        $user_extra = $CC_GLOBALS['user_extra'];
        $user_extra['user_info'][$contest] = $values;
        $args['user_extra'] = serialize($user_extra);
        $args['user_id'] = CCUser::CurrentUser();
        $users =& CCUsers::GetTable();
        $users->Update($args);
    }

    function OnFormFields(&$form,&$fields)
    {
        global $CC_GLOBALS;

        if( !empty($CC_GLOBALS['user_extra']['user_info']) &&  
            strtolower( get_class($form) ) == 'ccuserprofileform' )
        {
            $infos =& $CC_GLOBALS['user_extra']['user_info']; 
            $keys = array_keys($infos);
            $contests =& CCContests::GetTable();
            foreach( $keys as $contest )
            {
                if( empty($infos[$contest]) )
                    continue;
                $nice_name = $contests->GetFriendlyNameFromShortName($contest);
                $url = ccl('contest','userinfo',$contest,'edit');
                $value = "<a style=\width:100px\" href=\"$url\" class=\"cc_gen_button\"><span>For \"$nice_name\"</span></a>";
                $fields[ $contest . '_uinfo_link'] = 
                            array( 'label'      => cct('Contest Entry Info'),
                                   'form_tip'   => cct('Edit your personal contest information'),
                                   'formatter'  => 'statictext',
                                   'value'      => $value,
                                   'flags'      => CCFF_NOUPDATE | CCFF_STATIC
                                 );
            }
        }
    }


    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('contest', 'userinfo'), array( 'CCUserInfoX', 'UserInfo'),  CC_MUST_BE_LOGGED_IN );
    }

}


?>