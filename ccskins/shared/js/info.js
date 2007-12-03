
ccUploadInfo = Class.create();

ccUploadInfo.prototype = {

    onClickWatcher: null,
    bindWatcher: null,
    transport: null,
    openInfo: null,
    inOpen: false,

    initialize: function() {
    },

    hookInfos: function(class_i,parent) {
        
        var found = false;

        try
        {
            var me = this;
            $$(class_i,parent).each( function(pli) {
                var upload_id = pli.id.match(/[0-9]+$/);
                Event.observe( pli, 'click', me.onInfoClick.bindAsEventListener( me, upload_id ) );
                found = true;
            });            
        }
        catch (e)
        {
            alert( e.message );
        }

        return found;
    },

    onInfoClick: function(event, upload_id ) {

        try
        {
            var old_id = this.CloseInfo();
            var info_id = '__plinfo__' + upload_id;
            if( old_id == info_id )
                return;

            if( $(info_id) )
            {
                this.openInfo = $(info_id);
                this.openInfo.style.display = 'block';
                this.openInfo.style.width = "auto";
            }
            else
            {
                var url = query_url + 'f=html&t=info&ids=' + upload_id;
                var y = (Event.pointerY(event) + 12), x = (Event.pointerX(event) - 50);
                var html = '<div class="info_popup" id="' + info_id + '" ' +
                           'style="display:none;position:absolute;height:auto;top:'+y+'px;left:'+x+'px"></div>';
                new Insertion.After(Event.element(event),html);
                //var link = '<a href="' + url + '">' + url + '</a>';
                //$(info_id).innerHTML = link; $(info_id).style.display = 'block'; return;
                this.transport = new Ajax.Request( url, { method: 'get', onComplete: this._resp_info.bind(this, info_id ) } );
            }
            this.inOpen = true;
            if( this.onClickWatcher )
                this.onClickWatcher(event,info_id,this.transport);
        }
        catch (err)
        {
            alert('oninfo:' + err);
        }
    },

    CloseInfo: function() {
        if( this.openInfo )
        {
            var old_id = this.openInfo.id;
            this.openInfo.style.display = 'none';
            this.openInfo = null;
            return old_id;
        }
        return '-1';
    },

    _resp_info: function( info_id, resp ) {
        var info = $(info_id);
        info.innerHTML = resp.responseText;
        info.style.display = 'block';
        this.openInfo = info;
        var x = (document.body.offsetWidth/2) - (info.offsetWidth/2);
        if( x < 0 )
            x = 100;
        info.style.left = x + 'px';
        Effect.Appear( info, { duration: 2.0, delay: 0.2 } );
        this.transport = null;
        if( this.bindWatcher )
            this.bindWatch( info_id, resp );
    }

}
