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
* @subpackage feature
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS, array( 'CCTrackback', 'OnMapUrls') );

class CCTrackBack
{
    function Track($type,$upload_id)
    {
        require_once('cclib/cc-pools.php');
        $link = $this->_clean_url($_POST['trackback_link']);
        $pool_id = $this->_get_web_sample_pool();
        $pool_item_id = CCDatabase::QueryItem("SELECT pool_item_id FROM cc_tbl_pool_item WHERE pool_item_url = '$link'");
        $pool_items = new CCPoolItems();
        if( empty($pool_item_id) )
        {
            $new = true;
            $upload_license = CCDatabase::QueryItem('SELECT upload_license FROM cc_tbl_uploads WHERE upload_id='.$upload_id);
            $email = CCUTil::Strip($_POST['trackback_email']);
            $name = CCUTil::Strip($_POST['trackback_your_name']);
            if( empty($name) )
                $name = $email;

            $a['pool_item_id'] = $pool_item_id = $pool_items->NextID();
            $a['pool_item_pool']           = $pool_id;
            $a['pool_item_url']            = $link;
            $a['pool_item_download_url']   = '';
            $a['pool_item_description']    = '';
            $a['pool_item_extra']          = serialize( array( 'ttype'     => $type, 
                                                               'embed'     => CCUtil::StripSlash($_POST['trackback_media']),
                                                               'poster'    => $name,
                                                               'email'     => $email,
                                                               'upload_id' => $upload_id,
                                                        ) );
            $a['pool_item_license']        = $upload_license;
            $a['pool_item_name']           = $this->_get_item_name(CCUtil::Strip($_POST['trackback_name']),$link);
            $a['pool_item_artist']         = $this->_get_item_user(CCUtil::Strip($_POST['trackback_artist']),$link);
            $a['pool_item_approved']       = 0;
            $a['pool_item_timestamp']      = time();
            $a['pool_item_num_remixes']    = 0;
            $a['pool_item_num_sources']    = 0;

            $pool_items->Insert($a);
        }
        $pool_tree = new CCPoolTree();
        $x['pool_tree_parent'] = $upload_id;
        $x['pool_tree_pool_child'] = $pool_item_id;
        $pool_tree->Insert($x);
        if( empty($new) )
        {
            require_once('cclib/cc-sync.php');
            CCSync::Upload($upload_id);
        }

        print 'ok';
        exit;
    }

    function _get_item_name($name,$link)
    {
        if( !empty($name) )
            return $name;
        require_once('cclib/snoopy/Snoopy.class.php');
        $snoopy = new Snoopy();
        $snoopy->fetch($link);
        if( !empty($snoopy->error) )
            $this->_error_out($snoopy->error);
        if( preg_match( '/<meta name="title" content="([^"]+)">/U',$snoopy->results,$m ) )
            return $m[1];
        if( preg_match( '#<title>([^<]+)</title>#',$snoopy->results,$m) )
            return $m[1];
        if( preg_match( '#/([^/]+)$#',$link,$m) )
            return $m[1];
        return substr( str_replace('http://','',$link), 0, 20 );
    }

    function _get_item_user($user,$link)
    {
        if( !empty($user) )
            return $user;
        $purl = parse_url($link);
        return str_replace('www.','',$purl['host']);
    }


    function _clean_url($text)
    {
        if( substr($text,0,7) != 'http://' )
            $text = 'http://' . $text;
        return trim($text);
    }

    function _error_out($msg)
    {
        print $msg;
        exit;
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('track'),           array('CCTrackBack', 'Track'),   CC_DONT_CARE_LOGGED_IN, ccs(__FILE__) );
    }

    function _get_web_sample_pool()
    {
        $pool_id = CCDatabase::QueryItem('SELECT pool_id FROM cc_tbl_pools WHERE pool_short_name = \'_web\'');
        if( empty($pool_id) )
        {
            require_once('cclib/cc-pools.php');
            $pools = new CCPools();
            $a['pool_id'] = $pools->NextID();
            $a['pool_name'] = _('Trackback Sitings');
            $a['pool_short_name'] = '_web';
            $a['pool_description'] = _('People link to us!');
            // pool_api_url can ba a local module for searching the pool
            // classname:module_path
            // CCMagnatune:mixter-lib/mixter-magnatune.inc
            $a['pool_api_url'] = '';
            $a['pool_site_url'] = ccl();
            $a['pool_ip'] = '255.0.0.0';
            $a['pool_banned'] = 0;
            $a['pool_search'] = 0;
            $a['pool_default_license'] = '';
            $a['pool_auto_approve'] = 0;
            $pools->Insert($a);
            $pool_id = $a['pool_id'];
        }
        return $pool_id;
    }
}
?>
