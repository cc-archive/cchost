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
/******************************************
*
*  Query Browser Filters
*
*******************************************/
ccQueryBrowserFilters = Class.create();
ccQueryBrowserFilters.prototype = {

    initialize: function(init_values) {

        this.reqtags =     { name: 'Type', fmt: 'dropdown', param: 'reqtags', 
                                         vals: [  
                                                    ['remix,44k', 'default (remix)' ],
                                                    ['editorial_pick',      'ed picks' ],
                                                    ['remix,hip_hop',       'hip hop'], 
                                                    ['remix,downtempo',     'downtempo'], 
                                                    ['remix,funky',         'funky'], 
                                                    ['remix,female_vocals', 'female vocals'],
                                                    ['remix,chill',         'chill'],
                                                    ['remix,experimental',  'experimental'],
                                                    ['remix,trip_hop',      'trip hop'],
                                                    ['audio',               'all uploads']
                                                ] 
                           };
        this.user       = { name: 'Artist', fmt: 'user_lookup', param: 'user' };
        this.remixesof  = { name: 'Remixes of', fmt: 'remix_user', param: 'remixesof' };
        this.tags       = { name: 'Tags', fmt: 'tag_lookup', param: 'tags' };
        this.type       = { name: 'Match', fmt: 'dropdown', param: 'type' ,
                                         vals: [  [ 'all', 'Match all tags'],
                                                  [ 'any', 'Match any tags' ]
                                               ]
                                          };
        /*
        this.score      = { name: 'Ratings', fmt: 'dropdown', param: 'score',
                                         vals: [  [ '*', 'all'],
                                                  [ 500, '5.00' ], 
                                                  [ 450, '4.5 or better' ], 
                                                  [ 400, '4.0 or better' ], 
                                                  [ 350, '3.5 or better' ], 
                                                  [ 300, '3.0 or better' ]
                                               ]
                          };
        */
        this.lic  = { name: 'License', fmt: 'dropdown', param: 'lic',
                                         vals: [  [ '*', 'all'],
                                                  [ 'by', 'attribution'],
                                                  [ 'nc', 'non-commercial'],
                                                  [ 'sa', 'share-alike'],
                                                  [ 'byncsa', 'nc-share-alike'],
                                                  [ 's', 'sampling'],
                                                  [ 'splus', 'sampling+'],
                                                  [ 'ncsplut', 'nc-sampling+'],
                                                  [ 'pd', 'public domain']
                                               ]
                          };
        this.sinced = { name: 'Since', fmt: 'dropdown', param: 'sinced',
                                         vals: [  
                                                  [ '*', 'all time'],
                                                  [ '1 days ago', 'yesterday'],
                                                  [ '1 weeks ago', 'a week ago'],
                                                  [ '2 weeks ago', '2 weeks ago'],
                                                  [ '1 months ago', 'last month'],
                                                  [ '3 months ago', '3 months ago'],
                                                  [ '1 years ago', 'a year ago']
                                               ]
                          };

        this.limit = { name: 'Limit', fmt: 'dropdown', param: 'limit', value: 35,
                                         vals: [  
                                                  [ 1 ], 
                                                  [ 5 ],
                                                  [ 10 ],
                                                  [ 15 ],
                                                  [ 25 ],
                                                  [ 50 ]
                                               ]
                          };

        // this should be "playlist mode"
        // this.rand       = { name: 'Random Sort', fmt: 'checkbox', param: 'rand' };

        if( init_values )
        {
            var me = this;
            $H(init_values).each( function(pair) {
                if( me[pair[0]] )
                    me[pair[0]].value = pair[1];
            });
        }
    },

    filterOutUnknown: function(filters) {
        var me = this;
        var results = [];
        $H(filters).reject( function(pair)  {
            if( !me[pair[0]] )
                results[pair[0]] = pair[1];
        });
        return results;
    },

    queryString: function() {
        var elements = Form.getElements($(this.id));
        var q = Array();

        for (var i = 0; i < elements.length; i++) {
          var element = elements[i];
          if( !element.name.match(/^_/) )
          {
            var val = $F(element);
            if( val && (val != '*' ) )
                q.push(element.name + '=' + val);
          }
        }

        return q.join('&');

    },

    makeForm: function( baseId, formatter_arg ) {
        
        var _id = baseId + '_filter';
        var formInfo = {
            id: _id,
            innerId: _id + '_inner',
            closeId: _id + '_close',
            submitId: _id + '_submit',
            html: ''
        };

        formInfo.html = ' <div class="filterform" id="' + formInfo.id + '" ><div id="' 
                       + formInfo.innerId + '" style="display:none"><a href="javascript://close filter" id="' 
                       + formInfo.closeId 
                       + '" class="close_button">close</a>'
                   ;

        var formatter = formatter_arg || new ccFormatter();

        $H(this).each( function(af) {
            var f = af[1];
            if( f.fmt )
              formInfo.html += '<div><span class="th">' + f.name + ':</span><span class="field">' 
                        + formatter[f.fmt](f,0) + '</span></div>\n';
        });

        formInfo.html += '\n<div class="filterbuttontray"><a class="cc_gen_button" href="javascript://filter go" id="'
                   + formInfo.submitId + '"><span>see results</span></a></div>\n</div></div>\n';

        this.id = _id;
        return(formInfo);
    }
}

