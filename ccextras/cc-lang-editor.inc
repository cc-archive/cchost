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
    function Language($domain='default')
    {
        global $CC_GLOBALS;

        $tt = $this->_get_lang_file($domain);
        $args = array();
        $args['string_table'] = $tt;
        $args['domain'] = $domain;
        $args['msg_missing_domain'] = _('You must specify a target domain');

        CCPage::PageArg('langeditor', 'language-editor.xml/language_editor');
        CCPage::PageArg('LE',$args,'langeditor');
        CCPage::SetTitle(_('Language Domain String Editor'));
    }

    function GetString($domain,$hash,$cmd='')
    {
        $target_file = "locale/$domain/cchost.lang";
        $tt = $this->_get_lang_file($domain);
        $encoded = htmlentities($tt[$hash][0]);
        $string = $tt[$hash][ $cmd == 'revert' ? 0 : 1 ];
        $html =<<<END
     <div class="org_title">Original String</div>
     <div class="org_string">$encoded</div>
     <div class="org_title">Translates to:</div>
     <textarea id="translated" name="translated" rows="11" cols = "45">$string</textarea>
     <div><br /><a id="commentcommand" href="#" onclick='do_edit_string("$hash")'><span>Commit Changes</span></a> &nbsp; &nbsp; to: $target_file</div><br />
     <div><br /><a id="commentcommand" href="#" onclick='do_get_string("$hash","revert")'><span>Revert</span></a></div>
END;

       print $html;
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
        print("Changes saved");
        exit;
    }

    function _parse_pot_file($pot_file,$fname)
    {
        $text = file_get_contents($pot_file);
        $regex2 = '/msgid (.*)msgstr(.*)\n\n/sU';
        preg_match_all($regex2,$text,$matches,PREG_SET_ORDER);

        $regex3 = '/"(.+)"$/mSU';

        $hash = array();
        $sort = array();
        foreach( $matches as $m)
        {
            preg_match_all($regex3,$m[1],$t);
            $msgid = str_replace('\'','\\\'', join( "\n", $t[1] ));
            if( !$msgid )
                continue;
            preg_match_all($regex3,$m[2],$t);
            $msgstr = str_replace('\'','\\\'',join( "\n", $t[1] ));
            $key = sprintf('%08X',crc32($msgid));
            $hash[ $key ] = array( $msgid, $msgstr );
            $sort[ trim($msgid) ] = $key;
        }

        ksort($sort,SORT_STRING);
        $sorted = array();
        foreach( $sort as $key )
            $sorted[$key] = $hash[$key];

        $text = serialize($sorted);

        $f = fopen($fname,'w');
        fwrite($f,$text);
        fclose($f);
        chmod($fname,0777);
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


?>
