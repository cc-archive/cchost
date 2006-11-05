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
* @subpackage lang
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCLanguageEditor
{
    function Language($domain='default',$zone=0)
    {
        global $CC_GLOBALS;

        $tt = $this->_get_lang_file($domain);
        $q = empty($_REQUEST['q']) ? '' : CCUtil::Strip($_REQUEST['q']);

        if( empty($q) )
        {
            if( !intval($zone) || $zone > 4 )
                $zone = 0;
            $part = count($tt) / 5;
            $start = $part * $zone;
            $end = $start + $part;
            $strs = array();
            $keys = array_keys($tt);
            for( $i = $start; $i < $end; $i++ )
            {
                $e = $tt[$keys[$i]];
                if( empty($e[1]) )
                    $e[0] = '*' . $e[0];
                $strs[$keys[$i]] = $e;
            }
        }
        else
        {
            $this->_filter_search = '/' . preg_quote($q) . '/i';
            $strs = array_filter($tt, array( $this, '_search_strings' ) );
        }

        $args = array();
        $args['string_table'] = $strs;
        $args['domain'] = $domain;
        $args['what_part'] = $zone;

        $args['str_select_a_string'] =     _('Select a string');
        $args['str_domain'] =              _('Domain'); 
        $args['str_generate_pot'] =        _('Generate pot');
        $args['str_original_string'] =     _('Original String');    
        $args['str_copy_this_string'] =    _('copy this string');   
        $args['str_translates_to'] =       _('Translates to');      
        $args['str_commit_and_advance'] =  _('Commit and Advance'); 
        $args['str_commit'] =              _('Commit');
        $args['str_revert'] =              _('Revert');          
        $args['str_will_be_saved_to'] =    _('Will be saved to');
        $args['str_missing_domain'] =      _('You must specify a target domain');
        $args['str_waiting'] =             _('waiting...');
        $args['str_part1'] =               _('Part 1');
        $args['str_part2'] =               _('Part 2');
        $args['str_part3'] =               _('Part 3');
        $args['str_part4'] =               _('Part 4');
        $args['str_part5'] =               _('Part 5');
        $args['str_search'] =              _('Search');
        $args['str_empty_search'] =        _('No search string!');
        $args['str_query'] =               $q;

        CCPage::PageArg('langeditor', 'language-editor.xml/language_editor');
        CCPage::PageArg('LE',$args,'langeditor');
        CCPage::SetTitle(_('Language Domain String Editor'));

    }

    function _search_strings($str)
    {
        return preg_match( $this->_filter_search, $str[0] );
    }

    function GetString($domain,$hash)
    {
        require_once('cclib/zend/json-encoder.php');
        $tt = $this->_get_lang_file($domain);
        $json['msgid']       = $tt[$hash][0];
        $json['msgstr']      = $tt[$hash][1];
        $json['context']     = $tt[$hash][2];
        $json['target_file'] = "locale/$domain/cchost.lang";
        $js = CCZend_Json_Encoder::encode($json);
        header( "X-JSON: $js" );
        header( 'Content-type: text/plain' );
        print($js);
        exit;
    }

    function EditString($domain,$hash)
    {
        if( empty($hash) ) {
            print("Invalid hash");
            exit;
        }
        $tt = $this->_get_lang_file($domain);
        $tt[$hash][1] = CCUtil::StripSlash(urldecode($_REQUEST['string']));
        $text = serialize($tt);
        $target_file = "locale/$domain/cchost.lang";
        $f = fopen($target_file,'w');
        fwrite($f,$text);
        fclose($f);
        chmod($target_file,cc_default_file_perms());
        $this->_return_stat( _('String saved to:') . $target_file);
    }

    function WritePot($domain)
    {
        $target_file = $this->_write_pot_file($domain);
        $this->_return_stat( _('pot file written:') . $target_file);
    }
    
    function _return_stat($msg)
    {
        require_once('cclib/zend/json-encoder.php');
        $json['statmsg'] = $msg;
        $js = CCZend_Json_Encoder::encode($json);
        header( "X-JSON: $js" );
        header( 'Content-type: text/plain' );
        print($js);
        exit;
    }

    function _parse_pot_file($pot_file,$fname)
    {
        $text = file_get_contents($pot_file);
        //$regex2 = '/msgid (.*)msgstr(.*)\n\n/sU';
        $regex2 = '/#:(.*)(#,.*)?msgid (.*)msgstr(.*)\n\n/sU';
        preg_match_all($regex2,$text,$matches,PREG_SET_ORDER);

        $regex3 = '/"(.+)"$/mSU';

        $hash = array();
        foreach( $matches as $m)
        {
            preg_match_all($regex3,$m[3],$t);
            $msgid = str_replace('\'','\\\'', join( "\n", $t[1] ));
            if( !$msgid )
                continue;
            if( $m[4] )
            {
                preg_match_all($regex3,$m[4],$t);
                $msgstr = str_replace('\'','\\\'',join( "\n", $t[1] ));
            }
            else
            {
                $msgstr = '';
            }
            $key = CCUtil::HashString($msgid);
            $hash[ $key ] = array( $msgid, $msgstr, $m[1] );
        }

        $this->_merge_config_strings($hash);

        uasort($hash,'_cc_str_cmp');

        $text = serialize($hash);

        $f = fopen($fname,'w');
        fwrite($f,$text);
        fclose($f);
        chmod($fname,0777);
    }

    function _merge_config_strings(&$hash)
    {
        $changed = false;
        $configs =& CCConfigs::GetTable();
        // don't do this through GetConfig becuase
        // that will translate the strings
        $args['config_type'] = 'strhash';
        $row = $configs->QueryRow($args);
        if( empty($row) )
            return false;
        $hashtable = unserialize($row['config_data']);
        $hashkeys = array_keys($hashtable);
        $cstr = '#: config';
        foreach( $hashkeys as $hkey )
        {
            if( empty($hash[ $hkey ]) )
            {
                $str = $hashtable[ $hkey ];
                $hash[ $hkey ] = array( $str , '', 'config' );
                $changed = true;
            }
            else
            {
                if( strpos($hash[ $hkey ][2], $cstr ) === false )
                    $hash[ $hkey ][2] .= "\n#: config";
            }
        }

        return $changed;
    }

    function _write_pot_file($domain)
    {
        $txt =<<<END
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: PACKAGE VERSION\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: 2006-10-29 13:33-0800\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=CHARSET\\n"
"Content-Transfer-Encoding: 8bit\\n"
END;
        $intext = file_get_contents( 'locale/' . $domain . '/cchost.lang' );
        $I = unserialize($intext);
        $keys = array_keys($I);
        $c = count($keys);
        $heads = array( 'msgid: ', 'msgstr: ' );

        for( $i = 0; $i < $c; $i++ )
        {
            $e =& $I[$keys[$i]];

            $txt .= "\n#:{$e[2]}";
            for( $n = 0; $n < 2; $n++ )
            {
                $txt .= $heads[$n];
                $strs = split("\n",$e[$n]);
                if( count($strs) > 1  ) 
                {
                    $txt .= "\"\"\n";
                    foreach( $strs as $s )
                        $txt .= "\"$s\"\n";
                }
                else
                {
                    $txt .= "\"{$strs[0]}\"\n";
                }
            }
        }

        $outname = 'locale/' . $domain . '/cchost.pot';
        $f = fopen($outname,'w');
        fwrite($f,$txt);
        fclose($f);
        chmod($outname,0777);
        return $outname;
    }

    function _create_lang_file($domain)
    {
        CCUtil::MakeSubdirs("locale/$domain");
        $lang_file = "locale/$domain/cchost.lang";
        $pot_file = "cchost.pot";
        if( !file_exists($pot_file) )
        {
            CCPage::SystemError(_('Can not find language map') . " ($pot_file)" );
            return;
        }
        $this->_parse_pot_file($pot_file,$lang_file);
    }

    function _get_lang_file($domain)
    {
        $fname = "locale/$domain/cchost.lang";
        if( !file_exists($fname) )
            $this->_create_lang_file($domain);

        $text = file_get_contents($fname);
        return unserialize($text);
    }

}

function _cc_str_cmp($a,$b)
{
    return strncasecmp($a[0],$b[0],25);
}
?>
