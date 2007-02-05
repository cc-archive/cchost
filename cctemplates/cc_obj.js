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

var cc_xml;
var cc_target;

function cc_obj(name)
{
    var obj = $(name);
    obj.obj =  obj; // tada!
    return obj;
}

function cc_set_text(obj,text)
{
	if( obj.innerText )
	   obj.innerText = text;
	else if( obj.childNodes && obj.childNodes.length )
	   obj.childNodes[0].nodeValue = text;
	else
	   obj.value = text;
}


//just ping ccHost code
function cc_ping_cchost(url)
{
    new Ajax.Request( url, { method: 'get' } );
}

//Create our function to start the request
function cc_get_data(url,element_name,default_msg)
{
    $(element_name).innerHTML = default_msg;
    new Ajax.Updater ( element_name, url, { method: 'get' } );
}

function cc_add_tag(tag,fieldname)
{
  f = new cc_obj(fieldname);  
  tagstr = f.obj.value;
  tags = tagstr.split(',');
  tags.push(tag);
  tagstr = tags.join(',');
  if( tagstr.charAt(0) == ',' )
    tagstr = tagstr.substr(1);
  f.obj.value = tagstr;
}
