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

require_once('cchost_lib/cc-form.php');


class CCSearch
{
    function Search($ignore_request=false)
    {
        if( !empty($_REQUEST['search_text']) )
        {
            $search_text = CCUtil::StripText($_REQUEST['search_text']);

            if( !empty($search_text) )
            {
                $this->do_results($search_text);
                return;
            }
        }

        $this->do_search();
    }

    function do_search()
    {
        $search_meta = array();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );

        require_once('cchost_lib/cc-page.php');
        CCPage::SetTitle('str_search');
        $form = new CCSearchForm($search_meta,'normal');
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnSearchMeta(&$search_meta)
    {
        $search_meta[] = 
            array(
                'template' => '*',
                'title'    => 'str_search_site',
                'datasource' => '*',
                'group'    => 'all'
            );
        $search_meta[] = 
            array(
                'template'   => 'search_uploads',
                'datasource' => 'uploads',
                'title'      => 'str_search_uploads',
                'fields'     => array(),
                'group'      => 'uploads',
                'match'      => 'upload_name,upload_description,upload_tags',
            );
        $search_meta[] = 
            array(
                'template'   => 'search_users',
                'title'      => 'str_search_users',
                'datasource' => 'user',
                'fields'     => array(),
                'group'      => 'users',
                'match'      => 'user_name,user_real_name,user_description',
            );
    }

    function Results()
    {
        $search_text = CCUtil::StripText($_REQUEST['search_text']);

        if( empty($search_text) )
        {
            $this->do_search();
            return;
        }

        $this->do_results($search_text);
    }

    function do_results($search_text)
    {
        if( empty($_REQUEST['search_in']) )
            die('missing "search in" field'); // I think this is a hack attempt

        $what = CCUtil::StripText($_REQUEST['search_in']);
        $search_meta = array();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );
        require_once('cchost_lib/cc-page.php');
        require_once('cchost_lib/cc-query.php');

        $form = new CCSearchForm( $search_meta, 'horizontal' );
        $values['search_in'] = $what;
        $values['search_text'] = htmlentities($search_text);
        $form->PopulateValues($values);
        $gen = $form->GenerateForm();
        // ack
        $gen->_template_vars['html_hidden_fields'] = array();
        //d($gen);
        CCPage::AddForm($gen);

