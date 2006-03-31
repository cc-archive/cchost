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

/**
* Internal class used for event triggering
*/
class CCAction
{
    function _dummy() {}
}

/**
* Invoking and registering system wide events. 
*  
* You can register for an event (CCEvents::AddHandler()) so that when some code, somewhere triggers the
* event your code will be called.
*  
* You can also define an event and then invoke it (CCEvents::Invoke()) and get
* results back.
*  
* Using this system allows for modules to come and go, extending the system without 
* disturbing or rewriting the core code. Typical events are for when a menu is being
* constructed, when a row is fetched from a database, when a file is done being uploaded,
* etc. etc.
*  
* Events that are mapped to URLs are handled separately via CCEvents::MapUrl().
*
* @see AddHandler, Invoke, MapUrl
*/
class CCEvents
{
    /**
    * Call this to register (wait) for an event.
    *  
    * This is typically done outside of any function or class at the top of a source file
    * before the application is really going. This is the only way to ensure that you
    * don't actually miss the firing of the event.
    *  
    * <code>
    *  
    *    CCEvents::AddHandler(CC_EVENT_MAIN_MENU,   array( 'CCID3Tagger', 'OnBuildMenu') );
    *  
    * </code>
    *  
    * The <b>$eventname</b> parameter is typically a descriptively named define(). By
    * convention event defines all start with <b>CC_EVENT_</b>. 
    *  
    * The <b>$callback</b> parameter is an extended version of PHP's callback. It either be
    * a string (function name) or an array that has an instance of that class and the 
    * string method name.
    *  
    * As an extension you can make the first element in the callback array a string,
    * the name of the class with the callback. The class will only instantiated if
    * and when the event is triggered. (The class must have no constructor or an
    * empty parameter constructor.) 
    *  
    * The signature of the callback is different for every event and can not have
    * additional parameters (although it can have less). Every event publisher/invoker
    * must specify what it expects to pass along to the event listeners.
    *  
    * Event triggering is synchronous (which means each callback blocks any other callback).
    * It is therefore recommended that if the callback is a class that needs to be instantiated,
    * then the class should as little creation overhead as possible since there might be many, 
    * many others waiting for the same event (like when building the main menu). 
    *  
    * The <b>$includefile</b> paramater is not currently implemented but will provide
    * a way to dynamically load an entire file that has the callback implementation.
    *  
    * @param string $eventname Unique system-wide name for the event
    * @param mixed  $callback Either string method name or extended callback array
    * @param string $includefile (not used)
    * @see Invoke
    */
    function AddHandler($eventname, $callback, $includefile='')
    {
        $events =& CCEvents::_events();
        if( array_key_exists($eventname,$events) )
            $handlers = $events[$eventname];
        else
            $handlers = array();
        $handlers[] = $callback;
        $events[$eventname] = $handlers;
    }

    /**
    * Invokes a system wide event, optionally with parameters.
    *  
    * Most events are triggered at extensibility points, this way
    * the calling code can remain 'clean' without knowlegde of 
    * who is implementing the event handler.
    *  
    * <code>
    *    
    *     // file is uploaded, database record, let add-in modules
    *     // have a go at the file and record. 
    *     
    *     CCEvents::Invoke( CC_EVENT_FINALIZE_UPLOAD, array( &$record ) );
    * </code>
    *  
    *  
    * Other times they are used to gather information from disparate
    * places and multiple providers.
    *  
    * <code>
    *  
    *     // Only proceed if 'image' is a valid media type
    *  
    *     $types = array();
    *     CCEvents::Invoke( CC_EVENT_VALID_MEDIA_TYPES, array(&$types) );
    *     if( in_array('image',$types) )
    *     {
    *         //...
    *     }
    *</code>
    *  
    * Arguments are passed in an array because that is the only way to 
    * ensure that references are kept throughout the invocation. 
    *  
    * @param string $eventname Unique system-wide name for the event.
    * @param array  $args Array of parameters to pass along to event listeners.
    * @see AddHandler
    */
    function Invoke($eventname,$args=array())
    {
        $hook_list =& CCEvents::_hooks();
        if( !empty($hook_list) )
        {
            foreach( $hook_list as $hook_handler )
            {
                if( is_array($hook_handler) && is_string($hook_handler[0]))
                {
                    $class = $hook_handler[0];
                    $method = $hook_handler[1];
                    $obj = new $class;
                    $hook_handler = array( $obj, $method );
                }
                $hargs = array( $eventname, $args );
                if( call_user_func($hook_handler,$hargs) === false )
                    return;
            }
        }

        $events  =& CCEvents::_events();
        $results = array();
        if( array_key_exists($eventname,$events) )
        {
            foreach( $events[$eventname] as $handler )
            {
                if( is_array($handler) && is_string($handler[0]))
                {
                    $class = $handler[0];
                    $method = $handler[1];
                    $obj = new $class;
                    $handler = array( $obj, $method );
                }
                $results[] = call_user_func_array($handler,$args);
            }
        }

        return($results);
    }
  
    function & _hooks()
    {
        static $_hook_list;
        return( $_hook_list);
    }

    function AddHook( $func )
    {
        $hook_list =& CCEvents::_hooks();
        $hook_list[] = $func;
    }

