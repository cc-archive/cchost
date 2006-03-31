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

CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCRating' , 'OnGetConfigFields' ));
CCEvents::AddHandler(CC_EVENT_BUILD_UPLOAD_MENU,  array( 'CCRating',  'OnBuildUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_LISTING,     array( 'CCRating',  'OnUploadListing'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCRating',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCRating',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,         array( 'CCRating' , 'OnAdminMenu') );

class CCRatingsForm extends CCForm
{
    function CCRatingsForm($upload_id)
    {
        $this->CCForm();
        
        $fields = array( 
                    'ratings_score' =>
                        array( 'label'      => cct('Rate'),
                               'flags'      => CCFF_POPULATE,
                               'formatter'  => 'metalmacro',
                               'v'          => 'ratings_score',
                               'url'        => ccd('ccimages','star1.gif'),
                               'macro'      => 'ratings_field',

                              ),
                    );

        $this->AddFormFields( $fields );
        $this->SetHiddenField( 'ratings_upload', $upload_id );
        $this->SetSubmitText(cct('Submit Rating'));
    }

}

class CCRatingsChart extends CCTable
{
    function CCRatingsChart()
    {
        $this->CCTable('cc_tbl_ratings_chart','chart_id');
        $this->AddJoin( new CCUploads(), 'chart_upload' );
        $this->SetOrder('chart_rank','DESC');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCRatingsChart();
        return($_table);
    }

}


class CCRatings extends CCTable
{
    function CCRatings()
    {
        $this->CCTable('cc_tbl_ratings','ratings_id');
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCRatings();
        return($_table);
    }

    function HasRated($upload_id)
    {
        if( CCUser::IsLoggedIn() )
        {
            $args['ratings_user'] = CCUser::CurrentUser();
        }
        else
        {
            $args['ratings_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $args['ratings_upload'] = $upload_id;

        return( $this->CountRows($args) );
    }
}

class CCAdminRatingsForm extends CCEditConfigForm
{
    function CCAdminRatingsForm()
    {
        $this->CCEditConfigForm('chart',CC_GLOBAL_SCOPE);
            $fields = array( 
                        'per-star' =>  
                           array(  'label'      => 'Per User Rating',
                                   'form_tip'   => 'What each \'star\' is worth',
                                   'class'      => 'cc_form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE ),
                        'per-review' =>  
                           array(  'label'      => 'Per User Review',
                                   'form_tip'   => 'This rewards for the number of reviewer and is multipled against the average rating',
                                   'class'      => 'cc_form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE),
                        'per-child' =>  
                           array(  'label'      => 'Per Number of Times Remixed',
                                   'form_tip'   => 'This rewards for how people have remixed this work',
                                   'class'      => 'cc_form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE),
                        'per-parent' =>  
                           array(  'label'      => 'Per Number of Samples Used',
                                   'form_tip'   => 'This rewards for using lots of samples',
                                   'class'      => 'cc_form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE),
                        'cut-off' =>  
                           array(  'label'      => 'Cut Off Point',
                                   'form_tip'   => 'Only look at upload since this time',
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
                        'per-hour' =>  
                           array(  'label'      => 'Per Hour Since Cut Off',
                                   'form_tip'   => 'This puts more weight on newer uploads',
                                   'class'      => 'cc_form_input_short',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE),
                );

        $this->AddFormFields($fields);
        $this->SetHiddenField('dirty','1',CCFF_HIDDEN);
        $url = ccl('viewfile','picks.xml');

        $help =<<<END
            <p>
            Each value here determines the weight of all the corresponding element 
            that is used
            to calculate the ratings (favorites) chart. You can see an example of how
            to use this chart at <a href="$url">media/viewfile/picks.xml</a></p>
            <ul><li>A <b>1</b> (one) means use the value as found</li>
            <li>A <b>0</b> (zero) means don't use the value at all</li>
            <li>Less than 1 (e.g. <b>0.5</b>) means use a percentage of the value</li>
            <li>More than 1 (e.g. <b>5</b>) means use a multiple</li></ul>
                <p>For example if you just want to use the User's Ratings and no other
            values then set "Per User Rating" and "Per User" to <b>1</b> (one) and everything
                else to <b>0</b> (zero).</p>
                <p>None of this is very scientific. Just have fun.</p>
END;
        $this->SetHelpText($help);
    }
}

class CCRating
{

    function OnAdminMenu(&$items,$scope)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $items += array( 
                'ratingschart'   => array( 'menu_text'  => 'Ratings Criteria',
                                 'menu_group' => 'configure',
                                 'help'      => 'Configure ratings chart criteria',
                                 'access' => CC_ADMIN_ONLY,
                                 'weight' => 10,
                                 'action' =>  ccl('admin','ratings') )
                );
        }
    }

    function Admin()
    {
        CCPage::SetTitle("Configure Ratings Chart Engine");
        $form = new CCAdminRatingsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function GetChart($limit,$since='')
    {
        $configs =& CCConfigs::GetTable();
        $chart_settings = $configs->GetConfig('chart',CC_GLOBAL_SCOPE);
        if( $chart_settings['dirty'] == true || !empty($since) )
        {
            CCRating::_calc_ratings($chart_settings,$since);
            $clean['dirty'] = !empty($since);
            $configs->SaveConfig('chart',$clean,CC_GLOBAL_SCOPE);
        }

        $charts =& CCRatingsChart::GetTable();
        if( $limit )
            $charts->SetOffsetAndLimit(0,$limit);
        $rows = $charts->QueryRows('1','chart_upload');
        $ids = array();
        foreach($rows as $row)
            $ids[] = $row['chart_upload'];
        $uploads =& CCUploads::GetTable();
        $join = $uploads->AddJoin( new CCTable('cc_tbl_ratings_chart','chart_upload'), 'upload_id' );
        $uploads->SetSort('chart_rank','DESC');
        $records = $uploads->GetRecordsFromKeys($ids);
        $uploads->RemoveJoin($join);
        $uploads->SetSort('','');
        return($records);
    }


    function Rate($upload_id)
    {
        $ratings =& CCRatings::GetTable();
        if( $ratings->HasRated($upload_id) )
            return;

        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);

        $name = $record['upload_name'];
        CCPage::SetTitle(sprintf(cct('Rate "%s"'),$name));
        $form = new CCRatingsForm($upload_id);

        if( empty( $_POST['ratings'] ) )
        {
            $menu = CCUpload::GetRecordLocalMenu($record);
            unset($menu['comment']);
            $record['local_menu'] = $menu;
            $record['skip_remixes'] = true;
            unset($record['reviews_link']);
            $earg = array( $record );
            $form->CallFormMacro( 'file_records', 'list_files', $earg );
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->ValidateFields(); // oddly this is the only to get _POST values into the fields.
            $form->GetFormValues($args);
            if( CCUser::IsLoggedIn() )
            {
                $args['ratings_user'] = CCUser::CurrentUser();
            }
            else
            {
                $args['ratings_ip'] = $_SERVER['REMOTE_ADDRESS'];
            }
            $ratings->Insert($args);

            $configs =& CCConfigs::GetTable();
            $chart_settings['dirty'] = true;
            $configs->SaveConfig('chart',$chart_settings,CC_GLOBAL_SCOPE);

            $page_url = $record['file_page_url'] ;
            $msg = "Ratings for \"%s\" recorded. Click <a href=\"%s\">here</a> to see results.";
            CCPage::Prompt(sprintf(cct($msg),$name,$page_url));
        }
    }

    function _calc_ratings($C,$since)
    {
        if( $since == 'forever' )
        {
            $date_where = '';
        }
        else
        {
            $cutoff_t = strtotime( empty($since) ? $C['cut-off'] : $since);
            $cutoff = date('Y-m-d H:i:s', $cutoff_t);
            $date_where = " AND upload_date > '$cutoff' ";
        }

        $chart = new CCTable('cc_tbl_ratings_chart','chart_id');
        $chart->DeleteWhere('1');

        $sql = <<<END
            SELECT  upload_id as chart_upload, 
                    user_id as chart_user, 
                    AVG(ratings_score) AS chart_rating,
                    COUNT(ratings_score) AS chart_count,
                    AVG(ratings_score) * {$C['per-star']} 
                        * COUNT(ratings_score) * {$C['per-review']}  as chart_weight,
                    UNIX_TIMESTAMP(upload_date) / 360 as chart_time
            FROM    cc_tbl_uploads up,
                    cc_tbl_user us,
                    cc_tbl_ratings rt
            WHERE   up.upload_id = rt.ratings_upload AND
                    up.upload_user = us.user_id 
                    $date_where
                    GROUP BY upload_id
                    ORDER BY chart_weight DESC
                    LIMIT 25
END;

        $rows = CCDatabase::QueryRows($sql);

        if( empty($rows) )
            return;

        $chart->InsertBatch( array_keys($rows[0]), $rows);

        if( ($since == 'forever') || empty($C['per-hour']) )
        {
            CCDatabase::Query("UPDATE cc_tbl_ratings_chart SET chart_time = 1");
        }
        else
        {
            $min_time = CCDatabase::QueryItem("SELECT MIN(chart_time) FROM cc_tbl_ratings_chart");
            CCDatabase::Query("UPDATE cc_tbl_ratings_chart SET chart_time = {$C['per-hour']} * (chart_time - $min_time)");
        }

        $sql =<<<END
            SELECT chart_id, chart_upload, 
            COUNT(tree_parent) * {$C['per-parent']} as chart_num_parents
            FROM cc_tbl_ratings_chart c, cc_tbl_tree t
            WHERE c.chart_upload = t.tree_child
            GROUP BY chart_upload
END;

        $rows = CCDatabase::QueryRows($sql);
        foreach( $rows as $row )
            $chart->Update($row);

        $sql =<<<END
            SELECT chart_id, chart_upload, 
            COUNT(tree_child) * {$C['per-child']} as chart_num_children
            FROM cc_tbl_ratings_chart c, cc_tbl_tree t
            WHERE c.chart_upload = t.tree_parent
            GROUP BY chart_upload
END;

        $rows = CCDatabase::QueryRows($sql);
        foreach( $rows as $row )
            $chart->Update($row);

        $sql =<<<END
            UPDATE cc_tbl_ratings_chart SET chart_rank =
                (chart_weight + 1) * 
                    ((chart_num_children + chart_num_parents)+1) *
                    (chart_time + 1) 
END;

        CCDatabase::Query($sql);

        //$args['dirty']      = false;
        
    }


    function OnUploadListing(&$record)
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        if( empty($settings['ratings']) )
            return;
            
        $ratings =& CCRatings::GetTable();
        $where['ratings_upload'] = $record['upload_id'];
        $average = $ratings->QueryItem( 'AVG(ratings_score)/100', $where );
        $count = $ratings->CountRows($where);
        if( $average == 0 ) // not rated yet
            return;
        $stars = floor($average);
        $half  = fmod($average,$stars) > 0.25;
        for( $i = 0; $i < $stars; $i++ )
            $record['ratings'][] = 'full';
        if( $half )
        {
            $record['ratings'][] = 'half';
            $i++;
        }
        for( ; $i < 5; $i++ )
            $record['ratings'][] = 'empty';
        $record['ratings_score'] = number_format($average,2) . '/' . $count;
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['ratings'] =
               array(  'label'      => 'Ratings',
                       'form_tip'   => 'Allow users to rate uploads',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE,
                    );
        }
    }

    /**
    * Event handler for CC_EVENT_BUILD_UPLOAD_MENU
    * 
    * The menu items gathered here are for the 'local' menu at each upload display
    * 
    * @param array $menu The menu being built, put menu items here.
    * @see CCMenu::GetLocalMenu
    */
    function OnBuildUploadMenu(&$menu)
    {
        $menu['ratings'] = 
                 array(  'menu_text'      => cct('Rate'),
                         'weight'         => 50,
                         'group_name'     => 'comment',
                         'id'             => 'ratingscommand',
                         'access'         => CC_MUST_BE_LOGGED_IN);
    }

    /**
    * Event handler for CC_EVENT_UPLOAD_MENU
    * 
    * The handler is called when a menu is being displayed with
    * a specific record. All dynamic changes are made here
    * 
    * @param array $menu The menu being displayed
    * @param array $record The database record the menu is for
    * @see CCMenu::GetLocalMenu
    */
    function OnUploadMenu(&$menu,&$record)
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $ratings  =& CCRatings::GetTable();
        $is_me = $record['upload_user'] == CCUser::CurrentUser();

        if( $is_me || empty($settings['ratings']) || !empty($record['upload_banned']) || $ratings->HasRated($record['upload_id']) )
        {
            $menu['ratings']['access'] = CC_DISABLED_MENU_ITEM;
        }
        else
        {
            $menu['ratings']['action'] = ccl('rate',$record['upload_id']);
        }
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('rate'),  array('CCRating','Rate'), CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( ccp('admin','ratings'),  array('CCRating','Admin'), CC_MUST_BE_LOGGED_IN);
    }
}

?>