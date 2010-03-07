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
    N.B. Several of the results handlers are referred to from server code
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
var str_even_money   = 'even if you make money with your project';
var str_except_money = '(except where money is involved)';
var str_license_your = 'license your project in the same way as %upload_name% and';


/*
    ADVANCED UTILITY
*/

function onClearTag() {
    $('#tagpicker input[type=checkbox]:checked').attr('checked','');
    $('#tags-container').html('');
    $('#clear').hide();
    $('#dig-tags').val('');
    return false;
}

function doRemoveTag(tag) {    
    $('#tagpicker input[value='+tag+']').attr('checked','');
    tagChecked();
}

function selectTags() {
    var tags = $('#dig-tags').val();

    if( tags.length > 0 ){
        tags = tags.split(',') ;

        for( tag in tags  ){
            var F = $('#tagpicker input[value='+tags[tag]+']');
            if( F )
                F.attr('checked','checked');
        }
    }
    
    tagChecked();
}

function tagChecked() {
    var tags = [];
    
    var html = '';
    
    $('#tagpicker input[type=checkbox]:checked').each( function(i,e) {
        e = $(e);
        var a = e.attr('id').match(/cl_([^_]+)_(.*)$/), cat = a[1], tag = a[2];
        
        html +=  '<div class="'+ cat +' '+ tag +' tag nowrap">'
                       + '<span class="tag_name">'+ tag + '</span>'
                       + '<a href="javascript://" onclick="doRemoveTag(\''+ tag +'\');" class="remove">'
                       + '<span>'+str_remove_tag+'</span></a>'
                       + '<div class="clearer"></div>'
                       + '</div>';

        tags.push(tag);
    });
    
    $('#tags-container').html(html);
    $('#dig-tags').val( tags.toString() );
    
    if( tags.length > 0 )
        $('#clear').show();
    else
        $('#clear').hide();
}

var tagsCount = 0;

function tagGenreQueryResults(results) {
    _tagQueryResults(results,'genre','genre_results');
}

function tagInstrumentQueryResults(results) {
    
    var important = [],
        others = [],
        care_about = ['instrumental','vocals','female_vocals','male_vocals'];
    
    // pull out important ones
    $.each(results, function(i,e) {
        var mapping = $.inArray(e.tags_tag,care_about);
        
        if( mapping == -1 ) {
            others.push(e);
        }
        else {
            important[mapping] = e;
        }
    });
    
    
    var neworder = important.concat(others);
    
    _tagQueryResults(neworder,'instr','instr_results');
}

function tagStyleQueryResults(results) {
    _tagQueryResults(results,'mood','mood_results');
}

function populate_tags() {
    
    if( window.tags_populated )
        return;
    
    window.tags_populated = true;
    
    $('#clear').click( function(e) { onClearTag(); });

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
    $.each( results, function(i,result) {
    
        var tag = result.tags_tag;
        links[i] = '<option onclick="doAddTag(this,\'' + tag + '\',\'' + type + '\');" '
                        + ' value="' + tag + '">'
                        +  tag + ' (' + result.tags_count + ')'
                        + '</option>';
    });
    
    var checkListName = 'cl_' + type;
    
    $( '#' + results_div).html('<select multiple="multiple" id="'+checkListName+'" size="9">' + links.join("\n") + '</select>');

    $('#' + checkListName).toChecklist();
    
    $('#' + checkListName + ' input').click(tagChecked);

    if( ++tagsCount == 3 )
        selectTags();
}

function clean_advanced() {
    $("#advanced-dig-query").val('');
    $("#dig-limit option[value='10']").attr('selected', 'selected');
    $("#dig-since option[value='*']").attr('selected', 'selected');
    $("#dig-sort option[value='popularity']").attr('selected', 'selected');
    $("#dig-ord option[value='desc']").attr('selected', 'selected');
    $("#advanced-dig-lic option[value='']").attr('selected', 'selected');
    
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
                '"><a href="'+result['license_url']+'"><img src="'+license_image(result['license_tag'])+
                '" alt="'+result['license_name']+' Creative Commons License" /></a></div>';
    } else {
        html += '<h4><a href="'+result.files[0].download_url+'">'+
                safe_upload_name(result['upload_name'], max_name_length)+
                '</a> <span class="result-creator">'+str_by+' <a href="'+result['artist_page_url']+'">'+
                result['user_real_name']+'</a></span> <div class="license" id="license-'+num+'"><a href="'+
                result['license_url']+'"><img src="'+license_image(result['license_tag'])+'" alt="'+
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
            ' in a project, like a video, podcast, school project, album? You already have permission to copy, ' +
            'distribute, remix and embed it into your project '+
            commercial_clause(result['license_tag'])+
            ' as long as you '+share_alike_clause(result['license_tag'])+
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
    
    var snippet = attribution(result);
    
    var html = '<div class="item attribution-help">';
    
    html +=   '<h5>'+str_attribution+'</h5>'
            + '<p>'
            + 'In order to give proper attribution you need to include the type of license, the '
            + 'artist\'s name and the title of the track with links and urls. For example:'
            + '</p>'
            + '<p class="attribution-example"> '
            +     '"' + result['upload_name'] + '" by ' + result['user_real_name'] + '<br />'
            +     result['file_page_url'] + '<br />'
            +     ' is licensed under a Creative Commons license:<br />'
            +     result['license_url']
            + '</p>'
            + '<p>'
            +   'If you have a web page you can use this code snippet:'
            + '<textarea class="attribution-snippet">' + snippet.replace('<','&lt;').replace('>','&gt;') + '</textarea>'
            + '</p>'
            + '<p class="attribution-snippet-fmt">' + snippet + '</p>'
            + '<div class="modal-nav-container">'
            +   '<div class="prev-link-container">'
            +      '<a href="#" class="prev-link nowrap">&laquo; '+str_back+'</a>'
            +   '</div>'
            +   '<div class="clearer"></div>'
            + '</div>';
            
    html += '</div>';
    return html;
}

