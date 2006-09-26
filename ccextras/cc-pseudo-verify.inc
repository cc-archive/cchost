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

define('CC_PV_DISABLE', 'da' );
define('CC_PV_SIG', 'sig' );
define('CC_PV_VERIFY', 'vrfy' );

class CCPseudoVerifyAdminForm extends CCGridForm
{
    function CCPseudoVerifyAdminForm()
    {
        $this->CCGridForm();

        $help = _('Use this screen to allow uploads of less common and hard to verify
                   file types. Unlike other common media types the degree of support
                   for verifying the efficacy of these file types range greatly. 
                   <p>A guide to using this screen:</p>
                   <p>The <b>Del</b>ete option
                   only applies to types that you add here. Otherwise the types displayed 
                   here are culled from your current getID3 installation. (If you upgrade 
                   getID3, you should come back here
                   and the new types will automatcially show up.)</p>
                   <p><b>D/S/V</b> Here you chose what kind of support you wish to have
                   for the file type:</p>
                   <ul><li><b>D</b>isable the use of this file type, users will see an
                   error if they try to upload this type.</li>
                   <li><b>S</b>ignature check only. This will allow the file type to
                   be uploaded but
                   with only <i>very</i> minimal amount of verification. In other
                   words, if a virus is embedded into an Office document, this check
                   will not be able to detect it.</li>
                   <li>Attempt to <b>V</b>erify using the getID3 library. This will only
                   work if your version of getID3 supports full analysis of the file 
                   type. Many file types in this list require special tools to be
                   installed (see your helperapps directory for details). Others are
                   simply unsupported in which case you have to fall back to <b>S</b>ignature 
                   check only.</li>
                   </ul>
                   <p>Make sure to enter the file\'s <b>Ext</b>ension.</p>
                   <p><b>Tags</b> will be added to every file uploaded of this type.</p>
                   <p><b>Signature</b> is only relevant if you chose the <b>S</b>ignature
                   option</p>
                   <p>Use the <b>Add Format</b> button to extend this table and add
                   formats. Because these are not known to getID3, you must use
                   the <b>S</b>ignature only option, otherwise your users will see
                   an error message when trying to upload.</p>
');
        $this->SetFormHelp($help);

        $heads = array( 
            _('Del.'), 
                _('D/S/V'), 
             _('Type'), _('Ext.'), 
            _('Description'), 
            _('Tags'), _('Signature'), );
        
        $this->SetColumnHeader($heads);

        $configs =& CCConfigs::GetTable();
        $pv =  $configs->GetConfig('pseudo-verify',CC_GLOBAL_SCOPE);
        $ffa = CCPseudoVerifyAPI::_import_getid3_formats();

        if( empty($pv) )
        {
            $ffa['txt'] = array(
                'delete' => false,
                'media-type'  => 'document',
                'tags'        => 'document,txt',
                'default-ext' => 'txt',
                'action'      => CC_PV_SIG,
                'description' => _('Plain Text (ASCII)'),
                'isgetid3'    => false,
                'pattern'     => '^[\s\x21-\x7F]+$'
                );
        }

        foreach( $ffa as $K => $F )
        {
            if( empty($pv[$K]) )
                $pv[$K] = $F;
        }

        foreach( $pv as $K => $F  )
        {
            $a = $this->_get_row('mi', $K, $F);
            $this->AddGridRow( $K, $a );
            $this->SetHiddenField( "mi[$K][isgetid3]",  $F['isgetid3'] );
        }

        $new_fields = array(
                'delete' => false,
                'media-type'  => '',
                'tags'        => '',
                'default-ext' => '',
                'action'      => CC_PV_SIG,
                'description' => '',
                'isgetid3'    => false,
                'pattern'     => ''
                );

        $new_meta_row = $this->_get_row('new','%i%', $new_fields );
        $new_meta_row[0]['formatter'] = 'statictext';
        $this->AddMetaRow($new_meta_row, _('Add Format') );
    }

    function _get_row($pre,$K,$F)
    {
        $del_type = $F['isgetid3'] ? 'statictext' : 'checkbox';

        $a = array(
              array(
                'element_name'  => "{$pre}[$K][delete]",    
                'value'      => false,
                'formatter'  => $del_type,
                'flags'      => CCFF_NONE ),
              array(
                'element_name'  => "{$pre}[$K][action]",    
                'value'      => $F['action'],
                'formatter'  => 'radio',
                'nobr'       => true,
                'options'    => array( CC_PV_DISABLE => '',
                                       CC_PV_SIG => '', 
                                        CC_PV_VERIFY => ''),
                'flags'      => CCFF_POPULATE ),
              array(
                'element_name'  => "{$pre}[$K][media-type]",    // group
                'value'      => $F['media-type'],
                'class'      => 'cc_form_input_short',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
              array(
                'element_name'  => "{$pre}[$K][default-ext]",  // key
                'value'      => $F['default-ext'],
                'class'      => 'cc_form_input_short',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
              array(
                'element_name'  => "{$pre}[$K][description]",  // --
                'value'      => $F['description'],
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED ),
            );

        $a[] = array(
                'element_name'  => "{$pre}[$K][tags]",    
                'value'      => $F['tags'],
                'class'      => 'cc_form_input_short',
                'formatter'  => 'textedit',
                'flags'      => CCFF_REQUIRED );

        $a[] = array(
            'element_name'  => "{$pre}[$K][pattern]",      // pattern
            'value'      => htmlspecialchars($F['pattern']),
            'class'      => 'cc_form_input_short',
            'formatter'  => 'textedit',
            'flags'      => CCFF_NONE );

        return $a;
    }
}

class CCPseudoVerifyAPI
{

    function _import_getid3_formats()
    {
        global $CC_GLOBALS;

        $basepath = CCUtil::CheckTrailingSlash($CC_GLOBALS[CCGETID3_PATH_KEY],true);
        $getid3_obj = CCGetID3::InitID3Obj();
        $ffa = $getid3_obj->GetFileFormatArray();

        $media_ffa = CCGetID3::GetFormats();

        $desc = array( 
				'ac3'  => _('Dolby AC-3 / Dolby Digital'),
				'adif' => _('AAC - ADIF format'),
				'adts' => _('AAC - ADTS format'),
				'avr'  => _('Audio Visual Research'),
				'bonk' => _('Bonk v0.9+'),
				'la'   => _('Lossless Audio'),
				'lpac' => _('Lossless Predictive Audio Compression'),
				'mac'  => _('Monkey\'s Audio Compressor'),
				'mod'  => _('MODule (assorted sub-formats)'),
				'it'   => _('MODule (Impulse Tracker)'),
				'xm'   => _('MODule (eXtended Module)'),
				's3m'  => _('MODule (ScreamTracker)'),
				'mpc'  => _('Musepack / MPEGplus'),
				'ofr'  => _('OptimFROG'),
				'rkau' => _('RKive AUdio compressor'),
				'shn'  => _('MKW Shorten'),
				'tta'  => _('TTA Lossless Audio Compressor'),
				'voc'  => _('Creative Voice'),
				'vqf'  => _('Vector Quantization Format'),
				'wv'   => _('WavPack (v4.0+)'),
				'bink' => _('Bink / Smacker'),
				'flv' => _('FLash Video'),
				'matroska' => _('Mastroka (audio/video)'),
				'mpeg' => _('Moving Pictures Experts Group (audio/video)'),
				'nsv'  => _('Nullsoft Streaming Video'),
				'riff' => _('WAV / AVI / CD-audio / SDSS'),
				'pcd'  => _('Kodak Photo CD'),
				'tiff' => _('Tagged Information File Format'),
				'iso'  => _('(ISO) CD-ROM Image'),
				'rar'  => _('RAR compressed data'),
				'szip' => _('SZIP compressed data'),
				'tar'  => _('TAR compressed data'),
				'gz'  => _('GZIP compressed data'),
				'pdf'  => _('Adobe PDF'),
				'msoffice' => _('Office (Word, Excel, Powerpoint, etc.)'),
            );

        $formats = array();
        foreach( $ffa as $K => $info )
        {
            $found = false;
            foreach( $media_ffa as $K2 => $MF )
            {
                list( $d, $d2, $d3 ) = split('-', $K2);
                if( ($d2 == $K) || ($d3 == $K) || ($MF['name'] == $K) )
                {
                    $found = true;
                    break;
                }
            }

            if( $found )
                continue;

            if( strpos($info['group'],'-') === false )
            {
                $tags = 
                $group = $info['group'];
            }
            else
            {
                list( ,$group ) = split('-', $info['group']);
                $tags = str_replace('-', ',', $info['group']);
            }

            if( $group == 'misc' )
            {
                $group = 
                $tags = 'document';
            }

            $tags .= ',' . $K;

            $formats[$K]['media-type']  = $group;
            $formats[$K]['tags']        = $tags;
            $formats[$K]['pattern']     = $info['pattern'];
            $formats[$K]['default-ext'] = $K;
            $formats[$K]['action']      = CC_PV_DISABLE;
            $formats[$K]['isgetid3']    = true;
            $formats[$K]['description'] = empty($desc[$K]) ?
                                           '(??)' : $desc[$K];
        }

        return $formats;
    }

    function Admin()
    {
        CCPage::SetTitle(_('Configure Pseudo Verifier') . ' [BETA]');

        $form = new CCPseudoVerifyAdminForm();

        if( empty($_POST['pseudoverifyadmin']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCUtil::StripSlash($_POST);

            $types = $_POST['mi'];

            if( !empty($_POST['new']) )
            {
                foreach( $_POST['new'] as $newtype )
                {
                    if( empty($newtype['default-ext']) )
                        continue;
                    $newtype['isgetid3'] = false;
                    $types[ $newtype['default-ext'] ] = $newtype;
                }
            }

            $save_types = array();
            $c = count($types);
            $keys = array_keys($types);
            for( $i = 0; $i < $c; $i++ )
            {
                $K = $keys[$i];
                $T =& $types[$K];

                if( empty($T['delete']) )
                    $save_types[$K] = $T;
            }

            $configs =& CCConfigs::GetTable();
            $configs->SaveConfig('pseudo-verify',$save_types,CC_GLOBAL_SCOPE,false);
            
            CCPage::Prompt(_('New pseudo verification options saved'));
        }
    }

   function GetValidFileTypes(&$types)
   {
        global $old_validator;

        if( isset($old_validator) )
           $old_validator->GetValidFileTypes($types);

        $configs =& CCConfigs::GetTable();
        $pv = $configs->GetConfig('pseudo-verify');

        foreach( $pv as $K => $T )
            if( $T['action'] != CC_PV_DISABLE )
                $types[] = $K;

       return $types;
   }

    function FileValidate(&$formatinfo)
    {
        global $old_validator;

        $retval = false;

        if( isset($old_validator) )
        {
            $retval = $old_validator->FileValidate($formatinfo);
            if( $retval )
                return true;
        }

        $path = $formatinfo->GetFilePath();

        $got_ext = preg_match( '/\.([a-z0-9]+)$/', strtolower($path), $m );

        if( !$got_ext )
        {
            $formatinfo->SetErrors( 'Can not determine file type');
            return false;
        }

        $configs =& CCConfigs::GetTable();
        $pv = $configs->GetConfig('pseudo-verify');
        if( empty($pv[$m[1]]) )
        {
            $formatinfo->SetErrors( _('Unknown pseudo format'));
            return false;
        }

        $FF = $pv[$m[1]];

        if( $FF['action'] == CC_PV_DISABLE )
        {
            $formatinfo->SetErrors( _('That file format is not allowed'));
            return false;
        }

        if( $FF['action'] == CC_PV_VERIFY )
        {
            CCDebug::QuietErrors();
            $debug = CCDebug::Enable(false);

            $id3 =& CCGetID3::InitID3Obj();
            $tags = $id3->analyze($path);

            CCDebug::Enable($debug);
            CCDebug::RestoreErrors();

            if( !empty($tags['warning']) )
            {
                $formatinfo->SetWarnings($tags['warning']);
            }

            if( empty( $tags['fileformat'] ) )
            {
                $formatinfo->SetErrors('Unknown format');
            }
            elseif( !empty($tags['error']) )
            {
               $formatinfo->SetErrors($tags['error']);
            }
            else
            {
                $retval = true;
            }
        }
        elseif( $FF['action'] == CC_PV_SIG )
        {
            if (!$f = @fopen($path, 'rb')) 
            {
                $formatinfo->SetErrors(_('Could not open file.'));
            }
            else
            {
                $bytes = fread($f,2048);
                $retval = preg_match('/'.$FF['pattern'].'/s', $bytes);
                if( !$retval )
                {
                    $formatinfo->SetErrors(_('Invalid signature for file type'));
                }
                fclose($f);
            }
        }
        else
        {
            $formatinfo->SetErrors(_('Invalid action request'));
        }

        if( $retval )
        {
            $FI['tags'] = $FF['tags'];
            $FI['default-ext'] = $FF['default-ext'];
            $FI['media-type'] = $FF['media-type'];
            $formatinfo->SetData( $FI );
            $formatinfo->_errors = '';
        }

        return $retval;
    }


}