/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/
/*
    nvzion.com, 2010
*/

/*
    YAHOO! MEDIA PLAYER CONFIGURATION
*/
var YMPParams = {
    defaultalbumart: DIG_ROOT_URL + '/images/default-cover.jpg'
};


var str_remove_tag = 'remove tag';
var str_you_already = 'You already have permission&hellip;';
var str_more = 'more &raquo;';
var str_by = 'by';
var str_download = 'Download';
var str_IE_right = 'IE: Right-click select &lsquo;Save Target As&rsquo;';
var str_mac_control = 'Mac: Control-click select &lsquo;Save Link As&rsquo;';
var str_info = 'Info';
var str_featuring = 'Featuring';
var str_BPM = 'BPM';
var str_uploaded = 'Uploaded';
var str_you_already_have = 'You already have permission to use &ldquo;';
var str_more = 'More';
var str_permission = 'Permission';
var str_artist_contact_info = 'Artist contact info';
var str_suggestions_on = 'Suggestions on how to give credit?';
var str_click_here = 'Click here';
var str_attribution = 'Attribution';
var str_back = 'Back';
var str_editors_picks = 'Editors\' Picks';
var str_podcasts = 'Podcasts';

var original_search_type = null;

/*
    ADVANCED UTILITY
*/
function onAddTagToQuery() {
    var op_chars = [];
    op_chars['and'] = '*';
    op_chars['or']  = '|';
    op_chars['not'] = '*';
    
    $('#tag_worker').hide('fast');
    var op = $('input[name=tag_op]:checked').val();
    var target = $('#tags_' + op);
    var tag = op == 'not' ? '-' + the_work_tag : the_work_tag;
    var html = target.html();
    if( html.length )
        target.html( html + op_chars[op] + tag );
    else
        target.html( tag );
    
    var clear_button = $('#clear');
    if(clear_button.is(':hidden')) {
        clear_button.show();
    }
}

function onClearTag() {
    $('#tags-container').html('');
    var clear_button = $('#clear');
    clear_button.hide();
    var tags_input = $('#advanced-search-tags');
    tags_input.val('');
    return false;
}

function doRemoveTag(el) {
    var tags_input = $('#advanced-search-tags');
    var tags_value = tags_input.val();
    tags_value = tags_value.replace(','+el,'');
    tags_value = tags_value.replace(el+',','');
    tags_value = tags_value.replace(el,'');
    tags_input.val(tags_value);
    var tag = $('.'+el);
    tag.remove();
    var tags = $('#tags-container .tag');
    if(tags.length == 0) {
        onClearTag();
    }
    return false;
}

function doAddTag(el, tag, cat) {
    var sel = $(el).parent();
    var container = $('#tags-container');
    var container_content = container.html();
    var tags_input = $('#advanced-search-tags');
    
    // are there any tags yet?
    if(container_content == '') {
        tags_input.val(tag);
        container.html('<div class="'+cat+' '+tag+' tag nowrap"><span class="tag_name">'+tag+
                       '</span><a href="javascript://" onclick="doRemoveTag(\''+tag+
                       '\');" class="remove"><span>'+str_remove_tag+'</span></a><div class="clearer"></div></div>');
    } else {
        var tags = $('.tag');
        var tag_exists = false;
        // loop through each tag until an existing matching tag is found
        jQuery.each(tags, function() { 
            var this_tag = $(this);
            tag_exists = ($('.tag_name', this_tag).html() == tag);
            return (!tag_exists);
        });
        // only add the tag if it hasn't already been added
        if(!tag_exists) {
            tags_input.val(tags_input.val() + ',' + tag);
            container.html(container_content+' <div class="'+cat+' '+tag+
                           ' tag nowrap"><span class="tag_name">'+tag+
                           '</span><a href="javascript://" onclick="doRemoveTag(\''+tag+
                           '\');" class="remove"><span>'+str_remove_tag+'</span></a><div class="clearer"></div></div>');
        }
    }
    var clear_button = $('#clear');
    if(clear_button.is(':hidden')) {
        clear_button.show();
    }
    sel.attr('selectedIndex', '-1');
    
}

function tagGenreQueryResults(results) {
    _tagQueryResults(results,'genre','genre_results');
}

function tagInstrumentQueryResults(results) {
    _tagQueryResults(results,'instr','instr_results');
}

function tagStyleQueryResults(results) {
    _tagQueryResults(results,'mood','mood_results');
}

function populate_tags() {
    $('#clear').click( function(e) { onClearTag(); });
    $('#tag_add').click(function(e) { onAddTagToQuery(); });
    // populate the tag categories
    
    var options = {
        // debug: true,
        parent: '#tagpicker'
    }
    

    var parameters = {
        sort: 'name',
        ord: 'asc',
        pair: 'remix',
        dataview: 'tags',
        cat: 'genre'
    };
    new ccmQuery(options, parameters, tagGenreQueryResults).query();
    
    
    var parameters = {
        sort: 'name',
        ord: 'asc',
        pair: 'remix',
        dataview: 'tags',
        min: 3,
        cat: 'instr'
    };
    new ccmQuery(options, parameters, tagInstrumentQueryResults).query();

    var parameters = {
        debug:true,
        sort: 'name',
        ord: 'asc',
        dataview: 'tags',
        pair: 'remix',
        min: 10,
        cat: 'mood'   // 'Style' actually
    };
    new ccmQuery(options, parameters, tagStyleQueryResults).query();
}

