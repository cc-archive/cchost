/*
Creative Commons has made the contents of this file
available under a CC-GNU-GPL license:

 http://creativecommons.org/licenses/GPL/2.0/

 A copy of the full license can be found as part of this
 distribution in the file LICENSE.TXT

You may use the ccHost software in accordance with the
terms of that license. You agree that you are solely 
responsible for your use of the ccHost software and you
represent and warrant to Creative Commons that your use
of the ccHost software will comply with the CC-GNU-GPL.

$Id$
*/


function flip_link_type(box)
{
  var text = '';
  switch( box.selectedIndex )
  {
    case 1:
      text = '<' + 'input type="hidden" name="remixesof" value="' + username + '" />';
      break;
    case 0:
      text = '<' + 'input type="hidden" name="tags" value="remix" />';
      // fall thru:
    case 2:
      text += '<' + 'input type="hidden" name="user" value="' + username + '" />';
      break;
   }

   $('type_target').innerHTML = text;

   update_target();
}

function update_target()
{
   var url = home_url + 'api/query' + q + Form.serialize('puboptions_form');
   var text = '<' + 'script type="text/javascript" src="' + url + '&format=docwrite" ><' + '/script>';
   $('target_text').innerHTML = text;
   var myAjax = new Ajax.Request( 
     url + '&format=html', 
    { method: 'get', 
      onComplete: function(req) {
        $('preview').innerHTML = req.responseText;
        $('src_preview').innerHTML = req.responseText.escapeHTML();
        }
        
    } );    
}

is_preview = true;

function toggle_preview()
{
   is_preview = !is_preview;
   Element.show($( is_preview ? 'preview' : 'src_preview' ));
   Element.hide($( is_preview ? 'src_preview' : 'preview' ));
   Element.show($( is_preview ? 'preview_warn' : 'html_warn' ));
   Element.hide($( is_preview ? 'html_warn' : 'preview_warn' ));
   $('preview_button').innerHTML = is_preview ? seeHTML : showFormatted;
}

flip_link_type($('ty'));
