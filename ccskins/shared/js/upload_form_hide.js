/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/


function cc_hide_form_and_submit()
{
  if( navigator.userAgent.indexOf('Safari') > -1 )
  {
    var submit_button = new cc_obj('form_submit');
    if( submit_button )
    {
      submit_button.obj.value = "Submission in process, please wait...";
    }
  }
  else
  {
    cc_show_hide_form(form_id,'none','block');
  }
  return(true);
}

function cc_show_hide_form(form_id,form_display,msg_display)
{
    var the_form;
    var message_div;
    the_form = new cc_obj(form_id);
    the_form.style.display = form_display;
    message_div = new cc_obj('cc_form_submit_message');
    message_div.style.display = msg_display;
}

function cc_init_form_capture()
{
  var submit_button = new cc_obj('form_submit');
  if( submit_button )
    submit_button.obj.onclick = cc_hide_form_and_submit;
}

cc_init_form_capture();
