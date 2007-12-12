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


class CCSearch
{
    function Search()
    {
        $search_meta = $this->_get_search_meta();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );

        require_once('cclib/cc-page.php');
        CCPage::SetTitle('str_search');
        $meta_options = $this->_get_meta_select($search_meta);
        $form = new CCSearchForm($meta_options);
        CCPage::AddForm( $form->GenerateForm() );
    }

    function _get_search_meta()
    {
        return array(
            array(
                'template' => '*',
                'title'    => 'str_search_site',
                'datasource' => '*',
                'group'    => 'all'
            ),
            array(
                'template'   => 'search_uploads',
                'datasource' => 'uploads',
                'title'      => 'str_search_uploads',
                'fields'     => array(),
                'group'      => 'uploads',
            ),
            array(
                'template'   => 'search_users',
                'title'      => 'str_search_users',
                'datasource' => 'user',
                'fields'     => array(),
                'group'      => 'users',
            )
        );

    }

    function Results()
    {
        $search_text = CCUtil::StripText($_REQUEST['search_text']);
        if( empty($search_text) )
        {
            $this->Search();
            return;
        }

        if( empty($_REQUEST['search_in']) )
            die('missing "search in" field'); // I think this is a hack attempt

        $what = CCUtil::StripText($_REQUEST['search_in']);

        $search_meta = $this->_get_search_meta();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );
        require_once('cclib/cc-page.php');
        require_once('cclib/cc-query.php');

        $form_args['options'] = $this->_get_meta_select($search_meta);
        $form_args['search_in'] = $what;
        $form_args['search_text'] = $search_text;
        $opkeys = array_keys($form_args['options']);
        $i = 0;
        foreach($opkeys as $K )
        {
            if( $K == $what )
            {
                $form_args['selected'] = $i;
                break;
            }
            ++$i;
        }

        CCPage::PageArg('search_form',$form_args,'search_results_form');

        if( $what == 'all')
        {
            $results = array();
            foreach( $search_meta as $meta )
            {
                if( $meta['group'] == 'all' )
                    continue;
                $query = new CCQuery();
                $qs = "search=$search_text&datasource={$meta['datasource']}&t={$meta['template']}&sort="; 
                $q = $qs . "&limit=5&f=html&noexit=1&nomime=1";
                $args = $query->ProcessAdminArgs($q);
                ob_start();
                $query->Query($args);
                $html = ob_get_contents();
                ob_end_clean();
                $link = (count($query->records) == 5) 
                    ? url_args(ccl('search','results'),"search_text=$search_text&search_in={$meta['group']}") : '';
                $total = CCDatabase::Query($query->args['dataviewObj']->sql_count);
                $results[] = array( 
                    'meta' => $meta, 
                    'results' => $html, 
                    'total' => $total,
                    'more_results_link' => $link,
                    'query' => $qs );
            }

            CCPage::SetTitle('str_search_results');
            CCPage::PageArg('search_results_meta',$results,'search_results_all');
        }
        else
        {
            foreach( $search_meta as $meta )
            {
                if( $meta['group'] != $what )
                    continue;
                CCPage::SetTitle( array( 'str_search_results_from', $meta['title']) );
                $q = "search=$search_text&datasource={$meta['datasource']}&t={$meta['template']}&sort=";
                $query = new CCQuery();
                $args = $query->ProcessAdminArgs($q);
                $query->Query($args);
                if( empty($query->records) )
                {

                }
                break;
            }
        }
    }

    function OnFilterSearch(&$records,&$info)
    {
        $k = array_keys($records);
        $c = count($k);
        $terms = preg_split('/[^a-z_]+/',$info['queryObj']->args['search'],-1,PREG_SPLIT_NO_EMPTY);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[ $k[$i] ];
            $R['qsearch'] = $this->_highlight_results($R['qsearch'],$terms);
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

    function _get_meta_select($search_meta)
    {
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

        return $options;
    }
}

/**
*/
class CCSearchForm extends CCForm
{
    function CCSearchForm($search_meta_options)
    {
        $this->CCForm();

        $fields['search_text'] =
                        array( 'label'      => _('Search Text'),
                               'form_tip'   => array( 'str_search_help',
                                                     '<a href="http://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html">','</a>'),
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED);

        $fields['search_in'] =
                        array( 'label'      => _('What'),
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => $search_meta_options,
                               'flags'      => CCFF_POPULATE);

        $this->AddFormFields( $fields );
        $this->SetSubmitText(_('Search'));
        $this->SetHandler( ccl('search', 'results') );
        $this->SetTemplateVar('form_method','GET');
    }
}

?>