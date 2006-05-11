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

CCEvents::AddHandler(CC_EVENT_SUBMIT_FORM_TYPES,   array( 'CCMusicForms' , 'OnSubmitFormTypes') );

/**
* @package cchost
* @subpackage ui
*/
class CCMusicForms
{

    /*
    * Event handler for {@link CC_EVENT_SUBMIT_FORM_TYPES}
    *
    * @param array &$types Submit form meta information are put here
    */
    function OnSubmitFormTypes(&$types)
    {
        $new_types = array( 
                'remix' => array(
                        'text' => cct('Submit a Remix'),
                        'submit_type' => 'Remix',
                        'help' => cct('A remix using samples downloaded from this site. When submitting a remix make sure ' .
                                  'to properly attribute the artist you sampled to comply with the Attribution ' .
                                  'part the Creative Commons license. The next screen will have a search function ' .
                                  'that allows you do just that.'),
                        'tags'  => array( CCUD_MEDIA_BLOG_UPLOAD, CCUD_REMIX ),
                        'weight' => 1,
                        'isremix' => true,
                        'media_types' => array( 'audio' ),
                        'enabled' => true,
                        'form_help' => cct('Use this form to submit a remix'),
                        'logo' => 'mixter-remix.gif',
                         ),
                'pella' => array(
                        'text' => cct('Submit an A Cappella'),
                        'submit_type' => 'A Cappella',
                        'help' => cct('Stand alone vocal parts, either spoken word or sung. Mono recording with no effects  ' .
                                  '(reverb, delay, etc.) on them are best because they are the most flexible to work with. ' .
                                  'Many singers think they sound "better" with a lot of effects but it is always better to ' .
                                  'leave those choices to a producer/remixer to allow them to use their creative skills to ' .
                                  'the fullest potential.'),
                        'tags'  => array( 'acappella', CCUD_MEDIA_BLOG_UPLOAD ),
                        'weight' => 10,
                        'isremix' => false,
                        'form_help' => cct('Use this form to submit an a cappella'),
                        'enabled' => true,
                        'media_types' => array( 'audio' ),
                        'logo' => 'mixter-pella.gif',
                         ),
                'samples' => array(
                        'text' => cct('Submit Samples'),
                        'submit_type' => 'Sample',
                         'help' => cct('Samples can be a loop, a one-shot note or drum hit or any other snippet of sound ' .
                                   'that might be useful to a producer or remixer. You are encouraged to make a collection ' .
                                   'of samples and upload them together in archive format (ZIP), however sound files are ' .
                                   'accepted as well. By far the most flexible '.
                                   'samples to work with are mono and have no effects for acoustic instruments and minimal ' .
                                   'effects for synthesized sounds.'),
                        'tags'  => array( 'sample', CCUD_MEDIA_BLOG_UPLOAD ),
                        'weight' => 15,
                        'form_help' => cct('Use this form to submit samples'),
                        'enabled' => true,
                        'isremix' => false,
                        'media_types' => array( 'audio', 'archive' ),
                        'logo' => 'mixter-loop.gif',
                         ),
                'fullmix' => array(
                        'text' => cct('Submit a Fully Mixed Track'),
                        'submit_type' => 'Original',
                        'help' => cct('An original track that is fully mixed is <i>extremely unlikely</i> to be remixed because of ' .
                                  'the extra work the producer or remixer has to do the extract the parts they actually ' .
                                  'wish to use. Before uploading your track here, ' .
                                  'consider uploading to one of several free hosting sites sponsored by Creative Commons such ' .
                                  '<a href="http://archive.org/audio">Internet Archive</a> or <a href="http://ourmedia.org">' .
                                  'Our Media</a> both of which might be more appropriate places to post completely mixed ' .
                                  'tracks.'),
                        'tags'  => array( CCUD_MEDIA_BLOG_UPLOAD, CCUD_ORIGINAL ),
                        'weight' => 50,
                        'enabled' => true,
                        'form_help' => cct('Use this form to submit a fully mixed track'),
                        'isremix' => false,
                        'media_types' => array( 'audio' ),
                        'logo' => 'mixter-mixed.gif',
                         ),
            );

        $types += $new_types;
        
    }

}

?>