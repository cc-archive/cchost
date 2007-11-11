<?

//------------------------------------- 
function _t_collab_show_collabs($T,&$A) 
{
?>
    <link  rel="stylesheet" type="text/css" href="<?= $T->URL( 'css/playlist.css' ) ?>" title="Default Style"></link>
    <link  rel="stylesheet" type="text/css" href="<?= $T->URL( 'css/collab.css' ) ?>" title="Default Style"></link>
    <table  style="width:95%">
<?
    $A['rows'] = array_chunk($A['collabs']['collab_rows'], 2);

    $carr101 =& $A['rows'];
    $cc101= count( $carr101);
    $ck101= array_keys( $carr101);
    for( $ci101= 0; $ci101< $cc101; ++$ci101)
    { 
       $A['col'] = $carr101[ $ck101[ $ci101 ] ];
       
        ?><tr ><?

        $carr102 = $A['col'];
        $cc102= count( $carr102);
        $ck102= array_keys( $carr102);
        for( $ci102= 0; $ci102< $cc102; ++$ci102)
        { 
           $R = $carr102[ $ck102[ $ci102 ] ];
           
?>
    <td  style="vertical-align: top;width:50%;">
    <div  class="collab_entry">
    <span  class="collab_date"><?= CC_datefmt($R['collab_date'],'M d, Y');?></span>
    <a  class="collab_name" href="<?= $A['home-url']?>collab/<?= $R['collab_id']?>"><?= CC_strchop($R['collab_name'],35);?></a>
    <br  />
<?
        $carr103 = $R['users'];
        $cc103= count( $carr103);
        $ck103= array_keys( $carr103);
        for( $ci103= 0; $ci103< $cc103; ++$ci103)
        { 
           $u = $carr103[ $ck103[ $ci103 ] ];
           ?><a  href="<?= $A['home-url']?>people/<?= $u['user_name']?>"><?= $u['user_real_name']?></a><?
            if ( !($ci103 == ($cc103-1)) ) { ?>, <? }
        }

        if( !empty($R['uploads']) ) 
        {
            $carr104 = $R['uploads'];
            $cc104= count( $carr104);
            $ck104= array_keys( $carr104);
            for( $ci104= 0; $ci104< $cc104; ++$ci104)
            { 
                $ai = $carr104[ $ck104[ $ci104 ] ];
                if( !empty($ai['fplay_url']) )
                {
                    ?><div class="tdc cc_playlist_pcontainer"><a class="cc_player_button cc_player_hear" id="_ep_1_<?= $ai['upload_id']?>" 
                            href="<?= $ai['fplay_url']?>"> </a></div><?
                }
            }
        }

        ?><br  style="clear:both" /></div></td><?
        } // END: for loop

        ?></tr><?
    } // END: for loop

    ?></table><?

    $T->Call('prev_next_links');
    $T->Call('playerembed.xml/eplayer');

} // END: function show_collabs


