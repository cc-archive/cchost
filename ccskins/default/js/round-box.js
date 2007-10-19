
function cc_round_box( e ) {
    _cc_round_box( e, '' );
}

function cc_round_box_bw( e ) {
    _cc_round_box( e, '_bw' );
}

function _cc_round_box( e, color ) {
    e = $(e);
    var h2 = e.getElementsByTagName('H2');
    var caption = '';
    if( h2.length > 0 ) {
        h2 = h2[0];
        caption = h2.innerHTML;
        e.removeChild(h2);
    }
    var html = '<div class="cssbox' + color + '"><div class="cssbox_head' + color + '"><h2>' + caption + 
                '</h2></div><div class="cssbox_body' + color + '">  ' + e.innerHTML + '</div></div>';
    e.innerHTML = html;
}

function cc_round_boxes( className ) {
    $$('.cc_round_box').each( cc_round_box );
    $$('.cc_round_box_bw').each( cc_round_box_bw );
}