function _tagQueryResults(results,type,results_div) {
    // we have the results of a query
    // to get the tags for a given
    // category.
    
    var links = [];
    for( var i = 0; i < results.length; i++ )
    {
        var result = results[i];
        links[i] = '<option onclick="doAddTag(this,\'' + result.tags_tag + '\',\'' + type + '\'); ">' +
        result.tags_tag + ' (' + result.tags_count + ')' + '</option>';
    }
    $( '#' + results_div).html('<select size=8>' + links.join('<br />') + '</select>');
}

function clean_advanced() {
    $("#advanced-search-query").val('');
    $("#advanced-search-results option[value='10']").attr('selected', 'selected');
    $("#advanced-search-since option[value='*']").attr('selected', 'selected');
    $("#advanced-search-sortby option[value='popularity']").attr('selected', 'selected');
    $("#advanced-search-sortdir option[value='desc']").attr('selected', 'selected');
    $("#advanced-search-license option[value='']").attr('selected', 'selected');
    
    onClearTag();
}

/*
    RESULT
*/
function slidebox(id, panel) {
    /*
        params:
            id - The ID of the div which acts as the slidebox container
            panel - The panel to open the slidebox to by default    
    */
    if(!panel) {
        panel = 1; 
    }
    
    var slidebox = jQuery(id);
    var items = slidebox.find('.item');
    var parent = items.parent();
    var width = (parent.width()-10);
    var totalWidth = (width+15);
    if(panel == 1) {
        var leftpos = 0;
    } else {
        var leftpos = (0-(totalWidth*(panel-1)));
    }
    var height = parent.height();
    items.css({"position":"absolute", "width":width+"px", "height":"100%"});
    items.each(function(i) {
        var item = jQuery(this);
        item.css({"left":(leftpos+5), "top":"0"});          
        leftpos += totalWidth;
    });
    
    var next_links = items.find('.next-link');
    next_links.click(function(e) {
        items.animate({'marginLeft':"-="+totalWidth}, 250);
    });
    
    var prev_links = items.find('.prev-link');
    prev_links.click(function(e) {
        items.animate({'marginLeft':"+="+totalWidth}, 250);
    }); 
}

function build_result(result, num, max_name_length, featured) {
    var html = result_actions(num);
    html += result_heading(result, num, max_name_length, featured);
    html += '<div class="clearer"></div>';
    html += '<div class="license-details" id="license-details-'+num+'">'+
                 str_you_already +' <a href="#" class="license-more">'+str_more+'</a></div>';
    html += result_slidebox(result, num);
    return html;
}

function build_podcast_result(result, num, max_name_length) {
    var html = '<div class="avatar-container"><img src="images/avatar.gif" class="avatar-image round" alt="'+
                result.user_real_name+'" style="background-image: url('+result.user_avatar_url+');"></div>';
    html += '<div class="podcast-data-container">';
    html += '<h4>'+safe_upload_name(result.topic_name, max_name_length)+'</h4>';
    html += 'by <span class="result-creator">'+result.user_real_name+'</span>';
    html += '<div class="podcast-meta"><a href="'+result.enclosure_url+
            '" class="podcast-download-link" id="podcast-download-'+num+
            '"><span>Download</span></a> ('+ Math.floor(result.enclosure_size / (1024*1024)) +'MB) '+
            result.enclosure_duration+'</div>';
    html += '</div>';
    return html;
}

function result_actions(num) {
    return '<ul class="result-actions"><li><a href="#" class="download-link" id="download-'+num+
            '"><span>Download</span></a></li><li><a href="#" class="info-link" id="info-'+num+
            '"><span>Info</span></a></li></ul>';
}

function result_heading(result, num, max_name_length, featured) {
    var html = '';

    if(featured) {
        html += '<h4><a href="'+result.files[0].download_url+'">'+
                safe_upload_name(result['upload_name'], max_name_length)+'</a> </h4>';
        html += '<span class="result-creator">by <a href="'+result['artist_page_url']+'">'+
                result['user_real_name']+'</a></span> <div class="license" id="license-'+num+
                '"><a href="'+result['license_url']+'"><img src="'+license_image(result['license_name'])+
                '" alt="'+result['license_name']+' Creative Commons License" /></a></div>';
    } else {
        html += '<h4><a href="'+result.files[0].download_url+'">'+
                safe_upload_name(result['upload_name'], max_name_length)+
                '</a> <span class="result-creator">'+str_by+' <a href="'+result['artist_page_url']+'">'+
                result['user_real_name']+'</a></span> <div class="license" id="license-'+num+'"><a href="'+
                result['license_url']+'"><img src="'+license_image(result['license_name'])+'" alt="'+
                result['license_name']+' Creative Commons License" /></a></div></h4>';
    }

    return html;    
}

function result_slidebox(result, num) {
    var html = '<div class="result-info slidebox" id="result-info-'+num+'">';
    html += result_download(result, num);
    html += result_info(result, num);
    html += result_permission(result, num);
    html += result_attribution(result, num);
    html += '</div>';
    return html;
}

function result_download(result, num) {
    var html = '<div class="item">';
    html += '<h5>'+str_download+' <em>'+result['upload_name']+'</em></h5>';
    html += '<p class="note">'+str_IE_right+'<br />'+str_mac_control+'</p>';
    
    html += '<ol>';
    // loop through files
    var file_count = result['num_files'];
    var i = 0;
    while(i < file_count) {
        F = result.files[i];
        html += '<li><a href="'+F.download_url+'">'+
                F.file_name +'</a> (<strong>'+ F.file_format_info['default-ext'] +
                '</strong> '+clean_filesize(F.file_filesize)+')</li>';
        i++;
    }
    html += '</ol>';
    
    html += license_blurb(result);
    
    html += '<div class="modal-nav-container"><div class="next-link-container"><a href="#" class="next-link nowrap">'+
            str_info + ' &raquo;</a></div><div class="clearer"></div></div>';
    
    html += '</div>';
    return html;
}

