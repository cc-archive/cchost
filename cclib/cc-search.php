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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCSearch', 'OnMapUrls') );

/**
*/
class CCSearchForm extends CCForm
{
    function CCSearchForm()
    {
        $this->CCForm();
        $fields = array( 
                'search_text' =>
                        array( 'label'      => cct('Search Text'),
                               'form_tip'   => '',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
                'search_type' =>
                        array( 'label'      => cct('Type'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( 'any' => cct('Any match'),
                                                      'all' => cct('Match all'),
                                                       'phrase' => cct("Exact Phrase") ),
                               'flags'      => CCFF_POPULATE),
                'search_in' =>
                        array( 'label'      => cct('What'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( CC_SEARCH_UPLOADS => cct('Uploads'),
                                                      CC_SEARCH_USERS   => cct("Users"),
                                                CC_SEARCH_UPLOADS | CC_SEARCH_USERS => cct("Uploads and Users")
                                                    ),
                               'flags'      => CCFF_POPULATE),
                );

        $this->AddFormFields( $fields );
        $this->SetSubmitText(cct('Search'));
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
        CCEvents::MapUrl( 'search',         array('CCSearch','Search'),       CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'search/people',  array('CCSearch','OnUserSearch'), CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'search/results', array('CCSearch','OnSearch'),     CC_DONT_CARE_LOGGED_IN );
    }

    function Search()
    {
        global $CC_GLOBALS;

        $CC_GLOBALS['hide_sticky_search'] = true;


        CCPage::SetTitle(cct("Search"));
        $form = new CCSearchForm();
        $form_html = $form->GenerateHTML();
        $form_html .= cct('<h1>Search using Google (tm)</h1>');
        $site = preg_replace('#http://(.*)#','\1', cc_get_root_url() );
        $form_html .=<<<END
<form method="GET" action="http://www.google.com/search">
<input type="hidden" name="ie" value="utf-8">
<input type="hidden" name="oe" value="utf-8">
<table><tr>
<td>
    <a href="http://www.google.com/"><img src="http://www.google.com/logos/Logo_40wht.gif" alt="google"></a>
</td>
<td>
    <input type="text"   name="q" size="31" maxlength="255" value="">
    <input type="submit" name="btng"        value="Google search">
    <input type="hidden" name="domains"     value="$site">
    <input type="hidden" name="sitesearch"  value="$site">
</td></tr></table>
</form>
END;

        CCPage::AddPrompt('body_text',$form_html);

    }


    function OnSearch()
    {
        if( !empty($_POST['google']) )
        {
            //$url = "http://google.com?site:
        }

        CCPage::SetTitle(cct('Search Results'));

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
        $what = intval($_REQUEST['search_in']);

        $results = array();
        $limit_reached = $this->DoSearch($query,$type,$what,$results);

        if( !empty($results[CC_SEARCH_USERS]) )
        {
            CCPage::PageArg( 'user_record',$results[CC_SEARCH_USERS], 'user_listings' );
        }
        if( !empty($results[CC_SEARCH_UPLOADS]) )
        {
            CCUpload::ListRecords($results[CC_SEARCH_UPLOADS]);
        }

        $url = ccl('search');
        $msg = sprintf(cct("<a href=\"%s\">Search again...</a>"),$url);
        if( $limit_reached )
            $msg = cct("Search limit reached. ") . $msg;
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

    function OnUserSearch($field,$tag)
    {
        $field = 'user_' . $field;
        CCPage::SetTitle(cct("Users That Mentioned: ") . $tag);
        CCUser::ListRecords( "$field LIKE '%$tag%'" );
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