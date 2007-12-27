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

function updateLink()
{
    var args = Form.serialize('channel_form') + '&tags=' + $( 'cvalue_' + currChannel.id ).innerHTML;
    $('streamlink').href = stream_url + args + '&rand=1&format=m3u';
    $('podlink').href    = query_url  + args + '&rand=1&format=rss';

    if( sitePromoTag && (sitePromoTag.length > 0) )
        $('streamlink').href += '&promo_tag=' + sitePromoTag;

    var curl = query_url + args + '&format=count';
    var myAjax = new Ajax.Request( curl, {method: 'get', onComplete: showCount });    
}

function radio_play() 
{ 
    var args = Form.serialize('channel_form') + '&tags=' + $( 'cvalue_' + currChannel.id ).innerHTML;
    if( sitePromoTag && (sitePromoTag.length > 0) )
        args  += '&promo_tag=' + sitePromoTag;
    var url = home_url + 'playlist/popup' + q + args;
    var dim = "height=300,width=550";
    ajax_debug(url);
    var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                  "resizable=1,scrollbars=1," + dim );
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

var currChannel = $('tags00');
Element.classNames(currChannel).add('med_bg');

Event.observe('playlink','click', radio_play, false );

$$('.cbutton').each( function(e) {
      Event.observe(e,'click', function (e)
            {
                if( currChannel )
                    Element.classNames(currChannel).remove('med_bg');
                currChannel = Event.element(e);
                Element.classNames(currChannel).add('med_bg');
                updateLink();
            }, false )
});

Form.getElements('channel_form','SELECT').each( function(e) {
    Event.observe(e,'change', updateLink, false );
    Event.observe(e,'keypress', updateLink, false );
});

updateLink();