function result_info(result, num) {
    var html = '<div class="item">';

    html += '<div class="info-header" style="background-image: url('+result['user_avatar_url']+');">';
    html += '<h5><a href="'+result['file_page_url']+'">'+result['upload_name']+
            '</a> <span class="length">'+result.files[0].file_format_info.ps+'</span></h5>';
    html += '<h6>by <a href="'+result['artist_page_url']+'">'+result['user_real_name']+'</a></h6>';
    html += '<ul class="meta">';
    if(result['upload_extra/featuring'] != '') {        
        html += '<li><strong>'+str_featuring+':</strong> '+result['upload_extra/featuring']+'</li>';
    }
    if(result['upload_extra/bpm'] != '') {
        html += '<li><strong>'+str_BPM+':</strong> '+result['upload_extra/bpm']+'</li>';
    }
    html += '<li><strong>'+str_uploaded+':</strong> '+result['upload_date_format']+'</li>';
    if(result['upload_extra/nsfw'] == 'true') {
        html += '<li class="warning">NSFW</li>';
    }
    html += '</ul>';
    html += '</div>';
    
    if(result['upload_description_plain']) {
        html += '<div class="item-description">'+result['upload_description_plain']+'</div>';
    }
    
    html += tag_list(result['upload_tags']);
    html += license_blurb(result);
    
    html += '<div class="modal-nav-container"><div class="prev-link-container"><a href="#" class="prev-link nowrap">&laquo; '+
            str_download + '</a></div><div class="next-link-container">'+ str_you_already_have +
            safe_upload_name(result['upload_name'], 24)+'&rdquo;&hellip; <a href="#" class="next-link nowrap">'+
            str_more +' &raquo;</a></div><div class="clearer"></div></div>';
    
    html += '</div>';
    return html;
}

function result_permission(result, num) {
    var html = '<div class="item">';
    html += '<h5>'+str_permission+'</h5>';
    html += '<p>You want to use &ldquo;'+result['upload_name']+
            '&rdquo; by <a href="'+result['artist_page_url']+'/profile"><strong>'+
            result['user_real_name']+'</strong></a>' +
            ' in a project, like a video, podcast, school project, album? You already have permission to copy,' +
            'distribute, remix and embed it into your project '+
            commercial_clause(result['license_name'])+
            ' as long as you '+share_alike_clause(result['license_name'])+
            ' give proper credit to <a href="'+result['artist_page_url']+'/profile"><strong>'+
            result['user_real_name']+'</strong></a>. Please read the ' +
            '<a href="'+result['license_url']+'">Creative Commons '+result['license_name']+' license</a>' +
            ' for more details and context.</p><p>If you&rsquo;d like to do something with &ldquo;'+
            result['upload_name']+'&rdquo; that isn&rsquo;t part of the permissions you already have, ' +
            'you need to get permission directly from <a href="'+result['artist_page_url']+'/profile"><strong>'+
            result['user_real_name']+'</strong></a>.</p>';
    
    html += '<p><a href="'+result['artist_page_url']+'/profile">'+str_artist_contact_info+'</a></p>';
    
    html += '<div class="modal-nav-container"><div class="prev-link-container">'+
            '<a href="#" class="prev-link nowrap">&laquo; '+str_info+'</a></div><div class="next-link-container">' +
            str_suggestions_on +' <a href="#" class="next-link nowrap">'+str_click_here+
            ' &raquo;</a></div><div class="clearer"></div></div>';
    
    html += '</div>';
    return html;
}

function result_attribution(result, num) {
    var html = '<div class="item">';
    html += '<h5>'+str_attribution+'</h5><div class="modal-nav-container"><div class="prev-link-container">'+
            '<a href="#" class="prev-link nowrap">&laquo; '+str_back+'</a></div><div class="clearer"></div></div>';
    html += '</div>';
    return html;
}

function commercial_clause(license_name) {
    var clause = {
        'Attribution Share-Alike': 'even if you make money with your project',
        'Attribution Noncommercial Share-Alike (3.0)': 'except where money is involved',
        'Attribution Noncommercial Share-Alike  (3.0)': 'except where money is involved',
        'Attribution Noncommercial Share-Alike': 'except where money is involved',
        'Attribution Noncommercial  (3.0)': 'except where money is involved',
        'Attribution Noncommercial': 'except where money is involved',
        'Sampling Plus': 'even if you make money with your project',
        'Attribution': 'even if you make money with your project',
        'Attribution (3.0)': 'even if you make money with your project',
        'Noncommercial Sampling Plus': 'except where money is involved',
        'CC0 (CC Zero)': 'even if you make money with your project'
    }
    return clause[license_name];
}

function share_alike_clause(license_name, upload_name) {
    var clause = {
        'Attribution Share-Alike': 'license your project in the same way as <strong>'+upload_name+'</strong> and',
        'Attribution Noncommercial Share-Alike (3.0)': 'license your project in the same way as <strong>'+upload_name+'</strong> and',
        'Attribution Noncommercial Share-Alike  (3.0)': 'license your project in the same way as <strong>'+upload_name+'</strong> and',
        'Attribution Noncommercial Share-Alike': 'license your project in the same way as <strong>'+upload_name+'</strong> and',
        'Attribution Noncommercial  (3.0)': '',
        'Attribution Noncommercial': '',
        'Sampling Plus': '',
        'Attribution': '',
        'Attribution (3.0)': '',
        'Noncommercial Sampling Plus': '',
        'CC0 (CC Zero)': ''
    }
    return clause[license_name];
    
}

