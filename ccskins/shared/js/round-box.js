
function cc_round_box( e ) {
    _cc_round_box( e, '' );
}

function cc_round_box_bw( e ) {
    _cc_round_box( e, '_bw' );
}

rbox_counter = 1;

function _cc_round_box( e, color ) {
    e = $(e);
    var id_o = 'rboxo_' + (rbox_counter++);
    var id = 'rbox_' + (rbox_counter++);
    var h2 = e.getElementsByTagName('H2');
    var caption = '';
    if( h2.length > 0 ) {
        h2 = h2[0];
        caption = h2.innerHTML;
        e.removeChild(h2);
    }
    var html = '<div id="' + id_o + '" style="display:none" class="cssbox' + color + '"><div class="cssbox_head' + color + '"><h2>' + caption + 
                '</h2></div><div id="' + id + '" class="cssbox_body' + color + '">  </div></div>';
    new Insertion.Before(e,html);
    var e = Element.remove(e);
    $(id).appendChild(e);
    $(id_o).style.display = 'block';
}

function cc_round_boxes( className ) {
    $$('.cc_round_box').each( cc_round_box );
    $$('.cc_round_box_bw').each( cc_round_box_bw );
}