/******************************************
*
*  Query Browser Formatters
*
*******************************************/
ccFormatter = Class.create();

ccFormatter.prototype = {

    initialize: function() {
    },

    chop: function(s,col,x) {
        if( s.length > col.amt )
            s = s.substr(0,col.amt-3) + '...';
        return(s);
    },

    score: function(n,col,hidden) {
        n = parseInt(n);
        if( !n )
            return( 'n/r'  );
        var hi = Math.floor(n/100);
        var lo = n % 100;
        if( !lo )
            lo = '00';

        var ret = hi + '.' + lo;
        if( hidden && hidden.length )
        {
            ret += '/';
            var val = parseInt(hidden.pop());
            if( val < 10 )
                ret += '0';
            ret += val;
        }
        return( ret );
    },

    date: function(str,col) {
        // '2006-12-08 04:40:11'
        var sd = str.split(' ')[0].split('-');
        // how the heck do you locale this? parsing Date.toString()?
        var m = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
        return m[ sd[1]-1 ] + ' ' + sd[2] + ', ' + sd[0];
    },

    _tag: function(n,f) {
        return '<' + n + ' id="' + f.param + '" name="' + f.param + '" ';
    },

    _input: function(t,f,x) {
        var html = this._tag('input type="'+t+'"',f);
        var x = x || '';
        if( f.value )
            html += 'value="' + f.value + '" ';
        html += ' ' + x + ' />';
        return html;
    },

    user_lookup: function(f,col) {
        var autoComp = this.user_picker = new ccAutoComplete( {  url: home_url + 'browse' + q + 'user_lookup=' } );
        this._watches.push( { func: autoComp.hookUpEvents.bind(autoComp) } );
        return autoComp.genControls( f.param, f.value, 'enter user below' );
    },

    remix_user: function( f, col ) {
        this.remix_id    = f.param;
        this.remix_label = 'label_' + f.param;
        var val          = f.value || '';
        var checked      = f.value ? 'checked="checked"' : '';
        var html         = this._input('checkbox',f,checked) + ' <label id="' + this.remix_label +
                             '" for="' + f.param + '">'+val+'</label>';

        this._watches.push( { id: f.param, ev: 'click', func: this.onRemixesOfCheck.bindAsEventListener(this) } );
        if( this.user_picker )
            this.user_picker.options.onPick = this.onUserPick.bind(this);
        return html;
    },

    onUserPick: function( autoComp, element, value ) {
        if( $(this.remix_id).checked ) 
        {
            this.savedOffUser = '';
        }
        else
        {
            $(this.remix_id).value = value;
            $(this.remix_label).innerHTML = value;
        }
        return false;
    },

    onRemixesOfCheck: function(e) {
        var element = Event.element(e);
        if( element.checked )
        {
            this.savedOffUser = $('user').value;
            $('user').value = '';
        }
        else
        {
            if( this.savedOffUser ) 
                $('user').value = this.savedOffUser;
        }
    },

    tag_lookup: function(f,col) {
        var autoPick = new ccAutoPick( {  url: home_url + 'browse' + q + 'min=3&type=4&tag_lookup=*' } );
        this._watches.push( { func: autoPick.hookUpEvents.bind(autoPick) } );
        return autoPick.genControls( f.param, f.value, '' );
    },

    user_tags: '',

    dropdown: function(f,col) {
        var html = this._tag('select',f) + '>';
        f.vals.each( function(opt) {
            var val = opt[0], text = opt.length > 1 ? opt[1] : val;
            var sel = val == f.value ? ' selected="selected" ' : '';
            html += '<option ' + sel + 'value="' + val + '" >' + text + '</option>';
        });
        return html + '</select>';
    },

    checkbox: function(f,col) {
        return this._input('checkbox',f);
    },

    hidden: function(f, col) {
        return '<span id="stat_' + f.param + '">' + f.value + '</span>' +
               this._input('hidden',f);
    },
    
    edit: function( f,col ) {
        return this._input('text',f);
    },

    _watches: [],

    setup_watches: function() {
        if( !this._watches.length )
            return;
        this._watches.each( function(w) {
            if( w.ev )
                Event.observe( w.id, w.ev, w.func, false );
            else
                w.func(w);
        });
    }

}

