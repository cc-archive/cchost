<?/*
[meta]
    type = template_component
    name = info
    dataview = info
    embedded = 1
[/meta]
[dataview]
function info_dataview() 
{
    $urlf = ccl('files') . '/';
    $urlp = ccl('people') . '/';
    $urll = ccd('ccskins/shared/images/lics/'); 

    $sql =<<<EOF
SELECT 
    user_id, upload_user, upload_id, upload_name, upload_extra, 
    upload_description as _need_description_text, upload_tags,
    CONCAT( '$urlf', user_name, '/', upload_id ) as file_page_url,
    user_real_name,
    CONCAT( '$urlp', user_name ) as artist_page_url,
    CONCAT( '$urll', license_logo ) as license_logo_url,
    license_url, license_name,
    DATE_FORMAT( upload_date, '%a, %b %e, %Y @ %l:%i %p' ) as upload_date,
    collab_upload_collab as collab_id, upload_contest, user_name
    %columns%
FROM cc_tbl_uploads
JOIN cc_tbl_user ON upload_user = user_id
JOIN cc_tbl_licenses ON upload_license = license_id
LEFT OUTER JOIN cc_tbl_collab_uploads ON upload_id = collab_upload_upload
%joins%
%where% 
LIMIT 1
EOF;
    return array( 'sql' => $sql,
                   'e'  => array( CC_EVENT_FILTER_FILES,
                                  CC_EVENT_FILTER_UPLOAD_TAGS,
                                  CC_EVENT_FILTER_COLLAB_CREDIT,
                                  CC_EVENT_FILTER_EXTRA,
                                  CC_EVENT_FILTER_DESCRIPTION_TEXT,
                                  CC_EVENT_FILTER_REMIXES_FULL)
                );
}
[/dataview]
*/

$R =& $A['records'][0];

?>

<div class="info_detail" style="margin:0px;padding:0px;">
<div  class="cc_list" id="_info_<?= $R['upload_id']?>">
<a  href="<?= $R['license_url']?>" title="<?= $R['license_name']?>" class="cc_liclogo">
    <img  src="<?= $R['license_logo_url'] ?>" />
</a>
<?

if ( !empty($idetail)) 
{
    ?>
    <h3  class="dtitle"><a class="cc_file_link" href="<?= $R['file_page_url']?>"><?= $R['upload_name']?></a> <?= $T->String('str_by')?> 
        <a  class="cc_user_link" href="<?= $R['artist_page_url']?>"><?= $R['artist_full_namel']?></a>
    </h3>
    <?
} 

if ( !empty($R['upload_extra']['featuring'])) 
{
    ?><div ><?= $T->String('str_featuring')?> : <b ><?= $R['upload_extra']['featuring']?></b></div><?
}

?><div  class="cc_upload_date"><?= $R['upload_date']?></div><?

$A['tag_array'] = $R['upload_taglinks'];

?><div  class="taglinks">Tags: <? $T->Call('tags.xml/taglinks');?></div><?

if ( !empty($R['upload_description_text'])) 
{
    ?>
    <div  class="gd_description" id="iddesc_<?= $R['upload_id'] ?>">
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

if ( !empty($R['remix_parents']) ) 
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
        ?><a href="<?= $P['file_page_url']?>" class="cc_file_link"><?= $P['upload_name']?></a> <?= $T->String('str_by')?>
            <a  href="<?= $P['artist_page_url']?>" class="cc_user_link"><?= $P['user_real_name']?></a><?
        if( $i++ < $last )
            print '<br />';
    }
?>
    </td></tr>
    </table></div>
</td><?
} // END: if

?><td >&nbsp</td><?

if ( !empty($R['remix_children']) ) 
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
        ?><a href="<?= $P['file_page_url']?>" class="cc_file_link"><?= $P['upload_name']?></a> <?= $T->String('str_by')?>
          <a href="<?= $P['artist_page_url']?>" class="cc_user_link"><?= $P['user_real_name']?></a>
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