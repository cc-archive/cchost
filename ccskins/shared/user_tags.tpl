
<div id="user_tags_filter">%text(str_user_filter_tags)% <div id="user_tag_target"></div></div>

<script>
ccUserTags = Class.create();

ccUserTags.prototype = {

    thisUser: null,
    selTag: null,

    initialize: function(user_name,sel_tag) {
        this.thisUser = user_name;
        this.selTag = sel_tag;
        var url = home_url + 'user_hook/tags/' + user_name;
        var me = this;
        new Ajax.Request( url, { method: 'get', onComplete: me.onUserTags.bind(me) } );
    },

    onUserTags: function(resp) {
        var tags = eval(resp.responseText);
        var html = '<select id="user_tags_tags"><option value="">' + str_all + '</option>';
        var me = this;
        tags.each( function(tag) {
            html += '<option value="' + tag + '"';
            if( me.selTag == tag )
                html += ' selected="selected" ';
            html += '>' + tag + '</option>';
        });
        html += '</select>';
        $('user_tag_target').innerHTML = html;
        Event.observe('user_tags_tags','change',me.onUserTagChange.bindAsEventListener(me));
    },

    onUserTagChange: function() {
        var sel = $('user_tags_tags');
        document.location = home_url + 'people/' + this.thisUser + '/' + sel.options[sel.selectedIndex].value;
    }
}

new ccUserTags('%(user_tags_user)%','%(user_tags_tag)%');
</script>