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
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-admin.php');

/**
* Form for configuring upload rules
*
* @package cchost
* @subpackage admin
*/
class CCAdminThrottleForm extends CCEditConfigForm
{
    /**
    * Constructor
    *
    */
    function CCAdminThrottleForm()
    {
        $this->CCEditConfigForm('throttle',CC_GLOBAL_SCOPE);
        $fields['enabled'] = array (
           'label'      => _('Enable Throttling'),
           'value'      => 0,
           'formatter'  => 'checkbox',
           'flags'      => CCFF_POPULATE );

        $fields['user-exceptions'] = array (
           'label'      => _('User exceptions'),
           'form_tip'   => _('Comma separted list of user login names that are exempt from throttling.') . ' ' . _('Administrators are automatically exempt from throttling.') . ' ',
           'formatter'  => 'textedit',
           'flags'      => CCFF_POPULATE );

        $msg = _("You are not authorized to submit this type of file.") . ' ' . _('Please contact the site administrator for details.');

        $fields['quota-msg'] = array (
           'label'      => _('Quota Message'),
           'form_tip'   => _('Message to users who are outside the bounds of the throttle'),
           'value'      => $msg,
           'formatter'  => 'textarea',
           'flags'      => CCFF_POPULATE );

        $url = ccl('admin','throttlerules');
        $text = sprintf(_('%sEdit Upload Throttle Rules%s'), "<a href=\"$url\">" , "</a>");

        $fields['rules'] = array (
           'label'      => _('Rules'),
           'form_tip'   => _('Edit the rules governing the upload throttle'),
           'value'      => $text,
           'formatter'  => 'statictext',
           'flags'      => CCFF_NOUPDATE | CCFF_STATIC );

        $this->AddFormFields( $fields );
        $this->SetModule( ccs(__FILE__) );
    }
}

/**
* Form for editing upload rules
*
* @package cchost
* @subpackage admin
*/
class CCAdminThrottleRulesForm extends CCGridForm
{
    /**
    * Constructor
    *
    */
    function CCAdminThrottleRulesForm(&$rules)
    {
        $this->CCGridForm();

        $html = "<input type=\"submit\" name=\"addrule\" id=\"addrule\" value=\"" . _('Add A Rule') . "\" />";
        $this->SetFormHelp($html);

        if( empty($rules) )
        {
            $this->SetSubmitText('');
            return;
        }

        // If use has 'N' number of uploads of type: [submit form list] [time period] then 
        // [allow/disallow] type: [submit-form-types/all] [stop/nostop]
        // 

        $heads = array( _('Delete'), _('Order'), 
                        '', '', '', '', '', '', '', '', '' );
        $this->SetColumnHeader($heads);

        $configs =& CCConfigs::GetTable();
        $form_submit_types = $configs->GetConfig('submit_forms');
        if( empty($form_submit_types) )
        {
            $form_types = array();
            CCEvents::Invoke( CC_EVENT_SUBMIT_FORM_TYPES, array( &$form_submit_types ) );
        }

        $submit_types['all'] = _('All types');
        foreach( $form_submit_types as $type_name => $type_data )
        {
            $submit_types[$type_name] = $type_data['submit_type'];
        }

        $time_periods = array(
                '1 days ago'    => _('24 hours'),
                '1 weeks ago'   => _('7 days'),
                '2 weeks ago'   => _('2 weeks'),
                '1 months ago'  => _('1 month'),
                'forever'       => _('forever')
        );

        $allow_pick = array(
                'forbid'        => _('Forbid'),
                'allow'         => _('Allow')
                );

        $stop_pick = array(
                'stop'          => _('Stop here if rule is true'),
                'continue'      => _('Continue to next rule')
                );

        $count = count($rules);
        $numbers = array();
        for( $i = 1; $i <= $count; $i++ )
            $numbers["$i"] = '' . $i;

        for( $i = 0; $i < $count; $i++ )
        {
            $field = array(
                      array(
                        'element_name'  => "rule[$i][delete]",
                        'value'         => 0,
                        'formatter'     => 'checkbox',
                        'flags'         => CCFF_NONE),
                      array(
                        'element_name'  => "rule[$i][order]",
                        'value'         => $i + 1,
                        'options'       => $numbers,
                        'formatter'     => 'select',
                        'flags'         => CCFF_NONE),
                      array(
                        'element_name'  => 'stat1' . $i,
                        'value'         => _('If user has '),
                        'formatter'     => 'statictext',
                        'flags'         => CCFF_STATIC | CCFF_NOUPDATE ),
                      array(
                        'element_name'  => "rule[$i][num_uploads]",
                        'value'         => $rules[$i]['num_uploads'],
                        'class'         => 'cc_form_input_short',
                        'formatter'     => 'textedit',
                        'flags'         => CCFF_REQUIRED | CCFF_POPULATE ),
                      array(
                        'element_name'  => 'stat2' . $i,
                        'value'         => _('# of:') . ' ',
                        'formatter'     => 'statictext',
                        'flags'         => CCFF_STATIC | CCFF_NOUPDATE ),
                      array(
                        'element_name'  => "rule[$i][limit_by_type]",
                        'value'         => $rules[$i]['limit_by_type'],
                        'formatter'     => 'select',
                        'options'       => $submit_types,
                        'flags'         => CCFF_POPULATE  ),
                      array(
                        'element_name'  => 'stat3' . $i,
                        'value'         => _('since:') . ' ',
                        'formatter'     => 'statictext',
                        'flags'         => CCFF_STATIC | CCFF_NOUPDATE ),
                      array(
                        'element_name'  => "rule[$i][time_period]",
                        'value'         => $rules[$i]['time_period'],
                        'formatter'     => 'select',
                        'options'       => $time_periods,
                        'flags'         => CCFF_POPULATE ),
                      array(
                        'element_name'  => 'stat4' . $i,
                        'value'         => _('then:') . ' ',
                        'formatter'     => 'statictext',
                        'flags'         => CCFF_STATIC | CCFF_NOUPDATE ),
                      array(
                        'element_name'  => "rule[$i][allow]",
                        'value'         => $rules[$i]['allow'],
                        'formatter'     => 'select',
                        'options'       => $allow_pick,
                        'flags'         => CCFF_POPULATE ),
                      array(
                        'element_name'  => "rule[$i][allow_type]",
                        'value'         => $rules[$i]['allow_type'],
                        'formatter'     => 'select',
                        'options'       => $submit_types,
                        'flags'         => CCFF_POPULATE ),
                      array(
                        'element_name'  => "rule[$i][stop]",
                        'value'         => $rules[$i]['stop'],
                        'formatter'     => 'select',
                        'options'       => $stop_pick,
                        'flags'         => CCFF_POPULATE ),
                );

                $this->AddGridRow( $i, $field );
            }
    }
}