function attribution(result) {    
    var attrHtml = '<a rel="license" href="%license_url%"><img alt="Creative Commons License" style="border-width:0" src="%cc_img%" /></a> '
                 + '<span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Sound" property="dc:title" rel="dc:type">%upload_name%</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="%file_page_url%" property="cc:attributionName" rel="cc:attributionURL">%user_real_name%</a> is licensed under a <a rel="license" href="%license_url%">%license_name%</a>.';
    
    $.each(['upload_name','user_real_name','artist_page_url','license_name','license_url'],function(i,e) {
        attrHtml = attrHtml.replace('%' + e + '%', result[e]); 
    });

    return attrHtml.replace('%cc_img%',cc_logo(result.license_tag));
}

var lic_meta = {
    'attribution':                { img: 'images/by.png',                com: str_even_money,    cc: 'by/3.0/88x31.png',           sa: null   },
    'share_alike':                { img: 'images/by-sa.png',             com: str_even_money,    cc: 'by-sa/3.0/88x31.png',        sa: str_license_your   },
    'non_commercial_share_alike': { img: 'images/by-nc-sa.png',          com: str_except_money,  cc: 'by-nc-sa/3.0/88x31.png',     sa: str_license_your   },
    'non_commercial':             { img: 'images/by-nc.png',             com: str_except_money,  cc: 'by-nc/3.0/88x31.png',        sa: null   },
    'sampling_plus':              { img: 'images/sampling-plus.png',     com: str_even_money,    cc: 'sampling+/1.0/88x31.png',    sa: null   },
    'nc_sampling_plus':           { img: 'images/nc-sampling-plus.png',  com: str_except_money,  cc: 'nc-sampling+/1.0/88x31.png', sa: null   },
    'cczero':                     { img: 'images/cc0.png',               com: str_even_money,    cc: 'zero/1.0/88x31.png',         sa: null   }
};

function cc_logo(license_tag) {
    return 'http://i.creativecommons.org/l/' + lic_meta[license_tag].cc;
}

function commercial_clause(license_tag) {
    return lic_meta[license_tag].com;
}

function share_alike_clause(license_tag, upload_name) {
    var str = lic_meta[license_tag].sh;
    return str ? str.replace('%upload_name%','<strong>'+upload_name+'</strong>') : '';
}

function license_image(license_tag) {
    return lic_meta[license_tag].img;
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
    return    '<div class="license">'
            + '<a href="'+result['license_url']+'">'
            +   '<img src="'+license_image(result['license_tag'])+'" alt="'+result['license_name']+' Creative Commons License" />'
            +  '</a> Licensed under Creative Commons <a href="'+result['license_url']+'">'+result['license_name']+'</a> '
            +  '&mdash; '+result['upload_date_format']+'</div>';
}




/*
    PAGINATION
*/
function build_pagination(start_offset) {
    var soffset = (start_offset) ? start_offset : 0;
    var html = '';

    // Build pagination links if total is greater than 0
    if(this.values.total > 0) {
        var offset = this.values.offset;
        var limit = this.values.limit;
        var current_page = Math.floor(offset/limit)+1;
        var total_pages = Math.floor(this.values.total / this.values.limit);
        total_pages += ((this.values.total % this.values.limit) == 0) ? 0 : 1;
        
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
        
        if(this.values.offset < (total_pages-1)*10) {
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
    
    html += build_pagination.call(this);
    
    $('#results').html(html);

    _resultsEvents.call(this);
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
            
            $('#dig-query').val(alias);
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

            $('#advanced-dig-query').val(alias);
            onClearTag();
            do_advanced_search();

            return false;
        });
    }
}

function edpickQueryResults(results) {
    _digStyleQueryResults(results,'#edpicks', 'More picks&hellip;', 'picks', str_editors_picks);
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
        html += build_podcast_result.call(this, result, j, 40);
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

    html += build_pagination.call(this,1);

    $('#results').html(html);
    
    _podcastsPageEvents.call(this);
}

// for 'featured' page
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
 
    _hookupEvents.call(this);
    _hookupPagination.call(this)
    
    if( YAHOO.MediaPlayer && YAHOO.MediaPlayer.addTracks )
        YAHOO.MediaPlayer.addTracks(document.getElementById('results'), null, true);
}

