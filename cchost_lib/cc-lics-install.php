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
* Install licenses
*
* @package cchost
* @subpackage admin
*/

error_reporting(E_ALL);

/**
* Get 2.5 licenses to use in this installation
*/
function cc_get_lic_fields_2_5()
{
    // @todo -- domains

    $default_licenses= array( 
                array( 'license_id'         => 'attribution',
                       'license_url'        => "http://creativecommons.org/licenses/by/2.5/",
                       'license_name'       => 'Attribution',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by.png',
                       'license_tag'        => 'attribution',
                       'license_enabled'    => false,
                       'license_strict'     => 10,
                       'license_text'       => _('<strong>Attribution</strong>: People can copy, distribute, perform, display, transform and make money from your work for any purpose as long they give you credit (attribution).')
                       ),
                array( 'license_id'         => 'noncommercial', 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc/2.5/",
                       'license_name'       => 'Attribution Noncommercial',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nc.png',
                       'license_tag'        => 'non_commercial',
                       'license_enabled'    => false,
                       'license_strict'     => 20,
                       'license_text'       => _('<strong>Attribution Noncommercial</strong>: People can copy, distribute, perform, display and transform your work for any purpose as long they give you credit (attribution). People may <i>not</i> use your work for commercial purposes.')
                       ),
                array( 'license_id'         => 'share-alike'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-sa/2.5/",
                       'license_name'       => 'Attribution Share-Alike',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice,ShareAlike',
                       'license_logo'       => 'by-sa.png',
                       'license_tag'        => 'share_alike',
                       'license_enabled'    => false,
                       'license_strict'     => 90,
                       'license_text'       => _('<strong>Attribution Share Alike</strong>: People can copy, distribute, perform, display, transform and make money from your work for any purpose as long they give you credit (attribution). If someone  alters, transforms, or builds upon this work, they have to distribute the resulting work under this same license.')
                       ),
                array( 'license_id'         => 'noderives'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nd/2.5/",
                       'license_name'       => 'Attribution Non-derivative',
                       'license_permits'    => 'Reproduction,Distribution',
                       'license_prohibits'  => 'DerivativeWorks',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nd.png',
                       'license_tag'        => 'no_derivitives',
                       'license_enabled'    => false,
                       'license_strict'     => 30,
                       'license_text'       => _('<strong>Attribution NoDerivatives</strong>: People can copy, distribute, perform, and display your work "as is" (without changes) for any purpose (e.g. file sharing) as long they give you credit (attribution).')
                       ),
                array( 'license_id'         => 'by-nc-sa'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc-sa/2.5/",
                       'license_name'       => 'Attribution Noncommercial Share-Alike',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice,ShareAlike',
                       'license_logo'       => 'by-nc-sa.png',
                       'license_tag'        => 'non_commercial_share_alike',
                       'license_enabled'    => false,
                       'license_strict'     => 90,
                       'license_text'       => _('<strong>Attribution Noncommercial Share-Alike</strong>: People can copy, distribute, perform, display, transform your work for <b>non commercial purposes only</b> as long they give you credit (attribution). If someone  alters, transforms, or builds upon this work, they have to distribute the resulting work under this same license.')
                       ),
                array( 'license_id'         => 'by-nc-nd'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc-nd/2.5/",
                       'license_name'       => 'Attribution Noncommercial No-Derivs',
                       'license_permits'    => 'Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nc-nd.png',
                       'license_tag'        => 'non_commercial_no_derivs',
                       'license_enabled'    => false,
                       'license_strict'     => 40,
                       'license_text'       => _('<strong>Attribution Noncommercial No Derivatives</strong>: People can copy, distribute, perform, display, your work "as is" (without modifcations) for <b>non commercial purposes only</b> as long they give you credit (attribution).')
                       ),
                 );

    return $default_licenses;
}


