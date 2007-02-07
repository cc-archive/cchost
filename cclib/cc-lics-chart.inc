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

function & cc_license_chart()
{
    static $chart;
    
    if( !isset($chart) )
    {
        $chart = array
          (
            'nc-sampling+' => array
                (
                    'nc-sampling+' =>   'nc-sampling+', 
                    'sampling+' =>   'nc-sampling+', 
                    'sampling' =>   'nc-sampling+', 
                    'share-alike' =>   'nc-sampling+', 
                    'by-nc-sa' =>   'nc-sampling+', 
                    'noncommercial' =>   'nc-sampling+', 
                    'attribution' =>   'nc-sampling+', 
                    'publicdomain' =>   'nc-sampling+', 
                ),

            'sampling+' =>  array
                (
                    'nc-sampling+' =>   'nc-sampling+', 
                    'sampling+' =>   'sampling+', 
                    'sampling' =>   'sampling+', 
                    'share-alike' =>   'sampling+', 
                    'by-nc-sa' =>   'nc-sampling+', 
                    'noncommercial' =>  'nc-sampling+' , 
                    'attribution' =>   'sampling+', 
                    'publicdomain' =>   'sampling+', 
                ),

            'sampling' =>   array
                (
                    'nc-sampling+' =>  'nc-sampling+' , 
                    'sampling+' =>   'nc-sampling+', 
                    'sampling' =>   'sampling', 
                    'share-alike' =>   'sampling', 
                    'by-nc-sa' =>  'nc-sampling+' , 
                    'noncommercial' =>   'nc-sampling+', 
                    'attribution' =>   'sampling', 
                    'publicdomain' =>   'sampling', 
                ),

            'share-alike' =>   array
                (
                    'nc-sampling+' =>  'nc-sampling+'  , 
                    'sampling+' =>   'sampling+' , 
                    'sampling' =>   'sampling' , 
                    'share-alike' =>   'share-alike', 
                    'by-nc-sa' =>   'by-nc-sa', 
                    'noncommercial' =>  'by-nc-sa' , 
                    'attribution' =>   'share-alike', 
                    'publicdomain' =>  'share-alike' , 
                ),

            'by-nc-sa' =>   array
                (
                    'nc-sampling+' =>  'nc-sampling+' , 
                    'sampling+' =>   'nc-sampling+', 
                    'sampling' =>   'nc-sampling+', 
                    'share-alike' =>  'by-nc-sa', 
                    'by-nc-sa' =>   'by-nc-sa', 
                    'noncommercial' =>  'by-nc-sa' , 
                    'attribution' =>  'by-nc-sa' , 
                    'publicdomain' =>   'by-nc-sa', 
                ),

            'noncommercial' =>   array
                (
                    'nc-sampling+' =>   'nc-sampling+' , 
                    'sampling+' =>   'nc-sampling+' , 
                    'sampling' =>   'nc-sampling+' , 
                    'share-alike' =>  'by-nc-sa'  , 
                    'by-nc-sa' =>  'by-nc-sa'  , 
                    'noncommercial' =>  'noncommercial' , 
                    'attribution' =>   'noncommercial', 
                    'publicdomain' =>   'noncommercial', 
                ),

            'attribution' =>   array
                (
                    'nc-sampling+' =>  'nc-sampling+' , 
                    'sampling+' =>   'sampling+', 
                    'sampling' =>   'sampling' , 
                    'share-alike' =>  'share-alike'  , 
                    'by-nc-sa' =>  'by-nc-sa'  , 
                    'noncommercial' =>  'noncommercial' , 
                    'attribution' =>   'attribution', 
                    'publicdomain' =>   'attribution', 
                ),

            'publicdomain' =>   array
                (
                    'nc-sampling+' =>  'nc-sampling+' , 
                    'sampling+' =>   'sampling+', 
                    'sampling' =>   'sampling' , 
                    'share-alike' =>  'share-alike'  , 
                    'by-nc-sa' =>  'by-nc-sa'  , 
                    'noncommercial' =>  'noncommercial' , 
                    'attribution' =>   'attribution', 
                    'publicdomain' =>   'publicdomain' , 
                ),

            );
    }

    return $chart;
}

function cc_stricter_license( $lic1,$lic2 )
{
    $chart = function_exists('custom_lic_chart') ? $custom_lic_chart() : cc_license_chart();
    if( !empty($chart[$lic1][$lic2]) )
        return $chart[$lic1][$lic2];
    return null;
    //trigger_error("invalid lic mix $lic1 - $lic2");
}

?>