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

if( empty($no_ui) )
    require_once('cclib/cc-page.php');

/**
*
*
*/
class CCSettingsExporter
{
    function Import($fname='')
    {
        global $no_ui;

        if( !empty($_REQUEST['i']) )
            $fname = CCUtil::StripText($_REQUEST['i']);

        if( empty($fname) )
        {
            $msg = _('ERROR: No import file specified.');
            if( empty($no_ui) )
            {
                CCPage::Prompt($msg);
                return;
            }
            die($msg);
        }

        $this->ImportRead($fname);

        if( empty($no_ui) )
        {
            CCPage::SetTitle(_('Import Settings'));
            CCPage::Prompt(_('Settings have been imported'));
        }
    }

    function ImportRead($fname)
    {
        include($fname);

        if( empty($cc_host_config_export) )
            die(_('ERROR: could not read config file'));

        $configs =& CCConfigs::GetTable();
        $configs->DeleteWhere('1');
        $d =& $cc_host_config_export;

        $c = count($d);

        $got_string_map = false;

        for( $i = 0; $i < $c; $i++ )
        {
            if( $d[$i]['config_type'] == 'clangmap' )
            {
                // gotta add the language map before serializing 
                // string will work
                $d[$i]['config_data'] = serialize($d[$i]['config_data']);
                $configs->Insert($d[$i]);
                unset($d[$i]);
                $got_string_map = true;
                break;
            }
        }

        if( !$got_string_map )
        {
            // this should only happen in cases of 
            // transitioning between 3.2 and earlier
            // versions or upgrading to 5

            $configs->SaveDefaultCfgStringMap();
        }

        $keys = array_keys($d);
        $c = count($keys);
        $columns = array( 'config_type', 'config_scope', 'config_data' );
        for( $i = 0; $i < $c; $i++ )
        {
            $k = $keys[$i];
            //print('doing:' .$d[$k]['config_type'] . "\n");
            $d[$k]['config_data'] = $configs->CfgSerialize($d[$k]['config_type'],$d[$k]['config_data']);
        }
        $configs->InsertBatch( $columns, $d );
    }


    /**
    *
    */
    function Export()
    {
        global $no_ui;

        if( empty($no_ui) )
            header("Content-type: application/text-editor");

        $dohash    =  !empty($_REQUEST['h']);
        $dourlmap  =  !empty($_REQUEST['u']);
        $this->ExportPrint($dohash,$dourlmap,$dostrhash);
        exit;
    }

    function ExportPrint($dohash,$dourlmap)
    {
        // dohash := UnHash the strings. If this is false then we leave the strings as they are (hashed)
        //           and we output the hashtable.

        $configs =& CCConfigs::GetTable();
        $allrows = $configs->QueryRows('');

        $level = 0;
        
        print("<?\n\nif( !defined('IN_CC_HOST') ) exit; \n\n\$cc_host_config_export = array ( \n\n ");
        
        foreach( $allrows as $row )
        {
            if( ($row['config_type'] == 'strhash') && $dohash ) // we need the hash 
                continue;
            if( ($row['config_type'] == 'urlmap') && !$dourlmap )
                continue;

            print( "    array( \n" .
                   "        'config_type'  => '{$row['config_type']}',\n" .
                   "        'config_scope' => '{$row['config_scope']}',\n" .
                   "        'config_data'  => " );

            if( $dohash )
                $data = $configs->CfgUnserialize($row['config_type'],$row['config_data']);
            else
                $data = unserialize($row['config_data']);

            $this->_dump_data($data,0);

            print( "     ),\n" );
        }

        print("   ); \n\n ?>" );
    }


    function _dump_data(&$data,$level)
    {
        $tabs = array( '        ',
                       '            ',
                       '                ',
                       '                    ',
                       '                         ' );

        print("array (\n");
        foreach($data as $key => $item)
        {
            print('    ' . $tabs[$level]);
            if( is_numeric( $key ) )
            {
                print( "$key => ");
            }
            else
            {
                print("'$key' => ");
            }
            if( is_object( $item ) )
            {
                $d = str_replace("'","\\'",serialize($item));
                print( " unserialize('$d'), \n" );
            }
            elseif( is_array( $item ) )
            {
                $this->_dump_data($item,$level+1);
            }
            else
            {
                if( empty($item) )
                {
                    print( "'',\n");
                }
                elseif( is_numeric($item) )
                {
                    print( "$item,\n");
                }
                else
                {
                    $d = str_replace("'","\\'",$item);
                    print("'$d',\n");
                }
            }
        }

        print( '    ' . $tabs[$level] . "),\n" );
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('export'), array( 'CCSettingsExporter', 'Export'),
                          CC_ADMIN_ONLY, ccs(__FILE__), '', 
                          _('Exports configuration to browser'), 
                          CC_AG_MISC_ADMIN );

        CCEvents::MapUrl( ccp('import'), array( 'CCSettingsExporter', 'Import'),
                          CC_ADMIN_ONLY, ccs(__FILE__), '?i=path_to_file', 
                          _('Import configuration'), CC_AG_MISC_ADMIN );
    }

}



?>
