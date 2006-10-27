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

CCEvents::AddHandler(CC_EVENT_API_QUERY_FORMAT,   array( 'CCQueryFormats',  'OnApiQueryFormat')); 
//CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCQueryFormats' , 'OnGetConfigFields') );

/**
*/
class CCQueryFormats
{
    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        extract($args);

        $results = array();

        $format = strtolower($format); // just in case he screwed the case

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

                $text = '[';
                $count = count($records);
                $comm = '';
                for( $i = 0; $i < $count; $i++ )
                {
                    $text .= $comm;
                    $R =& $records[$i];
                    $keys = array_keys($R);
                    $comma = '';
                    foreach( $keys as $key )
                    {
                        if( !is_array($R[$key]) )
                        {
                            $text .= $comma . $key . ":'" . addslashes($R[$key]) . "'";
                            $comma = ',';
                        }
                    }
                    $comm = ',';
                }
                $text .= ']';
                $results = array( $text, $mime ); 
                break;
            }

            case 'xml':
            {
                $xml = "<records>\n";
                $count = count($records);
                for( $i = 0; $i < $count; $i++ )
                {
                    $R =& $records[$i];
                    $xml .= "   <record type=\"object\" id=\"{$R['upload_id']}\">\n";
                    $keys = array_keys($R);
                    foreach( $keys as $key )
                    {
                        if( !is_array( $R[$key] ) )
                            $xml .= "      <$key>" . htmlentities($R[$key]) . "</$key>\n";
                    }
                    $xml .= "   </record>\n";
                }
                $xml .= "</records>\n";
                $results = array( $xml, 'text/xml' );
                break;
            }
            
            case 'html':
            case 'docwrite':
            {
                if( empty($template) )
                    $template = 'med';
                $tname = 'format_' . $template . '.xml';

                $configs =& CCConfigs::GetTable();
                $targs = $configs->GetConfig('ttag');
                $targs['root-url'] = cc_get_root_url() . '/';
                $targs['home-url'] = ccl();
                $targs['records']  =& $records;
                $targs['dochop']   = isset($chop) && $chop > 0;
                $targs['chop']     = isset($chop) ? $chop : 25;
                if( !empty($template_args) )
                    $targs = array_merge($template_args);

                // normally we wouldn't go through extra step of
                // actually looking up the template, but since
                // this is a public api we want to be friendly
                // about it...
                $found_template = CCTemplate::GetTemplate($tname);
                if( !$found_template )
                {
                    print("\"$template\" is not a valid template");
                    exit;
                }

                $templateObj = new CCTemplate($tname);
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
                $count = count($records);
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
                    $keys = array_unique(array_merge( $keys, array_keys($records[$i]) ));
                }

                $text = join(',',$keys) . "\n";

                for( $i = 0; $i < $count; $i++ )
                {
                    $R =& $records[$i];
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

    function _clean_rec(&$R)
    {
        $fields = array( 'user_email', 'user_password', 'user_last_known_ip' );
        foreach( $fields as $f )
            if( isset($R[$f]) ) unset($R[$f]);
    }


    /**
     * @access private
     */
    function _resort_records(&$records,&$sort_order)
    {
        if( !empty($sort_order) )
        {
            $sorted = array();
            $count = count($records);
            for( $i = 0; $i < $count; $i++ )
            {
                $sorted[ $sort_order[ $records[$i]['upload_id'] ] ] = $records[$i];
            }
            $records = $sorted;
            $sorted = null;
            ksort($records);
        }
    }

} // end of class CCQueryFormats


?>