function _podcastsPageEvents() {
    _hookupPagination.call(this)
}

function _hookupPagination() {
    var queryObj = this;
    
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

function dialogCloser(dialog) {
    dialog.data.fadeOut('fast', function() {
        dialog.container.fadeOut('fast', function() {
            dialog.overlay.fadeOut('fast', jQuery.modal.close);
        });
    });
}

function _openDialogPanel(dialog,panel) {
    dialog.overlay.fadeIn('fast', function() {
        dialog.container.fadeIn('fast', function() {
            dialog.data.fadeIn('fast');
            slidebox('#'+result_info_id, panel);
        });
    });
}

function _hookupEvents()
{
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
            onClose : dialogCloser
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
            onClose : dialogCloser
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
                    onClose : dialogCloser
                });
            });
            
            jQuery('#'+license_details_id).animate({opacity: 1.0}, 3000).slideUp();
        }
    );
 
}

function _digStyleResultsEvents(target) {
    _hookupEvents.call(this);
    
    target = target.replace('#','');

    if( YAHOO.MediaPlayer && YAHOO.MediaPlayer.addTracks ) // TODO: Why is this null (sometimes)?
        YAHOO.MediaPlayer.addTracks(document.getElementById(target), null, false);
}

function progress_indicator() {
    return '<div id="loading"><img src="images/loading.gif"></div>';
}

/*
    SEARCH & POPULATE
*/


function do_search() {
    var search_val = $('#dig-query').val();
    var search_lic = $('#dig-lic').val();
    var page       = $('#dig-type').val();

    var q = '?';
    var url = page_opts['post_back_url'];
    
    if( search_lic == 'open' )
    {
        url += q + 'dig-lic=open';
        q = '&';
    }
    
    if( search_val.length > 0 )
    {
        url += q + 'dig-query=' + search_val;
        q = '&';
    }
    
    if( page_opts.show_adv )
    {
        var val;
        
        url += q +  'dig-limit=' + $('#dig-limit').val()
                 + '&dig-sort='  + $('#dig-sort').val()
                 + '&dig-ord=' + $('#dig-ord').val()
                 + '&dig-stype='   + $('#dig-stype').val()
                 + '&adv=1';
                 
        val = $('#dig-since').val();
        
        if( val.length > 0 )
        {
            url += '&dig-since=' + val;            
        }
        
        val = $('#dig-tags').val();
        
        if( val.length > 0 )
        {
            url += '&dig-tags=' + val;            
        }
    }

    document.location = url;
    return false; 
}

function handle_home_submit()
{
    var val = $('#q').val();
    var url = DIG_ROOT_URL + '/dig?dig-query=' + val;
    document.location = url;
    return false;
}

function populate_home()
{
    $('#entry-search').click( handle_home_submit );
    $('#entry-search-form').submit( handle_home_submit );
}

function show_advanced(e)
{
    var basic_search_link = jQuery('.basic-search-link');
    var advanced_search_link = jQuery('.advanced-search-link');
    
    window.old_s_height = $('.search-utility').height(); 
    $('.search-utility').height('42px');
    $('.advanced-search-utility').show();
    basic_search_link.show();
    advanced_search_link.hide();
    populate_tags();
    page_opts.show_adv = true;
    return false;    
}

function hide_advanced(e)
{
    var basic_search_link = jQuery('.basic-search-link');
    var advanced_search_link = jQuery('.advanced-search-link');
    
    $('.search-utility').height(window.old_s_height);
    $('.advanced-search-utility').hide();
    advanced_search_link.show();
    basic_search_link.hide();
    page_opts.show_adv = false;
    return false;    
}

function populate_dig()
{
    //var advanced_search_button = jQuery('#advanced-search');
    //advanced_search_button.click(do_advanced_search);
    
    var search_button = jQuery('#search');
    var basic_search_link = jQuery('.basic-search-link');
    var advanced_search_link = jQuery('.advanced-search-link');
    
    window.old_s_height = $('.search-utility').height(); 
    
    if( page_opts.show_adv )
        show_advanced();
    else
        clean_advanced();
    
    /*
        click action attached to "Advanced dig" link to hide the basic dig form,
        show the advanced dig form and populate tags
    */
    advanced_search_link.click( show_advanced );
    
    /*  
        click action attached to "Basic dig" link to hide the advanced dig form, 
        show the basic dig form and clear tags
    */
    basic_search_link.click( hide_advanced );
        
    search_button.click(do_search);
    
    $('#dig-type').change( function() {
        var val = $(this).val();
        page_opts['post_back_url'] = page_opts['post_back_url'].replace(page_opts['doc_url'],val);
        page_opts['doc_url'] = val;
    })

}


jQuery(document).ready(function() {
    // if the home page
    if(jQuery('#homepage').length > 0) {
        populate_home();
    }

    
    // if the dig page  
    if(jQuery('#dig').length > 0) {
        populate_dig();
    }
    
});