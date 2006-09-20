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
	if (document.getElementById)
	{
	  this.obj = document.getElementById(name);
	  if( !this.obj )
		alert("no obj: " + name);
	  this.style = document.getElementById(name).style;
	}
	else if (document.all)
	{
	  this.obj = document.all[name];
	  this.style = document.all[name].style;
	}
	else if (document.layers)
	{
	  this.obj = document.layers[name];
	  this.style = document.layers[name];
	}
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
    if (window.XMLHttpRequest)
    { 
        cc_xml = new XMLHttpRequest();            //set the request
    }
    else if(window.ActiveXObject)
    { 
        //Create our RequestObject
        cc_xml = new ActiveXObject("Microsoft.XMLHTTP"); 
    }

    if( cc_xml )
    {
        cc_xml.open("GET", url, true);            //set the page to request
        cc_xml.send(null);                        //initialize the request 
    }
}

//Create our function to start the request
function cc_get_data(url,element_name,default_msg)
{
    //inform there is an action!
    iobj = new cc_obj(element_name);
    iobj.obj.innerHTML = default_msg;

    cc_target = element_name;

    //determine if the browser is Moz, FF, NN, Op
    if (window.XMLHttpRequest)
    { 
        cc_xml = new XMLHttpRequest();            //set the request
        cc_xml.onreadystatechange = cc_response;  //function to call on each set
        cc_xml.open("GET", url, true);            //set the page to request
        cc_xml.send(null);                        //initialize the request 
    }
    // IE
    else if(window.ActiveXObject)
    { 
        //Create our RequestObject
        cc_xml = new ActiveXObject("Microsoft.XMLHTTP"); 
        if(cc_xml)
        { 
            cc_xml.onreadystatechange = cc_response;  //function to call on each step
            cc_xml.open("GET", url, true);            //set the page to request
            cc_xml.send(null);                        //initialize the request
        }
    }
    else
    {   // er, this'll be lovely
        document.write("<iframe src=\"" + url + "\" style=\"border:0px;width:80%;\"></iframe>");
    }       
}
          
function cc_response()
{
    //Look to see if the request is in the 4th stage (complete)         
    if(cc_xml.readyState == 4)
    {
        //Make sure that we get a sucess page status   
        target = new cc_obj(cc_target);

        if(cc_xml.status == 200)
        {
            target.obj.innerHTML = cc_xml.responseText;
        }
        else
        {
            target.obj.innerHTML = "err, no - Status: " + cc_xml.status;
        }
    }
}