function tag_list(tags) {
    var tag_array = tags.split(",");
    var html = '<ul class="tags">';
    jQuery.each(tag_array, function() {
        html += '<li><a href="">'+this+'</a></li>';
    });
    html += '</ul><div class="clearer"></div>';
    return html;
}

function clean_filesize(filesize) {
    return filesize.replace('(','').replace(')','');
}

function safe_upload_name(name, max_length) {
    if(name.length > max_length) {
        name = name.slice(0, (max_length-1))+'&hellip;';
    }
    return name;
}

function license_blurb(result) {
    return '<div class="license"><a href="'+result['license_url']+'"><img src="'+license_image(result['license_name'])+'" alt="'+result['license_name']+' Creative Commons License" /></a> Licensed under Creative Commons <a href="'+result['license_url']+'">'+result['license_name']+'</a> &mdash; '+result['upload_date_format']+'</div>';
}

function license_image(license_name) {
    var image = {
        'Attribution Share-Alike': 'images/by-sa.png',
        'Attribution Noncommercial Share-Alike': 'images/by-nc-sa.png',
        'Attribution Noncommercial Share-Alike  (3.0)': 'images/by-nc-sa.png',
        'Attribution Noncommercial Share-Alike (3.0)': 'images/by-nc-sa.png',
        'Attribution Noncommercial  (3.0)': 'images/by-nc.png',
        'Attribution Noncommercial': 'images/by-nc.png',
        'Sampling Plus': 'images/sampling-plus.png',
        'Attribution': 'images/by.png',
        'Attribution (3.0)': 'images/by.png',
        'Noncommercial Sampling Plus': 'images/nc-sampling-plus.png',
        'CC0 (CC Zero)': 'images/cc0.png'
    };
    
    return image[license_name];
}

/*
    PAGINATION
*/
function build_pagination(start_offset) {
    var soffset = (start_offset) ? start_offset : 0;
    var html = '';
    // Build pagination links if total is greater than 0
    if(queryObj.values.total > 0) {
        var offset = queryObj.values.offset;
        var limit = queryObj.values.limit;
        var current_page = Math.floor(offset/limit)+1;
        var total_pages = Math.floor(queryObj.values.total / queryObj.values.limit);
        total_pages += ((queryObj.values.total % queryObj.values.limit) == 0) ? 0 : 1;
        
        html += '<div class="pagination"><ul>';
        var i = 1;
        if(offset > soffset) {
            html += '<li><a href="#" class="round" id="prevlink">&laquo; Prev</a></li>';
        }
        if(offset <= 90) {
            while(i <= total_pages) {
                if((i <= 10) || (i >= (total_pages-1))) {
                    if(i == current_page) {
                        html += '<li><a href="#" class="pagelink current round">'+i+'</a></li>';
                    } else {
                        html += '<li><a href="#" class="pagelink round">'+i+'</a></li>';
                    }
                } else if(i == 11) {
                    html += '<li>..</li>';
                }
                i++;
            }
        } else if(offset >= (((total_pages-1)-10)*10)) {
            while(i <= total_pages) {
                if((i <= 2) || (i >= (total_pages-10))) {
                    if(i == current_page) {
                        html += '<li><a href="#" class="pagelink current round">'+i+'</a></li>';
                    } else {
                        html += '<li><a href="#" class="pagelink round">'+i+'</a></li>';
                    }
                } else if(i == (total_pages-11)) {
                    html += '<li>..</li>';
                }
                
                i++;
            }           
        } else {    
            while(i <= total_pages) {
                if((i <= 2) || (i >= (total_pages-1))) {
                    html += '<li><a href="#" class="pagelink round">'+i+'</a></li>';
                } else if((i > (current_page-5) && i < (current_page+5))) {
                    if(i == current_page) {
                        html += '<li><a href="#" class="pagelink current round">'+i+'</a></li>';
                    } else {
                        html += '<li><a href="#" class="pagelink round">'+i+'</a></li>';
                    }
                } else if((i == (current_page+5)) || (i == (current_page-5))) {
                    html += '<li>..</li>';
                }
                i++;                
            }       
        }
        
        if(queryObj.values.offset < (total_pages-1)*10) {
            html += '<li><a href="#" class="round" id="nextlink">Next &raquo;</a></li>';
        }
        html += '</ul></div>';      
    }
    
    return html;
}

/*
    QUERY OUTPUT
*/
function query_results(results) {
    // here we have the results of the main query
    
    // Builds your HTML here... 
    var diggingfor = $('#diggingfor').html();
    if(diggingfor) {
        var html = '<h3 id="diggingfor">'+$('#diggingfor').html()+'</h3>';
    } else {
        var html = '';
    }
    
    
    for(var i = 0; i < results.length; i++)
    {
        var result = results[i];
        if((i%2) != 0) {
            html += '<div class="result odd-result round">';
        } else {
            html += '<div class="result">';
        }
        html += build_result(result, i, 64);
        html += '</div>';
    }
    
    html += build_pagination();
    
    $('#results').html(html);

    _resultsEvents();
}

function didUMean_results(results) {
    if(results.length > 0) {
        var html = '<strong>Did you mean: </strong>';
        for(var i = 0; i < results.length; i++) {
            var result = results[i];
            var final_alias = result.tag_alias_alias.replace(",",", ");
            html += '<a class="aliassearch" href="#">' + final_alias + '</a>';
            if(i < (results.length-1)) {
                html += ', ';
            }
        }
        $('#didumean').html(html);
        $('.aliassearch').click(function(e) {
            var alias = $(this).html();
            
            $('#search-query').val(alias);
            do_search();

            return false;
        });
    }
}

