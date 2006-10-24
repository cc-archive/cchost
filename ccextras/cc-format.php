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
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
*/
require_once('ccextras/cc-topics.php');

CCEvents::AddHandler(CC_EVENT_FORM_FIELDS,    array( 'CCFormat', 'OnFormFields'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,       array( 'CCFormat', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_USER_ROW,       array( 'CCFormat', 'OnUserRow'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,     array( 'CCFormat', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCFormat' , 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_TOPIC_ROW,     array( 'CCFormat' , 'OnTopicRow') );

function _cc_format_links()
{
    static $done;
    if( !isset($done) )
    {
        CCPage::AddScriptBlock('ajax_block');
        CCPage::AddScriptBlock('dl_popup_script',true);
        CCPage::AddScriptLink( ccd('ccextras','cc-format.js') );
        CCPage::AddLink('head_links', 'stylesheet', 'text/css', ccd('ccextras','cc-format.css') , 'Default Style');
        $done = true;
    }
}

function generator_cc_format($form, $fieldname, $value, $class )
{
    _cc_format_links();

    $textarea = $form->generator_textarea($fieldname,$value,$class);

    $url = ccl('format','preview') . '/';
    $html =<<<END
        <div class="cc_box" style="display:none" id="format_preview_$fieldname">
            <h3>    <input type="button" onclick="cc_hide_preview('$fieldname')" value="x" class="cc_close_preview" />
Preview:</h3>
        <div class="cc_format_preview" id="format_inner_preview_$fieldname"></div>
            </div>
        <div class="cc_ed_buttons">
    <input type="button" onclick="cc_apply_format('$fieldname','b');" value="b" style="font-weight: bold;" />
    <input type="button" onclick="cc_apply_format('$fieldname','i');" value="i" style="font-style:italic;" />
    <input type="button" onclick="cc_apply_format('$fieldname','u');" value="u" style="text-decoration:underline;" />
    <input type="button" onclick="cc_apply_format('$fieldname','url');" value="link" style="text-decoration:underline;font-weight:bold;" />
    <input type="button" onclick="cc_apply_format('$fieldname','red');" value="R" style="color:red" />
    <input type="button" onclick="cc_apply_format('$fieldname','green');" value="G" style="color:green" />
    <input type="button" onclick="cc_apply_format('$fieldname','blue');" value="B" style="color:blue" />
    <input type="button" onclick="cc_apply_format('$fieldname','big');" value="+" style="" />
    <input type="button" onclick="cc_apply_format('$fieldname','small');" value="-" style="" />
    <input type="button" onclick="cc_format_preview('$fieldname')" id="preview_$fieldname" value="preview" style="font-size:smaller;" />
    </div>
        $textarea
END;

    return $html;
}

function _cc_format_unformat($text)
{
    $attrs = '(b|i|u|red|green|blue|big|small|url|quote)';
    return preg_replace("#\[/?$attrs(=[^\]]+)?\]#U",'',$text);
}

function _cc_format_format($text)
{
    $quote = _('Quote:');
    require_once('cclib/smartypants/smartypants.php');
    $attrs = '(b|i|u|red|green|blue|big|small)';
    $text = strip_tags($text);
    $text = preg_replace( "/\[$attrs\]/", '<span class="\1">', $text );
    $text = preg_replace( "#\[/$attrs\]#", '</span>', $text );
    $text = preg_replace( "/\[quote=?([^\]]+)?\]/", '<span class="quote"><span>'. $quote . ' $1</span>', $text );
    $text = preg_replace( "#\[/quote\]#", '</span>', $text );
    $text = SmartyPants($text);
    $urls = array( '@(?:^|[^">=\]])(http://[^\s$]+)@m',
                   '@\[url\]([^\[]+)\[/url\]@' ,
                   '@\[url=([^\]]+)\]([^\[]+)\[/url\]@' 
                    );
    $text = preg_replace_callback($urls,'_cc_format_url', $text);
    $text = nl2br($text);
    return $text;
}

function _cc_format_url(&$m)
{
    $url = $m[1];
    if( empty($m[2]) )
        $text = strlen($url) > 30 ? substr($url,0,27) . '...' : $url;
    else
        $text = $m[2];
    return( " <a title=\"$url\" class=\"cc_format_link\" href=\"$url\">$text</a>" );
}

function validator_cc_format($form, $fieldname)
{
    return $form->validator_textarea($fieldname);
}

function _cc_can_format_edit()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['format']) ||
           (!empty($CC_GLOBALS['adminformat']) && CCUser::IsAdmin());
}

function _cc_is_formatting_on()
{
    global $CC_GLOBALS;

    return !empty($CC_GLOBALS['format']) ||
           !empty($CC_GLOBALS['adminformat']);
}

/**
*
*
*/
class CCFormat
{
    function Preview()
    {
        if( !_cc_is_formatting_on() )
            return;

        $text = CCUtil::StripSlash(urldecode($_GET['ptext']));
        print _cc_format_format($text);
        exit;
    }

    function OnTopicRow(&$row)
    {
        if( !_cc_is_formatting_on() )
            return;

        _cc_format_links();

        $row['topic_text_html'] = _cc_format_format($row['topic_text']);
        $row['topic_text_plain'] = _cc_format_unformat($row['topic_text']);
    }

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_ROW}
    *
    * @param array &$record Upload row to massage with display data 
    */
    function OnUploadRow(&$row)
    {
        if( !_cc_is_formatting_on() )
            return;

        if( empty($row['upload_description']) && 
            empty($row['upload_extra']['edpicks']) )
        {
            return;
        }

        _cc_format_links();

        if( !empty($row['upload_description']) )
        {
            $row['upload_description_html'] = _cc_format_format($row['upload_description']);
            $row['upload_description_text'] = _cc_format_unformat($row['upload_description']);
        }

        if( !empty($row['upload_extra']['edpicks']) )
        {
            $picks =& $row['upload_extra']['edpicks'];
            $count = count($picks);
            $keys = array_keys($picks);
            for( $i = 0; $i < $count; $i++ )
            {
                $pick =& $row['upload_extra']['edpicks'][ $keys[$i] ];
                $pick['review_html'] = _cc_format_format($pick['review']);
                $pick['review_text'] = _cc_format_unformat($pick['review']);
            }
        }


    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$row)
    {
        if( !_cc_is_formatting_on() )
            return;

        $need_script = false;
        if( !empty($row['user_fields']) )
        {
            $count = count($row['user_fields']);
            $keys = array_keys($row['user_fields']);
            for( $i = 0; $i < $count; $i++ )
            {
                $F =& $row['user_fields'][ $keys[$i] ];
                if( !empty($F['id']) && $F['id'] == 'user_description' )
                {
                    $F['value'] = _cc_format_format($row['user_description']);
                    $need_script = true;
                    break;
                }
            }
        }

        if( $need_script )
            _cc_format_links();
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('format'), array('CCFormat','CallBack'), CC_DONT_CARE_LOGGED_IN,
                ccs(__FILE__));
    }

    function CallBack($cmd)
    {
        switch( $cmd )
        {
            case 'preview':
                return $this->Preview();
        }
    }


    /**
    * Event handler for {@link CC_EVENT_FORM_FIELDS}
    *
    * @param object &$form CCForm object
    * @param object &$fields Current array of form fields
    */
    function OnFormFields(&$form,&$fields)
    {
        if( !_cc_can_format_edit() )
            return;

        $fields_we_like = array( 'user_description', 'upload_description', 
                                  'editorial_review', 'topic_text' );

        $help = _('Select text and use the <br />buttons to apply format');


        $count = count($fields);
        $keys = array_keys($fields);
        for( $i = 0; $i < $count; $i++ )
        {
            if( in_array( $keys[$i], $fields_we_like ) )
            {
                $F =& $fields[ $keys[$i] ];
                if( $F['formatter'] == 'textarea' )
                {
                    $F['formatter'] = 'cc_format';
                    if( empty($F['form_tip']) )
                        $F['form_tip'] = $help;
                    else
                        $F['form_tip'] .= '<br /><br />' . $help;
                }
            }
        }
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['format'] =
               array(  'label'      => 'Allow User Text Formatting',
                       'form_tip'   => 'Allow users to format text (bold, italic, etc.)',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

            $fields['adminformat'] =
               array(  'label'      => 'Allow Admin Text Formatting',
                       'form_tip'   => 'Allow admins to format text',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}



?>