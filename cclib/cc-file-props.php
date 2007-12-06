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


/**
* @package cchost
* @subpackage admin
*/
class CCFileProps
{
    function GetProps($format_dir,$type,$ret_files=true)
    {
        global $CC_GLOBALS;
        $tdirs = CCUtil::SplitPaths($CC_GLOBALS['template-root'], CC_DEFAULT_SKIN_SEARCH_PATHS );
        $k = array_keys($tdirs);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $tdirs[$k[$i]] = CCUtil::CheckTrailingSlash($tdirs[$k[$i]],false);
        }
        $results = array();
        $this->_scan_dir($results, $tdirs, $format_dir, $type, $ret_files);
        return $results;
    }

    function GetFileProps($filename)
    {
        $text = file_get_contents($filename);
        if( !preg_match('#.*\[meta\](.*)\[/meta\].*#ms',$text,$m) )
            return null;
        $lines = split("\n",$m[1]);
        $props = array();
        foreach( $lines as $line )
        {
            $line = trim($line);
            if( empty($line) )
                continue;
            $parts = split('=',$line);
            $props[ trim($parts[0]) ] = trim($parts[1]);
        }
        if( !empty($props['desc']) )
        {
            if( preg_match("/^_\(['\"](.+)['\"]\)$/",$props['desc'],$m) )
                $props['desc'] = _($m[1]);
        }
        return $props;
    }

    function _scan_dir(&$match_files, $source, $format_dir, $type, $ret_files )
    {
        foreach( $source as $dir )
        {
            if( $format_dir )
            {
                $format_path = $dir . '/' . $format_dir;
                if( !file_exists( $format_path ) )
                    continue;
            }
            else
            {
                $format_path = $dir;
            }

            $subdirs = array();
            foreach( glob( $format_path . '/*.*' ) as $ffile)
            {
                if( is_dir($ffile) )
                    continue;

                $props = $this->GetFileProps($ffile);
                if( !$props || ($props['type'] != $type) )
                    continue;

                if( $ret_files )
                {
                    if( empty($props['desc']) )
                    {
                        $match_files[$ffile] = $ffile;
                    }
                    else
                    {
                        $match_files[$ffile] = $props['desc'];
                    }
                }
                else
                {
                    $props['id'] = $ffile;
                    $match_files[] = $props;
                }
            }

            $subdirs = glob( $dir . '/*', GLOB_ONLYDIR );
            if( !empty($subdirs) )
                $this->_scan_dir($match_files, $subdirs, $format_dir, $type, $ret_files );
        }

        return $match_files;
    }
}

?>