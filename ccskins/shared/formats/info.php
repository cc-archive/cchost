<?

$idetail = empty($A['detail']) ? null : $A['detail']; 
$R       = CC_get_details($A['records']['0']['upload_id'],$idetail);
$upname  = CC_strchop($R['upload_name'],$A['chop'],$A['dochop']);
$upuser  = CC_strchop($R['user_real_name'],$A['chop'],$A['dochop']);
$upid    = $R['upload_id'];

?>
<div class="info_detail" style="margin:0px;padding:0px;">
<div  class="cc_list" id="_info_<?= $upid?>"><?

if ( !empty($R['local_menu'])) 
{
    ?>
    <div  class="upload_menu_outer" id="upload_menu_outer_<?= $upid?>">
    <div  class="upload_menu_title" id="upload_menu_title_<?= $upid?>">menu</div>
    <?
       foreach( $R['local_menu'] as $grp )
        {
           foreach( $grp as $item )
            {
                ?><div ><a  id="<?= $item['id']?>" href="<?= $item['action']?>"><span ><?= $item['menu_text']?></span></a></div><?
            }
        }
    ?></div><?
}

?>
<a  href="<?= $R['license_url']?>" title="<?= $R['license_name']?>" class="cc_liclogo">
    <img  src="<?= $T->URL('images/lics/small-' . $R['license_logo']); ?>" />
</a>
<?

if ( !empty($idetail)) 
{
    ?>
    <h3  class="dtitle"><a  href="<?= $R['file_page_url']?>"><?= $upname?></a> <?= $T->String('str_by')?> 
        <a  href="<?= $R['artist_page_url']?>"><?= $upuser?></a>
    </h3>
    <?
} 

if ( !empty($R['upload_extra']['featuring'])) 
{
    ?><div >Featuring: <b ><?= $R['upload_extra']['featuring']?></b></div><?
}

?><div  class="cc_upload_date"><?= $R['upload_date_format']?></div><?

$A['tag_array'] = $R['upload_taglinks'];

?><div  class="cc_tags">Tags: <? $T->Call('tags.xml/taglinks');?></div><?

if ( !empty($R['upload_description_html'])) 
{
    ?>
    <div  class="gd_description" id="iddesc_<?= $upid?>">
        <div  style="padding: 10px;"><span ><?= CC_strchop($R['upload_description_text'],200);?></span></div>
    </div>
    <?
}

?>
<table  class="files_table">
<tr>
<td class="column files_column">
<span  class="title files_title"><?= $T->String('str_files') ;?></span>:<br  />
<?

foreach( $R['files'] as $F )
{
        print $F['file_nicname']; ?>: <a  href="<?= $F['download_url']?>">download</a> <?= $F['file_filesize']?><br  /><?
}

?></td><td >&nbsp;</td><?

if ( isset($R['remix_parents']) ) 
{
?>
<td class="column parents_column">
    <div><table><tr>
    <td><img src="<?= $T->URL('images/downloadicon.gif'); ?>" /></td>
    <td>
    <span class="title parents_title"><?= $T->String('str_list_uses') ;?></span>:<br  />
<?
    $last = count( $R['remix_parents']) - 1;
    $i = 0;
    foreach( $R['remix_parents'] as $P )
    {
        $upname = CC_strchop($P['upload_name'],$A['chop'],$A['dochop']);
        $upuser = CC_strchop($P['user_real_name'],$A['chop'],$A['dochop']);
        ?><a href="<?= $P['file_page_url']?>" class="cc_file_link"><?= $upname?></a> <?= $T->String('str_by')?>
            <a  href="<?= $P['artist_page_url']?>" class="cc_user_link"><?= $upuser?></a><?
        if( $i++ < $last )
            print '<br />';
    }
?>
    </td></tr>
    </table></div>
</td><?
} // END: if

?><td >&nbsp</td><?

if ( isset($R['remix_children']) ) 
{
?>
<td class="column children_column">
    <div><table><tr>
    <td><img src="<?= $T->URL('images/uploadicon.gif') ?>" /></td>
    <td><span class="title children_title"><?= $T->String('str_samples_from_here') ;?></span>:<br />
<?

    $last = count($R['remix_children']) - 1;
    $i = 0;
    foreach( $R['remix_children'] as $P )
    {
        $upname = CC_strchop($P['upload_name'],$A['chop'],$A['dochop']);
        $upuser = CC_strchop($P['user_real_name'],$A['chop'],$A['dochop']);

        ?><a href="<?= $P['file_page_url']?>" class="cc_file_link"><?= $upname?></a> <?= $T->String('str_by')?>
          <a href="<?= $P['artist_page_url']?>" class="cc_user_link"><?= $upuser?></a>
        <?
        if( $i++ < $last )
            print '<br />';
    }
?>
    </td></tr></table>
    </div>
</td><?
} // END: if

?></tr>
</table>
</div><!-- cc_list -->
</div><!-- info_detail -->