/**
* Get 3.0 licenses to use in this installation
*/
function cc_get_lic_fields_3_0()
{
    // @todo -- domains

    $default_licenses= array( 
                array( 'license_id'         => 'attribution_3',
                       'license_url'        => "http://creativecommons.org/licenses/by/3.0/",
                       'license_name'       => 'Attribution (3.0)',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-3.png',
                       'license_tag'        => 'attribution',
                       'license_enabled'    => true,
                       'license_strict'     => 10,
                       'license_text'       => _('<strong>Attribution</strong> 3.0: People can copy, distribute, perform, display, transform and make money from your work for any purpose as long they give you credit (attribution).')
                       ),
                array( 'license_id'         => 'noncommercial_3', 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc/3.0/",
                       'license_name'       => 'Attribution Noncommercial  (3.0)',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nc-3.png',
                       'license_tag'        => 'non_commercial',
                       'license_enabled'    => true,
                       'license_strict'     => 20,
                       'license_text'       => _('<strong>Attribution Noncommercial</strong>  3.0: People can copy, distribute, perform, display and transform your work for any purpose as long they give you credit (attribution). People may <i>not</i> use your work for commercial purposes.')
                       ),
                array( 'license_id'         => 'share-alike_3'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-sa/3.0/",
                       'license_name'       => 'Attribution Share-Alike  (3.0)',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice,ShareAlike',
                       'license_logo'       => 'by-sa-3.png',
                       'license_tag'        => 'share_alike',
                       'license_enabled'    => true,
                       'license_strict'     => 90,
                       'license_text'       => _('<strong>Attribution Share Alike</strong>  3.0: People can copy, distribute, perform, display, transform and make money from your work for any purpose as long they give you credit (attribution). If someone  alters, transforms, or builds upon this work, they have to distribute the resulting work under this same license.')
                       ),
                array( 'license_id'         => 'noderives_3'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nd/3.0/",
                       'license_name'       => 'Attribution Non-derivative  (3.0)',
                       'license_permits'    => 'Reproduction,Distribution',
                       'license_prohibits'  => 'DerivativeWorks',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nd-3.png',
                       'license_tag'        => 'no_derivitives',
                       'license_enabled'    => false,
                       'license_strict'     => 30,
                       'license_text'       => _('<strong>Attribution NoDerivatives</strong>  3.0: People can copy, distribute, perform, and display your work "as is" (without changes) for any purpose (e.g. file sharing) as long they give you credit (attribution).')
                       ),
                array( 'license_id'         => 'by-nc-sa_3'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc-sa/3.0/",
                       'license_name'       => 'Attribution Noncommercial Share-Alike  (3.0)',
                       'license_permits'    => 'DerivativeWorks,Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice,ShareAlike',
                       'license_logo'       => 'by-nc-sa-3.png',
                       'license_tag'        => 'non_commercial_share_alike',
                       'license_enabled'    => true,
                       'license_strict'     => 90,
                       'license_text'       => _('<strong>Attribution Noncommercial Share-Alike</strong>  3.0: People can copy, distribute, perform, display, transform your work for <b>non commercial purposes only</b> as long they give you credit (attribution). If someone  alters, transforms, or builds upon this work, they have to distribute the resulting work under this same license.')
                       ),
                array( 'license_id'         => 'by-nc-nd_3'   , 
                        'license_url'       => "http://creativecommons.org/licenses/by-nc-nd/3.0/",
                       'license_name'       => 'Attribution Noncommercial No-Derivs  (3.0)',
                       'license_permits'    => 'Reproduction,Distribution',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'by-nc-nd-3.png',
                       'license_tag'        => 'non_commercial_no_derivs',
                       'license_enabled'    => false,
                       'license_strict'     => 40,
                       'license_text'       => _('<strong>Attribution Noncommercial No Derivatives</strong>  3.0: People can copy, distribute, perform, display, your work "as is" (without modifcations) for <b>non commercial purposes only</b> as long they give you credit (attribution).')
                       ),
                array( 'license_id'         => 'sampling'   , 
                        'license_url'       => 'http://creativecommons.org/licenses/sampling/1.0/',
                       'license_name'       => 'Sampling',
                       'license_permits'    => 'DerivativeWorks,Reproduction',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice',
                       'license_logo'       => 'sampling.png',
                       'license_tag'        => 'sampling',
                       'license_enabled'    => false,
                       'license_strict'     => 125,
                       'license_text'       => _('<strong>Sampling</strong>: People can take and transform <strong>pieces</strong> of your work for any purpose other than advertising, which is prohibited. Copying and distribution of the <strong>entire work</strong> is also prohibited.')
                       ),
                 array( 'license_id'        => 'sampling+',
                        'license_url'       => 'http://creativecommons.org/licenses/sampling+/1.0/',
                       'license_name'       => 'Sampling Plus',
                       'license_permits'    => 'Sharing,DerivativeWorks,Reproduction',
                       'license_prohibits'  => '',
                       'license_required'   => 'Attribution,Notice',
                       'license_tag'        => 'sampling_plus',
                       'license_enabled'    => false,
                       'license_logo'       => 'sampling_plus.png',
                       'license_strict'     => 135,
                       'license_text'       => _('<strong>Sampling Plus</strong>: People can take and transform <strong>pieces</strong> of your work for any purpose other than advertising, which is prohibited. <strong>Noncommercial</strong> copying and distribution (like file-sharing) of the <strong>entire work</strong> are also allowed. Hence, "<strong>plus</strong>".')
                       ),
                 array( 'license_id'        =>   'nc-sampling+',
                        'license_url'       => 'http://creativecommons.org/licenses/nc-sampling+/1.0/',
                       'license_name'       => 'Noncommercial Sampling Plus',
                       'license_permits'    => 'Distribution,DerivativeWorks,Reproduction',
                       'license_prohibits'  => 'CommercialUse',
                       'license_required'   => 'Attribution,Notice',
                       'license_enabled'    => false,
                       'license_tag'        => 'nc_sampling_plus',
                       'license_logo'       => 'nc-sampling_plus.png',
                       'license_strict'     => 180,
                       'license_text'       => _('<strong>Noncommercial Sampling Plus</strong>: People can take and transform <strong>pieces</strong> of your work for <strong>noncommercial</strong> purposes only. <strong>Noncommercial</strong> copying and distribution (like file-sharing) of the <strong>entire work</strong> are also allowed.'),
                       ),
                 array( 'license_id'        => 'publicdomain' ,
                        'license_url'       => 'http://creativecommons.org/licenses/publicdomain',
                       'license_name'       => 'Public Domain',
                       'license_permits'    => 'Reproduction,Distribution,DerivativeWorks',
                       'license_prohibits'  => '',
                       'license_required'   => '',
                       'license_logo'       => 'pd.png',
                       'license_tag'        => 'public_domain',
                       'license_enabled'    => false,
                       'license_strict'     => 0,
                       'license_text'       => _('<strong>Public Domain</strong>: This choice suggests you want to dedicate your work to the public domain, the commons of information and expression where <strong>nothing is owned and all is permitted</strong>. The Public Domain Dedication is not a license. By using it, you do not simply carve out exceptions to your copyright; you grant your entire copyright to the public without condition. This grant is <strong>permanent and irreversible</strong>.')
                       ),
                 );

    return $default_licenses;
}


function cc_install_licenses( $lic_infos = array(  '3_0' ), $nuke_db = true )
{

    $licenses =  new CCTable('cc_tbl_licenses','license_id');

    if( $nuke_db )
        $licenses->DeleteWhere('1');

    foreach( $lic_infos as $LI )
    {
        $getter = 'cc_get_lic_fields_' . $LI;
        $default_licenses = call_user_func($getter);

        $active = array();
        foreach( $default_licenses as $lic )
        {
            if( $lic['license_enabled'] )
                $active[] = $lic['license_id'];
            unset($lic['license_enabled']);
            if( !$nuke_db )
            {
                $key['license_id'] = $lic['license_id'];
                $count = $licenses->CountRows($key);
                if( $count )
                    continue;
            }
            $licenses->Insert($lic);
        }
    }

    if( $nuke_db )
    {
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('licenses',$active,CC_GLOBAL_SCOPE,false);
    }
}

?>
