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

require_once('cclib/cc-form.php');

/**
*/
class CCSearchForm extends CCForm
{
    function CCSearchForm()
    {
        $this->CCForm();
        $fields = array( 
                'search_text' =>
                        array( 'label'      => _('Search Text'),
                               'form_tip'   => '',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
                'search_type' =>
                        array( 'label'      => _('Type'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( 'any' => _('Any match'),
                                                      'all' => _('Match all'),
                                                       'phrase' => _("Exact Phrase") ),
                               'flags'      => CCFF_POPULATE),
                'search_in' =>
                        array( 'label'      => _('What'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( CC_SEARCH_UPLOADS => _('Uploads'),
                                                      CC_SEARCH_USERS   => _("Users"),
                                                CC_SEARCH_UPLOADS | CC_SEARCH_USERS => _("Uploads and Users")
                                                    ),
                               'flags'      => CCFF_POPULATE),
                );

        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Search'));
        $this->SetHandler( ccl('search', 'results') );
    }
}

class CCSearch
{
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'search',         array('CCSearch','Search'),       
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _('Display search form'), CC_AG_SEARCH );
        CCEvents::MapUrl( 'search/people',  array('CCSearch','OnUserSearch'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '{field}/{tags}', _("'field' is whatilike, whatido or lookinfo"), CC_AG_SEARCH );
        CCEvents::MapUrl( 'search/results', array('CCSearch','OnSearch'),     
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _("Use this for 'action' in forms"), CC_AG_SEARCH );
    }

    function Search()
    {
        global $CC_GLOBALS;

        $CC_GLOBALS['hide_sticky_search'] = true;


        CCPage::SetTitle('str_search');
        $form = new CCSearchForm();
        CCPage::AddForm( $form->GenerateForm() );

        $google = '<h2><?= $T->String(\'str_search_google\') ?></h2>';
        $site = preg_replace('#http://(.*)#','\1', cc_get_root_url() );
        $google  .=<<<END
<form method="GET" action="http://www.google.com/search">
<input type="hidden" name="ie" value="utf-8" />
<input type="hidden" name="oe" value="utf-8" />
<table><tr>
<td>
    <a href="http://www.google.com/"><img src="http://www.google.com/logos/Logo_40wht.gif" alt="google"></a>
</td>
<td>
    <input type="text"   name="q" size="31" maxlength="255" value="" />
    <input type="submit" name="btng"        value="Google search" />
    <input type="hidden" name="domains"     value="$site" />
    <input type="hidden" name="sitesearch"  value="$site" />
</td></tr></table>
</form>
END;

        CCPage::AddContent($google);
    }


    function OnSearch()
    {
        if( !empty($_POST['google']) )
        {
            //$url = "http://google.com?site:
        }

        CCPage::SetTitle('str_search_results');

        $done_search = false;
        CCEvents::Invoke( CC_EVENT_DO_SEARCH, array(&$done_search) );
        if( $done_search )
            return;

        $query = CCUtil::StripText($_REQUEST['search_text']);
        if( empty($query) )
        {
            $this->Search();
            return;
        }
        $type = CCUtil::StripText($_REQUEST['search_type']);
        if( empty($_REQUEST['search_in']) )
            die('missing "search in" field'); // I think this is a hack attempt

        $what = intval($_REQUEST['search_in']);

        $results = array();
        $limit_reached = $this->DoSearch($query,$type,$what,$results);

        if( !empty($results[CC_SEARCH_USERS]) )
        {
            CCPage::PageArg( 'user_record',$results[CC_SEARCH_USERS], 'user_listings' );
        }
        if( !empty($results[CC_SEARCH_UPLOADS]) )
        {
            require_once('cclib/cc-upload.php');

            CCUpload::ListRecords($results[CC_SEARCH_UPLOADS]);
        }

        $url = ccl('search');
        $msg = sprintf("<a href=\"%s\">%s</a>",$url, _('Search Again...'));
        if( $limit_reached )
            $msg = _("Search limit reached.") . " $msg";
        CCPage::Prompt($msg);

    }

    /**
    * Search the uploads or artists databases
    * 
    * @param string $query Query string
    * @param string $type One of: 'all', 'any' or 'phrase'
    * @param integer $what Combination of flags CC_SEARCH_UPLOADS, CC_SEARCH_USERS
    * @param array $results Reference to array object to receive results, indexed by '$what' flags
    * @param integer $limit Limit the number of results (default is based on admin's per-page listing setting)
    */
    function DoSearch( $query, $type, $what, &$results, $limit = '', $afields = array() )
    {
        if( !($what & (CC_SEARCH_USERS | CC_SEARCH_UPLOADS)) )
        {
            // someone else has already handled the search
            return(false);
        }

        $query = trim($query);

        if( empty($query) || (strlen($query) < 3))
            return( null );

        if( empty($limit) )
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $limit    = $settings['max-listing'];
            if( !$limit )
                $limit = 10;
        }

        $query = addslashes($query);
        $qlower = strtolower($query);
        if( $type == 'phrase' )
            $terms = array( $qlower );
        else
            $terms = preg_split('/\s+/',$qlower);

        $at_limit = false;

        if( $what & CC_SEARCH_UPLOADS )
        {
            if( empty($afields) )
                $fields = array( 'upload_name', 'upload_description', 
                                  'user_name', 'user_real_name');
            else
                $fields = $afields;

            require_once('cclib/cc-upload-table.php');

            $filter = CCSearch::BuildFilter($fields, $qlower, $type);
            $uploads =& CCUploads::GetTable();
            $uploads->SetOffsetAndLimit(0,$limit);
            $uploads->SetOrder('upload_date','DESC');
            $up_results = $uploads->GetRecords($filter);
            $count = count($up_results);
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $up_results[$i][$field] . ' ';
                $up_results[$i]['result_info'] = CCSearch::_highlight_results($extra,$terms);
            }
            $results[CC_SEARCH_UPLOADS] =& $up_results;
            $at_limit = $count == $limit;
        }

        if( $what & CC_SEARCH_USERS )
        {
            if( empty($afields) )
                $fields = array( 'user_description', 'user_homepage', 'user_name', 'user_real_name');
            else
                $fields = $afields;

            $filter = CCSearch::BuildFilter($fields, $qlower, $type);
            $users  =& CCUsers::GetTable();
            $users->SetOffsetAndLimit(0,$limit);
            $user_results = $users->GetRecords($filter);
            $count = count($user_results);
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $user_results[$i][$field] . ' ';
                $user_results[$i]['result_info'] = CCSearch::_highlight_results($extra,$terms);
            }
 
            $results[CC_SEARCH_USERS] = $user_results;
            $at_limit |= $count == $limit;
        }

