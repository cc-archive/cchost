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

function cc_trim(str,c)
{
	while (str.substring(0,1) == c)
	    str = str.substring(1, str.length);
	while (str.substring(str.length-1, str.length) == c)
	    str = str.substring(0,str.length-1);
	return str;
}

function cc_get_sel(obj)
{
	if( document.selection )
	    return ( document.selection.createRange() );

	var selLength = obj.textLength;
	var selStart = obj.selectionStart;
	var selEnd = obj.selectionEnd;
	if (selEnd == 1 || selEnd == 2)
	    selEnd = selLength;

	return ((obj.value).substring(selStart, selEnd));
}


function cc_apply_url(fname)
{
	var obj = new cc_obj(fname);
	var url = cc_get_sel(obj.obj);
	_cc_apply_format(obj, '"' + url + '":', url );
}

function cc_apply_format(fname,tag)
{
	var obj = new cc_obj(fname);
	var open;
	if( tag == 'url' )
	{
		var url = prompt('URL:','http://');
		if( url )
			open = '[url=' + url + ']';
	}

	if( !open )
		open = '[' + tag + ']';

	var close = '[/' + tag + ']';

	_cc_apply_format(obj,open,close);
}

function _cc_apply_tag(text,open,close)
{
	var arr = text.split("\n");
	var len = arr.length;

	var trimmed = cc_trim(text,' ');
	var R;
	if( trimmed != text )
	{
		var rstr = '^(\\s+)?' + trimmed + '(\\s+)?$';
		R = new RegExp(rstr);
		text = text.replace(R,"$1" + open + trimmed + close + "$2");
	}
	else
	{
		text = open + text + close 
	}

	return(text);
}


function _cc_apply_format(obj,open,close)
{
	obj.obj.focus();
	if( document.selection )
	{
	    var sel = document.selection.createRange();
		var text = _cc_apply_tag(sel.text,open,close);
		sel.text = text;
	}
	else
	{
		obj = obj.obj;
		var selLength = obj.textLength;
		var selStart = obj.selectionStart;
		var selEnd = obj.selectionEnd;
		if (selEnd == 1 || selEnd == 2)
			selEnd = selLength;

		var s1 = (obj.value).substring(0,selStart);
		var s2 = (obj.value).substring(selStart, selEnd)
		var s3 = (obj.value).substring(selEnd, selLength);

		var text = _cc_apply_tag(s2,open,close);

		obj.value = s1 + text + s3;
		var cursorLoc = selEnd + open.length + close.length;
		obj.selectionStart = cursorLoc;
		obj.selectionEnd = cursorLoc;
	}
}

function cc_format_preview(fname)
{
	var obj = new cc_obj(fname);
	var txt = obj.obj.value;
	txt = escape(txt);
	var pbox = new cc_obj('format_preview_' + fname);
	pbox.style.display = 'block';
	obj.obj.focus();
	cc_get_data(home_url + 'format/preview?ptext=' + txt, 'format_inner_preview_' + fname,'getting preview...')
}

function cc_hide_preview(fname)
{
	var pbox = new cc_obj('format_preview_' + fname);
	pbox.style.display = 'none';
}
