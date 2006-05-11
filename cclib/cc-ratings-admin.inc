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
* Module for admin UI for ratings
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


/**
* Form for admin ui for ratings
*/
class CCAdminRatingsForm extends CCEditConfigForm
{
    /**
    * Constructor
    */
    function CCAdminRatingsForm()
    {
        $this->CCEditConfigForm('chart',CC_GLOBAL_SCOPE);

        $url = ccl('admin','ratings','recalc');

        $fields = array( 
                    'bayesian-min' =>  
                       array(  'label'      => 'Chart minimum',
                               'form_tip'   => 'Mininum number of ratings an upload must have to seen in charts',
                               'class'      => 'cc_form_input_short',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),
                    'cut-off' =>  
                       array(  'label'      => 'Cut Off Point',
                               'form_tip'   => 'Only chart uploads since this time',
                               'formatter'  => 'select',
                               'options'    => array( '1 day ago' => 'The day before',
                                                      '3 days ago' => 'The previous 3 days',
                                                      '1 week ago' => 'The previous week',
                                                      '2 weeks ago' => 'The previous 2 weeks',
                                                      '1 month ago' => 'The previous month',
                                                      'forever' => 'Since forever and ever',
                                                        ),
                               'value'      => '2 weeks ago',
                               'flags'      => CCFF_POPULATE),
                    'list-min' =>  
                       array(  'label'      => 'Listing minium',
                               'form_tip'   => 'Minium number of ratings an upload must have to display ratings in listing',
                               'class'      => 'cc_form_input_short',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE),
                    'recalc_now' =>  
                       array(  'label'      => 'Recalc now',
                               'form_tip'   => 'Recalculate rating during submit. NOTE: This can take a very, very (very) long time if you have a lot of uploads.',
                               'value'      => '<a href="$url" class="cc_gen_button"><span>Recalc</span></a>',
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE),
            );

        $this->AddFormFields($fields);
        $url = ccl('viewfile','picks.xml');

        $help =<<<END
            <p>The system uses the Bayesian method for calculating the ranking of 
            an upload for the 'picks' page.
END;
        $this->SetHelpText($help);

        // quick hack to make up for the fact that the admin forms
        // don't know how to include this file
        // ALSO: gives us a chance to recalc ratings if we have to
        $this->SetHandler( ccl('admin', 'ratings', 'savesettings') );

    }
}

