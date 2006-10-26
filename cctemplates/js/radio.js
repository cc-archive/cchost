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


function getURL()
{
    return (baseCmd + '?' + Form.serialize('channel_form') + '&tags=' + 
                $( 'cvalue_' + prevChannel.id ).innerHTML);
}

function updateLink()
{
    var url = getURL() + '&rand=1';
    $('streamlink').href = url + '&format=m3u';
    $('podlink').href = url + '&format=rss';

    if( sitePromoTag && (sitePromoTag.length > 0) )
        $('streamlink').href += '&promo_tag=' + sitePromoTag;

    var myAjax = new Ajax.Request( url + '&format=count', {
				method: 'get', onComplete: showCount });    
}


function showCount(obj)
{
    var rcount = eval(obj.responseText)[0];

    if( rcount < 1 )
    {
        Element.hide('gobuttons');
        $('countresults').innerHTML = 'Sorry, no remixes match';
    }
    else
    {
        Element.show('gobuttons');
        $('countresults').innerHTML = rcount + ' remixes match';
    }
}

var prevChannel = $('tags00');
Element.classNames(prevChannel).add('cbutton_selected');

$$('.cbutton').each( function(e) {
      Event.observe(e,'click', function (e)
            {
                if( prevChannel )
                    Element.classNames(prevChannel).remove('cbutton_selected');
                prevChannel = Event.element(e);
                Element.classNames(prevChannel).add('cbutton_selected');
                updateLink();
            }, false )
});

Form.getElements('channel_form','SELECT').each( function(e) {
    Event.observe(e,'change', updateLink, false );
    Event.observe(e,'keypress', updateLink, false );
});

updateLink();
