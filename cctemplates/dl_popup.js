/* cc javascript */


var cc_current_dl_box;
var cc_in_open;

function cc_close_dl_box()
{
    if( cc_in_open )
      cc_in_open = false;
    else if( cc_current_dl_box )
      cc_current_dl_box.style.display = "none";
}

function cc_dl_winclick()
{
    cc_close_dl_box();
}

Event.observe( document.body, 'click', cc_dl_winclick );

function cc_show_dl_box(event,upload_id,button)
{
    obj = $('dlmenu' + upload_id);

    if( (!obj.style.display) || (obj.style.display == "none") )
    {
        if( typeof button == 'undefined' )
            button = Event.element(event); // hope this works...

        cc_close_dl_box();

        Position.clone( button, obj, {  setWidth:   false,
                                        setHeight:  false,
                                        offsetTop:  20,
                                        offsetLeft: 20 } );
        Element.show(obj);

        cc_current_dl_box = obj;           
        cc_in_open = true;
    }
    else
    {
       obj.style.display = "none";
    }
    return(false);
}


function cc_star_hover_on(id,star_num)
{
    for( i = 1; i < (star_num+1); i++ )
    {
        var name = 'cc_star_' + id + '_' + i;
        $( name ).src = root_url + 'ccimages/stars/star-red.gif';
    }
    
}

function cc_star_hover_off(id,star_num)
{
    for( i = 1; i < 6; i++ )
    {
        var name = 'cc_star_' + id + '_' + i;
        $( name ).src = root_url + 'ccimages/stars/star-empty.gif';
    }

}

function cc_star_pick(id,star_num)
{
    var hname = "cc_rate_head_" + id;
    $(hname).style.display = "inline";

    var bname = "cc_star_block_" + id;
    $(bname).style.display = "none";

    var dname = "cc_rate_block_" + id;
    if( typeof lang_get_ratings == 'undefined' )
        lang_get_ratings = '...';
    cc_get_data(home_url + "rate/" + id + "/" + star_num,dname,lang_get_ratings);
}

