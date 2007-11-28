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

define('CCDV_RET_RECORDS',  1);
define('CCDV_RET_ITEMS',  2);
define('CCDV_RET_RESOURCE',  3);
define('CCDV_RET_ITEM',  4);

class CCDataView
{
    function GetDataView($dataview_name)
    {
        global $CC_GLOBALS;
        $filename = CCUtil::SearchPath( $dataview_name . '.php', $CC_GLOBALS['dataview-dir'], 'ccdataviews', true );

        // we don't do a dig_out here because we don't want the data, 
        // we just want to find the file. to dig the meta info out
        // we would have to load the file and that would be redundant
        // (we'll do that at the sql query stage)

        $props['dataview'] = $dataview_name;
        $props['file'] = $filename;
        return $props;
    }

    function GetDataViewFromTemplate($template)
    {
        $skinmac = new CCSkinMacro($template);
        list( $file, $macro ) = $skinmac->LookupMacro();
        if( empty($file) )
            return null;
        $fp = new CCFileProps();
        $props = $fp->GetFileProps($file);
        if( empty($props['dataview']) )
            return null;
        if( empty($props['embedded']) )
        {
            if( empty($props['file']) )
            {
                global $CC_GLOBALS;
                $props = $this->GetDataView($props['dataview']);
            }
        }
        else
        {
            // we grab the template and suck out the 
            // embedded dataview

            $text = file_get_contents($file);
            if( !preg_match('#\[dataview\](.*)\[/dataview\]#s',$text,$m) )
                return null;
            $props['code'] = $m[1];
        }
        return $props;
    }

    function & PerformFile($dataview_name,$args,$ret_type = CCDV_RET_RECORDS) 
    {
        $props = $this->GetDataView($dataview_name);
        return $this->Perform($props,$args,$ret_type);
    }

    function & Perform($dataview,$args,$ret_type = CCDV_RET_RECORDS,$queryObj=null)
    {
        if( empty($dataview['code']) )
        {
            if( empty($dataview['file']) )
            {
                die('No code or file in ' . $dataview['dataview']);
            }
            else
            {
                if( !file_exists($dataview['file']) )
                    die("Can't find dataview file: " . $dataview['file']);

                require_once($dataview['file']);
            }
        }
        else
        {
            eval($dataview['code']);
        }
        
        $func = $dataview['dataview'] . '_dataview';
        if( !function_exists($func) )
            die("Can't find dataview function in " . $dataview['dataview']);

        $sqlargs = array();
        foreach( array( 'joins', 'order', 'limit', 'columns', 'group_by' ) as $f )
            $sqlargs[$f] = isset($args[$f]) ? $args[$f] : '';
        $sqlargs['where'] = !isset($args['where']) ? '"$1"' : '"' . $args['where'] . '" . ("$1" ? "AND" : "")';

        $info = $func();

        $this->sql = preg_replace( array( '/%joins%/', '/%order%/', '/%limit%/', '/%columns%/', '/%group%/', '/(WHERE )?%where%/e'  ),
                                    $sqlargs, $info['sql'] );

        switch( $ret_type )
        {
            case CCDV_RET_RECORDS:
            {
                $records =& CCDatabase::QueryRows($this->sql);
//CCDebug::PrintVar($this->sql);
                if( count($records) > 0 )
                {
                    //$info['query'] = $queryObj;
                    //$info['dvobj'] = $this;
                    while( count($info['e']) )
                    {
                        $k = array_keys($info['e']);
                        $e = $info['e'][$k[0]];
                        CCEvents::Invoke( $e, array( &$records, &$info ) );
                        if( in_array( $e, $info['e'] ) )
                            $info['e'] = array_diff( $info['e'], array( $e ) );
                    }
                }

                return $records;        
            }

            case CCDV_RET_ITEMS:
            {
                $records =& CCDatabase::QueryItems($this->sql);
                return $records;
            }

            case CCDV_RET_ITEM:
            {
                $records = CCDatabase::QueryItem($this->sql);
                return $records;
            }

            case CCDV_RET_RESOURCE:
            {
                $qr = CCDatabase::Query($this->sql);
                return $qr;
            }
        }

        die('Invalid return type for dataview: ' . $ret_type );
    }

}

?>