function advanced_didUMean_results(results) {
    if(results.length > 0) {
        var html = '<strong>Did you mean: </strong>';
        for(var i = 0; i < results.length; i++) {
            var result = results[i];
            var final_alias = result.tag_alias_alias.replace(",",", ");
            html += '<a class="aliassearch" href="#">' + final_alias + '</a>';
            if(i < (results.length-1)) {
                html += ', ';
            }
        }
        $('#didumean').html(html);
        $('.aliassearch').click(function(e) {
            var alias = $(this).html();

            $('#advanced-search-query').val(alias);
            onClearTag();
            do_advanced_search();

            return false;
        });
    }
}

function edpickQueryResults(results) {
    _digStyleQueryResults(results,'#edpicks', 'More picks&hellip;', 'picks', str_editors_picks);
}

function edpickPageQueryResults(results) {
    
}

function popchartQueryResults(results) {
    _digStyleQueryResults(results,'#popchart', 'More popular&hellip;', 'popular', 'Popular');
}

function podcastQueryResults(results) {
    var html ='';
    html += '<h3>'+str_podcasts+'</h3>';
    html += '<p>Subscribe to all by dragging <a href="http://feeds2.feedburner.com/ccMixter_music">this link</a> to your music player</p>';
    var result_count = jQuery('.result').length;
    var j = (result_count == 0) ? 0 : result_count+1;
    html += '<div class="block wider first">';
    for(var i = 0; i < results.length; i++) {
        var result = results[i];
        if((i%2) != 0) {
            html += '<div class="result odd-result round">';
        } else {
            html += '<div class="result round">';
        }
        html += build_podcast_result(result, j, 40);
        html += '</div>';
        if((i == 4) || (i == 9)) {
            html += '</div>';
            if(i == 4) {
                html += '<div class="block wider">';
            }
        }
        j++;
    }

    html += '<p><a href="podcasts">More podcasts&hellip;</a></p>';
    $('#podcasts').html(html);  
}

function podcastPageQueryResults(results) {
    var html ='';
    html += '<p>Subscribe to all by dragging <a href="http://feeds2.feedburner.com/ccMixter_music">this link</a> to your music player</p>';
    for(var i = 0; i < results.length; i++) {
        var result = results[i];
        if((i%2) != 0) {
            html += '<div class="result odd-result round">';
        } else {
            html += '<div class="result round">';
        }
        html += build_podcast_result(result, i, 64);
        html += '</div>';
    }

    html += build_pagination(1);

    $('#results').html(html);
    
    _podcastsPageEvents();
}

function _digStyleQueryResults(results, target, more_label, more_url, heading) {
    var html ='';
    html += '<h3>'+heading+'</h3>';
    var result_count = jQuery('.result').length;
    var j = (result_count == 0) ? 0 : result_count+1;
    for(var i = 0; i < results.length; i++) {
        var result = results[i];
        if((i%2) != 0) {
            html += '<div class="result odd-result round">';
        } else {
            html += '<div class="result round">';
        }
        html += build_result(result, j, 40, true);
        html += '</div>';
        j++;
    }
    html += '<p><a href="'+more_url+'">'+more_label+'</a></p>';
    $(target).html(html);
    
    _digStyleResultsEvents(target);
}

/*
    EVENTS
*/
function _resultsEvents() {
    var download_links = jQuery(".download-link");
    var info_links = jQuery(".info-link");
    var license = jQuery('.license');
    
    /*
        adds a click action to all results' download links that brings up the download
        modal dialog panel
    */  
    download_links.click(function(e) {
        var id_num = jQuery(this).attr("id").split("-")[1];
        var result_info_id = "result-info-"+id_num;
        jQuery('#'+result_info_id).modal({
            opacity : 80,
            onOpen: function (dialog) {
                dialog.overlay.fadeIn('fast', function () {
                    dialog.container.fadeIn('fast', function () {
                        dialog.data.fadeIn('fast');
                        slidebox('#'+result_info_id);
                    });
                });
            },
            onClose : function(dialog) {
                dialog.data.fadeOut('fast', function() {
                    dialog.container.fadeOut('fast', function() {
                        dialog.overlay.fadeOut('fast', function() {
                            jQuery.modal.close();
                        });
                    });
                });
            }
        });
        
        return false;
    });
    /*
        adds a click action to all results' info links that brings up the information
        modal dialog panel
    */
    info_links.click(function(e) {
        var id_num = jQuery(this).attr("id").split("-")[1];
        var result_info_id = "result-info-"+id_num;
        jQuery('#'+result_info_id).modal({
            opacity : 80, 
            onOpen : function(dialog) {
                dialog.overlay.fadeIn('fast', function() {
                    dialog.container.fadeIn('fast', function() {
                        dialog.data.fadeIn('fast');
                        slidebox('#'+result_info_id, 2);
                    });
                });
            },
            onClose : function(dialog) {
                dialog.data.fadeOut('fast', function() {
                    dialog.container.fadeOut('fast', function() {
                        dialog.overlay.fadeOut('fast', function() {
                            jQuery.modal.close();
                        });
                    });
                });
            }
        });
        
        return false;
    });
    /*
        adds the cc license badge info reveal hover action
        and the permissions 'more' link click action that brings up
        the permissions modal dialog panel
    */
    license.find('a').hover(
        function() {
            var id_num = jQuery(this).parent().attr("id").split("-")[1];
            var result_info_id = "result-info-"+id_num;
            var license_details_id = "license-details-"+id_num;
            var license_details = jQuery('#'+license_details_id);
            if(license_details.is(':hidden')) { 
                license_details.slideDown();
            }
        }, 
        function() {
            var id_num = jQuery(this).parent().attr("id").split("-")[1];
            var result_info_id = "result-info-"+id_num;
            var license_details_id = "license-details-"+id_num;
            var license_more = jQuery('#'+license_details_id).find(".license-more");
            license_more.click(function(e) {
                jQuery('#'+result_info_id).modal({
                    opacity : 80, 
                    onOpen : function(dialog) {
                        dialog.overlay.fadeIn('fast', function() {
                            dialog.container.fadeIn('fast', function() {
                                dialog.data.fadeIn('fast');
                                slidebox('#'+result_info_id, 3);
                            });
                        });
                    },
                    onClose : function(dialog) {
                        dialog.data.fadeOut('fast', function() {
                            dialog.container.fadeOut('fast', function() {
                                dialog.overlay.fadeOut('fast', function() {
                                    jQuery.modal.close();
                                });
                            });
                        });
                    }
                });
            });
            
            jQuery('#'+license_details_id).animate({opacity: 1.0}, 3000).slideUp();
        }
    );
    
    // add click events to pagination
    jQuery('.pagelink').click(function(e) {
        var offset = queryObj.values.offset;
        var limit = queryObj.values.limit;
        var page = jQuery(this).html();
        var current_page = Math.floor(offset/limit)+1;
        if(page != current_page) {
            if(page < current_page) {
                queryObj.page(-(current_page-page));
            } else {
                queryObj.page(page-current_page);
            }
        }
        return false;
    });
    
    jQuery('#prevlink').click(function(e) { queryObj.page(-1);return false; });
    jQuery('#nextlink').click(function(e) { queryObj.page(1);return false; });
    
    if( YAHOO.MediaPlayer && YAHOO.MediaPlayer.addTracks )
        YAHOO.MediaPlayer.addTracks(document.getElementById('results'), null, true);
}

