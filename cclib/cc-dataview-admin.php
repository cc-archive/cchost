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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


class CCDataview
{
    function GetDataView($dataview_name)
    {
        global $CC_GLOBALS;
        $props['dataview'] = $dataview_name;
        $props['file'] = CCUtil::SearchPath( $dataview_name . '.php', $CC_GLOBALS['dataview-dir'], 'ccdataviews', true );
        return $props;
    }

    function GetDataViewFromTemplate($template)
    {
        $skinmac = new CCSkinMacro($template);
        list( $file, $macro ) = $skinmac->LookupMacro();
        if( empty($file) )
            return null;
        $props = CCTemplateAdmin::_get_format_props($file);
        if( empty($props['dataview']) )
            return null;
        if( empty($props['embedded']) )
        {
            global $CC_GLOBALS;
            $props['file'] = CCUtil::SearchPath( $props['dataview'] . '.php', $CC_GLOBALS['dataview-dir'], 'ccdataviews', true );
        }
        else
        {
            $text = file_get_contents($file);
            if( !preg_match('#\[dataview\](.*)\[/dataview\]#s',$text,$m) )
                return null;
            $props['code'] = $m[1];
        }
        return $props;
    }

    function Perforum($dataview)
    {
        if( empty($dataview['file']) )
        {
            if( empty($dataview['code']) )
            {
                die('Dataview name ' . $dataview['dataview'] . ' but no code found');
            }
            else
            {
                eval($dataview['code']);
            }
        }
        else
        {
            if( !file_exists($dataview['file']) )
                die("Can't find dataview file: " . $dataview['file']);

            require_once($dataview['file']);
        }
        
        $func = $this->args['dataview'] . '_dataview';
        if( !function_exists($func) )
            die("Can't find dataview function in " . $this->args['dataview']);

        $info = $func();
        $this->sql = preg_replace( array( '/%joins%/', '/(WHERE )?%where%/e', '/%order%/', '/%limit%/', '/%columns%/', '/%group%/'  ),
                             array( $this->sql_joins, 
                                    empty($this->sql_where) ? '"$1"' : '"' . $this->sql_where . '" . ("$1" ? "AND" : "")', 
                                    $this->sql_sort, 
                                    $this->sql_limit, 
                                    '',
                                    $this->sql_group_by),
                             $info['sql'] );

        $records =& CCDatabase::QueryRows($this->sql);

        if( count($records) > 0 )
        {
            $info['query'] = $this;
            while( count($info['e']) )
            {
                $k = array_keys($info['e']);
                $e = $info['e'][$k[0]];
                CCEvents::Invoke( $e, array( &$records, &$this->args, &$info ) );
                if( in_array( $e, $info['e'] ) )
                    $info['e'] = array_diff( $info['e'], array( $e ) );
            }
        }

        return $records;        
    }


}

?>
