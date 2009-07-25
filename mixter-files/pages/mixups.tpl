<!--
%%
[meta]
    name = mixups
    type = template_component
    desc = _('Display mixups')
    dataview = mixups
    bread_crumbs = home
[/meta]
%%
-->
<!-- template mixups -->

<style title="Default Style" type="text/css">
#mixup_table {
    margin: 0px auto;
    width: 80%;
}
.mixup_mode_type {
    display: block;
    border: 1px solid black;
    padding: 3px;
    color: white;
    float: left;
    margin: 0.7em;
    font-style: italic;
}

.mixup_name {
    font-size: 150%;
}

#mixup_table div {
    margin-bottom: 1em;
}

.mixup_status p {
    padding: 0.7em;
}

.pictoggle, #admin_button {
    float: right;
    margin-right: 8%;
}
.mixup_mode_type_<?= CC_MIXUP_MODE_DISABLED  ?> { background-color: orange; }
.mixup_mode_type_<?= CC_MIXUP_MODE_SIGNUP    ?> { background-color: green; }
.mixup_mode_type_<?= CC_MIXUP_MODE_MIXING    ?> { background-color: green; }
.mixup_mode_type_<?= CC_MIXUP_MODE_UPLOADING ?> { background-color: green; } 
.mixup_mode_type_<?= CC_MIXUP_MODE_DONE      ?> { background-color: red; }
.mixup_mode_type_<?= CC_MIXUP_MODE_CUSTOM    ?> { background-color: inherit; color: inherit; }

</style>

<script type="text/javascript">
var mixupAPI = Class.create();

mixupAPI.prototype = {

    initialize: function(mixup_id)
    {
        this.mixup_id = mixup_id;
        this.statusDiv = $('signup_status_' + this.mixup_id );
        var user_list_id = 'mixup_user_list_' + this.mixup_id;
        this.userList = $(user_list_id);
        if( this.statusDiv )
        {
            this.action = 'status';
            this.doAction();
        }
        else
        {
            this.doUserList();
        }
    },
    
    doAction: function()
    {
        this.statusDiv.innerHTML = '...';
        var url = home_url + 'api/mixup/' + this.action + '/' + this.mixup_id;
        new Ajax.Request( url, { method: 'get', onComplete: this.onUserStatus.bind(this) } );
        this.doUserList();
    },
    
    doUserList: function()
    {
        if( this.userList )
        {
            url = query_url + 't=mixup_users&f=html&mixup=' + this.mixup_id;
            new Ajax.Updater( this.userList, url, { method: 'get' } );
        }
        
    },

    onUserStatus: function(resp, json) {
        var id = 'signup_link_' + this.mixup_id;
        var html = '<a href="javascript://signup" class="small_button" id="' + id + '">';
        var msg = '';
        if( json.notSignedUp )
        {
            msg = 'You are not signed up for this mixup.';
            html += "Sign Up Now!";
            this.action = 'signup';
        }
        else if( json.signedUp )
        {
            msg = 'You are signed up for this mixup. To remove yourself from the mixup, click on the "Remove me" button below.';
            html += 'Remove me';
            this.action = 'remove';
        }
        else if( json.msg ) {
            this.statusDiv.innerHTML = json.msg;
            return;
        }
        
        html += '</a>';
        this.statusDiv.innerHTML = '<p>' + msg + '</p><p>' + html + '</p>';

        Event.observe(id,'click', this.doAction.bindAsEventListener(this) );
    }
}

var miximg_on = true;

function toggle_img()
{
    miximg_on = !miximg_on;
    show_hide_miximg();
}

function show_hide_miximg()
{
    var newstyle = miximg_on ? '' : 'none';
    var text     = miximg_on ? 'Hide avatars' : 'Show avatars';
    var height   = miximg_on ? '120px' : '';
    
    CC$$('.hidemixup').each( function(e) {
        e.style.display = newstyle;
    });
    CC$$('.pictoglink').each( function(a) {
       a.innerHTML = text; 
    });
    CC$$('.miximgbox').each( function(d) {
        d.style.height = height;
    })
    
    cc_set_cookie( 'miximg_on', miximg_on );
}
</script>

<div id="mixup_table">
    %loop(records,R)%
        <div id="mixup_record">
            <div class="box">
                %if_not_null(is_admin)%
                    <a class="small_button" id="admin_button" href="%(home-url)%admin/mixup/edit/%(#R/mixup_id)%">Admin</a>
                %end_if%
                <h2>
                    %(#R/mixup_display)%
                </h2>
                <div class="mixup_desc">
                    %(#R/mixup_desc_html)%
                </div><!-- mixup_desc -->
            </div>
            <div class="mixup_status">
                <span class="mixup_mode_type mixup_mode_type_%(#R/mixup_mode_type)%">%(#R/mixup_mode_name)%</span>
                <p>%(#R/mixup_mode_desc_html)%</p>
                %map(show_who,'1')%
                %map(show_status,'0')%
                %map(show_matches,'0')%
                %switch(#R/mixup_mode_type)%
                    %case(CC_MIXUP_MODE_DISABLED)%
                        %map(show_who,'0')%
                    %end_case%
                    %case(CC_MIXUP_MODE_SIGNUP)%
                        %map(show_status,'1')%
                    %end_case%
                    %case(CC_MIXUP_MODE_MIXING)%
                        %map(show_status,'1')%
                    %end_case%
                    %case(CC_MIXUP_MODE_UPLOADING)%
                    %end_case%
                    %case(CC_MIXUP_MODE_DONE)%
                      %map(show_matches,'1')%
                    %end_case%
                %end_switch%
                %if(show_status)%
                    <div id="signup_status_%(#R/mixup_id)%">...</div><!-- signup_status_%(#R/mixup_id)% -->
                %end_if%
                %if(show_who)%
                    <div class="pictoggle">
                        <a class="pictoglink small_button" id="pictoglink_%(#R/mixup_id)%" href="javascript://pictoggle">Hide avatars</a>
                    </div>
                    <div class="mixup_users_%(#R/mixup_id)%">
                        <div class="who_caption">Who signed up:</div>
                        <div id="mixup_user_list_%(#R/mixup_id)%">...</div><!-- mixup_user_list -->
                    </div><!-- mixup_users -->
                %end_if%
            </div><!-- mixup_status -->
        </div><!-- mixup_record -->
        <script type="text/javascript">
          function mixup_hook_%(#R/mixup_id)%()
          {
            new mixupAPI(%(#R/mixup_id)%);
          }
          Event.observe(window,'load',mixup_hook_%(#R/mixup_id)%);
          Event.observe('pictoglink_%(#R/mixup_id)%','click',toggle_img)
        </script>
    %end_loop%
</div><!-- mixup_table -->

%call(prev_next_links)%