/**
*
*
*/
class CCThrottle
{

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ALLOWED}
    *
    * @param array $submit_types Array of submit forms meta data
    */
    function OnUploadAllowed(&$submit_types)
    {
        $type_keys  = array_keys($submit_types);
        foreach( $type_keys as $type_key )
        {
            $submit_types[$type_key]['quota_reached'] = false;
        }

        static $_throttle_on;
        if( !isset($_throttle_on) )
            $_throttle_on = $this->_is_throttle_on();

        if( !$_throttle_on )
            return;

        $configs    =& CCConfigs::GetTable();
        $rules      = $configs->GetConfig('throttle_rules');
        $throttle   = $configs->GetConfig('throttle');
        $uploads    =& CCUploads::GetTable();
        $user_id    = CCUser::CurrentUser();


        $all_submit_types = array();
        CCEvents::Invoke( CC_EVENT_SUBMIT_FORM_TYPES, array( &$all_submit_types ) );

        foreach( $rules as $rule )
        {
            $where = "upload_user = $user_id ";

            $allow_type = $rule['allow_type'];

            if( $allow_type != 'all' )
            {
                if( empty($submit_types[$allow_type]) || !empty($submit_types[$allow_type]['quota_reached']) )
                {
                    // This rule does not apply to any type being asked 
                    // about here or has already been banned in a previous rule
                    continue;
                }
            }

            $limit_by_type = $rule['limit_by_type'];

            if( $limit_by_type != 'all' )
            {
                $uploads->SetTagFilter( $all_submit_types[$limit_by_type]['tags'], 'all' );
            }

            if( $rule['time_period'] != 'forever' )
            {
                $time_period = date('Y-m-d H:i:s', strtotime( $rule['time_period'] ));
                $where = "($where AND upload_date > '$time_period')";
            }

            $C = $uploads->CountRows($where);
            $N = $rule['num_uploads'];

            $done = false;
            
            if( $C >= $N )
            {
                if( $rule['allow'] == 'forbid' )
                {
                    if( $allow_type == 'all' )
                    {
                        foreach( $type_keys as $type_key )
                        {
                            $submit_types[$type_key]['quota_reached'] = true;
                            $submit_types[$type_key]['quota_message'] = $throttle['quota-msg'];
                        }
                    }
                    else
                    {
                        $submit_types[$allow_type]['quota_reached'] = true;
                        $submit_types[$allow_type]['quota_message'] = $throttle['quota-msg'];
                    }
                }

                $done = $rule['stop'] == 'stop';
            }

            $uploads->SetTagFilter('');

            if( $done )
                break;
        }
    }

    function _is_throttle_on()
    {
        if( !CCUser::IsLoggedIn() || CCUser::IsAdmin() )
        {
            return(false);
        }

        $configs =& CCConfigs::GetTable();
        $throttle = $configs->GetConfig('throttle');
        if( empty($throttle['enabled']) )
        {
            return(false);
        }

        $username = CCUser::CurrentUserName();

        if( !empty($throttle['user-exceptions']) )
        {
            $exceptions = CCTag::TagSplit($throttle['user-exceptions']);
            
            if( in_array( $username, $exceptions ) )
            {
                return(false);
            }
        }

        return(true);
    }

    function Admin()
    {
        CCPage::SetTitle(_("Administer Upload Throttle"));
        $form = new CCAdminThrottleForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function Rules()
    {
        $configs =& CCConfigs::GetTable();
        CCPage::SetTitle(_("Upload Throttle Rules"));

        if( !empty($_POST['addrule']) )
        {
            $rules = $_POST['rule'];
            $rules[] =
                    array( 'num_uploads'    => 15,
                           'limit_by_type'  => 'all',
                           'time_period'    => 'forever',
                           'allow'          => 'forbid',
                           'allow_type'     => 'all',
                           'stop'           => 'stop' );

            $form = new CCAdminThrottleRulesForm($rules);
            $tmpl = $form->GenerateForm();
            CCPage::AddForm($tmpl );
        }
        elseif( empty($_POST['adminthrottlerules']) )
        {
            $throttle_rules = $configs->GetConfig('throttle_rules');

            if( empty($throttle_rules) )
            {
                $throttle_rules[] =
                        array( 'num_uploads'    => 3,
                               'limit_by_type'  => 'all',
                               'time_period'    => '1 days ago',
                               'allow'          => 'forbid',
                               'allow_type'     => 'all',
                               'stop'           => 'stop' );
            }

            $form = new CCAdminThrottleRulesForm($throttle_rules);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $rules = $_POST['rule'];
            usort($rules,'cc_order_sorter');
            $keys = array_keys($rules);
            $count = count($keys);
            for( $i = 0; $i < $count; $i++ )
            {
                $K = $keys[$i];
                if( !empty($rules[$K]['delete']) )
                {
                    unset($rules[$K]);
                }
                else
                {
                    unset($rules['order']);
                }
            }
            $configs->SaveConfig('throttle_rules',$rules,CC_GLOBAL_SCOPE,false);
            CCUtil::SendBrowserTo( ccl('admin','throttle') );
        }

    }

    /**
    * Event handler for {@link CC_EVENT_ADMIN_MENU}
    *
    * @param array &$items Menu items go here
    * @param string $scope One of: CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    */
    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array(
                'throttle'   => array( 
                    'menu_text'     => _('Upload Throttles'),
                    'menu_group'    => 'configure',
                    'help'          => _('Limit the number and types of uploads per user'),
                    'access'        => CC_ADMIN_ONLY,
                    'weight'        => 40,
                    'action'        => ccl('admin','throttle')
                    ),
                );
        }
    }



    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','throttle'),  array( 'CCThrottle', 'Admin'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Display throttle settings form'), CC_AG_MISC_ADMIN );
        CCEvents::MapUrl( ccp('admin','throttlerules'),  array( 'CCThrottle', 'Rules'), 
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('Display throttle rules form'), CC_AG_MISC_ADMIN );
    }

}

/**
* @access private
*/
function cc_order_sorter($a,$b)
{
    return( $a['order'] > $b['order'] ? 1 : -1 );
}

?>