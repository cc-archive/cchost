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

CCEvents::AddHandler(CC_EVENT_ADMIN_MENU,   array( 'CCMixterAdmin', 'OnAdminMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMixterAdmin', 'OnMapUrls'));

class CCMixterAdmin
{
    function Database()
    {
        CCPage::SetTitle("Tables");
        $rows =& CCDatabase::QueryRows("SHOW TABLES");
        $T = new CCTemplate('mixter-lib/mixter-admin.xml');
        global $CC_CFG_ROOT;
        $args['config_root'] = $CC_CFG_ROOT;
        $args['dbmacro_names'][] = 'tables';
        $args['tables'] = $rows;
        $html = $T->SetAllAndParse($args,false,true);
        CCPage::AddPrompt('body_text',$html);
    }

    function Describe($table)
    {
        CCPage::SetTitle("Describe $table");
        $rows =& CCDatabase::QueryRows("DESCRIBE $table");
        $T = new CCTemplate('mixter-lib/mixter-admin.xml');
        global $CC_CFG_ROOT;
        $args['table'] = $table;
        $args['config_root'] = $CC_CFG_ROOT;
        $args['dbmacro_names'][] = 'describe';
        $args['table'] = $rows;
        $html = $T->SetAllAndParse($args);
        CCPage::AddPrompt('body_text',$html);
    }

    function Dump($table)
    {
        $dirswitch = array( 'ASC' => 'DESC',
                            'DESC' => 'ASC' );

        CCPage::SetTitle("Dump $table");
        $heads   =& CCDatabase::QueryRows("DESCRIBE $table");
        $key = $heads[0]['Field'];


        $tableobj = new CCTable($table,$key);

        if( !empty($_REQUEST['scol']) )
            $tableobj->SetSort($_REQUEST['scol'] ,$_REQUEST['dir']);
        
        $where = '';
        if( !empty($_POST['where']) )
        {
            $where = $_POST['where'];
            $keys = array_keys($where);
            if( !empty($_POST['like']) )
            {
                $newwhere = array();
                foreach( $keys as $key )
                {
                    if( !empty($where[$key]) )
                        $newwhere[] = "($key LIKE '%{$where[$key]}%')";
                }
                $where = implode( ' AND ', $newwhere );
            }
            else
            {
                foreach( $keys as $key )
                    if( empty($where[$key]) )
                        unset($where[$key]);
            }
        }

        CCPage::AddPagingLinks($tableobj,$where,50);


        $records =& $tableobj->QueryRows($where);
        global $CC_CFG_ROOT;
        $args['table'] = $table;
        $args['config_root'] = $CC_CFG_ROOT;
        $args['dbrecords'] =& $records;
        $args['heads']   = $heads;
        $args['dir']     = empty($_REQUEST['dir']) ? 'ASC' : $dirswitch[$_REQUEST['dir']];
        $ruri = $_SERVER['REQUEST_URI'];
        if( strstr( $ruri,'?') )
        {
            $up = explode('?', $ruri);
            $args['urlbase'] = $up[0];
        }
        else
        {
            $args['urlbase'] = $ruri;
        }
        CCPage::PageArg('db',$args);
        $incs = array( '../mixter-lib/mixter-admin.xml/d_records' );
        CCPage::PageArg('inc_names',$incs);
    }

    function DumpRecord($table,$key)
    {
        CCPage::SetTitle("Dump $table/$key");
        $heads   =& CCDatabase::QueryRows("DESCRIBE $table");
        $keyfield = $heads[0]['Field'];
        $record = CCDatabase::QueryRow("SELECT * FROM $table WHERE $keyfield = '$key'");
        CCMixterAdmin::_htmlize($record);
        $T = new CCTemplate('mixter-lib/mixter-admin.xml');
        global $CC_CFG_ROOT;
        $args['table'] = $table;
        $args['config_root'] = $CC_CFG_ROOT;
        $args['dbmacro_names'][] = 'show_record';
        $args['record'] = $record;
        $args['heads']   = $heads;
        $args['key'] = $key;
        //CCDebug::PrintVar($args);
        $html = $T->SetAllAndParse($args);
        CCPage::AddPrompt('body_text',$html);
    }


