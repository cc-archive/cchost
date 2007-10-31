<?


if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCQueryBrowser', 'OnMapUrls') );

class CCQueryBrowser
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('browse'),     array( 'CCQueryBrowser', 'Browse'),    CC_DONT_CARE_LOGGED_IN ); 
            /*, 'ccextras/cc-forums.inc', '{user_name}', 
            _('Display forum topics for user'), CC_AG_FORUMS ); */
    }

    function Browse()
    {
        if( isset($_GET['user_lookup']) )
        {
            $user_mask = trim(CCUtil::Strip($_GET['user_lookup']));
            if( $user_mask )
            {
                // yes, yes, it's a hack
                $sql = "SELECT user_name as un, " .
                       "IF(user_name = user_real_name,user_name,CONCAT(user_real_name,' (',user_name,')')) as ut ".
                       ' FROM cc_tbl_user WHERE ' .
                       "((user_name LIKE '{$user_mask}%') OR (user_real_name LIKE '{$user_mask}%')) AND " .
                       ' user_num_uploads > 0 ' .
                       ' ORDER BY user_name ASC';
                $this->_output_q($sql);
            }
            exit;
        }
        else if( isset($_GET['tag_lookup']) )
        {
            $tag_mask = trim(CCUtil::Strip($_GET['tag_lookup']));
            if( $tag_mask )
            {
                $limit = '';
                if( !empty($_GET['limit']) )
                {
                    $limit = sprintf("%d",$_GET['limit']);
                    if( !empty($limit) )
                        $limit = "LIMIT 0, $limit";
                }

                $type = empty($_GET['type']) ? 4 : sprintf('%d',$_GET['type']);
                if( empty($type) )
                    $type = 4;
                $where = "WHERE ( (tags_type & $type) <> 0 ) ";

                if( $tag_mask != '*' )
                    $where .= " AND (tags_tag LIKE '{$tag_mask}%')";

                if( !empty($_GET['min']) )
                {
                    $min = sprintf("%d",$_GET['min']);
                    if( !empty($min) )
                        $where .= " AND (tags_count >= $min)";
                }

                // yes, yes, it's a hack
                $sql = "SELECT tags_tag as un, CONCAT(tags_tag,' (',tags_count,')') as ut " .
                       ' FROM cc_tbl_tags  ' .
                       $where .
                       ' ORDER BY tags_tag ASC ' . $limit;
                
                $this->_output_q($sql,false);
            }
            exit;
        }
        else
        {
            require_once('cclib/cc-page.php');
            $args = array_diff($_GET,array('ccm'=>'/media/browse'));
            if( empty($args) )
            {
                CCPage::SetTitle(_('Browse Uploads'));
                $args = '[]';
            }
            else
            {
                require_once('cclib/cc-query.php');
                require_once('cclib/zend/json-encoder.php');

                $query = new CCQuery();
                $args = $query->ProcessUriArgs();
                $args = array_filter($args);
                $args = CCZend_Json_Encoder::encode($args);
            }
            CCPage::PageArg('query_browser','query_browser.xml/query_browser');
            CCPage::PageArg('browse_args', $args, 'query_browser');
        }
    }

    function _output_q($sql,$do_json=true) 
    {
        $qr = mysql_query($sql) or die( mysql_error() );
        $args['count'] = mysql_num_rows($qr);
        $args['html'] = '';
        while( $row = mysql_fetch_assoc($qr) )
            $args['html'] .= '<p class="cc_autocomp_line" id="_ac_' . $row['un'] . '">' . $row['ut'] . '</p>';
        require_once('cclib/zend/json-encoder.php');
        $args = CCZend_Json_Encoder::encode($args);
        if( $do_json )
            header("X-JSON: $args");
        header('Content-type: text/javascript');
        print($args);
    }
}

?>