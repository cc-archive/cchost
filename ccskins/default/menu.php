<div id="menu">
<? global $_TV; ?>

<?  foreach( $_TV['menu_groups'] as $group ) { ?>
<div class="menu_group">
    <p><?= $group['group_name'] ?></p>
    <ul>
        <? foreach( $group['menu_items'] as $mi ) { ?>
            <li><a href="<?= $mi['action'] ?>" id="<?= cc_not_empty($mi['id']); ?>"> 
               <?= $mi['menu_text'] ?></a>
            </li>
        <? } ?>
    </ul>
</div>
<? } ?>

<? if( !empty($_TV['custom_macros']) ) { 
    foreach( $_TV['custom_macros'] as $macro ) { ?>
        <div class="menu_group">
            <? _template_call_template($macro); ?>
        </div>
<?  } 
   } ?>
</div>