//------------------------------------- 
function _t_collab_show_collab($T,&$A) 
{
    ?><div id="collab_ajax_msg"></div><?

    $collab = $A['collab'];
    $C = $collab['collab'];

    $collab_id = $C['collab_id'];

    ?>
    <link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/collab.css') ?>" title="Default Style"></link>
    <link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/topics.css') ?>" title="Default Style"></link>
    <fieldset >
    <legend class="dark_bg light_color"><?= $T->String('str_info')?></legend>
    <?

    if ( !empty($collab['is_owner'])) {

    ?><div  style="float:right">
    <a  id="commentcommand" href="<?= $A['home-url']?>collab/edit/<?= $collab_id?>"><span ><?=$T->String('str_collab_edit')?></span></a>
    </div><?
    } // END: if

    ?><div  class="cc_collab_by"><?=$T->String('str_collab_created_by')?>: <a  href="<?= $A['home-url']?>people/<?= $C['user_name']?>"><?= $C['user_real_name']?></a> 
              <?= $C['collab_date']?></div>
    <div  class="cc_collab_desc light_bg dark_border"><?= $C['collab_desc']?></div>
    </fieldset>
    <div  class="cc_collab_fields">
    <fieldset >
    <legend class="dark_bg light_color" ><?= $T->String('str_artists') ?></legend>
    <div  class="user_lines">
    <div  id="user_inserter"></div>
    </div>
    <div  id="invite_container">
    </div>
    </fieldset>
    <fieldset >
    <legend class="dark_bg light_color" ><?= $T->String('str_files') ?> </legend>
    <div  class="file_list" id="file_list">
    </div>
    <?

    if ( !empty($collab['is_member']))
    {
        ?><iframe  style="display:none;" name="upload_frame"></iframe>
        <form  target="upload_frame" 
                enctype="multipart/form-data" 
                action="<?= $A['home-url']?>collab/upload/file/<?= $collab_id?>" 
                method="post" id="upform" name="upform"><?=$T->String('str_collab_upload_file')?>: <select  name="uptype" id="uptype">
<?
                $forms = cc_get_config('submit_forms');
                foreach($forms as $form)
                {
                    if( !$form['enabled'] ) 
                        continue;
                    $tags = join(',',$form['tags']);
                    $name = substr($form['submit_type'],0,10);
                    print "<option value=\"{$tags}\">{$name}</option>\n";
                }
?>
                </select>
        <input type="file" id="upfile" name="upfile"></input> <?=$T->String('str_collab_name')?>: <input  name="upname" id="upname" type="text"></input><select  name="lic" id="lic">
        <?
            foreach( $collab['lics'] as $lic )
            { ?><option  value="<?= $lic['license_id']?>"><?= $lic['license_name']?></option><? }

        ?></select>
        <button  id="fileok"><?=$T->String('str_collab_ok')?></button>
        </form>
        <div  id="upcover" style="position:absolute;display:none;" class="light_bg"> 
        <img  style="margin-left:45%" src="<?= $T->URL('images/spinner.gif') ?>" /></div>
        <?
    } // END: if member

    ?></fieldset><?

    if ( !empty($collab['is_member'])) 
    {
        ?><fieldset>
        <legend class="dark_bg light_color" ><?= $T->String('str_conversation') ?></legend>
        <p ><?=$T->String('str_collab_this_conv')?>:</p>
        <?
        $A['topics'] = $collab['topics'];
        //$T->Call('topics.xml/list_topics');

        ?>
        <div class="c_commands">
            <a href="<?= $A['home-url']?>collab/topic/add/<?= $collab_id?>" id="commentcommand"><span ><?=$T->String('str_collab_add_topic')?></span></a>
        </div>
        </fieldset><?
    } // END: if member

    ?></div>
    <script  src="<?= $T->URL('js/autocomp.js'); ?>"></script>
    <script  src="<?= $T->URL('js/collab.js'); ?>"></script>
    <script >
        var str_credit = '<?=$T->String('str_collab_credit2')?>';
        var str_remove = '<?=$T->String('str_collab_remove2')?>';
        var str_send_email = '<?=$T->String('str_collab_send_email')?>';
        var collab_template = '<' + 'div class="user_line light_bg med_color" id="_user_line_#{user_name}">' +
                '<' + 'div class="user" ><' + 'a href="'+home_url+'people/#{user_name}">#{user_real_name}<' + '/a><' + '/div>' +
                '<' + 'div class="role dark_color">#{role}<' + '/div>' +
                '<' + 'div class="credit" id="_credit_#{user_name}">#{credit}<' + '/div>' +
<?
    if ( !empty($collab['is_owner'])) 
    {
?>
         '<' + 'div><' + 'a href="javascript://edit credit" id="_user_credit_#{user_name}" class="user_cmd edit_credit"><' 
                  + 'span>' + str_credit +'<' + '/span><' + '/a><' + '/div>' +
         '<' + 'div><' + 'a href="javascript://remove user" id="_user_remove_#{user_name}" class="user_cmd"><' 
                  + 'span>' + str_remove + '<' + '/span><' + '/a><' + '/div>' +
<?
    } // END: if

    if ( !empty($collab['is_member'])) 
    {
?>
        '<' + 'div>' +
        '    <' + 'a href="javascript://contact" id="_contact_#{user_name}" class="user_cmd edit_contact"><' + 'span>' + str_send_email
             + '<' + '/span><' + '/a> ' +
        '<' + '/div>' +
<?  } // END: if

?>
       '<' + '/div>';
         var cu = new ccCollab('<?= $collab_id?>','<?= $collab['is_member']?>','<?= $collab['is_owner']?>');
         cu.updateFiles('<?= $collab_id?>');
<?

    $carr106 = $collab['users'];
    $cc106= count( $carr106);
    $ck106= array_keys( $carr106);
    for( $ci106= 0; $ci106< $cc106; ++$ci106)
    { 
       $_u = $carr106[ $ck106[ $ci106 ] ];
?>
        cu.addUser( '<?= $_u['user_name']?>', '<?= $_u['user_real_name']?>', '<?= $_u['collab_user_role']?>', '<?= $_u['collab_user_credit']?>' );
<?
    } // END: for loop
?>
    function upload_done(upload_id,msg)
    {
      $('upcover').style.display = 'none';
      if( upload_id )
      {
        cu.updateFiles('<?= $collab_id?>');
        cu.msg('<?=$T->String('str_collab_upload_succeeded')?>.','green');
      }
      else
      {
        cu.msg('<?=$T->String('str_collab_upload_failed')?>: ' . msg,'red');
      }
    }
</script>
    <?
} // END: function show_collab


