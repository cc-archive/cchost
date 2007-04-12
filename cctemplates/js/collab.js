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

    initialize: function(collab,is_member,is_owner) {
        this.collab_id = collab;
        if( is_member )
        {
            var pickFunk = this.onUserPick.bind(this);
            this.autoComp =  new ccAutoComplete( {  url: home_url + 'browse' + q + 'user_lookup=', onPick: pickFunk } );
            var container = $('invite_container');
            container.innerHTML = this.autoComp.genControls( 'collab_user', '', 'invite other artists' );
            this.autoComp.hookUpEvents();
            var me = this;
            $$('.file_remove').each( function(a) {
                var id = a.id.match(/[0-9]+$/);
                Event.observe( a, 'click', me.onUploadRemove.bindAsEventListener(me,id) );
            });
            $$('.file_publish').each( function(a) {
                var id = a.id.match(/[0-9]+$/);
                Event.observe( a, 'click', me.onUploadPublish.bindAsEventListener(me,id) );
            });
        }
    },

    onUploadPublish: function( e, id ) {
        this.msg( 'thinking...', 'working' );
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
            $('_pubtext_' + json.upload_id).innerHTML = json.published ? 'hide' : 'publish';
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },


    onUploadRemove: function( e, id ) {
        this.msg( 'thinking...', 'working' );
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

    onUserCredit: function(e, user_name) {
        this.closeContact();
        if( this.userCredit && (this.userCredit != user_name) )
            this.closeCredit();
        var user_line = $("_user_line_" + user_name);
        var credit = $("_credit_" + user_name);
        this.userCredit = user_name;
        this.userCreditValue = credit.innerHTML;
        var html = '<div id="credit_editor">Enter '+user_name+'\'s role (e.g. bass, producer, vocals): <input type="text" id="credit_edit" value="' + this.userCreditValue +
                     '" /> <a href="javascript://ok edit" id="ok_edit">ok</a> ' +
                     '<a href="javascript://ok edit" id="cancel_edit">cancel</a></div>';
        new Insertion.Before(user_line,html);
        user_line.style.display = 'none';
        this.okWatcher = this.onCreditOk.bindAsEventListener(this,user_name);
        this.cancelWatcher = this.onCreditCancel.bindAsEventListener(this,user_name);
        Event.observe( 'ok_edit',     'click', this.okWatcher );
        Event.observe( 'cancel_edit', 'click', this.cancelWatcher );
    },

    onUserContact: function(e, user_name) {
        this.closeContact();
        if( this.userContact && (this.userContact != user_name) )
            this.closeContact();
        var user_line = $("_user_line_" + user_name);
        var credit = $("_credit_" + user_name);
        this.userContact = user_name;
        var html = '<div id="contact_editor">Send mail to '+user_name+':<br /><textarea style="width:60%;height:35px;" id="contact_edit"></textarea>' +
                     '<a href="javascript://contact ok" id="ok_contact">ok</a> ' +
                     '<a href="javascript://contact cancel" id="cancel_contact">cancel</a></div>';
        new Insertion.Before(user_line,html);
        user_line.style.display = 'none';
        this.okContactWatcher     = this.onContactOk.bindAsEventListener(this,user_name);
        this.cancelContactWatcher = this.onContactCancel.bindAsEventListener(this,user_name);
        Event.observe( 'ok_contact',     'click', this.okContactWatcher );
        Event.observe( 'cancel_contact', 'click', this.cancelContactWatcher );
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

    onCreditCancel: function(e, user_name) {
        this.closeCredit();
    },

    onContactCancel: function(e, user_name) {
        this.closeContact();
    },

    onUserRemove: function( e, user_name ) {
        this.msg( 'thinking...', 'working' );
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
        $('collab_ajax_msg').innerHTML = '<div id="amsg" class="ajaxmsg_' + type + '">' + text + '</div>';
        new ccDelayAndFade( 0, $('amsg'), 1.0, 250, 5 );
    },
 
    _req_adduser: function( resp, json ) {
        if( json.error )
        {
            this.msg( json.error, 'error' );
        }
        else
        {
            this.addUser( json.user_name, json.user_real_name, 'member', '' );
            $(this.autoComp.options.editID).value = '';
            this.autoComp._list_close(); 
            if( json.msg )
                this.msg( json.msg, 'msg' );
        }
    },

    addUser: function( username, fullname, userrole, usercredit ) {
        var vars = {
                user_name: username,
                user_real_name: fullname,
                role: userrole,
                credit: usercredit
            };
        
        var html = new Template( collab_template ).evaluate( vars );
        new Insertion.Before( 'user_inserter', html );

        if( $('_user_remove_' + username) )
            Event.observe( '_user_remove_' + username, 'click', this.onUserRemove.bindAsEventListener(this,username) );
        if( $('_user_credit_' + username) )
            Event.observe( '_user_credit_' + username, 'click', this.onUserCredit.bindAsEventListener(this,username) );
        if( $('_contact_' + username) )
            Event.observe( '_contact_'     + username, 'click', this.onUserContact.bindAsEventListener(this,username) );
    }
}