/**
* Admin management API for ratings
*/
class CCRatingsAdmin
{
    /**
    * @access private
    */
    function _manage_user_rating($user_id,$ratings_id,$cmd,$confirmed)
    {
        global $CC_GLOBALS;

        if( $confirmed != 'confirmed' )
        {
            $users =& CCUsers::GetTable();
            $username = $users->QueryItemFromKey('user_name',$user_id);
            if( !empty($_SERVER['HTTP_REFERER']) )
            {
                $backlink = '<p style="width: 250px"><a class="cc_gen_button" href="' . $_SERVER['HTTP_REFERER'] . '"><span>Cancel without doing anything</span></a></p>';
            }
            else
            {
                $backlink = '<p>Use your browser &lArr; BACK button to cancel the operation</p>';
            }
            
            $ratings =& CCRatings::GetTable();
            $ratings->AddJoin( new CCUploads(), 'ratings_upload');
            $upload_name = $ratings->QueryItemFromKey('upload_name',$ratings_id);

            $banlist = CCTag::TagSplit($CC_GLOBALS['ratings_ban']);
            $isbanned = in_array($username,$banlist);
            $bantext = $isbanned ? 'UnBan' : 'Ban';

            CCPage::SetTitle('Manage a User\'s Ratings [BETA]');
            $deluser_url = ccl('admin','ratings','user',$user_id,$ratings_id,'deluser','confirmed');
            $delrate_url = ccl('admin','ratings','user',$user_id,$ratings_id,'delete','confirmed');
            $banuser_url = ccl('admin','ratings','user',$user_id,$ratings_id,'ban','confirmed');
            $html =<<<EOF
            $backlink
                <hr style="width:50%" />
            <p style="width: 250px"><a class="cc_gen_button" href="$banuser_url"><span style="color:red">$bantext $username from future rating</span></a></p>            
                <hr style="width:50%" />
                <h3><b>Warning</b>: Deleting $username's rating for "$upload_name" can not
                be undone. <br /><span style="color:red">This action has no undo.</span></h3>
            <p style="width: 250px"><a class="cc_gen_button" href="$delrate_url"><span style="color:red">DELETE $username's Rating for this upload</span></a></p>            
                <hr style="width:50%" />
                <h3><b>Warning</b>: Deleting $username's ratings means <i>all</i>
            of the ratings $username <i>ever</i> gave will be removed. <br /><span style="color:red">This
            action has no undo.</span></h3>
            <p style="width: 250px"><a class="cc_gen_button" href="$deluser_url"><span style="color:red">DELETE ALL $username's RATINGS</span></a></p>
EOF;
            CCPage::AddPrompt('body_text',$html);
            return true;
        }

        if( $cmd == 'deluser' )
        {
            $ratings =& CCRatings::GetTable();
            $w['ratings_user'] = $user_id;
            $rows = $ratings->QueryRows($w,'ratings_id,ratings_ip');
            foreach($rows as $row)
            {
                if( !empty($CC_GLOBALS['cc_mixter_installed']) &&
                       ($row['ratings_ip'] == '255.0.0.0') ) 
                {
                    // there was a bug in old ccMixter:
                    // rows with localhost IP have the 
                    // wrong ratings_user in them
                    continue;
                }
                $this->DeleteRating($row['ratings_id'],false);
            }
            
        }
        elseif( $cmd == 'delete' ) 
        {
            $this->DeleteRating($ratings_id,false);
        }
        elseif( $cmd == 'ban' )
        {
            $users    =& CCUsers::GetTable();
            $username = $users->QueryItemFromKey('user_name',$user_id);
            $banlist  = CCTag::TagSplit($CC_GLOBALS['ratings_ban']);
            $isbanned = in_array($username,$banlist);
            if( $isbanned )
            {
                $banlist = array_diff( $banlist, array( $username ) );
                $bantext = 'Unbanned';
            }
            else
            {
                $banlist[] = $username;
                $bantext   = 'Banned';
            }
            $arr['ratings_ban'] = join(', ',$banlist);
            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig( 'config', $arr, CC_GLOBAL_SCOPE, true );
        }

        $url = ccl('admin','ratings','msg', $cmd );
        
        CCUtil::SendBrowserTo($url);

        return false;
    }

