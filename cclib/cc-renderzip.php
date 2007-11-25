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
* @subpackage archive
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-render.php');

/**
* @package cchost
* @subpackage archive
*/
class CCRenderZip extends CCRender
{
    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    * @see CCTable::GetRecordFromRow()
    */
    function OnUploadRow(&$record)
    {
        if( empty($record['works_page']) || !CCUploads::InTags('zip',$record) )
            return;

        CCUpload::EnsureFiles($record,true);

        $need_macro = false;
        foreach( $record['files'] as $file )
        {
            if( !empty($file['file_format_info']['zipdir']) )
            {
                $dir = array( 'dir' => $file['file_format_info']['zipdir'],
                              'name' => $file['file_nicname']
                             );
                /* break; **/
                $record['zipdirs'][] = $dir;
                $need_macro = true;
            }
        }

        if( $need_macro )
            $record['file_macros'][] = 'show_zip_dir';
    }

    function OnFilterMacros(&$records)
    {
        $k = array_keys($records);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];
            if( !CCUploads::InTags('zip',$R) )
                continue;
            $need_macro = false;
            $kf = array_keys($R['files']);
            $kc = count($kf);
            for( $ki = 0; $ki < $kc; $ki++ )
            {
                $F =& $R['files'][$kf[$ki]];
                if( empty($F['file_format_info']['zipdir'] ) )
                    continue;
                $R['zipdirs'][] = array( 'dir' => &$F['file_format_info']['zipdir'],
                                              'name' => $F['file_nicname']
                                            );
                $need_macro = true;
            }
            if( $need_macro )
                $R['file_macros'][] = 'show_zip_dir';
        }
    }
}


?>
