
<style text="type/css">

.autocomp_label, .cc_autocomp_stat, 
a.autocomp_links {
    display: block;
    float: right;
    margin-left: 9px;
    padding: 2px;
}

a.autocomp_links {
    border: 1px solid #777;
}

#user_tags_filter p.cc_autocomp_line {
	margin: 1px;
    background-color: white;
}

#user_tags_filter p.cc_autocomp_selected {
    /*background-color: #CCC;*/
}

#user_tags_filter p.cc_autocomp_picked {
	font-style: italic;
	color: blue; 
}

#user_tags_filter .cc_autocomp_list {
	margin-top: 3px;
	cursor: pointer;
	width: 220px;
    background-color: white;
}

#user_tags_filter  {
    margin: 0px 0px 11px 10px;
}

.cc_autocomp_stat {
    color: green;
    font-weight: bold;
}

#user_tags_filter .cc_autocomp_border {
    border-top: 4px solid #666;
    border-left: 4px solid #666;
    border-right: 7px solid #444;
    border-bottom: 7px solid #444;
}

</style>

<div id="user_tags_filter" style="position:relative">
<a class="cc_autocomp_clear autocomp_links" style="" href="javascript://clear list" id="_ap_clear_utg">%text(str_filter_clear)%</a>
<a class="cc_autocomp_submit autocomp_links" href="javascript://show list" id="_ap_submit_utg">%text(str_filter_go)%</a>
<span class="cc_autocomp_stat" id="_ap_stat_utg"></span> 
<a class="cc_autocomp_show autocomp_links" href="javascript://show list" id="_ap_show_utg">%text(str_user_filter_tags)%</a>

<div style="clear: both">&nbsp;</div>
    <div style="overflow: scroll; display: none; height: 170px;float:right;" 
                   class="cc_autocomp_list cc_autocomp_border" id="_ap_list_utg">
    </div>
</div>
<input name="utg" id="utg" value="" type="hidden" />

<script type="text/javascript" src="%url('js/autopick.js')%" ></script>
<script type="text/javascript">
ccTagFilter = Class.create();

ccTagFilter.prototype = {

    options: 
        {  url: home_url + 'tags',
           tags: 'remix',
           id: 'utg'
        },
    autoPick: null,

    initialize: function( options )
    {
        this.options = Object.extend( this.options, options || {} );
        this.autoPick = new ccAutoPick( {url: this.options.url });
        this.autoPick.onDataReceived = this.latePosition.bind(this);
        var id = this.options.id;
        this.autoPick.options.listID = '_ap_list_' + id;
        this.autoPick.options.statID = '_ap_stat_' + id;
        this.autoPick.options.showID = '_ap_show_' + id;
        this.autoPick.options.clearID = '_ap_clear_' + id;
        this.autoPick.options.submitID = '_ap_submit_' + id;
        this.autoPick.options.targetID = id;
        this.autoPick.options.pre_text = '';

        if( options.tags )
        {
            this.autoPick.selected = options.tags.split(/[,\s]+/);
            $(this.autoPick.options.statID).innerHTML = this.autoPick.selected.join(', ');
            $(this.autoPick.options.clearID).style.display = '';
        }
        else
        {
            $(this.autoPick.options.clearID).style.display = 'none';
        }

        $(this.autoPick.options.submitID).style.display = 'none';

        this.autoPick.hookUpEvents();
        Event.observe( this.autoPick.options.submitID, 'click', this.onSubmitClick.bindAsEventListener(this) );
    },

    latePosition: function() {
        if( !this.list_posed )
        {
            if( !Prototype.Browser.IE )
                Position.absolutize($(this.autoPick.options.listID));
            this.list_posted = true;
        }
    },

    onSubmitClick: function(event) {
        document.location.href =  this.options.target_url + $('utg').value.replace(' ','+');
    }
}
</script>
<input name="utg" id="utg" value="" type="hidden" />
