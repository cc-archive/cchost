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
* $Header: /cvsroot/cctools/cchost1/ccextras/cc-format.php,v 1.7 2006/03/16 17:10:25 fourstones Exp $
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCFilesAddForm extends CCUploadForm
{
    function CCFilesAddForm($dirs)
    {
        global $CC_GLOBALS;

        $this->CCUploadForm();

        $dir = $CC_GLOBALS['files-root'];

        if( empty($dirs) )
        {
            $fields['targetdir'] = array( 'label' => 'Destination directory',
                             'value' => $dir,
                             'formatter' => 'statictext',
                             'flags' => CCFF_STATIC | CCFF_NOUPDATE );
        }
        else
        {
            $targets = array( '[home]' => $CC_GLOBALS['files-root'] );
            //$values = array_values($targets);
            //$targets += array_combine($values,$values);
            foreach( $dirs as $sdir )
                $targets[$sdir] = $dir . $sdir;
            $fields['targetdir'] = array( 'label' => 'Destination directory',
                                      'formatter' => 'select',
                                      'options'  => $targets,
                                      'flags'   =>  CCFF_POPULATE);
        }
        
        $fields += array( 
            'file1' => array(  'label'      => 'Add file(s)',
                               'formatter'  => 'upload',
                               'form_tip'   => '',
                               'flags'      => CCFF_NONE  ),
            'file2' => array(  'label'      => '',
                               'formatter'  => 'upload',
                               'form_tip'   => '',
                               'flags'      => CCFF_NONE  ),
            'file3' => array(  'label'      => '',
                               'formatter'  => 'upload',
                               'form_tip'   => '',
                               'flags'      => CCFF_NONE  ),
            'file4' => array(  'label'      => '',
                               'formatter'  => 'upload',
                               'form_tip'   => '',
                               'flags'      => CCFF_NONE  ),

            );

        $this->AddFormFields($fields);

    }

}


/**
*
*
*/
class CCFileManAdmin
{
    function Add($subdir='')
    {
        global $CC_GLOBALS;

        //////////////////////////////
        //
        // figure out the pathing..
        //
        $root = $CC_GLOBALS['files-root'];
        if( substr($root,-1,1) == '/' )
            $dir = substr($root,0,strlen($root)-1);
        else
            $dir = $root;
        $path = realpath($dir);

        list( , $dirs ) = $this->_get_dir_contents($path,$dir,'');

        $form = new CCFilesAddForm($dirs);

        if( !empty($_POST['filesadd']) && $form->ValidateFields() )
        {
            
            $form->GetFormValues($values);

            if( !empty($values['targetdir']) && ($values['targetdir'] != '[home]') )
            {
                $path = realpath( $dir . '/' . $values['targetdir'] );
            }

            $ok = true;
            $uploads = 0;
            for( $i = 1; $i <= 4; $i++ )
            {
                $fname = 'file' . $i;
                if( !empty($values[$fname]['name']) )
                {
                    $F =& $values['file' . $i];
                    $newfile = $values[$fname]['name'];
                    $realpath = $path . '/' . $newfile;
                    if( file_exists($realpath) )
                    {
                        $form->SetFieldError($fname,"The file '$realpath' already exists");
                        $ok = false;
                    }
                    else
                    {
                        $fok = move_uploaded_file($F['tmp_name'],$realpath );

                        if( $fok )
                        {
                            $fok = @chmod($realpath,CC_DEFAULT_FILE_PERMS);
                        }

                        if( $fok )
                            ++$uploads;

                        $ok = $ok && $fok;
                    }
                }
            }

            if( !$ok )
                CCPage::Prompt("There were problems uploading (see below)");
            elseif( $uploads )
                CCPage::Prompt("$uploads files uploaded ok");

        }

        CCPage::AddForm( $form->GenerateForm() );
    }