    function Edit($table,$key)
    {
        CCPage::SetTitle("Edit $table/$key");
        $heads   =& CCDatabase::QueryRows("DESCRIBE $table");
        $keyfield = $heads[0]['Field'];
        $record = CCDatabase::QueryRow("SELECT * FROM $table WHERE $keyfield = '$key'");
        CCMixterAdmin::_htmlize($record);

        $types = array();
        $patterns = array( '/.*int\(.*/',
                           '/.*text.*/',
                           '/datetime/',
                           '/varchar.*/' );
        $replacements = array( 'integer',
                               'textarea',
                               'date',
                               'input');
        foreach( $heads as $head )
        {
            $types[] = preg_replace( $patterns, $replacements, $head['Type'] );
        }
        $types[0] = 'static';
        $keys = array_keys($record);
        $count = count($keys);
        $meta = array();
        for( $i = 0; $i < $count; $i++ )
        {
            $meta[] = array( 'fname' => $keys[$i],
                           $types[$i] => 1,
                           'value' => $record[$keys[$i]] );

        }
        $T = new CCTemplate('mixter-lib/mixter-admin.xml');
        global $CC_CFG_ROOT;
        $args['table'] = $table;
        $args['keyfield'] = $keyfield;
        $args['key'] = $record[$keyfield];
        $args['config_root'] = $CC_CFG_ROOT;
        $args['dbmacro_names'][] = 'edit_record';
        $args['record'] = $meta;
        //CCDebug::PrintVar($args);
        $html = $T->SetAllAndParse($args,false,true);
        CCPage::AddPrompt('body_text',$html);
    }

    function SaveRecord()
    {
        $tablename = $_POST['table'];
        $key       = $_POST['key'];
        $keyfield  = $_POST['keyfield'];
        $record    = CCUtil::StripSlash($_POST['rec']);
        $record[$keyfield] = $key;
        $table = new CCTable($tablename,$keyfield);
        $table->Update($record);
        CCMixterAdmin::DumpRecord($tablename,$key);
    }