        return( $at_limit );
    }

    function OnUserSearch($field,$tag='')
    {
        if( $field == 'lookinfor' )
        {
            CCPage::SetTitle('str_search_wipo');

            $org_tag = $tag;
            $tag = strtolower($tag);
            $users = new CCUsers();
            $where = "(LOWER(user_whatido) REGEXP '(^| |,)($tag)(,|\$)' )";
            $count = $users->CountRows($where);
            $got_tag = $count > 0;
            $first_letter = $tag ? $tag{0} : '';
            $where = "user_whatido > ''";
            $users->SetSort('user_registered','DESC');
            $rows = $users->QueryRows($where,'user_name,user_real_name,LOWER(user_whatido) as wid');
            $whatidos = array();
            $base = ccl('people') . '/';
            foreach( $rows as $row )
            {
                $wids = split(',',$row['wid']);
                unset($row['user_whatido']);
                foreach($wids as $wid)
                    $whatidos[strtolower($wid)][] = 
                       "<a href=\"{$base}{$row['user_name']}\">{$row['user_real_name']}</a>";
            }

            ksort($whatidos);
            $wid_links = array();
            // TODO: This should really go into a stylesheet proper.
            $html =<<<EOF
<style>
#wid_table td, #wid_table th {
  vertical-align: top;
}
#wid_table th {
  text-align: right;
  font-weight: normal;
  font-style: italic;
  padding-right: 4px;
  background-color: #CCF;
}
</style>
<table id="wid_table">
EOF;
            $got_first_letter = false;
            $show_all = empty($_GET['filter']);
            foreach( $whatidos as $wid => $alinks )
            {
                if( !$show_all && (count($alinks) < 2) )
                    continue;

                $html .= '<tr><th>' . $wid;
                if( ($got_tag && ($wid == $tag)) ||
                    (!$got_tag && ($first_letter == $wid{0}) )
                   )
                {
                    $html .= '<a name="' . $org_tag . '" />';
                }
                $html .= '</th><td>' .
                            join(', ',$alinks) . '</td></tr>' . "\n";
            }

            $html .= '</table>';

            CCPage::AddContent($html);
        }
        else
        {
            $field = 'user_' . $field;
            CCPage::SetTitle('str_search_users_that', $tag);
            require_once('cclib/cc-user.inc');
            CCUserAPI::ListRecords( "$field LIKE '%$tag%'" );
        }
    }


    function _highlight_results($input,&$terms,$maxoutlen = 100)
    {
        $max = $maxoutlen;

        // stripos is only on PHP 5 so we have to fake it...
        $xcopy = strtolower($input);
        foreach( $terms as $term )
        {
            if( !$term )
                continue;
            $pos = strpos($xcopy,$term);
            if( $pos !== false )
            {
                $len   = strlen($term);
                $term  = substr($input,$pos,$len); // get mixed version of term
                $repl  = "<span>$term</span>";
                $temp  = substr_replace($input, $repl, $pos, $len);
                if( $pos + $len + 20 > $max )
                    $temp = "..." . substr($temp,$pos-20,$max-5);
                $input = $temp;
                break;
            }
        }
        if( strlen($input) > $max )
            $input = substr($input,0,$max) . '...';

        return($input);
    }

    //
    // $fields     - array of fields (e.g. array( "aboutme", "whatilike" )
    // $searchterm - string (e.g. "Elvis Presley") 
    // $searchtype - 'phrase' match exact phrase
    //               'all'    match all
    //               'any'    mantch any 
    // 
    // returns string to be used in WHERE clause of SQL statement,
    //

    function BuildFilter( $fields, $searchterm, $searchType = "any" )
    {
        $fieldlist  = implode(",",$fields);
        if( count($fields) > 1 )
            $field = "LOWER(CONCAT_WS(' ',$fieldlist))";
        else
            $field = "LOWER($fieldlist)";

        $terms = preg_split("/[\s,+]+/",$searchterm);
        if( empty($terms) )
            return;

        if( ($searchType == 'phrase') || (count($terms) == 1) )
            return( " ($field LIKE '%$searchterm%') ");

        $fields = array_fill(0,count($terms),$field);
        $terms  = array_map("_search_wrap_like",$terms,$fields);
        $OP     = $searchType == 'all' ? " AND " : " OR ";
        $filter = implode($OP,$terms);

        return( "($filter)" );
    }


    function _build_date_filter($datestart,$dateend,$datefield)
    {
        $datestart = trim($datestart);
        $dateend   = trim($dateend);
        $s = '';
        if( $datestart )
        {
            if( !$dateend || ($datestart == $dateend) )
            {
                $s = " ( $datefield LIKE '$datestart%') ";
            }
            else
            {
                $datestart .= " 00:00:00";
                $dateend   .= " 00:00:00";
                $s = " ( ($datefield >= '$datestart') AND ($datefield <= '$dateend') )";
            }
        }

        if( !$s )
            $s = " 1 ";

        return($s);
    }

}

function _search_wrap_like($term,$field)
{
    $term = addslashes($term);
    return( "\n  ($field LIKE '%$term%')" );
}


if (!function_exists('array_fill')) 
{
    function array_fill($iStart, $iLen, $vValue) {
       $aResult = array();
       for ($iCount = $iStart; $iCount < $iLen + $iStart; $iCount++) {
           $aResult[$iCount] = $vValue;
       }
       return $aResult;
    }
}


?>