    function Manage($tab='')
    {
        $navigator = new CCNavigator();
        $base_url = ccl('admin','files','manage');
        $this->_ensure_tabs();
        if( empty($tab) )
            $tab = 'files';
        $navigator->ShowSubTabs('fileman',$tab,$base_url,true);
        if( $tab == 'addfiles' )
            $this->Add();
        else
            $this->Files();
    }

    function _ensure_tabs()
    {
        $configs =& CCConfigs::GetTable();
        $pages = $configs->GetConfig('tab_pages');
        if( empty($pages['fileman']) )
        {
            $pages['fileman'] = array(
                 'files'   =>    array(  'text'   => 'Manage Files',
                                       'help'   => 'Manage files your viewfile directory',
                                       'tags'   => '/files',
                                       'limit'  => 0,
                                       'access' => CC_ADMIN_ONLY,
                                       'function' => 'url' ),
                 'addfiles'   =>  array(  'text'   => 'Add Files',
                                       'help'   => 'Add files to the system',
                                       'tags'   => 'addfiles',
                                       'limit'  => 0,
                                       'access' => CC_ADMIN_ONLY,
                                       'function' => 'url' ),
                     );
        
            $configs->SaveConfig('tab_pages',$pages,'',false);
        }
    }

    function Files()
    {
        global $CC_GLOBALS;

        $subdir = empty($_REQUEST['subdir'])  ? '' : $_REQUEST['subdir'];

        //////////////////////////////
        //
        // figure out the pathing..
        //
        $root = CCUtil::CheckTrailingSlash($CC_GLOBALS['files-root'],false);
        if( $subdir )
            $dir =  CCUtil::CheckTrailingSlash( $root . '/' . $subdir, false );
        else
            $dir = $root;
        $path = realpath($dir);

        //////////////////////////////
        //
        // Handle POST
        //
        $newdir_error = '';
        $replace_error = '';
        $delete_error = '';
        $rename_error = '';
        $ops_err = false;

        if( !empty($_POST['newdir_submit']) )
        {
            $ok = false;
            $newdir = $_POST['new_dir'];
            if( empty($newdir) )
            {
                $newdir_error = '<div class="ferr">Can\'t leave this blank</div>';
            }
            else
            {
                $realpath = $path . '/' . $newdir;
                if( file_exists($realpath) || is_dir($realpath) ) // redundant, no?
                {
                    CCPage::Prompt('That directory already exists');
                }
                else
                {
                    $ok = CCUtil::MakeSubdirs($realpath);
                }
                if( $ok )
                {
                    $msg = 'Directory created ok';
                }
                else
                {
                    $msg = 'There was a problem creating the directory ';
                }

                CCPage::Prompt($msg);
            }
        }
        elseif( !empty($_POST['rename_submit']) )
        {
            $workfile = empty($_POST['filename']) ? '' : $_POST['filename'];

            if( empty($workfile) )
            {
                $rename_error = '<div class="ferr">no file selected for renaming</div>';
                $ops_err = true;
            }
            elseif( empty($_POST['rename']) )
            {
                $rename_error = '<div class="ferr">can no be blank</div>';
                $ops_err = true;
            }
            else
            {
                $oldname = realpath($dir) . '/' . $workfile;
                $newname = realpath($dir) . '/' . $_POST['rename'];
                if( file_exists($newname) )
                {
                    $rename_error = '<div class="ferr">a file with that name already exists</div>';
                    $ops_err = true;
                }
                else
                {
                    $ok = rename($oldname,$newname);
                    if( $ok )
                    {
                        CCPage::Prompt("File rename succeeded");
                        $currfile = $_POST['rename'];
                    }
                    else
                    {
                        $rename_error = '<div class="ferr">could not rename the file</div>';
                        $ops_err = true;
                    }
                }
            }
        }
        elseif( !empty($_POST['replace_submit']) )
        {
            $workfile = empty($_POST['filename']) ? '' : $_POST['filename'];

            if( empty($workfile) )
            {
                $replace_error = '<div class="ferr">no file selected for replacement</div>';
                $ops_err = true;
            }
            elseif( empty($_FILES) || empty($_FILES['replace']) )
            {
                $replace_error = '<div class="ferr">can no be blank</div>';
                $ops_err = true;
            }
            else
            {
                $realpath = realpath($dir) . '/' . $workfile;

                if( file_exists($realpath) )
                    unlink($realpath);

                $ok = move_uploaded_file($_FILES['replace']['tmp_name'],$realpath );

                if( $ok )
                {
                    $ok = @chmod($realpath,CC_DEFAULT_FILE_PERMS);
                }

                if( $ok )
                {
                    $msg = 'replaced ' . $workfile;
                }
                else
                {
                    $msg = 'ERROR: could not replace ' . $workfile;
                }

                CCPage::Prompt( $msg );
            }
        }
        elseif( !empty($_POST['delete_submit']) )
        {
            $workfile = empty($_POST['filename']) ? '' : $_POST['filename'];

            if( empty($_POST['delete']) )
            {
                $delete_error = '<div class="ferr">please confirm delete by checking here:</div>';
                $ops_err = true;
            }
            elseif( empty($workfile) )
            {
                $delete_error = '<div class="ferr">no file selected for deletion</div>';
                $ops_err = true;
            }
            else
            {
                $realpath = realpath( $dir ) . '/' . $workfile;

                $ok = false;
                if( file_exists($realpath) )
                    $ok = unlink($realpath);

                if( $ok )
                    $msg = $workfile . ' has been deleted';
                else
                    $msg = 'could not delete ' . $workfile;

                CCPage::Prompt( $msg );
            }
        }

        //////////////////////////////
        //
        // collect all the files and dirs
        //
        list( $files, $dirs ) = $this->_get_dir_contents($path,$dir);

        if( empty($files) && empty($dirs)  && $subdir )
        {
            CCPage::Prompt('Directory is empty, use "Add Files" to populate');
            return;
        }
        //////////////////////////////
        //
        // Generate the form
        // (hand rolled because it's just too specialized for CCForm)
        //
        $url = $_SERVER['REQUEST_URI'];

        if( empty($currfile) )
            $currfile = empty($_POST['filename']) ? '' : $_POST['filename'];

        $html = $this->_get_styles();

        $html .=<<<END

<form enctype="multipart/form-data" action="$url" method="post">
<table class="ftable">
<tr><th>Files in "<span>$dir</span>"</th>
    <td><select onchange="filechanged(this);" size="8" ondblclick="ondirclicked(this);">
END;
        if( $subdir )
        {
            $purl = preg_replace('@^([^\?]+)(\?.*)$@','\1',$url);
            $html .= "<option value=\"$purl\">[..]</option>\n";
        }

        foreach( $dirs as $tdir => $text )
        {
            $html .= "<option value=\"$tdir\" >[$text]</option>\n";
        }

        
        $selected = empty($currfile) ? 'selected="selected"' : '';

        foreach( $files as $value => $text )
        {
            if( empty($currfile) )
                $currfile = $text;
            if( $text == $currfile )
                $selected = 'selected="selected"';
            $html .= "<option $selected value=\"$value\" >$text</option>\n";
            $selected = '';
        }

        $html .= <<<END
        </select>
        </td>
    </tr>
END;
        if( empty($subdir) )
        {
            $html .=<<<END
<tr>
    <th>Create directory in "<span>$dir</span>"</th>
    <td>$newdir_error<input type="text" length="35" id="new_dir" name="new_dir" />
        <input type="submit" name="newdir_submit" id="newdir_submit" value="Create Dir."/>
    </td>
</tr>
END;
        }

        $show = $ops_err || $currfile ? '' : 'style="display:none"';

        $html .=<<<END
</table>
<table id="fileopstable" $show class="ftable">
  <tr><th>Download <span id="fname_dl">&nbsp;$currfile</span></th>
      <td><a id="dl_link_href" href="" class="cc_gen_button" style="width: 50%"><span 
               id="dl_link_text">&nbsp;$currfile</span></a><br />
            (right/control click on the link above to download)</td>
  </tr>
  <tr><th>Rename <span id="fname_ren">&nbsp;$currfile</span> to:</th>
      <td>$rename_error<input type="text" length="35" id="rename" name="rename" />
          <input type="submit" name="rename_submit" id="rename_submit" value="Rename" /></td>
  </tr>
  <tr><th>Replace <span id="fname_rep">&nbsp;$currfile</span> with:<br />
             <span style="color:red;">No confirm - no undo!</span></th>
      <td>$replace_error<input type="file" id="replace" name="replace" />
          <input type="submit" name="replace_submit" id="replace_submit" value="Replace" /></td>
  </tr>
  <tr><th>Delete <span id="fname_del">&nbsp;$currfile</span><br />
             <span style="color:red;">No confirm - no undo!</span></th>
      <td>$delete_error<input type="checkbox" id="delete" name="delete" />
          <input type="submit" name="delete_submit" id="delete_submit" value="Delete" /></td>
  </tr>
</table>
<input type="hidden" id="filename" name="filename" value="$currfile" />
</form>
<script>
function filechanged(obj)
{
   var fileopstable = new cc_obj('fileopstable');
   var fname = obj.options[obj.options.selectedIndex].text;
   if( (fname.charAt(0) == '[') && (fname.charAt(fname.length-1) == ']') )
   {
        fileopstable.style.display = 'none';
        return;
   }

   fileopstable.style.display = 'block';

   var url = obj.options[obj.options.selectedIndex].value;
   var ulink = new cc_obj( 'dl_link_href' );
   ulink.obj.href = url;
   var utext = new cc_obj( 'dl_link_text' );
   var rtext = new cc_obj( 'fname_rep' );
   var dtext = new cc_obj( 'fname_del' );
   var ftext = new cc_obj( 'filename' );
   cc_set_text(utext.obj,fname);
   cc_set_text(rtext.obj,fname);
   cc_set_text(dtext.obj,fname);
   cc_set_text(ftext.obj,fname);
}

function ondirclicked(obj)
{
   var vdirname = obj.options[obj.options.selectedIndex].text;
   if( (vdirname.charAt(0) == '[') && (vdirname.charAt(vdirname.length-1) == ']') )
   {
        var url = obj.options[obj.options.selectedIndex].value;
        document.location = url;
   }
}
</script>
END;

        CCPage::PageArg('body_html',$html,'show_body_html');

    }

    function _get_dir_contents($path,$dir)
    {
        $purl = preg_replace('@^([^\?]+)(\?.*)$@','\1',$_SERVER['REQUEST_URI']);
        $files = array();
        $dirs = array();
        if ($cc_dh = opendir($path)) 
        {
           while (($cc_file = readdir($cc_dh)) !== false) 
           {
               if( $cc_file == '.' || $cc_file == '..' )
                   continue;
               $p = realpath( $dir . '/' . $cc_file );
               if( is_dir($p) )
                   $dirs[htmlentities($purl . '?subdir=' . $cc_file)] = $cc_file;
               else
                   $files[htmlentities(ccd($dir,$cc_file))] =  $cc_file;
           }
           closedir($cc_dh);
        }

        return array( $files, $dirs );
    }

    function _get_styles()
    {
        $html =<<<EOF
  <style>
.ftable td {
   vertical-align: top;
   padding-bottom: 22px;
    }
.ftable th {
   vertical-align: top;
   text-align: right;
   width: 320px;
}
.ftable th span {
    font-weight: normal;
    font-style: italic;
}
.ferr {
    color: red;
    font-weight: bold;
}
</style>
EOF;
        return $html;
    }

}



?>