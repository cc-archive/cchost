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


function cc_grow_textarea(elemname)
{
  var elemobj;
  var elemlink;
  elemobj = new cc_obj(elemname);
  if( elemobj )
  {
      elemlink = new cc_obj('grow_' + elemname);
      if( elemobj.style.height == '100px' )
      {
          cc_set_text(elemlink.obj,'[ - ]');
          elemobj.style.height = '300px';
          elemobj.style.width = '450px';
      }
      else
      {
          cc_set_text(elemlink.obj,'[ + ]');
          elemobj.style.height = '100px';
          elemobj.style.width = '300px';
      }
      elemobj.obj.focus();
  }
}
