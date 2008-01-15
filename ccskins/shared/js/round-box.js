
rbox_counter = 1;

function cc_round_box( e ) {
    e = $(e);
    if( !e )
        return;
    var id_o = 'rboxo_' + (rbox_counter++);
    var id = 'rbox_' + (rbox_counter++);
    var h2 = e.getElementsByTagName('H2');
    var caption = '';
    if( h2.length > 0 ) {
        h2 = h2[0];
        caption = h2.innerHTML;
        e.removeChild(h2);
    }
    var html = '<div id="' + id_o + '" style="display:none" class="cssbox"><div class="cssbox_head"><h2>' + caption + 
                '</h2></div><div id="' + id + '" class="cssbox_body">  </div></div>';
    new Insertion.Before(e,html);
    var e = Element.remove(e);
    $(id).appendChild(e);
    $(id_o).style.display = 'block';
    Element.removeClassName(e,'box'); // this allows multiple calls to cc_round_box as elements arrive
}

function cc_round_boxes( className ) {
    $$('.box').each( cc_round_box );
}
