<?

function _t_skin_editor_edit_color_schemes($T,&$A) 
{
    $props = $A['field']['props'];
    $fid = $A['field']['name'];
    $value = empty($A['field']['value']) ? $props[0]['id'] : $A['field']['value'];

    ?>
    <input type="hidden" name="<?= $fid ?>" id="<?= $fid ?>" value="<?= $value ?>"/>
    <div style="padding-left: 20px;border: 2px solid #999; overflow: scroll; height: 240px; width: 250px">
    <style>table.ed td { height: 10px; width:20px; border-style:solid; border-width: 1px; }</style>'<?

    foreach( $props as $P )
    {
        $id = $P['id'];
        preg_match_all( '/\.([^\s{]+)[\s{]/U', $P['css'], $m );
        $markup = preg_replace( '/\./', "#ecs_{$id} .", $P['css'] );
        print '<br /><br /><b>' . $T->String($P['caption']) . '</b><br />';
        print "<style>{$markup}</style><table class=\"skin_colors_pick ed\" id=\"ecs_{$id}\">";
        $rows = array_chunk($m[1],7);
        foreach( $rows as $row )
        {
            print '<tr>';
            foreach( $row as $col )
            {
                print "<td class=\"{$col}\">&nbsp;</td>";
            }
            print "</tr>\n";
        }
        print "</table>\n";

        
    }

    $val_id = 'ecs_' . $value;

    ?>
    </div>
    <script type="text/javascript">
        new ccSkinEditor('skin_colors_pick','<?= $fid ?>','<?= $val_id ?>');
    </script>
    <?
    
}

function _t_skin_editor_edit_layouts($T,&$A) 
{
    $props = $A['field']['props'];
    $fid = $A['field']['name'];
    $value = empty($A['field']['value']) ? $props[0]['id'] : $A['field']['value'];
    static $inst = 1;

    ?>
    <input type="hidden" name="<?= $fid ?>" id="<?= $fid ?>" value="<?= $value ?>"/>
    <div style="padding-left: 20px;border: 2px solid #999; overflow: scroll; height: 240px; width: 250px">
    <style>#el td { vertical-align: top; padding:2px;} </style>
    <table>
    <?

    $class = 'skin_layout_pick_' . $inst;

    foreach( $props as $P )
    {
        $id = 'esl_' . $P['id'];
        ?><tr class="<?=$class?>" id="<?=$id?>" ><td><img src="<?= $T->URL($P['img'])?>" /></td>
          <td><?= $T->String($P['caption'])?></td></tr><?
    }

    $val_id = 'esl_' . $value;

    ?>
    </table></div>
    <script type="text/javascript">
        new ccSkinEditor('<?=$class?>','<?= $fid ?>','<?= $val_id ?>');
    </script>
    <?

    ++$inst;
}

?>