
/*
    Hook menu items so they go to a browser popup

    usage:

    new popupHook( [ 'mi_managesite', 'mi_global_settings' ] );  

*/
var popupHook = Class.create();

popupHook.prototype = {

    initialize: function(ids) {
        var me = this;
        ids.each( function( id ) {
            if( $(id) )
            {
                var e = $(id);
                var href = e.href;
                e.href = 'javascript://hooked for popup ' + id;
                title = e.innerHTML.stripTags();
                Event.observe( id, 'click', me.onClick.bindAsEventListener( me, href, title ) );
            }
        } );
    },

    onClick: function( e, href, thetitle ) {
        if( href.indexOf('?') == -1 )
            href += '?popup=1';
        else
            href += '&popup=1';
        var dim = "height=600,width=900";
        var win = window.open( href, 'cchostextrawin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                                      "resizable=1,scrollbars=1," + dim );
        win.title = thetitle;

    }
}

/*
    Hook a link so it goes to a DHTML model popup div
*/
var modalHook = Class.create();

modalHook.prototype = {

    initialize: function(ids) {
        var me = this;
        ids.each( function( id ) {
            if( $(id) )
            {
                var e = $(id);
                var href = e.href;
                e.href = 'javascript://hooked ' + id;
                title = e.innerHTML.stripTags();
                Event.observe( id, 'click', me.onClick.bindAsEventListener( me, href, title ) );
            }
        } );
    },

    onClick: function( e, href, thetitle ) {
        if( href.indexOf('?') == -1 )
            href += '?ajax=1';
        else
            href += '&ajax=1';
        Modalbox.show( href, {title: thetitle, width: 700, height: 550} );
    }
}


/*
    hook a class of links so the id triggers a query in a DHTML modal popup
*/
var queryPopup = Class.create();

queryPopup.prototype = {

    className: '',
    template: '',
    title: '',

    initialize: function(className,template,title) {
        this.className = className;
        this.template = template;
        this.title = title;
    },

    hookLinks: function() {
        var me = this;
        $$('.' + this.className).each( function(link) {
            var upload_id = link.id.match(/[0-9]+$/);
            Event.observe( link, 'click', me.onClick.bindAsEventListener( me, upload_id ) );
        });
    },

    onClick: function( e, upload_id ) {
        var url = query_url + 'f=html&t='+this.template+'&ids=' + upload_id;
        Modalbox.show( url, {title: this.title, width: 500} );
    }
}


/*
    If user is logged in, make ratings stars interactive
    (called from userHooks below)
*/
var ratingsHooks = Class.create();

ratingsHooks.prototype = {

    full_star_url: null,
    null_star_url: null,
    return_macro: null,
    initialize: function(ok_to_rate) {
        try
        {
            this.full_star_url = full_star;
            this.null_star_url = null_star;
            this.return_macro = rate_return_t || null;
            var me = this;
            $$('.rate_star').each( function(img) {
                var m = img.id.match(/([0-9]+)_([0-9]+)$/);
                var id = m[2];
                var num = m[1];
                if( ok_to_rate.include(id) )
                {
                    img.altsrc = img.src;
                    Event.observe(img,'click',me.onRateClick.bindAsEventListener(me,id,num));
                    Event.observe(img,'mouseover',me.onRateHover.bindAsEventListener(me,id,num));
                    Event.observe(img,'mouseout',me.onRateOff.bindAsEventListener(me,id,num));
                }
            });
        }
        catch (e)
        {
            alert(e);
        }
    },
    onRateClick: function(event,id,num) {
        var rlabel = $("rate_label_" + id);
        if( rlabel )
            rlabel.innerHTML = str_ratings;

        var h_elem = $("rate_head_" + id);
        if( h_elem )
            h_elem.style.display = 'none';

        var b_elem = $("rate_edit_" + id);
        if( b_elem )
            b_elem.style.display = 'none';

        var d_elem = $("rate_block_" + id);
        d_elem.innerHTML = '...';
        var url = home_url + "rate/" + id + "/" + num;
        if( this.return_macro )
            url += q + 'rmacro=' + this.return_macro;
        new Ajax.Updater(d_elem,url);

    },
    onRateOff: function(event,id,num) {
        var i;
        for( i=1; i<6; i++)
        {
            var img = $('rate_star_' + i + '_' + id);
            img.src = img.altsrc;
        }
    },
    onRateHover: function(event,id,num) {
        var i;
        for( i=1; i<=num; i++)
        {
            var img = 'rate_star_' + i + '_' + id;
            $(img).src = this.full_star_url;
        }
    }
}

/*
    If the user is logged in while listing records, enable
    in-situ reviewing (called from userHooks below)
*/
var quickReviewHooks = Class.create();

quickReviewHooks.prototype = {

    initialize: function(reviewable_ids) {
        try
        {
            var me = this;
            reviewable_ids.each( function(id) {
                    var btn_holder = $('instareview_btn_' + id);
                    if( btn_holder )
                    {
                        var btn_id     = 'review_button_' + id;
                        var html = '<a href="javascript://instarview" class="instareview_btn" id="' + btn_id + '">&nbsp;</a>';
                        btn_holder.innerHTML = html;
                        Event.observe(btn_id,'click',me.onQuickReviewClick.bindAsEventListener(me,id));
                    }
                });
        }
        catch (e)
        {
            alert(e);
        }
    },

    onQuickReviewClick: function(event,id) {
        var url = home_url + 'reviews/post/' + id + q + 'ajax=1';
        Modalbox.show( url, {title: str_review_write, width: 500, height: 500} );
    }
}

/*
    If the user is logged in, make the 'recommends' thumbs up interactive
    (called from userHooks below)
*/
var recommendsHooks = Class.create();

recommendsHooks.prototype = {

    return_macro: null,

    initialize: function(ok_to_rate) {
        try
        {
            var me = this;
            this.return_macro = recommend_return_t || null ;
            $$('.recommend_block').each( function(e) {
                var id = e.id.match(/[0-9]+$/);
                if( ok_to_rate.include(id) ) {
                    var html = e.innerHTML;
                    var newHtml = '<span class="recommend_link">' + html + '</span>';
                    e.innerHTML = newHtml;
                    Event.observe(e,'click',me.onRecommendClick.bindAsEventListener(me,id));
                    Element.removeClassName(e,'rated');
                }
            });
        }
        catch (e)
        {
            alert(e);
        }
    },

    onRecommendClick: function(event,id) {
        var d_elem = $("recommend_block_" + id);
        d_elem.innerHTML = '...';
        var url = home_url + "rate/" + id + "/5";
        if( this.return_macro )
            url += q + 'rmacro=' + this.return_macro;
        new Ajax.Updater(d_elem,url,{onComplete:this.onRecommendFilled.bind(this,id)});
    },

    onRecommendFilled: function(id) {
        Element.addClassName($("recommend_block_" + id),'rated');
    }
}

/*
    If the user is logged in, make the topics/reviews interactive
    (called from userHooks below)
*/
var topicHooks = Class.create();

topicHooks.prototype = {

    initialize: function(topics_cmds) {
        try
        {
            topics_cmds.each( function(cmd_meta) {
                var id = cmd_meta.id;
                var html = '';
                cmd_meta.cmds.each( function(cmd) {
                    html += '<a class="cc_gen_button" href="' + cmd.href + '"><span>' + cc_str(cmd.text) + '</span></a> ';
                });
                $('commands_' + id).innerHTML = html;
            });
        }
        catch (e)
        {
            alert(e);
        }
    }
}


/*
    If the user is logged in, make the appropriate HTML parts interactive
    (ratings, topic commands, etc.)
*/
var userHookup = Class.create();

userHookup.prototype = {

    initialize: function(req,params) {
        var url = home_url + 'user_hook/' + req + q + params;
        new Ajax.Request( url, { method: 'get', onComplete: this.onUserHooks.bind(this) } );
    },

    onUserHooks: function(resp,json) {
        try
        {
            if( !json )
                json = eval( '(' + resp.responseText + ')' );
            
            if( json  )
            {
                if( json.ok_to_rate && json.ok_to_rate.length )
                {
                    if( json.rate_mode == 'rate' )
                    {
                        new ratingsHooks(json.ok_to_rate);
                    }
                    else if( json.rate_mode == 'recommend' )
                    {
                        new recommendsHooks(json.ok_to_rate);
                    }
                    else
                    {
                        alert('error: unknown rate mode: ' + json.rate_mode );
                    }
                }
                else
                {
                    if( json.topic_cmds )
                    {
                        new topicHooks(json.topic_cmds);
                    }
                }
                if( json.reviewable )
                {
                    new quickReviewHooks(json.reviewable);
                }
            }
        }
        catch (e)
        {
            alert(e);
        }
    }

}

function cc_str(s)
{
    if( !s.match )
    {
        var template = new Template(cc_str(s[0]));
        var args = $A(s);
        s = template.evaluate( args );
    }

    if( s.match(/^str_/) )
        return eval( s );

    return s;
}

function upload_trackback( upload_id, type )
{
    var url = query_url + 'ajax=1&t=trackback&ttype=' + type + '&ids=' + upload_id;
    var h = type == 'video' ? 560 : 500;
    Modalbox.show( url, {title: str_trackback_title, width: 480, height: h} );
}


function ajax_debug(url)
{
    if( !$('debug') )
        new Insertion.Top('content','<div id="debug"></div>');
    $('debug').style.display = 'block';
    if( url.match(/^http:/) )
        $('debug').innerHTML = '<a href="' + url + '">' + url + '</a>';
    else
        $('debug').innerHTML = url;
}

var ccPopupManagerMethods = {

    openPopup: null,
    currX: 0,
    currY: 0,
    thinkingDiv: null,
    thinkingEnabled: false,
    msgDiv: null,
    prevMsgClass: null,
    errCount: 0,
    itme: 'hello me',
    mode: null,

    StartThinking: function(event) {
        this.currY  = (parseInt(Event.pointerY(event)) - 5); 
        this.currX  = (parseInt(Event.pointerX(event)) + 15); 
        if( this.currX > (  document.body.offsetWidth - 50 ) )
            this.currX /= 2;
        this.thinkingEnabled = true;
    },

    StopThinking: function(dur) {
        if( this.mode && this.mode == 'err' )
            return;
        if( this.thinkingEnabled )
        {
            Effect.Fade(this.thinkingDiv, { delay: dur, duration: 0.3 } );
            this.thinkingEnabled = false;
        }
        
    },

    ShowThinking: function(text){
        if( this.mode && this.mode == 'err' )
            return;
        if( !this.thinkingEnabled )
        {
            this.currY = 20;
            this.currX = 300;
            this.thinkingEnabled = true;
            //return;
        }

        if( !$('cc_thinking') )
        {
            this.thinkingDiv = document.createElement('div');
            this.thinkingDiv.id = 'cc_thinking';
            this.thinkingDiv.className = 'light_bg dark_border';
            document.body.appendChild(this.thinkingDiv);
        }

        if( this.prevMsgClass )
        {
            Element.removeClassName(this.thinkingDiv,this.prevMsgClass);
            this.prevMsgClass = null;
        }

        this.thinkingDiv.innerHTML = text || str_thinking;
        this.thinkingDiv.style.top  = this.currY + 'px';
        this.thinkingDiv.style.left = this.currX + 'px';
        Effect.Appear( this.thinkingDiv, { duration: 0.2 } );
    },

    onAjaxReturn: function(json) {

        if( json )
        {
            var dur = 4.5;

            if( json.message )
            {
                this.ShowMessage('message',json.message,dur);
            }
            else if( json.warning )
            {
                this.ShowMessage('warning',json.warning,dur);
            }
            else if( json.err )
            {
                this.ShowMessage('error',json.err,5.0);
            }
            else
            {
                if( Ajax.activeRequestCount == 0 )
                    this.StopThinking(0.2);
            }
        }
        else
        {
            if( Ajax.activeRequestCount == 0 )
                this.StopThinking(0.2);
        }
    },

    ShowMessage: function(type,text,dur)
    {
        if( this.mode && this.mode == 'err' )
            return;

        try
        {
            this.ShowThinking(cc_str(text));
            var className = 'ajaxmsg_' + (type == 'exception' ? 'error' : type);
            this.prevMsgClass = className;
            Element.addClassName(this.thinkingDiv,className);
            this.mode = type == 'exception' ? 'err' : null;
            this.StopThinking(dur);
        }
        catch(ex)
        {
            alert('show message: ' + ex.message);
        }
    },

    ShowElement: function(id) {
        Effect.Appear( id, { duration: 0.5 } );
    },

    HideElement: function(id) {
        Effect.Fade( id, { duration: 0.5 } );
    },

    /**
    *    User clicked something that trigged an ajax call for data
    *
    *  Put up the little 'thinking' div, wait for response
    */
    userClickDataFetch: function(event,id) {
        this._close_any_popups();
        this.StartThinking(event);
        Event.stop(event);
    },

    /**
    *  Data came back from ajax request, open the popup with 'id'
    */
    dataFetchedOpenPopup: function(id) {
        if( id == this.openPopup )
            return;
        this._close_any_popups();
        this.openPopup = id;
        this.ShowElement(id);
        this._hook_window();
    },

    /*
    * Data is cached in hidden popup, reopen it now
    */
    reopenPopupOrCloseIfOpen: function(event,id) {
        if( id == this.openPopup )
        {
            this._close_any_popups();
        }
        else
        {
            this.StartThinking(event);
            this.dataFetchedOpenPopup(id);
        }
        Event.stop(event);
    },

    /**
    * 
    */
    clearWindowClick: function(event) {
        this._close_any_popups();
    },

    _close_any_popups: function() {
        if( this.openPopup )
        {
            this.HideElement(this.openPopup);
            this.openPopup = null;
        }
    }
}


var ccPopupManager = Object.extend(
        {
            bodyHooked: false,
            _hook_window: function() {
                if( !this.bodyHooked )
                {
                    Event.observe( document.body /* window */, 'click', this.clearWindowClick.bindAsEventListener(this));
                    this.bodyHooked = true;
                }
            },

            onCreate: function(req){
                //ajax_debug(req.url);
                if( !Prototype.Browser.IE )
                {
                    // this completely blows IE to smithereens
                    this.ShowThinking();
                }
                else
                {
                    if( !this.thinkingEnabled )
                    {
                        this.currY = 20;
                        this.currX = 300;
                        this.thinkingEnabled = true;
                    }
                }
            },

            onException: function(req,ex) {
                this.ShowMessage( 'exception', ex.toString(), 6.0 );
            },

            onComplete: function(req,t,json) {
                this.onAjaxReturn(json);
            }
        }, ccPopupManagerMethods );

Ajax.Responders.register(ccPopupManager);


var ccFormMask = Class.create();

ccFormMask.prototype = {

    text: null,
    title: null,

    initialize: function(form_id,msg,hook_submit,title)
    {
        this.text = msg;
        this.title = title;
        if( hook_submit )
            Event.observe(form_id,'submit', this.dull_screen.bindAsEventListener(this) );
    },

    dull_screen: function()
    { 
        Modalbox.show( this.text, {title: this.title, 
                                   width: 600, height: 400, 
                                   overlayClose: false, 
                                   transitions: false,
                                   slideDownDuration: 0.0 } );
        return true;
    }
}

var ccReviewFormHook = Class.create();

ccReviewFormHook.prototype = {
    initialize: function(form_id) {
        Event.observe(form_id,'submit', this.quiet_submit.bindAsEventListener(this,form_id) );
    },

    quiet_submit: function(event,form_id) {
        var url = $(form_id).action;
        var params = Form.serialize(form_id);
        new Ajax.Request( url, { method: 'post', parameters: params, onComplete: this.form_return.bind(this) } );
        Event.stop(event);
        return false;
    },

    form_return: function(resp,json) {
        if( json && json.reviews_url )
        {
            var html = '<a class="upload_review_link" href="' + json.reviews_url + '">(' + json.num_reviews + ')</a>';
            var target = $('review_' + json.upload_id);
            target.innerHTML = html;
            Modalbox.hide();
        }
    }

}

function cc_window_dim() {
    var w = window;
    var T, L, W, H;
    with (w.document) {
      if (w.document.documentElement && documentElement.scrollTop) {
        T = documentElement.scrollTop;
        L = documentElement.scrollLeft;
      } else if (w.document.body) {
        T = body.scrollTop;
        L = body.scrollLeft;
      }
      if (w.innerWidth) {
        W = w.innerWidth;
        H = w.innerHeight;
      } else if (w.document.documentElement && documentElement.clientWidth) {
        W = documentElement.clientWidth;
        H = documentElement.clientHeight;
      } else {
        W = body.offsetWidth;
        H = body.offsetHeight
      }
    }
    return { top: T, left: L, width: W, height: H };
}