function _podcastsPageEvents() {
    // add click events to pagination
    jQuery('.pagelink').click(function(e) {
        var offset = queryObj.values.offset;
        var limit = queryObj.values.limit;
        var page = jQuery(this).html();
        var current_page = Math.floor(offset/limit)+1;
        if(page != current_page) {
            if(page < current_page) {
                queryObj.page(-(current_page-page));
            } else {
                queryObj.page(page-current_page);
            }
        }
        return false;
    });
    
    jQuery('#prevlink').click(function(e) { queryObj.page(-1);return false; });
    jQuery('#nextlink').click(function(e) { queryObj.page(1);return false; });
}

function _digStyleResultsEvents(target) {
    target = target.replace('#','');
    var download_links = jQuery(".download-link");
    var info_links = jQuery(".info-link");
    var license = jQuery('.license');
    
    /*
        adds a click action to all results' download links that brings up the download
        modal dialog panel
    */  
    download_links.click(function(e) {
        var id_num = jQuery(this).attr("id").split("-")[1];
        var result_info_id = "result-info-"+id_num;
        jQuery('#'+result_info_id).modal({
            opacity : 80,
            onOpen: function (dialog) {
                dialog.overlay.fadeIn('fast', function () {
                    dialog.container.fadeIn('fast', function () {
                        dialog.data.fadeIn('fast');
                        slidebox('#'+result_info_id);
                    });
                });
            },
            onClose : function(dialog) {
                dialog.data.fadeOut('fast', function() {
                    dialog.container.fadeOut('fast', function() {
                        dialog.overlay.fadeOut('fast', function() {
                            jQuery.modal.close();
                        });
                    });
                });
            }
        });
        
        return false;
    });
    /*
        adds a click action to all results' info links that brings up the information
        modal dialog panel
    */
    info_links.click(function(e) {
        var id_num = jQuery(this).attr("id").split("-")[1];
        var result_info_id = "result-info-"+id_num;
        jQuery('#'+result_info_id).modal({
            opacity : 80, 
            onOpen : function(dialog) {
                dialog.overlay.fadeIn('fast', function() {
                    dialog.container.fadeIn('fast', function() {
                        dialog.data.fadeIn('fast');
                        slidebox('#'+result_info_id, 2);
                    });
                });
            },
            onClose : function(dialog) {
                dialog.data.fadeOut('fast', function() {
                    dialog.container.fadeOut('fast', function() {
                        dialog.overlay.fadeOut('fast', function() {
                            jQuery.modal.close();
                        });
                    });
                });
            }
        });
        
        return false;
    });
    /*
        adds the cc license badge info reveal hover action
        and the permissions 'more' link click action that brings up
        the permissions modal dialog panel
    */
    license.find('a').hover(
        function() {
            var id_num = jQuery(this).parent().attr("id").split("-")[1];
            var result_info_id = "result-info-"+id_num;
            var license_details_id = "license-details-"+id_num;
            var license_details = jQuery('#'+license_details_id);
            if(license_details.is(':hidden')) { 
                license_details.slideDown();
            }
        }, 
        function() {
            var id_num = jQuery(this).parent().attr("id").split("-")[1];
            var result_info_id = "result-info-"+id_num;
            var license_details_id = "license-details-"+id_num;
            var license_more = jQuery('#'+license_details_id).find(".license-more");
            license_more.click(function(e) {
                jQuery('#'+result_info_id).modal({
                    opacity : 80, 
                    onOpen : function(dialog) {
                        dialog.overlay.fadeIn('fast', function() {
                            dialog.container.fadeIn('fast', function() {
                                dialog.data.fadeIn('fast');
                                slidebox('#'+result_info_id, 3);
                            });
                        });
                    },
                    onClose : function(dialog) {
                        dialog.data.fadeOut('fast', function() {
                            dialog.container.fadeOut('fast', function() {
                                dialog.overlay.fadeOut('fast', function() {
                                    jQuery.modal.close();
                                });
                            });
                        });
                    }
                });
            });
            
            jQuery('#'+license_details_id).animate({opacity: 1.0}, 3000).slideUp();
        }
    );
    
    if( YAHOO.MediaPlayer && YAHOO.MediaPlayer.addTracks ) // TODO: Why is this null (sometimes)?
        YAHOO.MediaPlayer.addTracks(document.getElementById(target), null, false);
}

