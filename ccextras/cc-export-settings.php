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

CCEvents::AddHandler(CC_EVENT_MAP_URLS, array( 'CCSettingsExporter',  'OnMapUrls'));

/**
*
*
*/
class CCSettingsExporter
{
    function Import()
    {
        if( !empty($_REQUEST['i']) )
            $fname = CCUtil::StripText($_REQUEST['i']);
        if( empty($fname) )
        {
            CCPage::Prompt(_('No import file specified in the URL.'));
            return;
        }

        include($fname);

        // CCDebug::PrintVar($cc_host_config_export);

        $configs =& CCConfigs::GetTable();
        $configs->DeleteWhere('1');
        $columns = array( 'config_type', 'config_scope', 'config_data' );
        $d =& $cc_host_config_export;
        $c = count($d);
        for( $i = 0; $i < $c; $i++ )
            $d[$i]['config_data'] = serialize($d[$i]['config_data']);
        $configs->InsertBatch( $columns, $cc_host_config_export );
        CCPage::SetTitle(_('Import Settings'));
        CCPage::Prompt(_('Settings have been imported'));
    }

    /**
    *
    */
    function Export()
    {
        $configs =& CCConfigs::GetTable();
        $allrows = $configs->QueryRows('');

        header("Content-type: text/plain");

        $level = 0;
        print("<?\n\nif( !defined('IN_CC_HOST') ) exit; \n\n\$cc_host_config_export = array ( \n\n ");
        foreach( $allrows as $row )
        {
            print( "    array( \n" .
                   "        'config_type'  => '{$row['config_type']}',\n" .
                   "        'config_scope' => '{$row['config_scope']}',\n" .
                   "        'config_data'  => " );
            $data = unserialize($row['config_data']);
            $this->_dump_data($data,0);
            print( "     ),\n" );
        }
        print("   ); \n\n ?>" );
        exit;
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
