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

ccPublicize = Class.create();

ccPublicize.prototype = {

    is_preview: true,
    username: '',

    initialize: function(username) {

        this.username = username;
        var me = this;

        $$('.queryparam').each( function(e) {
                Event.observe( e, 'click', me.updateTarget.bindAsEventListener(me) );
        });

        if( $('usertypechanger') )
        {
            Event.observe( 'usertypechanger', 'click', me.updateUserType.bindAsEventListener(me) );
            this.updateUserType();
        }

        if( $('preview_button_link') )
            Event.observe( 'preview_button_link', 'click', me.togglePreview.bindAsEventListener(me) );
    },

    updateTarget: function(){

       var url = query_url + Form.serialize('puboptions_form');
       var text = '<' + 'script type="text/javascript" src="' + url + '&format=docwrite" ><' + '/script>';
       $('target_text').value = text;
       //if( tt.value )
           //tt.value = text;
       //else
       //    tt.innerHTML = text;

       new Ajax.Request( url + '&format=html', { method: 'get', onComplete: this.resp_updateTarget } );    
    },

    resp_updateTarget: function(req) {

       var prev = $('preview');
       if( prev.innerHTML )
            prev.innerHTML = req.responseText;
       else if( prev.innerText )
            prev.innerText = req.responseText;
       else
           alert('wups');
        $('src_preview').innerHTML = req.responseText.escapeHTML();
    },

    updateUserType: function() {

        var box = $('usertypechanger');
        var text = '';
        switch( box.selectedIndex )
        {
            case 1:
                text = '<' + 'input type="hidden" name="remixesof" value="' + this.username + '" />';
                break;
            case 0:
                text = '<' + 'input type="hidden" name="tags" value="remix" />';
                // fall thru:
            case 2:
                text += '<' + 'input type="hidden" name="user" value="' + this.username + '" />';
                break;
        }

        $('type_target').innerHTML = text;

        this.updateTarget();
    },

    togglePreview: function() {

        this.is_preview = !this.is_preview;
        Element.show($( this.is_preview ? 'preview' : 'src_preview' ));
        Element.hide($( this.is_preview ? 'src_preview' : 'preview' ));
        Element.show($( this.is_preview ? 'preview_warn' : 'html_warn' ));
        Element.hide($( this.is_preview ? 'html_warn' : 'preview_warn' ));
        $('preview_button').innerHTML = this.is_preview ? seeHTML : showFormatted;
    }
}

