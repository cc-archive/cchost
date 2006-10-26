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

function updateGroup(gid)
{
    if( prevGroup )
    {
        pc = $('acc_knob_' + prevGroup);
        Element.classNames(pc).remove('acc_knob_selected');
        pc = $('acc_group_' + prevGroup);
        Element.classNames(pc).remove('acc_group_selected');
    }
    pc = $('acc_knob_' + gid);
    Element.classNames(pc).add('acc_knob_selected');
    pc = $('acc_group_' + gid);
    Element.classNames(pc).add('acc_group_selected');
    prevGroup = gid;
}

var prevGroup;


$$('.acc_knob').each( function(e) {
      e.gid = e.id.match(/[0-9]+/);
      Event.observe(e,'click', function (e)
            {
                var e = Event.element(e);
                updateGroup(e.gid);
            }, false )
});


updateGroup('0');
