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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCViewLogs', 'OnMapUrls') );

define('CC_MAX_LOGBUFFER', (100 * 1024));
/**
*
*
*/
class CCViewLogs
{
    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('admin','logs'), array('CCViewLogs','View'), CC_ADMIN_ONLY,
                ccs(__FILE__), '[log|error]', _('ajax call to view log files'),
                CC_AG_ADMIN_MISC );
        CCEvents::MapUrl( ccp('admin','logs','archive'), array('CCViewLogs','Archive'), CC_ADMIN_ONLY,
                ccs(__FILE__), '[log|error]', _('Archive logs.'),
                CC_AG_ADMIN_MISC );
    }

    function Archive($type='')
    {
        if( empty($type) )
            CCUtil::Send404();

        global $CC_GLOBALS;

        $logdir = empty($CC_GLOBALS['logfile-dir']) ? './' : $CC_GLOBALS['logfile-dir'];
        if( $type == 'error' )
        {
            $srcfile = $logdir. CC_ERROR_FILE;
            $parts = split('\.',CC_ERROR_FILE);
        }
        elseif( $type == 'log' )
        {
            $srcfile = $logdir . CC_LOG_FILE;
            $parts = split('\.',CC_LOG_FILE);
        }
        else
            CCUtil::Send404();

        if( !file_exists($srcfile) )
        {
            print sprintf( _('%s does not exist'), $srcfile );
            exit;
        }
        $target_name = $logdir . $parts[0] . '_' . date('Y-m-d') . '.' . $parts[1];

        rename($srcfile,$target_name);

        print sprintf( _('Log renamed to %s'), $target_name );
        exit;
    }

    function View($type='')
    {
        if( empty($type) )
            CCUtil::Send404();

        global $CC_GLOBALS;

        $logdir = empty($CC_GLOBALS['logfile-dir']) ? './' : $CC_GLOBALS['logfile-dir'];
        if( $type == 'error' )
            $filename = $logdir. CC_ERROR_FILE;
        elseif( $type == 'log' )
            $filename = $logdir . CC_LOG_FILE;
        else
            CCUtil::Send404();

        if( !file_exists($filename) )
        {
            print sprintf(_('%s does not exist'),$filename);
            exit;
        }
        $size = filesize($filename);
        if( $size > CC_MAX_LOGBUFFER )
        {
            print _('Log file is larger than 100K so this is the truncated version:');
            if( !($f = fopen($filename,'r')) )
            {
                print sprintf(_('Error trying to open %s',$filename));
                exit;
            }
            if( fseek($f,-CC_MAX_LOGBUFFER,SEEK_END) == -1 )
            {
                print sprintf(_('Error during seek of %s'),$filename);
                exit;
            }
            if( ($text = fread($f,CC_MAX_LOGBUFFER)) === false )
            {
                print sprintf(_('Error reading %s'),$filename);
                exit;
            }
            fclose($f);
        }
        else
        {
            $text = file_get_contents($filename);
        }
        print str_replace( "\n", '<br />', $text );
        exit;
    }

}



?>