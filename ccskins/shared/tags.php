<?

if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

//------------------------------------- 
function _t_tags_taglinks($T,&$A) 
{
    $carr101 = $A['tag_array'];
    $cc101= count( $carr101);
    $ck101= array_keys( $carr101);
    for( $ci101= 0; $ci101< $cc101; ++$ci101)
    { 
       $A['tag'] = $carr101[ $ck101[ $ci101 ] ];
       
        ?><a href="<?= $A['tag']['tagurl']?>" rel="tag" class="taglink"><?= $A['tag']['tag']?></a><?
        if ( !($ci101 == ($cc101-1)) ) { ?>, <? }
    } // END: for loop
} // END: function taglinks
  

//------------------------------------- 
function _t_tags_popular_tags($T,&$A) 
{
    $carr102 = $A['field']['tags'];
    $cc102= count( $carr102);
    $ck102= array_keys( $carr102);
    for( $ci102= 0; $ci102< $cc102; ++$ci102)
    { 
       $A['tag'] = $carr102[ $ck102[ $ci102 ] ];
       ?><a href="javascript://popular" onclick="cc_add_tag('<?= $A['tag']?>','<?= $A['field']['target']?>');" class="taglink"><?= $A['tag']?></a><?
       if ( !($ci102 == ($cc102-1)) ) { ?>, <? }
     } // END: for loop
} // END: function popular_tags
  

//------------------------------------- 
function _t_tags_tag_picker($T,&$A) 
{
  
    ?><div  class="cc_tags_list"><?

    $carr103 = $A['minus_tags'];
    $cc103= count( $carr103);
    $ck103= array_keys( $carr103);
    for( $ci103= 0; $ci103< $cc103; ++$ci103)
    { 
       $A['m'] = $carr103[ $ck103[ $ci103 ] ];
       ?><div  style="text-align:right">
        <a class="taglink" rel="nofollow" href="<?= $A['base_tag_url']?>/<?= $A['m']['tag']?>"><?= $A['m']['tag']?></a>
        <b>[<a href="<?= $A['m']['url']?>"> - </a>]</b>
        </div><?
    } // END: for loop
    
    ?><hr  /><?

    $carr104 = $A['all_tags'];
    $cc104= count( $carr104);
    $ck104= array_keys( $carr104);
    for( $ci104= 0; $ci104< $cc104; ++$ci104)
    { 
       $A['t'] = $carr104[ $ck104[ $ci104 ] ];
       
       ?><div  style="text-align:right">
        <a class="taglink" rel="nofollow" href="<?= $A['base_tag_url']?>/<?= $A['t']?>"><?= CC_strchop($A['t'],12)?></a>
        <b>[<a href="<?= $A['base_addtag_url']?><?= $A['t']?>"> + </a>]</b>
        </div><?
    } // END: for loop
    
    ?></div><?
} // END: function tag_picker
  

function _t_tags_tags($T,&$A) 
{
  
    ?><div  class="cc_tag_switch_link"><?= $A['tag_switch_link']?></div><?

    $carr105 =& $A['tag_array'];
    $cc105= count( $carr105);
    $ck105= array_keys( $carr105);
    for( $ci105= 0; $ci105< $cc105; ++$ci105)
    { 
       $A['tag'] = $carr105[ $ck105[ $ci105 ] ];
       
        ?><span  class="cc_tag_count">
        <a href="<?= $A['tag']['tagurl']?>" rel="nofollow tag" class="taglink" style="line-height:110%;font-size:<?= $A['tag']['fontsize']?>px"><?= $A['tag']['tags_tag']?></a> (<?= $A['tag']['tags_count']?>)</span><?
        if ( !($ci105 == ($cc105-1)) ) { ?>, <? }
    } // END: for loop
    
    ?><p  class="cc_tag_bottom">&nbsp;</p><?
}
  
?>