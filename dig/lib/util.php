<?
/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

// make this available directly in dig
require_once('config.php');
require_once( $MIXTER_ROOT_DIR . '/cchost_lib/zend/json-encoder.php' );

class digArgs
{
    function digArgs($str_or_arr,$strip=array())
    {
        if( is_string($str_or_arr) )
        {
            $this->fromString($str_or_arr,$strip);
        }
        elseif( is_array($str_or_arr) )
        {
            $this->fromArray($str_or_arr,$strip);
        }
        else
        {
            $this->_clear();
        }
        
    }
    
    function _clear()
    {
        $this->args            = array();
        $this->query_str           = '';
        $this->stripped        = array();
        $this->stripped_query_str  = '';        
    }
    
    function fromString($http_query_str, $strip = array())
    {
        $this->_clear();
        
        if( strpos($http_query_str,'?') !== false )
        {
            $temp = explode('?',$http_query_str);
            if( count($temp) > 1 )
            {
                $this->query_str = $temp[1];
            }
        }
        else
        {
            $this->query_str = $http_query_str;
        }
    
        parse_str($this->query_str,$this->args);
        $this->stripped = $this->args;
        $this->stripped_query_str = $this->stripped;

        if( !empty($strip) )
        {
            strip_keys($this->stripped,$strip);
            $this->stripped_query_str = http_build_query($this->stripped);
        }
        
        $this->_setup_tags();
    }
    
    function _setup_tags()
    {
        // special case tag fields because they
        // require merging
        
        foreach( array('tags','reqtags','dig-tags') as $K )
        {            
            if( empty($this->args[$K]) )
            {
                $this->$K = null;
            }
            else
            {
                $this_field = $K == 'dig-tags' ? 'tags' : $K;
                $this->$this_field = preg_split('/[^a-z0-9_-]+/',$this->args[$K]);
            }
        }
    }
    
    function fromArray($args,$strip=array())
    {
        $this->_clear();
        $this->args = $this->stripped = $args;
        $this->query_str = http_build_query($this->args);
        if( !empty($strip) )
            strip_keys($this->stripped,$strip);
        $this->stripped_query_str = http_build_query($this->stripped);
        $this->_setup_tags();
    }
}

function cpy_keys(&$to_here, $from_here, $keymap )
{
    $keys = array_keys($keymap);
    
    foreach( $keys as $K )
    {
        if( array_key_exists($K,$from_here) )
        {
            $to_here[$keymap[$K]] = $from_here[$K];
        }
    }
}

function strip_keys(&$arr,$keys)
{
    foreach( $keys as $K )
    {
        if( array_key_exists($K,$arr) )
        {
            unset($arr[$K]);
        }
    }
}

function dbg(&$obj)
{
    $str =& _textize($obj);
    $html = '<pre style="font-size: 10pt;text-align:left;">' .
            htmlspecialchars($str) .
            '</pre>';
    print("<html><body>$html</body></html>");
    exit;
}

function & _textize(&$var)
{
    ob_start();
    if( is_array($var) || is_object($var) || is_resource($var) )
        print_r($var);
    else
        var_dump($var);
    $t = ob_get_contents();
    ob_end_clean();

    $r =& $t;
    return($r);
}

function strip_slash(&$mixed)
{
    if( get_magic_quotes_gpc() == 1 )
    {
        if( is_array($mixed) )
        {
            $keys = array_keys($mixed);
            foreach( $keys as $key )
                $mixed[$key] = strip_slash($mixed[$key]);
        }
        else
        {
            $mixed = trim(stripslashes( $mixed ));
        }
    }
    return($mixed);
}

?>