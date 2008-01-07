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

ccCollab = Class.create();

ccCollab.prototype = {

    collab_id: null,
    autoComp: null,
    userCredit: null,
    userContact: null,
    is_member: false,
    is_owner: false,
    user_template: null,

    initialize: function(collab,is_member,is_owner) {
        this.collab_id = collab;
        this.is_member = is_member;
        this.is_owner = is_owner;
        if( is_member )
        {
            var pickFunk = this.onUserPick.bind(this);
            this.autoComp =  new ccAutoComplete( {  url: home_url + 'browse' + q + 'user_lookup=', onPick: pickFunk } );
            var container = $('invite_container');
            container.innerHTML = this.autoComp.genControls( 'collab_user', '', str_invite_other_artists );
            this.autoComp.hookUpEvents();
            if( $('fileok') )
                Event.observe( 'fileok', 'click', this.onFileSubmitOK.bindAsEventListener(this) );
        }
        this.user_template = this._make_user_template();
    },

    updateFiles: function(collab_id) {
        var url = home_url + 'collab/upload/update/' + this.collab_id ;
        new Ajax.Request( url, { method: 'get', onComplete: this._req_updatefiles.bind(this) } );
    },

    _req_updatefiles: function( resp ) {
        $('file_list').innerHTML = resp.responseText;
        var me = this;
        $$('.file_remove').each( function(a) {
            var id = a.id.match(/[0-9]+$/);
            Event.observe( a, 'click', me.onUploadRemove.bindAsEventListener(me,id) );
        });
        $$('.file_publish').each( function(a) {
            var id = a.id.match(/[0-9]+$/);
            Event.observe( a, 'click', me.onUploadPublish.bindAsEventListener(me,id) );
        });
        $$('.file_tags').each( function(a) {
            var id = a.id.match(/[0-9]+$/);
            Event.observe( a, 'click', me.onUploadTags.bindAsEventListener(me,id) );
        });
    },

    onFileSubmitOK: function( e ) {
        Position.clone( $('upform') , $('upcover'));
        $('upcover').style.display = 'block';
        $('upform').submit();            
    },

    onUploadPublish: function( e, id ) {
        this.msg( str_thinking, str_working ); 
        var url = home_url + 'collab/upload/' + this.collab_id + '/' + id + '/publish';
        new Ajax.Request( url, { method: 'get', onComplete: this._req_publishupload.bind(this) } );
    },

    _req_publishupload: function( resp, json ) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            $('_pubtext_' + json.upload_id).innerHTML = json.published ? str_hide : str_publish;
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },


    onUploadRemove: function( e, id ) {
        this.msg( str_thinking, str_working ); 
        var url = home_url + 'collab/upload/' + this.collab_id + '/' + id + '/remove';
        new Ajax.Request( url, { method: 'get', onComplete: this._req_removeupload.bind(this) } );
    },

    _req_removeupload: function( resp, json ) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            $('_file_line_' + json.upload_id).remove();
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    onUploadTags: function(e, id) {
        this.closeContact();
        this.closeCredit();
        if( this.uploadTags && (this.uploadTags != id) )
            this.closeTags();
        var file_line = $("_file_line_" + id);
        this.uploadTags = id;
        var tags = $('_user_tags_' + id ).innerHTML;
        var html = '<div id="tags_editor" style="position:absolute;background:white;padding: 10px;">'+str_tags_label+
                      ': <input type="text" id="tags_edit" value="' + tags +
                     '" /> <a href="javascript://ok tags" id="ok_tags">'+str_ok+'</a> ' +
                     '<a href="javascript://ok edit" id="cancel_tags">'+str_cancel+'</a></div>';
        new Insertion.Before(file_line,html);
        Position.clone( file_line, $('tags_editor'), { setHeight: false } );
        //file_line.style.display = 'none';
        this.okTagsWatcher = this.onTagsOk.bindAsEventListener(this,id);
        this.cancelTagsWatcher = this.onTagsCancel.bindAsEventListener(this,id);
        Event.observe( 'ok_tags',     'click', this.okTagsWatcher );
        Event.observe( 'cancel_tags', 'click', this.cancelTagsWatcher );
    },

    onUserCredit: function(e, user_name) {
        this.closeContact();
        this.closeTags();
        if( this.userCredit && (this.userCredit != user_name) )
            this.closeCredit();
        var user_line = $("_user_line_" + user_name);
        var credit = $("_credit_" + user_name);
        this.userCredit = user_name;
        this.userCreditValue = credit.innerHTML;
        var text = str_enter_role.replace('/%s/',user_name);
        var html = '<div id="credit_editor">'+text+': <input type="text" id="credit_edit" value="' + this.userCreditValue +
                     '" /> <a href="javascript://ok edit" id="ok_edit">'+str_ok+'</a> ' +
                     '<a href="javascript://ok edit" id="cancel_edit">'+str_cancel+'</a></div>';
        new Insertion.Before(user_line,html);
        user_line.style.display = 'none';
        this.okWatcher = this.onCreditOk.bindAsEventListener(this,user_name);
        this.cancelWatcher = this.onCreditCancel.bindAsEventListener(this,user_name);
        Event.observe( 'ok_edit',     'click', this.okWatcher );
        Event.observe( 'cancel_edit', 'click', this.cancelWatcher );
    },

    onUserContact: function(e, user_name) {
        this.closeTags();
        this.closeCredit();
        if( this.userContact && (this.userContact != user_name) )
            this.closeContact();
        var user_line = $("_user_line_" + user_name);
        var credit = $("_credit_" + user_name);
        this.userContact = user_name;
        text = str_send_mail_to.replace('/%s/',user_name);
        var html = '<div id="contact_editor">'+text+':<br /><textarea style="width:60%;height:35px;" id="contact_edit"></textarea>' +
                     '<a href="javascript://contact ok" id="ok_contact">'+str_ok+'</a> ' +
                     '<a href="javascript://contact cancel" id="cancel_contact">'+str_cancel+'</a></div>';
        new Insertion.Before(user_line,html);
        user_line.style.display = 'none';
        this.okContactWatcher     = this.onContactOk.bindAsEventListener(this,user_name);
        this.cancelContactWatcher = this.onContactCancel.bindAsEventListener(this,user_name);
        Event.observe( 'ok_contact',     'click', this.okContactWatcher );
        Event.observe( 'cancel_contact', 'click', this.cancelContactWatcher );
    },

    closeTags: function() {
        if( this.uploadTags ) {
            Event.stopObserving( 'ok_tags',     'click', this.okTagsWatcher );
            Event.stopObserving( 'cancel_tags', 'click', this.cancelTagsWatcher );
            $('tags_editor').remove();
            $("_file_line_" + this.uploadTags).style.display = 'block';
            this.uploadTags = null;
        }
    },

    closeContact: function() {
        if( this.userContact ) {
            Event.stopObserving( 'ok_contact',     'click', this.okContactWatcher );
            Event.stopObserving( 'cancel_contact', 'click', this.cancelContactWatcher );
            $('contact_editor').remove();
            $("_user_line_" + this.userContact).style.display = 'block';
            this.userContact = null;
        }
    },

    closeCredit: function() {
        if( this.userCredit ) {
            Event.stopObserving( 'ok_edit',     'click', this.okWatcher );
            Event.stopObserving( 'cancel_edit', 'click', this.cancelWatcher );
            $('credit_editor').remove();
            $("_user_line_" + this.userCredit).style.display = 'block';
            this.userCredit = null;
        }
    },

    onTagsOk: function(e, id) {
        this.msg( 'thinking...', 'working' );
        var value = $('tags_edit').value;
        var url = home_url + 'collab/upload/tags/' + this.collab_id + '/' + id + '?tags=' + value;
        ajax_debug(url);
        new Ajax.Request( url, { method: 'get', onComplete: this._req_tagsupload.bind(this) } );
        this.closeTags();
    },


    onCreditOk: function(e, user_name) {
        this.msg( 'thinking...', 'working' );
        var value = $('credit_edit').value;
        var url = home_url + 'collab/user/' + this.collab_id + '/' + user_name + '/credit?credit=' + value;
        new Ajax.Request( url, { method: 'get', onComplete: this._req_credituser.bind(this) } );
        this.closeCredit();
    },

    onContactOk: function(e, user_name) {
        this.msg( 'thinking...', 'working' );
        var url = home_url + 'collab/user/' + this.collab_id + '/' + user_name + '/contact';
        new Ajax.Request( url, { method: 'post', 
                                 parameters: 'text=' + $('contact_edit').value,
                                 onComplete: this._req_contactuser.bind(this) } );
        this.closeContact();
    },

    _req_contactuser: function(resp,json) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    _req_credituser: function(resp,json) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            $("_credit_" + json.user_name).innerHTML = json.credit;
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    _req_tagsupload: function(resp,json) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            if( json.msg )
                this.msg( json.msg, 'msg' );
            $("_user_tags_" + json.upload_id).innerHTML = json.user_tags;
        }
    },

    onCreditCancel: function(e, user_name) {
        this.closeCredit();
    },

    onContactCancel: function(e, user_name) {
        this.closeContact();
    },

    onTagsCancel: function(e, user_name) {
        this.closeTags();
    },

    onUserConfirm: function( e, user_name ) {
        this.msg( str_thinking, str_working );
        var url = home_url + 'collab/user/' + this.collab_id + '/' + user_name + '/confirm';
        new Ajax.Request( url, { method: 'get', onComplete: this._req_confirmuser.bind(this) } );
    },

    _req_confirmuser: function( resp, json ) {
        try
        {
            if( json.error )
            {
                this.msg( json.error, 'error' );
            }
            else
            {
                $('confirm_link').remove();
                $('_confirm_label_' + json.user_name).innerHTML = str_collab_confirmed;
                if( json.msg )
                    this.msg( json.msg, 'msg' );
            }
        }
        catch(e)
        {
            alert(e);
        }
    },


    onUserRemove: function( e, user_name ) {
        this.msg( str_thinking, str_working );
        var url = home_url + 'collab/user/' + this.collab_id + '/' + user_name + '/remove';
        new Ajax.Request( url, { method: 'get', onComplete: this._req_removeuser.bind(this) } );
    },

    _req_removeuser: function( resp, json ) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            this.closeCredit();
            this.closeContact();
            $('_user_line_' + json.user_name).remove();
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    onUserPick: function( ac, elem, value ) {
        var url = home_url + 'collab/user/' + this.collab_id + '/' + value + '/add';
        new Ajax.Request( url, { method: 'get', onComplete: this._req_adduser.bind(this) } );
        return true;
    },

    msg: function( text, type ) {
        ajax_msg(type,text);
    },
 
    _req_adduser: function( resp, json ) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            this.addUser( json.user_name, json.user_real_name, 'member', '', 0 );
            $(this.autoComp.options.editID).value = '';
            this.autoComp._list_close(); 
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    _make_user_template: function()
    {
        var collab_template = 
            '<div class="user_line" id="_user_line_#{user_name}">' +
            '<div class="user" ><a href="'+home_url+'people/#{user_name}">#{user_real_name}</a></div>' +
            '<div class="role">#{role} (<span id="_confirm_label_#{user_name}">#{confirmed}</span>)</div>' +
            '<div class="credit" id="_credit_#{user_name}">#{credit}</div>';
        if( this.is_owner )
        {
            collab_template +=
                '<div><a href="javascript://edit credit" id="_user_credit_#{user_name}" class="user_cmd edit_credit"><' 
                      + 'span>'+str_credit+'</span></a></div>' +
                '<div><a href="javascript://remove user" id="_user_remove_#{user_name}" class="user_cmd"><' 
                      + 'span>'+str_remove+'</span></a></div>';
        }
        if( this.is_member )
        {
            collab_template +=
                '<div>' +
                '    <a href="javascript://contact" id="_contact_#{user_name}" class="user_cmd edit_contact"><span>' 
                + str_send_email + '</span></a> ' +
                '</div>';
        }
        
        return collab_template + '#{confirm_link}</div>';
    },

    addUser: function( username, fullname, userrole, usercredit, userconfirmed, itsme ) {
        try
        {
            
            var confirmlink = '';
            
            if( itsme && !userconfirmed )
            {
                confirmlink = 
                '<div id="confirm_link">' +
                        '<a href="javascript://confirm" id="_confirm_'+user_name+'" class="user_cmd confirm_user"><span>' +
                         str_confirm_membership + '</span></a> ' +
                        '</div>';
            }
            var vars = {
                    user_name: username,
                    user_real_name: fullname,
                    role: userrole,
                    credit: usercredit,
                    confirmed: userconfirmed ? str_collab_confirmed : str_collab_not_confirmed,
                    confirm_link: confirmlink
                };
            
            var html = new Template( this.user_template ).evaluate( vars );
            new Insertion.Before( 'user_inserter', html );

            if( $('_user_remove_' + username) )
                Event.observe( '_user_remove_' + username, 'click', this.onUserRemove.bindAsEventListener(this,username) );
            if( $('_user_credit_' + username) )
                Event.observe( '_user_credit_' + username, 'click', this.onUserCredit.bindAsEventListener(this,username) );
            if( $('_contact_' + username) )
                Event.observe( '_contact_'     + username, 'click', this.onUserContact.bindAsEventListener(this,username) );
            if( $('_confirm_' + username) )
                Event.observe( '_confirm_'     + username, 'click', this.onUserConfirm.bindAsEventListener(this,username) );
        }
        catch (e)
        {
            alert(e);
        }
    }
}