//------------------------------------- 
function _t_collab_show_collab_files($T,&$A)
{
    $carr107 =& $A['uploads'];
    $cc107 = count($carr107);
    $ck107 = array_keys($carr107);
    for( $ci107= 0; $ci107< $cc107; ++$ci107)
    { 
        $up =& $carr107[ $ck107[ $ci107 ] ];
       
        $html =<<<EOF
    <div class="file_line {$up['collab_type']}_line" id="_file_line_{$up['upload_id']}">
    <div class="file_info"><a  class="fname" href="{$up['file_page_url']}">{$up['upload_name']}</a> {$T->String('str_by')}
                   <a  href="{$up['artist_page_url']}">{$up['user_real_name']}</a></div>
EOF;
        print $html;

        if ( !empty($up['is_collab_owner'])) {

    ?><div ><a  href="javascript://remove " id="_remove_<?= $up['upload_id']?>" class="file_cmd file_remove">
    <span ><?=$T->String('str_collab_remove2')?></span></a></div>
    <div ><a  href="javascript://publish" id="_publish_<?= $up['upload_id']?>" class="file_cmd file_publish">
    <span  id="_pubtext_<?= $up['upload_id']?>"><?=

    empty($up['upload_published']) ? $T->String('str_collab_publish') : $T->String('str_collab_hide');

    ?></span></a>
    </div>
    <div ><a  href="javascript://tags" id="_tags_<?= $up['upload_id']?>" class="file_cmd file_tags"><?=$T->String('str_collab_tags')?></a></div>
    <?
    } // END: if

    ?><div  class="ccud"><?= $up['collab_type']?></div>
    <div  class="tags" id="_user_tags_<?= $up['upload_id']?>"><?= $up['upload_extra']['usertags']?></div>
    <br  />
    <table  class="file_dl_table">
    <?

    $carr108 =& $up['files'];
    $cc108= count( $carr108);
    $ck108= array_keys( $carr108);
    for( $ci108= 0; $ci108< $cc108; ++$ci108)
    { 
       $f =& $carr108[ $ck108[ $ci108 ] ];
       ?><tr ><td  class="nic"><?= $f['file_nicname']?></td>
        <td ><a  href="<?= $f['download_url']?>" class="down_button"></a></td>
        <td ><?= $f['file_name']?></td>
        </tr><?
    } // END: for loop

    ?></table>
    </div>
    <?
    } // END: for loop
} // END: function show_collab_files

?>