        if( $what == 'all')
        {
            $grand_total = 0;
            $results = array();
            foreach( $search_meta as $meta )
            {
                if( $meta['group'] == 'all' )
                    continue;
                $query = new CCQuery();
                $qs = "search=$search_text&datasource={$meta['datasource']}&t={$meta['template']}"; 
                $q = $qs . "&limit=5&f=html&noexit=1&nomime=1";
                $args = $query->ProcessAdminArgs($q);
                ob_start();
                $query->Query($args);
                $html = ob_get_contents();
                ob_end_clean();
                $link = (count($query->records) == 5) 
                    ? url_args(ccl('search'),"search_text=$search_text&search_in={$meta['group']}") : '';
                $total = $query->dataview->GetCount();
                $grand_total += $total;
                $results[] = array( 
                    'meta' => $meta, 
                    'results' => $html, 
                    'total' => $total,
                    'more_results_link' => $link,
                    'query' => $qs );
            }

            CCPage::SetTitle('str_search_results');
            CCPage::PageArg('search_results_meta',$results,'search_results_all');

            if( !$grand_total )
                $this->_eval_miss($search_text);
        }
        else
        {
            foreach( $search_meta as $meta )
            {
                if( $meta['group'] != $what )
                    continue;
                CCPage::AddMacro('search_results_head');
                // heaven bless global variables
                global $CC_GLOBALS;
                $result_limit = 30; // todo: option later
                $CC_GLOBALS['max-listing'] = $result_limit; 
                CCPage::SetTitle( array( 'str_search_results_from', $meta['title']) );
                $q = "search=$search_text&datasource={$meta['datasource']}&t={$meta['template']}&limit=30";
                $query = new CCQuery();
                $args = $query->ProcessAdminArgs($q);
                $query->Query($args);
                $total = $query->dataview->GetCount();
                if( empty($total) )
                {
                    $this->_eval_miss($search_text);
                }
                else
                {
                    $msg = array( 'str_search_viewing', 
                        $query->args['offset'], $query->args['offset'] + count($query->records), '<span>' . $total . '</span>' );
                    CCPage::PageArg('search_result_viewing',$msg);
                }
                break;
            }
        }
    }

    function OnFilterSearch(&$records,&$info)
    {
        $k = array_keys($records);
        $c = count($k);
        $r = '("([^"]+)"|(\w+))';
        preg_match_all( "/$r/", $info['queryObj']->args['search'], $m );
        $terms = array_filter(array_merge($m[2],$m[3]));
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[ $k[$i] ];
            $R['qsearch'] = $this->_highlight_results($R['qsearch'],$terms);
        }
    }

    function _highlight_results($input,&$terms,$maxoutlen = 150)
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

    function _show_google_search()
    {
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

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'search',         array('CCSearch','Search'),       
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _('Display search form'), CC_AG_SEARCH );
        CCEvents::MapUrl( 'search/results', array('CCSearch','Results'),     
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _("Use this for 'action' in forms"), CC_AG_SEARCH );
    }

    function _eval_miss($search_text)
    {
        // gather some stats about the search term:
        $words = str_word_count($search_text,1);
        $num_words = count($words);
        $biggest_word = 0;
        for( $i = 0; $i < $num_words; $i++ )
            $biggest_word = max(strlen($words[$i]),$biggest_word);
        $sophis = preg_match('/[+<>~(-]/',$search_text);
        $quoted = strpos($search_text,'"');
        /*
        if( ($biggest_word < 4) && $num_words == 1 )
        {
            $msg = array( 'str_search_miss_tiny', ' <span>"' . $search_text . ' joebob"</span> ', ' <span>"' . $search_text . '_joebob"</span> ' );
        }
        elseif( ($biggest_word < 4) && ($num_words > 1) && !$quoted)
        {
            $msg = array( 'str_search_miss_quote', ' <span>"' . $search_text . '"<span> ', 
                         ' <span>' . substr(str_replace(' ','_',$search_text),0,25) . '</span> ' );
        }
        else
        */
        {
            $msg = array( 'str_search_miss', 
                             '<a href="http://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html">','</a>' );
        }

        CCPage::PageArg('search_miss_msg',$msg);
    }
}

/**
*/
class CCSearchForm extends CCForm
{
    function CCSearchForm($search_meta,$mode )
    {
        $this->CCForm();

        foreach( $search_meta as $meta )
        {
            $field_groups = array();
            $options[$meta['group']] = $meta['title'];
            if( !empty($meta['fields']) )
            {
                foreach( $meta['fields'] as $K => $F )
                {
                    $field_groups[] = $meta['group'];
                    if( empty($F['class']) )
                        $F['class'] = $meta['group'];
                    else
                        $F['class'] .= ' ' . $meta['group'];
                    $fields[$K] = $F;
                }
           }
        }

        $fields['search_text'] =
                        array( 'label'      =>  $mode == 'horizontal' ? '' : _('Search Text'),
                               'form_tip'   => '',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED);

        $fields['search_in'] =
                        array( 'label'      => $mode == 'horizontal' ? '' : _('What'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => $options,
                               'flags'      => CCFF_POPULATE);

        if( $mode == 'horizontal' )
        {
            $this->SetTemplateVar('form_fields_macro','horizontal_form_fields');
        }
        else
        {
            $this->SetFormHelp( array( 'str_search_help',
                             '<a href="http://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html">','</a>') );
        }
        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Search'));
        $this->SetTemplateVar('form_method','GET');
        //$this->SetHandler( ccl('search', 'results') );
    }
}

?>