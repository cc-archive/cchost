<?

function _t_skin_editor_edit_font_schemes($T,&$A) 
{
    $props = $A['field']['props'];
    $fid = $A['field']['name'];
    $value = empty($A['field']['value']) ? $props[0]['id'] : $A['field']['value'];
    $scroll = empty($A['field']['scroll']) ? '' : 'overflow: scroll; height: 240px;';
    static $inst = 1;

    $class = 'skin_font_pick' . $inst;


    ?>
    <input type="hidden" name="<?= $fid ?>" id="<?= $fid ?>" value="<?= $value ?>"/>
    <div style="padding-left: 20px;border: 2px solid #999; width: 250px; <?=$scroll?>">
    <table><?

    foreach( $props as $P )
    {
        $id = $P['id'];
        preg_match_all( '/{([^}]+)}/Ums', $P['css'], $m );
        $caption = $T->String($P['caption']);
        print "<tr class=\"{$class} ed\" id=\"efs_{$id}\">";
        foreach( $m[1] as $style )
        {
            print "<td style=\"{$style}\">{$caption}</td>";
        }
        print "</tr>\n";
    }

    print '</table></div>';

    $val_id = 'efs_' . $value;

    ?>
    </div>
    <script type="text/javascript">
        new ccSkinEditor('<?=$class?>','<?= $fid ?>','<?= $val_id ?>');
    </script>
    <?
    
}

function _t_skin_editor_edit_color_schemes($T,&$A) 
{
    $props = $A['field']['props'];
    $fid = $A['field']['name'];
    $value = empty($A['field']['value']) ? $props[0]['id'] : $A['field']['value'];
    $scroll = empty($A['field']['scroll']) ? '' : 'overflow: scroll; height: 240px;';
    static $inst = 1;

    $class = 'skin_colors_pick' . $inst;


    ?>
    <input type="hidden" name="<?= $fid ?>" id="<?= $fid ?>" value="<?= $value ?>"/>
    <div style="padding-left: 20px;border: 2px solid #999; width: 250px; <?=$scroll?>">
    <style>table.ed td { height: 10px; width:20px; border-style:solid; border-width: 1px; }</style><?

    foreach( $props as $P )
    {
        $id = $P['id'];
        preg_match_all( '/\.([^\s{]+)[\s{]/U', $P['css'], $m );
        $markup = preg_replace( '/\./', "#ecs_{$id} .", $P['css'] );
        print '<br /><b>' . $T->String($P['caption']) . '</b><br />';
        print "<style>{$markup}</style><table class=\"{$class} ed\" id=\"ecs_{$id}\">";
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
        print '</table>';
    }

    $val_id = 'ecs_' . $value;

    ?>
    </div>
    <script type="text/javascript">
        new ccSkinEditor('<?=$class?>','<?= $fid ?>','<?= $val_id ?>');
    </script>
    <?
    
}

function _t_skin_editor_edit_layouts($T,&$A) 
{
    $props = $A['field']['props'];
    $fid = $A['field']['name'];
    $value = empty($A['field']['value']) ? $props[0]['id'] : $A['field']['value'];
    $scroll = empty($A['field']['scroll']) ? '' : 'overflow: scroll; height: 240px;';
    static $inst = 1;

    ?>
    <input type="hidden" name="<?= $fid ?>" id="<?= $fid ?>" value="<?= $value ?>"/>
    <div style="padding-left: 20px;border: 2px solid #999; width: 250px; <?=$scroll?>">
    <style>#el td { vertical-align: top; padding:2px;} </style>
    <table>
    <?

    $class = 'skin_layout_pick_' . $inst;

    foreach( $props as $P )
    {
        $id = 'esl_' . $P['id'];
        if( empty($P['img']) )
        {
            ?><tr class="<?=$class?>" id="<?=$id?>" ><td></td><td><?= $T->String($P['caption'])?></td></tr><?
        }
        else
        {
            ?><tr class="<?=$class?>" id="<?=$id?>" ><td><img src="<?= $T->URL($P['img'])?>" /></td>
              <td><?= $T->String($P['caption'])?></td></tr><?
        }

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