function progress_indicator() {
    return '<div id="loading"><img src="images/loading.gif"></div>';
}

var default_digging_for = '<h3 id="diggingfor">You went digging for: All types of music</h3>';

/*
    SEARCH & POPULATE
*/
function update_fields(parameters) {
    var search_field = $('#search-query');
    if( search_field ) { // basic search
        $('#search-query').val(parameters.s ? parameters.s : parameters.search);
        $('#search-license').val( parameters.lic );
    }    
}

function do_search() {
    var search_val = $('#search-query').val();
    var search_lic = $('#search-license').val();
    var search_type = $('#search-type').val();

    if( original_search_type && (search_type != original_search_type) )
    {
        var mapping = {
            videos: 'music_for_film_and_video',
            games: 'music_for_games',
            podcasting: 'podcast_music',
            entertainment: 'dig'
        };
        
        if( search_type )
        {
            page = mapping[search_type];
        }
        else
        {
            page = 'dig';
        }

        
        url = DIG_ROOT_URL + '/' + page + '?search-query=' + search_val
                                        + '&search-license=' + search_lic
                                        + '&search-type=' + search_type;
        document.location = url;
        return false; 
    }

    
    var search_reqtags = 'remix';
    var search_tags = '';
    var search_param_type = 'all';
    var digging_for = default_digging_for;
    
    switch(search_type) {
        case 'videos':
            search_reqtags += ',instrumental,-male_vocals,-female_vocals';
            search_param_type = 'any';
            digging_for = '<h3 id="diggingfor">You went digging for: Music for Videos</h3>';
            break;
        case 'games':
            search_reqtags += ',instrumental,-male_vocals,-female_vocals';
            search_tags = 'electronic,experimental';
            search_param_type = 'any';
            digging_for = '<h3 id="diggingfor">You went digging for: Music for Games</h3>';
            break;
        case 'podcasting':
            search_tags = 'male_vocals,female_vocals,vocals';
            search_param_type = 'any';
            digging_for = '<h3 id="diggingfor">You went digging for: Music for Podcasting</h3>';
            break;
        case 'entertainment':
            digging_for = '<h3 id="diggingfor">You went digging for: Music for Entertainment</h3>';
            break;
    }

    var parameters = get_search_param_defaults();
    
    parameters.search  = search_val;
    parameters.lic     = search_lic;
    parameters.tags    = search_tags;
    parameters.reqtags = search_reqtags;
    parameters.type    = search_param_type;

    // generic search starts here ....
    
    var form_id = "search-utility";
    var form = $('#'+form_id);

    $('#loading').show();
    
    // video - instrumental
    // game - instrumental + (electronic|experimental)
    // podcast - male_vocals | female_vocals
    // entertainment?
    
    var options = {
        // debug: true,
        paging: true,
        parent: '#'+form_id
    };

    $('#results').html(digging_for+progress_indicator());
    
    queryObj = new ccmQuery(options, parameters, query_results);
    queryObj.query();
    
    var options2 = {
        // debug: true,
        paging: false,
        parent: '#'+form_id
    };
    
    var parameters2 = {
        dataview: 'tag_alias',
        search: parameters.s ? parameters.s : parameters.search
    };
    
    $('#didumean').html('');
    
    if( parameters2.search )
    {
        var didUMeanQuery = new ccmQuery(options2, parameters2, didUMean_results);
        didUMeanQuery.query();
    }
    
    return false;   
}

function get_search_param_defaults()
{
    var p = {
        dataview: 'diginfo',
        ord: 'desc',
        sort: 'rank',
        offset: 0,
     /* tagexp: '(remix|original)', */
        limit: '10'
    };
    
    return p;
}


