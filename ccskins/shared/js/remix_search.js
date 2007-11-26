
ccRemixSearch = Class.create();

ccRemixSearch.prototype = {

    oldTypeVal: -1,

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

        Event.observe( 'do_remix_search', 'click', me.onDoRemixSearch.bindAsEventListener(me) );
        
        if( $('remix_toggle_link') )
            Event.observe( 'remix_toggle_link', 'click', me.onToggleBox.bindAsEventListener(me) );

        this._scan_checks(true);
    },

    _scan_checks: function(check_all) {
        try
        {
            var me = this;
            $('license_info').style.display = 'none';
            var remix_sources = [];
            var pool_sources = [];
            var numChecked = 0;
            $$('.remix_checks').each( function(e) { 
                if( check_all )
                    e.checked = true;

                var m = e.name.match(/(remix|pool)_sources\[([0-9]+)\]/);
                var id = m[2];
                var label = $('rc_' + id );

                if( check_all || e.checked )
                {
                    numChecked++;
                    if( m[1] == 'remix' )
                        remix_sources.push(id);
                    else
                        pool_sources.push(id);
                    Element.addClassName(label,'remix_source_selected');
                }
                else
                {
                    Element.removeClassName(label,'remix_source_selected');
                }

                if( !e.hooked )
                {
                    Event.observe(e,'click',me.onRemixCheck.bindAsEventListener(me, id ));
                    e.hooked = true;
                }

            });

            if( numChecked )
            {
                var url = home_url + '/remixlicenses' + q + 'remix_sources=' + remix_sources + '&pool_sources=' + pool_sources;
                new Ajax.Request(url, { method: 'get', onComplete: this.onLicenseResults.bind(this) } );
            }

            $('remix_search_toggle').style.display = numChecked ? 'block' : 'none';

            if( $('form_submit') )
                $('form_submit').disabled = numChecked ? false : true;
        }
        catch (e)
        {
            alert(e);
        }
    },

    onRemixCheck: function( ev, id ) {
        this._scan_checks(false);
        var controls = $('remix_search_controls');
        if( controls.style.display == 'none' )
            this._toggle_open();
    },

    onLicenseResults: function(resp,json) {
        $('license_info').innerHTML = str_remix_lic.replace('%s','<a href="' + json.license_url 
                                                                 + '">' + json.license_name + '</a>' );
        $('license_info').style.display = 'block';
        $('upload_license').value = json.license_id;
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
            this._scan_checks(false);
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