    function pEval()
    {
        global $CC_GLOBALS;
        $form = new CCForm();
        $have_scripts = false;
        $F = array( 
                    'filetoload' => array (
                                'label'      => 'Load',
                                'formatter'  => 'select',
                                'options'    => $this->_get_scripts($have_scripts),
                                'flags'      => CCFF_NONE ),
                    'loadb' => array (
                                'label'      => '',
                                'formatter'  => 'passthru',
                                'value'      => $have_scripts ? '<input type="submit" value="Load" name="load" id="load" />' .
                                                '<input type="submit" value="Delete" name="delete" id="delete" />' : '',
                                'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                    'expression' => array (
                                'label'      => 'Expression',
                                'formatter'  => 'textarea',
                                'value'      => $have_scripts ? '' : 'print("hello world");',
                                'flags'      => CCFF_POPULATE | CCFF_NOSTRIP ),
                    'filetosave' => array (
                                'label'      => 'File to save to',
                                'formatter'  => 'textedit',
                                'flags'      => CCFF_NONE ),
                    'saveb' => array (
                                'label'      => '',
                                'formatter'  => 'passthru',
                                'value'      => '<input type="submit" value="Save" name="save" id="save" />',
                                'flags'      => CCFF_STATIC | CCFF_NOUPDATE ),
                    'result'    => array(
                                'label'      => 'Result',
                                'formatter'  => 'statictext',
                                'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
                     ) ;

        $form->AddFormFields($F);
        $form->SetSubmitText('Run');

        $dir = $CC_GLOBALS['logfile-dir'] . 'scripts';
        if( !is_dir($dir) )
        {
            mkdir($dir);
            chmod($dir,0777);
        }
        $dir .= '/';

        if( !empty($_POST) && $form->ValidateFields() )
        {
            $form->GetFormValues($fields);

            if( !empty($_POST['save']) && !empty($fields['filetosave']) )
            {
                $fname =  $dir . str_replace(' ','_',$fields['filetosave']);
                $f = fopen($fname,'w');
                fwrite($f,$fields['expression']);
                fclose($f);
                chmod($fname,0777);
                $x = false;
                $form->SetFormFieldItem('filetoload','options',$this->_get_scripts($x));
            }
            elseif( !empty($_POST['load']) && !empty($fields['filetoload']) )
            {
                if( $fields['filetoload'] != '=' )
                {
                    $fname = $dir . $fields['filetoload'];
                    $text = file_get_contents($fname);
                    $form->SetFormValue('expression',$text);
                    $form->SetFormValue('filetosave', $fields['filetoload']);
                }
            }
            elseif( !empty($_POST['delete']) && !empty($fields['filetoload']) )
            {
                $fname = $dir . $fields['filetoload'];
                unlink($fname);
                $x = false;
                $form->SetFormFieldItem('filetoload','options',$this->_get_scripts($x));
            }
            else
            {
                ob_start();
                eval($fields['expression']);
                $result = ob_get_contents();
                ob_end_clean();
                $form->SetFormValue('result',$result);
            }
        }

        CCPage::SetTitle("Eval");
        CCPage::AddForm( $form->GenerateForm() );
    }

    function _get_scripts(&$have_scripts)
    {
        global $CC_GLOBALS;

        $dir = realpath( $CC_GLOBALS['logfile-dir'] . 'scripts');
        $files = array();
        if ($dh = opendir($dir)) 
        {
            while (($fname = readdir($dh)) !== false) 
            {
                $fpath = $dir . '/' . $fname;
                if( is_file($fpath) )
                    $files[$fname] = $fname;
            }
            closedir($dh);
        }

        if( empty($files) )
            return( array( '=' => '(no scripts saved yet)' ) );

        $have_scripts = !empty($files);

        ksort($files);
        return($files);
    }

    function _htmlize(&$record)
    {
        $count = count($record);
        $keys = array_keys($record);
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $record[$keys[$i]];
            if( is_array($R) )
                CCMixterAdmin::_htmlize($R);
            if( is_string($R) )
                $R = htmlspecialchars($R);
        }
    }

    function DumpErrorLog($delete='')
    { 
        global $CC_GLOBALS;
        $errfile = $CC_GLOBALS['logfile-dir'] . CC_ERROR_FILE;
        if( file_exists($errfile ) )
        {
            if( !empty($delete) )
            {
                unlink($errfile );
                $html = "<h3>Error log deleted</h3>";
            }
            else
            {
                $url = ccl( 'mixter', 'errors', 'delete' );
                $html = "<pre>" . file_get_contents($errfile ) . "</pre>" .
                        "<br /><a href=\"$url\">[ DELETE ]</a><br />";
            }
        }
        else
        {
            $html = "Error log is empty";
        }

        CCPage::SetTitle("Error Log Dump");
        CCPage::AddPrompt('body_text',$html);
    }

    function DumpLog($delete='')
    { 
        global $CC_GLOBALS;
        $errfile = $CC_GLOBALS['logfile-dir'] . CC_LOG_FILE;
        if( file_exists($errfile ) )
        {
            if( !empty($delete) )
            {
                unlink($errfile );
                $html = "<h3>Log deleted</h3>";
            }
            else
            {
                $url = ccl( 'mixter', 'log', 'delete' );
                $html = "<pre>" . file_get_contents($errfile ) . "</pre>" .
                        "<br /><a href=\"$url\">[ DELETE ]</a><br />";
            }
        }
        else
        {
            $html = "Log is empty";
        }

        CCPage::SetTitle("Log Dump");
        CCPage::AddPrompt('body_text',$html);
    }


    function OnAdminMenu(&$items,$scope)
    {
        if( $scope != CC_GLOBAL_SCOPE )
            return;

        $items['heshoots'] = array( 'menu_text' => 'Mixter DB',
                            'action'    => ccl('mixter','db'), 
                            'weight'    => 11001,
                            'help'      => '',
                            'access'    => CC_ADMIN_ONLY );

        $items['hescores']= array( 'menu_text' => 'Eval',
                            'action'    => ccl('mixter', 'eval'), 
                            'help'      => '',
                            'weight'    => 11002,
                            'access'    => CC_ADMIN_ONLY );
    }