function do_advanced_search() {
    var today = new Date();
    var search_before = prep_date(today);
    
    var form_id = "advanced-search-utility";
    var form = $('#'+form_id);
    var search_val = $('#advanced-search-query').val();
    var search_lic = $('#advanced-search-license').val();
    var search_results = $('#advanced-search-results').val();
    var search_since = $('#advanced-search-since').val();
    var search_sort = $('#advanced-search-sortby').val();
    var search_order = $('#advanced-search-sortdir').val();
    var search_tags = $('#advanced-search-tags').val();
    
    var since_date = new Date();
    var search_sinced = '';
    
    switch(search_since) {
        case '*':
            search_before = '';
            break;
        case '1 days ago':
            since_date.setDate(since_date.getDate()-1);
            search_sinced = prep_date(since_date);
            break;
        case '1 weeks ago':
            since_date.setDate(since_date.getDate()-7);
            search_sinced = prep_date(since_date);
            break;
        case '2 weeks ago':
            since_date.setDate(since_date.getDate()-14);
            search_sinced = prep_date(since_date);
            break;
        case '1 months ago':
            since_date.setDate(since_date.getDate()-30);
            search_sinced = prep_date(since_date);
            break;
        case '3 months ago':
            since_date.setDate(since_date.getDate()-90);
            search_sinced = prep_date(since_date);
            break;
        case '1 years ago':
            since_date.setDate(since_date.getDate()-365);
            search_sinced = prep_date(since_date);
            break;
    }
    
    $('#loading').show();
    
    var options = {
        // debug: true,
        paging: true,
        parent: '#'+form_id
    };
    var parameters = {
        dataview: 'diginfo',
        sinced: search_sinced,
        befored: search_before,
        search: search_val,
        ord: search_order,
        sort: search_sort,
        lic: search_lic,
        offset: 0,
        reqtags: search_tags,
        type: 'any',
        tagexp: '(remix|original)',
        limit: search_results
    };

    $('#results').html('<div id="loading"><img src="images/loading.gif"></div>');
    
    queryObj = new ccmQuery(options, parameters, query_results);
    queryObj.query();
    
    
    var options2 = {
        // debug: true,
        paging: false,
        parent: '#'+form_id
    };
    var parameters2 = {
        dataview: 'tag_alias',
        search: search_val+','+search_tags
    };
    
    $('#didumean').html('');
    
    var didUMeanQuery = new ccmQuery(options2, parameters2, advanced_didUMean_results);
    didUMeanQuery.query();
    
    return false;
}

function populate_featured() {
    // populate edpicks
    
    var options = {
        //debug: true,
        parent: '#results'
    }
    var parameters = {
        dataview: 'diginfo',
        tags: 'editorial_pick',
        limit: 6
        
    };
    new ccmQuery(options, parameters, edpickQueryResults).query();
    
    var parameters = {
        dataview: 'diginfo',
        tags: 'remix',
        sort: 'rank',
        sinced: '2 weeks ago',
        limit: 6
        
    };
    new ccmQuery(options, parameters, popchartQueryResults).query();

    var parameters = {
        dataview: 'topics_podinfo',
        type: 'podcast',
        limit: 10,
        offset: 1
    };
    new ccmQuery(options, parameters, podcastQueryResults).query();
}

function populate_picks() {
    var options = {
        // debug: true,
        paging: true,
        parent: '#results'
    }
    var parameters = {
        dataview: 'diginfo',
        tags: 'editorial_pick',
        limit: 10,
        offset: 0
    };
    $('#results').html(progress_indicator());

    queryObj = new ccmQuery(options, parameters, query_results);
    queryObj.query();
}

function populate_popular() {
    var options = {
        // debug: true,
        paging: true,
        parent: '#results'
    }

    var parameters = {
        dataview: 'diginfo',
        tags: 'remix',
        sort: 'rank',
        sinced: '2 weeks ago',
        limit: 10,
        offset: 0
    };

    queryObj = new ccmQuery(options, parameters, query_results);
    queryObj.query();
}

function populate_podcasts() {
    var options = {
        debug: true,
        paging: true,
        parent: '#results'
    }

    var parameters = {
        dataview: 'topics_podinfo',
        datasource: 'topics',
        type: 'podcast',
        limit: 10,
        offset: 1
    };
    $('#results').html(progress_indicator());

    queryObj = new ccmQuery(options, parameters, podcastPageQueryResults);
    queryObj.query();
}

function populate_home()
{
    $('#entry-search').click( function(e) {
        $('#entry-search-form').submit();
    });
}

function populate_dig()
{
    var license = jQuery('.license');
    var advanced_search_link = jQuery('.advanced-search-link');
    var basic_search_link = jQuery('.basic-search-link');
    var advanced_search_button = jQuery('#advanced-search');
    var search_button = jQuery('#search');
    
    clean_advanced();
    
    /*
        click action attached to "Advanced dig" link to hide the basic dig form,
        show the advanced dig form and populate tags
    */
    advanced_search_link.click(function(e) {
        $('.search-utility').hide();
        $('.advanced-search-utility').show();
        $('#results').html('');
        $('#didumean').html('');
        basic_search_link.show();
        $(this).hide();
        populate_tags();
        return false;
    });
    
    /*  
        click action attached to "Basic dig" link to hide the advanced dig form, 
        show the basic dig form and clear tags
    */
    basic_search_link.click(function(e) {
        $('.search-utility').show();
        $('#results').html('');
        $('#didumean').html('');
        clean_advanced();
        $('.advanced-search-utility').hide();
        advanced_search_link.show();
        $(this).hide();
        return false;
    });
    
    advanced_search_button.click(do_advanced_search);
    
    search_button.click(do_search);
    
}
/*
    UTILITY
*/
function prep_date(date_object) {
    var date = normalize_date_segment(date_object.getDate());
    var month = normalize_date_segment(date_object.getMonth()+1);
    var year = date_object.getFullYear();
    
    return month+'/'+date+'/'+year;
}

function normalize_date_segment(segment) {
    if(segment < 10) {
        segment = '0'+segment;
    }
    return segment;
}


jQuery(document).ready(function() {
    // if the home page
    if(jQuery('#homepage').length > 0) {
        populate_home();
    }
    
    // if the dig page  
    if(jQuery('#dig').length > 0) {
        populate_dig();
        execute_url();
        original_search_type = $('#search-type').val();
    }
   
/* 
    // if the featured page
    if(jQuery('#featured').length > 0) {
        populate_featured();
    }

    // if the picks page
    if(jQuery('#pickspage').length > 0) {
        populate_picks();
    }
    
    // if the popular page
    if(jQuery('#popularpage').length > 0) {
        populate_popular();
    }
    
    // if the podcasts page
    if(jQuery('#podcastspage').length > 0) {
        populate_podcasts();
    }   
*/  
    
});