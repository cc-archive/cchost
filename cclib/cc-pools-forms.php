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
* Module for admin management of sample pools
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to ccHost');

/**
*/
require_once('cclib/cc-form.php');

/**
* Form for editing the properties of a known pool
*
*/
class CCAdminEditPoolForm extends CCForm
{
    function CCAdminEditPoolForm()
    {
        $this->CCForm();
        $fields = array( 
            'pool_name' =>  
               array(  'label'      => _('Name'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
            'pool_short_name' =>  
               array(  'label'      => _('Internal Name'),
                       'formatter'  => 'statictext',
                       'flags'      => CCFF_NOUPDATE | CCFF_STATIC ),
            'pool_description' =>
               array(  'label'      => _('Description'),
                       'formatter'  => 'textarea',
                       'flags'      => CCFF_POPULATE ),
            'pool_api_url' =>  
               array(  'label'      => _('API URL'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),
            'pool_site_url' =>  
               array(  'label'      => _('Site URL'),
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE ),
            'pool_banned' =>  
               array(  'label'      => _('Banned'),
                       'form_tip'   => _('Ignore communications from this pool'),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),
            'pool_search' =>  
               array(  'label'      => _("Allow to be searched remotely"),
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE ),
            );

        $this->AddFormFields($fields);
    }
}

class CCAdminPoolsForm extends CCForm
{
    function CCAdminPoolsForm()
    {
        $this->CCForm();

        $fields = array( 
            /*
            'allow-pool-search' =>  
                   array(  'label'      => 'Allow users to search remote pools',
                           'formatter'  => 'checkbox',
                           'flags'      => CCFF_POPULATE  ),

            'pool-push-hub' =>  
                   array(  'label'      => 'Request to be a pool at:',
                           'form_tip'   => 'Must the URL to the site\'s pool API',
                           'formatter'  => 'doitnow',
                           'nowbutton'  => 'Request Now',
                           'flags'      => CCFF_POPULATE ),
            */
            'pool-remix-throttle' =>
                   array(  'label'      => _('Remote Remix Throttle'),
                           'form_tip'   => _('Maximum remote unnapproved remixes.'),
                           'formatter'  => 'textedit',
                           'class'      => 'cc_form_input_short',
                           'flags'      => CCFF_POPULATE  ),

            'pool-pull-hub' =>  
                   array(  'label'      => _('Add a sample pool to your site:'),
                           'form_tip'   => _("This must be the URL to the site's pool API (e.g. http://ccmixter.org/media/api)."),
                           'formatter'  => 'doitnow',
                           'nowbutton'  => 'Add Now',
                           'flags'      => CCFF_POPULATE ),
/*
            'allow-pool-register' =>  
                   array(  'label'      => 'Allow remote pools to register here',
                           'formatter'  => 'checkbox',
                           'flags'      => CCFF_POPULATE  ),
*/
               );
        $this->AddFormFields($fields);
    }

    function generator_doitnow($varname,$value='',$class='')
    {
        $html = $this->generator_textedit($varname,$value,$class);
        $caption = $this->GetFormFieldItem($varname,'nowbutton');
        $html .= " <input type='submit' id=\"doitnow_$varname\" name=\"doitnow_$varname\" value=\"$caption\" />";
        return( $html );
    }

    function validator_doitnow($fieldname)
    {
        return( $this->validator_textedit($fieldname) );
    }

    /**
     * Overrides base class in order to populate fields with current contents of environment's config.
     *
     */
    function GenerateForm($hiddenonly = false)
    {
        $configs =& CCConfigs::GetTable();
        $values = $configs->GetConfig('config');
        $this->PopulateValues($values);
        return( parent::GenerateForm($hiddenonly) );
    }
}


?>
