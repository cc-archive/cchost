<?


CCEvents::AddHandler( CC_EVENT_MAP_URLS, 'bad_user_url_map' );
//CCEvents::AddHandler( CC_EVENT_APP_INIT, 'opt_in_app_init' );



function bad_user_url_map()
{
    CCEvents::MapUrl( ccp('admin','spamaccount'), 'atm_bad_user', CC_ADMIN_ONLY );
}

function atm_bad_user()
{
    require_once('cchost_lib/cc-page.php');
    $page =& CCPage::GetPage();
    $page->SetTitle('Possible Spam Acounts');
    if( empty($_POST) )
    {
        $bad_accs = file_get_contents('mixter-lib/bad_account_names.txt');
        $bad_accs = array_filter(explode(",\n",$bad_accs));
        $url = ccl('admin','spamaccount');
        $html = "<form method=\"post\" action=\"{$url}\"><table>\n";
        foreach( $bad_accs as $BA )
        {
            $results = CCDatabase::QueryRows("SELECT user_id, user_name FROM cc_tbl_user WHERE user_name LIKE '%{$BA}%'");
            foreach( $results as $R )
            {
                $html .= '<tr><td><input type="checkbox" name="user_ids['.$R['user_id'].']" checked="checked" /></td>'.
                            '<td>' . $R['user_id'] . '</td><td><b>' . $R['user_name'] . '</b></td>' .
                            '</tr>';
            }
        }
        $html .= '</table><br /><input type="submit" value="Delete Checked"></form>';
    }
    else
    {
        if( empty($_POST['user_ids']) )
        {
            $html = "ok, nothing to delete";
        }
        else
        {
            $ids = array_keys( $_POST['user_ids']);
            $ids = join(',',$ids);
            $sql = "DELETE FROM cc_tbl_user WHERE user_id IN ({$ids})";
            CCDatabase::Query($sql);
            $html = "Accounts have been deleted";
        }
    }
    
    $page->AddContent($html);
}

function atm_bad_user2($cmd='')
{
    global $CC_GLOBALS;

    require_once('cchost_lib/cc-page.php');
    require_once('cchost_lib/cc-form.php');
    
    $page =& CCPage::GetPage();
    $page->SetTitle('Clean Spam accounts');
    
    $form = new CCForm();
    $bad_accs = file_get_contents('mixter-lib/bad_account_names.txt');
    
    $fields = array(
        'opts' => array(
                'label' => 'Keywords',
                'formatter' => 'textarea',
                'value' => $bad_accs,
                'flags' => CCFF_REQUIRED | CCFF_POPULATE_WITH_DEFAULT
                ),
            );
        
    $form->AddFormFields($fields);
    
    if( empty($_POST) || !$form->ValidateFields() )
    {
        $page->AddForm( $form->GenerateForm() );
    }
    else
    {
        d($_POST);
    }
}
