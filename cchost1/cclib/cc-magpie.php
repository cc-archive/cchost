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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/magpie/rss_fetch.inc');
require_once('cclib/magpie/rss_parse.inc');


class CCStatusParser extends MagpieRSS
{
    var $status;
    var $element;
    var $good_elements;

    function CCStatusParser ($source, $output_encoding='ISO-8859-1', 
                        $input_encoding=null, $detect_encoding=true) 
    {
        $this->element = null;
        $this->good_elements = array( 'status', 'message' );

        foreach( $this->good_elements as $e )
        {
            $this->status[$e] = '';
        }
        $this->MagpieRSS($source, $output_encoding, $input_encoding, $detect_encoding);
    }

    function IsSuccess()
    {
        return( $this->status['status'] == 'ok' );
    }

    function feed_start_element($p, $element, &$attrs) 
    {
        if( in_array( $element, $this->good_elements ) )
            $this->element = $element;
        else
            $this->element = null;
    }

    function feed_cdata ($p, $text) 
    {
        if( isset($this->element ) )
            $this->status[$this->element] = $text;
    }

    function feed_end_element($p, $element) 
    {
    }

    function normalize()
    {
    }
}

class CCMagpieParser extends MagpieRSS
{
    var $cc_attr_stack = array();
    var $in_author;

    function CCMagpieParser ($source, $output_encoding='ISO-8859-1', 
                        $input_encoding=null, $detect_encoding=true) 
    {
        $this-> MagpieRSS($source, $output_encoding, $input_encoding, $detect_encoding);
        $this->in_author = false;
    }

    function feed_start_element($p, $element, &$attrs) {
        parent::feed_start_element($p, $element, &$attrs);
        if( strstr($element,':') )
            list( , $element ) = split( ':', $element, 2); 
        $element = strtolower($element);
        if( $element == 'enclosure' )
        {
            $this->current_item['enclosure'] = $attrs;
        }
        elseif( $element == 'license' )
        {
            $keys = array_keys($attrs);
            $url = $attrs[$keys[0]];
            if( preg_match('#^http://creativecommons.org/licenses#',$url) )
                $this->current_item['license_url'] = $url;
        }
        
        $this->in_author = $element == 'author';
    }

    function feed_cdata ($p, $text) {
        if( $this->in_author )
        {
            $this->current_item['artist'] = $text;
            $this->in_author = false;
        }
        parent::feed_cdata ($p, $text);

    }


    function normalize()
    {
        if( !isset($this->channel['description']) )
            $this->channel['description'] = '';
            
        if( !isset($this->channel['tagline']) )
            $this->channel['tagline'] = '';

        parent::normalize();

        $count = count($this->items);
        $keys = array_keys($this->items);
        for( $i = 0; $i< $count; $i++ )
        {
            $I =& $this->items[ $keys[$i] ];
            if( !isset($I['artist']) )              $I['artist'] = '';
            if( !isset($I['enclosure']['url']) )    $I['enclosure']['url'] = '';
            if( !isset($I['enclosure']['length']) ) $I['enclosure']['length'] = '';
            if( !isset($I['enclosure']['type']) )   $I['enclosure']['type'] = '';
            if( !isset($I['category']) )            $I['category'] = '';
            if( !isset($I['guid']) )                $I['guid'] = '';
            if( !isset($I['license_url']) )         $I['license_url'] = '';
            if( !isset($I['description']) )         $I['description'] = '';
            if( !isset($I['date_timestamp']) )      $I['date_timestamp'] = '';
        }
    }

    function concat (&$str1, $str2="") {
        if (!isset($str1) ) {
            $str1="";
        }
        $str1 .= ' ' . $str2;
    }
    

}

?>