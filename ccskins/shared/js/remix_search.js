
ccRemixSearch = Class.create();

ccRemixSearch.prototype = {

    oldTypeVal: -1,
    numChecked: 0,

    initialize: function() {

        var me = this;
        if( pools.length > 0 )
        {
            var html = '<select id="pools"><option value="-1" selected="selected">' + str_this_site + '</option>';
            pools.each( function(p) {
                html += '<option value="' + p.pool_id + '">' + p.pool_name + '</option>';
            });
            $('pool_select_contaner').innerHTML = html;
            Event.observe($('pools'),'change',me.onPoolChange.bindAsEventListener(me));
        }

        Event.observe('do_remix_search','click',me.onDoRemixSearch.bindAsEventListener(me));
        
        if( $('remix_toggle_link') )
            Event.observe('remix_toggle_link', 'click', me.onToggleBox.bindAsEventListener(me));

        this._hook_checks(true);

        if( this.numChecked == 0 && $('form_submit') )
            $('form_submit').disabled = true;

    },

    onToggleBox: function(ev){ 
        this._toggle_open();
    },

    _toggle_open: function() {
        var controls = $('remix_search_controls');
        var show_now = controls.style.display == 'none';
        $$('.remix_checks').each( function(e) { 
            if( !e.checked )
            {
                var id = e.id.match(/[0-9]+$/);
                $('rl_' + id).style.display = show_now ? 'block' : 'none';
            }
        });
        controls.style.display = show_now ? 'block' : 'none';
        $('remix_toggle_link').innerHTML = show_now ? str_remix_close : str_remix_open;
    },

    onDoRemixSearch: function(ev) {
        var value = $('remix_search').value.strip();
        if( value.length < 4 )
        {
            alert(str_no_search_term);
            return;
        }
        $('remix_no_match').innerHTML = '&nbsp;';

        var sel_pool = pools ?  $('pools').options[ $('pools').selectedIndex ].value : -1;
        if( sel_pool == -1 )
        {
            var search_type = $('remix_search_type');
            var query = query_url + 't=remix_checks&f=html&dataview=' + search_type.options[ search_type.selectedIndex ].value;
        }
        else
        {
            var query = home_url + 'pools/search/' + sel_pool + q + 't=remix_pool_checks';
        }
        query += '&search=' + value;
        new Ajax.Request(query, { method: 'get', onComplete: this.onSearchResults.bind(this,value) } );
    },

    onSearchResults: function( value, resp ) {
        try
        {
            if( resp.responseText.length )
            {
                var ids = $$('.remix_checks').inject([], function(array, e) {
                    if(!e.checked)
                        array.push('rl_' + e.id.match(/[0-9]+$/) );
                    return array;
                });
                ids.each( function(id) {
                    Element.remove(id);
                });
                new Insertion.Top($('remix_search_results'),resp.responseText);
            }
            else
            {
                $('remix_no_match').innerHTML = str_no_matches.gsub('%s',value);
            }
            this._hook_checks(false);
        }
        catch (e)
        {
            alert(e);
        }
    },

    _hook_checks: function(check_now) {
        var me = this;
        $$('.remix_checks').each( function(e) { 
            if( check_now )
            {
                e.checked = true;
                me.numChecked++;
            }
            var id = e.id.match(/[0-9]+$/);
            Event.observe(e,'click',me.onRemixCheck.bindAsEventListener(me, id ));
        });
    },

    onRemixCheck: function( ev, id ) {
        try
        {
            var check = $('src_' + id);
            var label = $('rc_' + id );

            if( check.checked )
            {
                this.numChecked++;
                Element.addClassName(label,'remix_source_selected');
            }
            else
            {
                this.numChecked--;
                Element.removeClassName(label,'remix_source_selected');
            }

            if( $('remix_search_toggle') )
                $('remix_search_toggle').style.display = this.numChecked ? 'block' : 'none';
            if( $('form_submit') )
                $('form_submit').disabled = this.numChecked ? false : true;

            var controls = $('remix_search_controls');
            if( controls.style.display == 'none' )
                this._toggle_open();
        }
        catch (e)
        {
            alert(e);
        }
    },

    onPoolChange: function(ev) {
        var pools = $('pools');
        var pool = pools.options[ pools.selectedIndex ].value;
        var search_type = $('remix_search_type');
        if( pool == -1 )
        {
            search_type.disabled = false;
            search_type.selectedIndex = this.oldTypeVal;
        }
        else
        {
            this.oldTypeVal = search_type.selectedIndex;
            search_type.selectedIndex = 2;
            search_type.disabled = true;
        }
    }
}
