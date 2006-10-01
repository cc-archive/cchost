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

CCEvents::AddHandler(CC_EVENT_APP_INIT,  array( 'CCRelicense',  'OnAppInit'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCRelicense',  'OnMapUrls'));

class CCRelicense
{
    function Relicense()
    {
        $uploads =& CCUploads::GetTable();
        $where = $this->_get_relicensed_filter();
        $records = $uploads->GetRecords($where);
        $args =& $this->_get_relic_form_args($records);
        if( empty($args) )
        {
            $rargs['done'] = true;
            $this->_mark_user_relicensed($rargs);
            CCPage::SetTitle("Relicensing");
            CCPage::Prompt("You don't have any more uploads that can be relicensed");
        }
        else
        {
            CCPage::PageArg('relicense_info',$args);
            CCPage::ViewFile('relicense.xml');
        }
    }

    function & _get_relic_form_args(&$records)
    {
        $count = count($records);

        if( !$count  )
            return(array());

        $args = array();

        $stuck     = array();
        $originals = array();
        $frozen    = array();
        $cleared   = array();

        $sources =& CCRemixSources::GetTable();

        $cols = array( 'upload_id', 'upload_name', 'license_name' );
        for($i = 0; $i < $count; $i++ )
        {
            $R =& $records[$i];

            $parents = $sources->GetSources($R,false);

            $A = array();
            foreach( $cols as $col )
                $A[$col] = $R[$col];

            if( !empty($parents) )
            {
                $flags = array();
                $A['permit_by'] = true;
                $this->_check_tree($flags,$parents,$A,$sources);
                if( !empty($flags['frozen']) )
                {
                    $frozen[] = $A;
                }
                elseif( !empty($flags['stuck']) )
                {
                    $stuck[] = $A;
                }
                else
                {
                    $cleared[] = $A;
                }
            }
            else
            {
                $originals[] = $A;
            }
        }

        if( empty($stuck) && empty($cleared) && empty($originals) )
            return( array() );

        $args['frozen'] = $frozen;
        $args['stuck'] = $stuck;
        $args['cleared'] = $cleared;
        $args['originals'] = $originals;
        $args['relicensed_url'] = ccl('relicensed');
        $args['relicense_form'] = true;

        return $args;
    }

    function _check_tree(&$flags,&$records,&$A,&$sources)
    {
        $count = count($records);
        for($i = 0; $i < $count; $i++ )
        {
            $R =& $records[$i];
            $user = strtolower($R['user_name']);
            //CCDebug::PrintVar($R);
            if( $user == 'wired' || $user == 'militiamix' )
            {
                $flags['frozen'] = true;
                return;
            }
            $flags['stuck'] = strpos($R['upload_license'],'samp') !== false;
            if( !$flags['stuck'] )
                $A['permit_by'] &= ($R['license_id'] != 'noncommercial');
            //CCDebug::PrintVar($A);
            //CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$R));
            $parents = $sources->GetSources($R,false);
            if( !empty($parents) )
            {
                $this->_check_tree($flags,$parents,$A,$sources);
                if( in_array('frozen',$flags) )
                    return;
            }
        }
    }

    function Relicensed()
    {
        global $CC_GLOBALS;

        $args['done'] = true;
        if( !empty($_POST['relicenselater']) )
        {
            $args['later'] = true;
        }
        else
        {
            if( !empty($_POST['relicid']) && is_array($_POST['relicid']))
            {
                $uploads =& CCUploads::GetTable();

                $relic_ids = $_POST['relicid'];
                foreach( $relic_ids as $upload_id => $newlic )
                {
                    if( $newlic == 'keep' )
                        continue;
                    $upargs['upload_license'] = $newlic;
                    $upargs['upload_id'] = $upload_id;
                    $uploads->Update($upargs);
                    CCUploadAPI::_recalc_upload_tags($upload_id);
                }
            }
        }
        

        $args['done'] = true;
        $this->_mark_user_relicensed($args);
        CCUtil::SendBrowserTo(ccl('people',CCUser::CurrentUserName()));
    }


    function _mark_user_relicensed($args)
    {
        global $CC_GLOBALS;
        $new_extra = $CC_GLOBALS['user_extra'];
        $new_extra['relicense'] = $args;
        $users =& CCUsers::GetTable();
        $uargs['user_id'] = CCUser::CurrentUser();
        $uargs['user_extra'] = serialize($new_extra);
        $users->Update($uargs);
        $CC_GLOBALS['user_extra'] = $new_extra;
    }

    function _get_relicensed_filter($user_id='')
    {
        if( empty($user_id) )
            $user_id = CCUser::CurrentUser();
        return( "(upload_user = $user_id) AND (upload_license LIKE '%sampling%')" );
    }

    function OnAppInit()
    {
        if( !CCUser::IsLoggedIn() )
            return;

        $outs = array( '/relicense', '/logout', '/privacy', '/terms' );
        foreach( $outs as $out )
            if( strpos($_SERVER['REQUEST_URI'],$out) !== false )
                return;

        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['user_extra']['relicense']) ||
                $CC_GLOBALS['user_extra']['relicense']['done'] !== true )
        {
            $uploads =& CCUploads::GetTable();
            $where = $this->_get_relicensed_filter();
            if( !$uploads->CountRows($where) )
            {
                $args['done'] = true;
                $this->_mark_user_relicensed($args);
            }
            else
            {
                CCUtil::SendBrowserTo(ccl('relicense'));
            }
        }

    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('relicense'),   array('CCRelicense', 'Relicense'),   CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( ccp('relicensed'),  array('CCRelicense', 'Relicensed'),  CC_MUST_BE_LOGGED_IN );
    }
}

?>