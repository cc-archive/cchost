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

function cc_update_hot_topics()
{
    var box = $('cc_hot_topic_picker');
    if( !box )
        return;
    if( !box.observing )
    {   
        box.observing = true;
        Event.observe(box, 'change', function (e)
            {
                cc_update_hot_topics();
            }
        );

    }
    var date = box.options[ box.selectedIndex ].value;
    var url = home_url + 'reviews/hottopics/' + date;
    var myAjax = new Ajax.Updater( 
                    'cc_hot_topics_div',
                     url, 
                    { method: 'get' });    

}

window.onload = cc_update_hot_topics;
