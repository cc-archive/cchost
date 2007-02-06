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
*/
class CCQueryFormats
{
    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        global $CC_GLOBALS;

        extract($args);

        $results = array();

        $format = strtolower($format); // just in case he screwed the case

        $query = new CCQuery();

        switch( $format )
        {
            case 'json':
            case 'js':
            {
                // returning text/javascript to prototype.js will automatcially
                // execute the code, even if it has a JSON header (I think)
                // so we return text/plain on json to make sure that doesn't
                // happen. Secure? not a chance, maybe should 
                // return application/javascript (??)

                $mime = $format == 'js' ? 'text/javascript' : 'text/plain';

                require_once('cclib/zend/json-encoder.php');

                $text = '[';
                $k = array_keys($records);
                $count = count($k);
                $comm = '';
                for( $i = 0; $i < $count; $i++ )
                {
                    $R =& $records[$k[$i]];
                    $text .= $comm;
                    $query->CleanRec($R);
                    $filtered = array_filter($R);
                    $text .= CCZend_Json_Encoder::encode($filtered);
                    $comm = ',';
                }
                $text .= ']';
                $results = array( $text, $mime ); 
                break;
            }

            case 'xml':
            {
                $xml = "<ajax-response>\n" .
                       "   <response type=\"object\" id=\"upload_grid_updater\">\n" .  // {$R[$idname]}\">\n";
                       "      <rows upload_ui=\"true\">\n" ;
                $k = array_keys($records);
                $count = count($k);
                if( $count > 0 )
                {
                    $keys = array_keys($records[$k[0]]);

                    $idname = $keys[0];
                    for( $i = 0; $i < $count; $i++ )
                    {
                        $R =& $records[$k[$i]];
                        $query->CleanRec($R);
                        $xml .= "   <tr>\n"; // <record type=\"object\" id=\"{$R[$idname]}\">\n";
                        $this->_dump_xml_obj($R,$xml);
                        $xml .= "   </tr>\n";
                    }
                }
                $xml .= "     </rows>\n" .
                        "  </response>\n" .
                        "</ajax-response>\n";
                $results = array( $xml, 'text/xml' );
                break;
            }
            
            case 'html':
            case 'docwrite':
            {
                if( empty($macro) )
                {
                    if( empty($template) )
                        $template = 'med';

                    $tname = 'formats/' . $template . '.xml';

                    // normally we wouldn't go through extra step of
                    // actually looking up the template, but since
                    // this is a public api we want to be friendly
                    // about it...

                    if( !CCTemplate::GetTemplate($tname) )
                    {
                        // developer might not have put it in skins/formats dir
                        $tname = $template . '.xml';
                        if( !CCTemplate::GetTemplate($tname) )
                        {
                            print(sprintf(_('"%s" is not a valid template'), 
                                          $template));
                            exit;
                        }
                
                    }
                        
                    $templateObj = new CCTemplate($tname);
                }
                else
                {
                    $templateObj = new CCTemplateMacro($template,$macro);
                }

                $k = array_keys($records);
                $count = count($k);
                for( $i = 0; $i < $count; $i++ )
                    $query->CleanRec($records[$k[$i]]);

                $configs =& CCConfigs::GetTable();
                $targs = array_merge($configs->GetConfig('ttag'),$args);
                $targs['root-url'] = cc_get_root_url() . '/';
                $targs['home-url'] = ccl();
                $targs['records']  =& $records;
                $targs['dochop']   = isset($chop) && $chop > 0;
                $targs['chop']     = isset($chop) ? $chop : 25;
                $targs['q'] = $CC_GLOBALS['pretty-urls'] ? '?' : '&';

                if( !empty($template_args) )
                    $targs = array_merge($template_args);

                $text = $templateObj->SetAllAndParse($targs);
                
                if( $format == 'docwrite' )
                {
                    $lines = preg_split('/[\n\r]/',$text);
                    $text = '';
                    foreach( $lines as $L )
                       $text .= "document.write('" . addslashes($L) . "' + \"\\n\")\n";
                    $mime = 'application/javascript';
                }
                else
                {
                    $mime = '';
                }
                $results = array( $text, $mime );
                break;
            }

            case 'csv':
            {
                $k = array_keys($records);
                $count = count($k);
                // this is scary if called from the browser 
                // and there are gazillion records but not
                // every record exactly the same layout
                // (e.g. user_avatar_url might be in the 
                // first record, or it might not be) So....
                // we rifle through all the records to 
                // make sure we normalize all the keys
                $keys = array();
                for( $i = 0; $i < $count; $i++ )
                {
                    $keys = array_unique(array_merge( $keys, array_keys($records[$k[$i]]) ));
                }

                $text = join(',',$keys) . "\n";

                for( $i = 0; $i < $count; $i++ )
                {
                    $R =& $records[$k[$i]];
                    $query->CleanRec($R);
                    $fields = array();
                    foreach( $keys as $key )
                    {
                        if( !array_key_exists($key,$R) || is_array($R[$key]) )
                        {
                            $fields[] = '';
                        }
                        else
                        {
                            // I'm following the rules here:
                            // http://www.creativyst.com/Doc/Articles/CSV/CSV01.htm#FileFormat
                            if( preg_match('/(?:^\s|[\n\r,"]|\s$)/',$R[$key]) )
                                $fields[] = '"' . str_replace('"', '""', $R[$key]) . '"';
                            else
                                $fields[] = $R[$key];
                        }
                    }
                    $text .= join( ',', $fields );
                }
                $results = array( $text, 'plain/text' );
                break;
            }

        } // end switch

        if( empty($results) )
            return;

        list( $value, $mime ) = $results;

        // json returns value in header
        //
        if( $format == 'json' )
            header( "X-JSON: $value");

        if( !empty($mime) )
            header( "Content-type: $mime" );

        print($value);
        exit;
    }

    function _old_dump_xml_obj($obj,&$xml)
    {
        $keys = array_keys($obj);
        foreach( $keys as $key )
        {
            if( is_array( $obj[$key] ) )
            {
                if( is_integer($key) )
                    $keyname = 'rec_' . $key;
                else
                    $keyname = $key;
                $xml .= "      <$keyname>\n";
                $this->_dump_xml_obj( $obj[$key], $xml );
                $xml .= "      </$keyname>\n";
            }
            else
            {
                if( !empty($obj[$key]) )
                    $xml .= "      <$key>" . htmlentities($obj[$key]) . "</$key>\n";
            }
        }
    }

    function _dump_xml_obj($obj,&$xml)
    {
        $keys = array_keys($obj);
        foreach( $keys as $key )
        {
            if( is_array( $obj[$key] ) )
            {
            }
            else
            {
                $xml .= '        <td>';
                if( !empty($obj[$key]) )
                    $xml .= htmlentities($this->_cct($obj[$key]));
                $xml .= "</td>\n";
            }
        }
    }

    function _cct($str)
    {
        return( preg_replace('&[^a-zA-Z0-9()!@#$%^*-_=+\[\];:\'\"\\.,/?~ ]&','',$str ) );
    }
} // end of class CCQueryFormats


?>