    /**
    * @access private
    */
    function _admin_ratings_chart()
    {
        CCPage::SetTitle("Configure Ratings Chart Engine");
        $form = new CCAdminRatingsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Implementation of racalculate all ratings in system
    */
    function Recalc()
    {
        $configs =& CCConfigs::GetTable();
        $chart = $configs->GetConfig('chart');

        if( empty($chart['bayesian-min']) )
            $chart['bayesian-min'] = 5;

        $uploads =& CCUploads::GetTable();
        $where = 'upload_num_scores >= ' . $chart['bayesian-min'];
        $rows = $uploads->QueryRows($where);
        $count = count($rows);
        for( $i = 0; $i < $count; $i++ )
        {
            CCSync::_calc_rank($chart,$rows[$i]);
            $args['upload_id'] = $rows[$i]['upload_id'];
            $args['upload_rank'] = $rows[$i]['upload_rank'];
            $uploads->Update($args);
        }
        $upcount = $count;
        $count   = 0;

        $args = array();
        $users =& CCUsers::GetTable();
        $where = 'user_num_scores > ' . $chart['bayesian-min'];
        $rows = $users->QueryRows($where);
        $count = count($rows);
        for( $i = 0; $i < $count; $i++ )
        {
            CCSync::_calc_rank($chart,$rows[$i],'user');
            $args['user_id'] = $rows[$i]['user_id'];
            $args['user_rank'] = $rows[$i]['user_rank'];
            $users->Update($args);
        }

        CCPage::Prompt("$count user scores and $upcount uploads recalculated");
    }

    /**
    * Catch all for ratings admin UI (implementation)
    *
    * @param string $cmd One of 'chart' (default), 'user', 'msg'
    * @param integer $user_id User to operate on
    * @param integet $ragings_id Rating id to operate on
    * @param string $cmd2 One of 'delete', 'banuser', 'deluser'
    * @param string $confirmed If present and set to 'confirmed' operation will proceed without UI
    * @see CCRatingsAdmin::Admin()
    */
    function Admin($cmd='',$user_id='',$ratings_id='',$cmd2='',$confirmed='')
    {
        if( $cmd == 'savesettings' )
        {
            // quick hack for getting around that this file
            // is not automatically included
            $admin_api = new CCAdmin();
            $admin_api->SaveConfig();
            if( !empty($_POST['recalc_now']) )
            {
                $this->Recalc();
            }
            return;
        }

        if( empty($cmd) || $cmd == 'chart' )
        {
            $this->_admin_ratings_chart();
            return;
        }

        if( $cmd == 'user' )
        {
            $ui_handled = $this->_manage_user_rating($user_id,$ratings_id,$cmd2,$confirmed);
            if( $ui_handled )
                return;
        }

        if( $cmd == 'msg' )
        {
            $msgid = $user_id; // sorry;

            if( $msgid == 'deluser' )
            {
                CCPage::Prompt("User's ratings deleted");
            }
            elseif( $msgid== 'delete' ) 
            {
                CCPage::Prompt("Rating deleted");
            }
            elseif( $msgid == 'ban' )
            {
                CCPage::Prompt( "Updated ban list" );
            }

        }

        $html =<<<END
            <style>
                .rt th { 
                    background: black;
                    padding: 2px;
                    border: 2px solid transparent;
                    color: white;
                    white-space: nowrap;
                   }
                .rt th a {
                    color: white;
                }
                .rt td {
                   vertical-align: top;
                }
                .rt .g {
                    background: #DDD;
                }
                .rt .d {
                    vertical-align: middle;
                }
                a.btn, a.rbtn {
                    display: block;
                    background: #CCC;
                    border-top: 1px #DDD solid;
                    border-left: 1px #DDD solid;
                    border-right: 1px #333 solid;
                    border-bottom: 1px #333 solid;
                    padding: 1px;
                }
                a.rbtn {
                    color: red;
                }
                a.btn:hover, a.rbtn:hover {
                    text-decoration: none;
                    border-right: 1px #DDD solid;
                    border-bottom: 1px #DDD solid;
                    border-left: 1px #333 solid;
                    border-top: 1px #333 solid;
                    background: #EEE;
                }
                a.btn {
                    float: left;
                    margin-right: 2px;
                }
            </style>
END;

        CCPage::SetTitle('Manage Ratings [BETA]');

        $arrows['DESC'] = '&dArr;';
        $arrows['ASC']  = '&uArr;';

        $F = array(
                    'rid' => array( 'ratings_id',
                                     'ASC', 
                                     'ID',
                                     '',
                                     '',
                                     'ratings_id',
                                     5,
                                     '',
                                     ),
                    'ruse' => array( 'reviewer',
                                     'ASC',
                                     'Reviewer',
                                     '',
                                     'pview',
                                     'revr.user_name',
                                     17,
                                     '',
                                     ),
                    'rscr' => array( 'ratings_score',
                                     'ASC',
                                      'Rating',
                                     '',
                                     '',
                                     'ratings_score',
                                     4,
                                     '',
                                     ),
                    'rip' => array( 'ratings_ip',
                                     'ASC',
                                      'IP',
                                     '',
                                     'iview',
                                     'ratings_ip',
                                     15,
                                     '',
                                     ),
                    'upid' => array( 'upload_name',
                                     'ASC',
                                     'Upload',
                                     '',
                                     'uview',
                                     'upload_name',
                                     32,
                                     '',
                                     ),
                    );

        $q = array();

        CCUtil::StripSlash($_GET); // is this ok?

        // sort order:
        if( empty($_GET['o']) )
            $q['o'] = 'rid';
        else
            $q['o'] = $_GET['o'];

        // direction:
        if( empty($_GET['d']) )
            $q['d'] = 'DESC';
        else
            $q['d'] = $_GET['d'];
        
        // set up sort/dir:
        $currF =& $F[ $q['o'] ];
        $currF[1] = $q['d'] == 'ASC' ? 'DESC' : 'ASC';
        $currF[3] = $arrows[ $q['d'] ];

        // offset:
        if( empty($_GET['s']) )
            $offset = 0;
        else
            $offset = intval($_GET['s']);

        $q['s'] = $offset;

        // filter:
        $q['f'] = array();
        $where = '';
        if( !empty($_GET['f']) )
        {
            $filters = split(';', $_GET['f']);
            $curr_filters = array();
            foreach( $filters as $filter )
            {
                list( $field, $value ) = split( ':', $filter, 2);
                $svalue = addslashes($value);
                $where .= "AND ($field = '$svalue')";
                $q['f'][$field] = $value;
            }
        }

        // LIKE filters
        if( !empty($_GET['likefilters']) )
        {
            $lf = $_GET['likefilters'];

            foreach( $lf as $lfield => $lvalue )
            {
                $field = $F[$lfield][5];
                $lvalue = CCUtil::StripText($lvalue);
                $F[$lfield][7] = $lvalue;
                $svalue = addslashes($lvalue);
                if( !empty($lvalue) )
                {
                    $where .= "AND ($field LIKE '%$svalue%')";
                    $q['lf'][$lfield] = $lvalue;
                }
            }
        }
        elseif( !empty($_GET['lf']) )
        {
            $filters = split(';', $_GET['lf']);
            $curr_filters = array();
            foreach( $filters as $filter )
            {
                list( $lfield, $value ) = split( ':', $filter, 2);
                $field = $F[$lfield][5];
                $svalue = addslashes($value);
                $where .= "AND ($field LIKE '%$svalue%')";
                $q['lf'][$lfield] = $value;
                $F[$lfield][7] = $value;
            }
        }

        $sql_part =<<<END
            FROM cc_tbl_ratings ratings, 
                 cc_tbl_user    revr
            JOIN (cc_tbl_user    revee,
                  cc_tbl_uploads uploads ) 
                     ON revee.user_id = uploads.upload_user
            WHERE ratings_upload = upload_id AND
                  ratings_user = revr.user_id
                  $where
END;
        $sql =<<<END
            SELECT ratings.*, 
                   uploads.*, 
                   revr.user_name reviewer,
                   revee.user_name reviewee
            $sql_part
            ORDER BY {$currF[0]} {$q['d']}
            LIMIT 50
            OFFSET $offset
END;
        
        $sql_count =<<<END
            SELECT COUNT(*)
            $sql_part
END;

        $rows       = CCDatabase::QueryRows($sql);
        $total_rows = CCDatabase::QueryItem($sql_count);

        $prevlink = '';
        $nextlink = '';

        $base = ccl('admin','ratings','manage');

        if( $total_rows > 50 )
        {
            if( $offset > 0 )
            {
                $prev = $offset - 50;
                if( $prev < 0 )
                    $prev = 0;
                $copy = $q;
                $copy['s'] = $prev;
                $u = $this->_burl( $base, $copy );
                $prevlink = "<a class=\"cc_gen_button\" href=\"$u\"><span>&lArr; Prev</span></a>";
            }

            if( $offset + 50 < $total_rows )
            {
                $copy = $q;
                $copy['s'] = $offset + 50;
                $u = $this->_burl( $base, $copy );
                $nextlink = "<a class=\"cc_gen_button\" href=\"$u\"><span>Next &rArr;</span></a>";
            }

        }

        if( !empty($q['f']) )
        {
            $html .= "Current filters: ";
            $slash = '';
            foreach( $q['f'] as $field => $value )
            {
                $copy = $q;
                unset($copy['f'][$field]);
                unset($copy['s']);
                $u = $this->_burl( $base, $copy );
                $html .= "$slash<b>$value</b> <a href=\"$u\">(remove)</a>  ";
                $slash = ' --- ';
            }
            $html .= '<br /><br />';
        }

        $copy = $q;
        unset($copy['lf']);
        $u = $this->_burl( $base, $copy );

        $html .= "<form action=\"$u\" method=\"get\"> ";
        $html .= '<table class="rt">';
        $form = "<tr>\n";
        $head = "<tr>\n";
        foreach( $F as $k => $fs )
        {
            $sz = $fs[6];
            $likeval = $fs[7];
            $form .= "<td><input size=\"$sz\" name=\"likefilters[$k]\" value=\"$likeval\" /></td>\n";
            $copy = $q;
            $copy['o'] = $k;
            $copy['d'] = $fs[1];
            $u = $this->_burl( $base, $copy );
            $value = $fs[2] . ' ' . $fs[3];
            $head .= "<th><a href=\"$u\">$value</a></th>\n";
        }
        $form .= '<td colspan="2"><input type="submit" name="dofilter" value="&lArr; filter" /></td></tr>' ;
        $head .= '<td colspan="2"></td></tr>' ;

        $html .= $form . "\n" . $head . "\n" ;

        $count = count($rows);

        $classes = array( '', 'class="g"' );
        $ci = 0;
        for( $i = 0; $i < $count; $i++ )
        {
            $cls = $classes[$ci];
            $ci ^= 1;
            $html .= '<tr>';

            $R =& $rows[$i];
            
            $purl = ccl('people',$R['reviewer']);
            $surl = ccl('files',$R['reviewee'],$R['ratings_upload']);

            $v['pview'] = "<a class=\"btn\" href=\"$purl\">view</a></div>"; 
            $v['uview'] = "<a class=\"btn\" href=\"$surl\">view</a></div>";
            $v['iview'] = 
                "<a class=\"btn\" href=\"http://www.dnsstuff.com/tools/whois.ch?ip={$R['ratings_ip']}\">IP</a>";
            foreach( $F as $fs )
            {   
                $field = $fs[0];
                $value = $R[ $field ];
                $copy = $q;
                $copy['f'][$fs[5]] = $value;
                unset($copy['s']);
                $u = $this->_burl( $base, $copy );
                $vv = empty($fs[4]) ? '' : $v[ $fs[4] ];
                $html .= "<td $cls>$vv<a href=\"$u\">$value</a></td>";
            }

            $durl = ccl('admin','ratings','user', $R['ratings_user'], $R['ratings_id']);

            $html .= "<td><a class=\"rbtn\" href=\"$durl\"> &lArr; edit</a></td>";
            $html .= "<td ></td>";
            $html .= "</tr>\n";
        }
        $html .= '</table></form><br /><br />';

        $html .= "<table><tr><td>$prevlink</td><td>&nbsp;</td><td>$nextlink</td></tr></table";

        CCPage::AddPrompt('body_html',$html);
    }

    /**
    * @access private
    */
    function _burl($base,$parts)
    {
        //CCDebug::PrintVar($parts);

        $c = '?';
        $q = '';
        foreach( $parts as $k => $v )
        {
            $q .= $c;
            if( is_array($v) )
            {
                if( !empty($v) )
                {
                    $c2 = '';
                    $q .= $k . '=';
                    foreach( $v as $k2 => $v2 )
                    {
                        $q .= $c2 . $k2 . ':' . $v2;
                        $c2 = ';';
                    }
                }
            }
            else
            {   
                $q .= $k . '=' . $v;
            }
            $c = '&';
        };

        return $base . $q;
    }

    /**
    * Delete a rating
    * 
    * @param integer $ratings_id ID of ratings to delete
    * @param boolean $redir If true a will exit and redirect browser to calling page
    */
    function DeleteRating($rating_id,$redir=true)
    {
        $ratings =& CCRatings::GetTable();
        $upload_id = $ratings->QueryItemFromKey('ratings_upload',$rating_id);
        $where['ratings_id'] = $rating_id;
        $ratings->DeleteWhere($where);
        $uploads =& CCUploads::GetTable();
        $uwhere['upload_id'] = $upload_id;
        $record = $uploads->QueryRow($uwhere);
        if( !empty($record) )
            CCSync::Ratings($record,$ratings);
        if( $redir ) 
            CCUtil::SendBrowserTo(); 
    }
}

?>