    function Utils()
    {
    }

    function Phpinfo()
    {
        phpinfo();
        exit;
    }

    function GetLiveInfo()
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter('editorial_pick');
        $where['upload_license'] = 'nc-sampling+';
        $records = $uploads->GetRecords($where);
        $info = array();
        $remix_api = new CCRemix();
        $this->_get_live_info($info,$records,'',$remix_api);
        $keys = array_keys($info);
        foreach( $keys as $key )
            if( !empty($info[$key]['dirty']) )
                unset($info[$key]);

        $keys = array_keys($info);
        $emails = array();
        foreach( $keys as $key )
        {
            $I = $info[$key];
            if( !in_array($I['email'],$emails) )
                $emails[] = $I['email'];
            if( !empty($I['depends_on']) )
            {
                foreach( $I['depends_on'] as $d )
                {
                    if( !in_array($d[1],$emails) )
                        $emails[] = $d[1];
                }
            }
        }
        foreach( $keys as $key )
        {
            $I =& $info[$key];
            if( empty($I['dependency']) )
            {
                unset($info[$key]);
            }
            else
            {
                $I['dependency'] = implode( ', ', $I['dependency'] );
                unset($I['depends_on']);
                unset($I['email']);
            }
        }
    
        $emails = array_unique($emails);
        sort($emails);
        $info['ALL EMAILS'] = array( 'count' => count($emails),
                    'emails' => implode( '; ', $emails ) );
        ksort($info);
        CCDebug::Enable(true); 
        CCDebug::PrintVar($info);
        
    }

    function _get_live_info(&$info,&$records,$under_owner,$remix_api)
    {
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $R =& $records[$i];
            if( empty($R['user_id']) )
                continue;

            if( !array_key_exists($R['user_id'],$info) )
            {
                if( $R['user_name'] == 'Wired' || $R['user_name'] == 'militiamix' )
                {
                        $info[$under_owner]['dirty'] = true;
                }
                else
                {
                    if( empty($under_owner) )
                    {
                        $info[$R['user_name']]['email'] = $R['user_email'];
                    }
                    else
                    {   
                        if( $R['user_name'] != $under_owner )
                        {
                            $val = array( $R['user_name'], $R['user_email'] );
                            if( empty($info[$under_owner]['depends_on']) || !in_array( $val, $info[$under_owner]['depends_on'] ) )
                            {
                                $info[$under_owner]['depends_on'][] = $val;
                                $info[$under_owner]['dependency'][] = $R['user_name'];
                            }
                        }
                    }
                }
            }

            if( empty($R['seen_it']) )
            {
                $remix_api->OnUploadListing($R);
                $R['seen_it'] = true;
            }

            if( !empty($R['has_parents']) )
            {
                if( empty($under_owner) )
                    $owner = $R['user_name'];
                else
                    $owner = $under_owner;
                $this->_get_live_info($info,$R['remix_parents'],$owner,$remix_api);
            }
        }
    }

    function OnMapUrls()
    {
        CCEvents::MapUrl( 'mixter/',             array('CCMixterAdmin', 'Utils'),         CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/db',           array('CCMixterAdmin', 'Database'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/db/dump',      array('CCMixterAdmin', 'Dump'),          CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/db/describe',  array('CCMixterAdmin', 'Describe'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/db/edit',      array('CCMixterAdmin', 'Edit'),          CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/db/saverec',   array('CCMixterAdmin', 'SaveRecord'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/eval',         array('CCMixterAdmin', 'pEval'),         CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/errors',       array('CCMixterAdmin', 'DumpErrorLog'),  CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'mixter/log',          array('CCMixterAdmin', 'DumpLog'),  CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'phpinfo',             array('CCMixterAdmin', 'Phpinfo'),       
            CC_ADMIN_ONLY, ccs(__FILE__), '', _('See phpinfo() results'), 'mixter only' );
        CCEvents::MapUrl( 'live',                array('CCMixterAdmin', 'GetLiveInfo'),       CC_ADMIN_ONLY );
    }
}

?>