    /**
    * Maps incoming urls to functions/methods
    *
    * You call this method in your event handler for CC_EVENT_MAP_URLS
    * It will tell the system what method to call in repsone to incoming URLs
    *
    * The system uses a 'drupal' method of scoping. The more specific mapping
    * is always respected first. If no handler is found for a specific url
    * the trailing part of the url is assumed to be arguments to the method
    * that handles the base url.
    * 
    * For example:
    * <code>
           
// Given:
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'MyClass' , 'OnMapUrls'));

class MyClass
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'foo',      
                          array( 'MyClass', 'HandleFoo'),    CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'foo/bar',  
                           array( 'MyClass', 'HandleFooBar'), CC_MUST_BE_LOGGED_IN );
    }
 }

 // Here is what the mapping looks like:
 //
 //    URL                             Method called
 //  ------                            ----------------
 //  http://cchost.org/media/foo       $this->HandleFoo()
 //  http://cchost.org/media/foo/bar   $this->HandleFooBar()
 //  http://cchost.org/media/foo/BAZ   $this->HandleFoo('BAZ')
 //

      </code>
    *
    * 
    * @param string $url What the incoming url looks like stripped of domain and vroot
    * @param mixed $callback Method to be called 
    * @param integer $permissions CC_* flags to mask off unauthorized users
    */
    function MapUrl( $url, $callback, $permissions )
    {
        $action              = new CCAction();
        $action->callback    = $callback;
        $action->permissions = $permissions;
        $paths =& CCEvents::_paths();
        $paths[$url] = $action;
    }

    /**
    * Creates ant straigh-across mapping between two urls
    *
    * @param string $this_url Incoming URL 
    * @param string $becomes_this Outgoing aliases URL
    */
    function AddAlias( $this_url, $becomes_this )
    {
        $aliases =& CCEvents::_aliases();
        $aliases[$this_url] = $becomes_this;
    }

    /**
    * Grabs the current incoming URL and calls the approproate method mapped to it
    *
    */
    function PerformAction($action = null )
    {
        if( !isset($action) )
            $action = CCEvents::ResolveUrl();

        if( isset($action) )
        {
            if( ($action->permissions & CCMenu::GetAccessMask() ) == 0 )
                $action = CCEvents::ResolveUrl('/homepage');

            if( is_string($action->callback) )
            {
                $method = $action->callback;
            }
            else
            {
                if( is_string($action->callback[0]) )
                {
                    $obj = new $action->callback[0];
                    $method = array( &$obj, $action->callback[1] );
                }
                else
                {
                    $method = $action->callback;
                }
            }
            
            if( !isset($action->args) )
                $action->args = array();

            call_user_func_array($method,$action->args);
        }
        else
        {
            CCPage::SystemError("Invalid path");
        }
    }

    function _send_no_cache_headers()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        // always modified
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
         
        // HTTP/1.1
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);

        // HTTP/1.0
        header("Pragma: no-cache");
    }

    /**
    * Convert an url into an internal action structure.
    * 
    */
    function ResolveUrl($url='')
    {
        global $CC_GLOBALS;

        $regex = '%/([^/\?]+)%';

        if( empty($url) )
        {
            preg_match_all($regex,CCUtil::StripText($_REQUEST['ccm']),$a);
            array_shift($a[1]);
            $A =& $a[1];
        }
        else
        {
             preg_match_all($regex,$url,$a);
             $A =& $a[1];
        }

        if( empty($A) )
            $P = 'homepage';
        else
            $P = implode('/',$A);

        $paths          =& CCEvents::GetUrlMap();
        $current_action =& CCEvents::_current_action();
        $aliases        =& CCEvents::_aliases();

        if( array_key_exists($P,$aliases) )
        {
             $P = $aliases[$P];
             preg_match_all($regex,$P,$a);
             $A =& $a[1];
        }

        $current_action = $P;

        $argcount  = 0;

        while( $P )
        {
            if( array_key_exists($P,$paths) )
            {
                $action = $paths[$P];
                $shiftby = count($A) - $argcount;
                for( $i = 0; $i < $shiftby; $i++ )
                    array_shift($A);
                $action->args = $A;
                return( $action );
            }
            $P = substr( $P, 0, strrpos($P,'/') );
            if( $P )
               $argcount++;
        }
    }

    function & GetUrlMap($force = false)
    {
        $paths =& CCEvents::_paths();
        $configs =& CCConfigs::GetTable();
        if( !$force && empty($paths) )
        {
            $paths = $configs->GetConfig('urlmap');
        }
        if( $force || empty($paths) )
        {
            CCEvents::Invoke(CC_EVENT_MAP_URLS);
            $configs->SaveConfig('urlmap',$paths,CC_GLOBAL_SCOPE);
        }
        return($paths);
    }

    /**
    * Internal goody
    */
    function & _paths()
    {
        static $_paths;
        if( !isset($_paths) )
            $_paths = array();
        return( $_paths );
    }

    /**
    * Internal goody
    */
    function & _events()
    {
        static $_events;
        if( !isset($_events) )
            $_events = array();
        return( $_events );
    }

    /**
    * Internal goody
    */
    function & _current_action()
    {
        static $_current_action;
        return( $_current_action );
    }

    /**
    * Internal goody
    */
    function & _aliases()
    {
        static $_aliases;
        if( !isset($_aliases) )
            $_aliases = array();
        return( $_aliases );
    }

}


?>