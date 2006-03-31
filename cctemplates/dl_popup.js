var cc_current_dl_box;
var cc_in_open;

function cc_close_dl_box()
{
    if( cc_in_open )
    {
      cc_in_open = false;
    }
    else if( cc_current_dl_box )
    {
      cc_current_dl_box.style.display = "none";
    }
}

document.body.onclick = cc_close_dl_box;

function cc_show_dl_box(event,upload_id,button)
{
    obj = new cc_obj('dlmenu' + upload_id);

    if( (!obj.style.display) || (obj.style.display == "none") )
    {
        cc_close_dl_box();
       
        x = 0;
        y = 0;
        if (!event) 
          event = window.event;

        if( document.all && obj.obj.offsetLeft )
        {
            coords = cc_get_ie_pos(obj.obj);
            x = coords.left; // + 55;
            y = coords.top; //  + 55;
        }
        else if (event.pageX || event.pageY)
        {
            x = event.pageX + 5;
            y = event.pageY + 5;
        }
        else if (event.clientX || event.clientY)
        {
            x = event.clientX + document.body.scrollLeft + 5;
            y = event.clientY + document.body.scrollTop + 5;
        }

        // IE likes it this way
        obj.style.top  = y;
        obj.style.left = x;
        obj.style.display = "block";
        obj.style.position = "absolute";

        // Moz likes it this way
        val = "position:absolute;display:block;left:" + x + "px;top:" + y + "px;";
        obj.obj.setAttribute('style',val);

        cc_current_dl_box = obj;           
        cc_in_open = true;
    }
    else
    {
       obj.style.display = "none";
    }
    return(false);
}

function cc_get_ie_pos (obj) 
{
  var left = obj.offsetLeft;
  var top  = obj.offsetTop;
  if( left < 0 )
  {
    while ((obj = obj.offsetParent) != null) 
    {
      left += obj.offsetLeft; 
      top  += obj.offsetTop;
    }

    left += 55; // slightly more than half the width
    top += 55;
  }
  var retobj = new Object();
  retobj.left = left;
  retobj.top  = top;
  return(retobj);
}

function cc_star_hover_on(id,star_num)
{
    for( i = 1; i < (star_num+1); i++ )
    {
        var name = 'cc_star_' + id + '_' + i;
        cc_set_img( name, root_url + 'ccimages/stars/star-red.gif' );
    }
    
}

function cc_star_hover_off(id,star_num)
{
    for( i = 1; i < 6; i++ )
    {
        var name = 'cc_star_' + id + '_' + i;
        cc_set_img( name, root_url + 'ccimages/stars/star-empty.gif' );
    }

}

function cc_star_pick(id,star_num)
{
    var hname = "cc_rate_head_" + id;
    obj = new cc_obj(hname);
    obj.style.display = "inline";

    var bname = "cc_star_block_" + id;
    obj = new cc_obj(bname);
    obj.style.display = "none";

    var dname = "cc_rate_block_" + id;
    cc_get_data(home_url + "rate/" + id + "/" + star_num,dname,lang_get_ratings);
}

function cc_set_img(name,img)
{
    obj = new cc_obj( name );
    if( obj.obj.src )
       obj.obj.src = img;
}
