function playRevision(data, svg_area, silent) {
    var silent = silent || false;
    if (!silent) {
        total_edits += 1;
    }
    if (total_edits == 1) {
        $('#edit_counter').html('You have seen <span>' + total_edits + ' edit</span>.');
    } else {
        $('#edit_counter').html('You have seen a total of <span>' + insert_comma(total_edits) + ' edits</span>.');
    }


    var size = data.size / 1000;
    var label_text = data.timestamp;
    var csize = size;
    var no_label = false;
    var type;

    //console.log(data.user);

    // if (data.minor == '')
    // {   
    //     type = 'minor';
    // }

    //White if Anon
    if (data.userid == 0) {
         type = 'anon';
    } 
    //Purple if bot
    else if (/bot/i.test(data.user)) {
        type = 'bot';
    //Green if user
    } 
    else {
         type = 'user';
    }

    var circle_id = 'd' + ((Math.random() * 100000) | 0);
    var abs_size = Math.abs(size);
    size = Math.max(Math.sqrt(abs_size) * scale_factor, 3);

    Math.seedrandom(data.revid);
    var x = Math.random() * (width - size) + size;
    var y = Math.random() * (height - size) + size;
    if (!silent) {
        if (csize > 0) {
            play_sound(size, 'add', 1);
        } else {
            play_sound(size, 'sub', 1);
        }
    }
    
    if (silent) {
        var starting_opacity = 0.2;
    } else {
        var starting_opacity = 1;
    }

    var circle_group = svg_area.append('g')
        .attr('transform', 'translate(' + x + ', ' + y + ')')
        .attr('fill', edit_color)
        .style('opacity', starting_opacity)

    var ring = circle_group.append('circle')
         .attr({r: size + 20,
                stroke: 'none'})
         .transition()
         .attr('r', size + 40)
         .style('opacity', 0)
         .ease(Math.sqrt)
         .duration(2500)
         .remove();

    var circle_container = circle_group.append('a')
        .attr('xlink:href', "https://en.wikipedia.org/w/index.php?oldid="+data.revid)
        .attr('target', '_blank')
        .attr('fill', svg_text_color);

    var circle = circle_container.append('circle')
        .classed(type, true)
        .attr('r', size)
        .transition()
        .duration(max_life)
        .style('opacity', 0)
        .each('end', function() {
            circle_group.remove();
        })
        .remove();

    circle_container.on('mouseover', function() {
        if (no_label) {
            no_label = false;
            circle_container.append('text')
                .text(label_text)
                .classed('article-label', true)
                .attr('text-anchor', 'middle')
                .transition()
                .delay(1000)
                .style('opacity', 0)
                .duration(2000)
                .each('end', function() { no_label = true; })
                .remove();
        }

    });

    if (s_titles && !silent) {
        var text = circle_container.append('text')
            .text(label_text)
            .classed('article-label', true)
            .attr('text-anchor', 'middle')
            .transition()
            .delay(1000)
            .style('opacity', 0)
            .duration(2000)
            .each('end', function() { no_label = true; })
            .remove();
    } else {
        no_label = true;
    }

    if($('#timediff').is(':checked'))
    {
    timediff(data.timestamp, svg_area);
    }
    else
    {
        $(".x5").fadeOut(300, function(){$(this).remove();});
    }

    if(allrevisions.length > 1)
    {
        $('#nbleft').text(allrevisions.length +" left");
    }
    else
    {
        $('#nbleft').text("0"+" left");
    }

}


function newRev (data, svg_area) {


                    // if (!isNaN(data.change_size) && (TAG_FILTERS.length == 0 || $(TAG_FILTERS).filter($.map(data.hashtags, function(i) { 
                    //     return i.toLowerCase();
                    // })).length > 0)) {
                    // 
                        if (TAG_FILTERS.length > 0) {
                            console.log('Filtering for: ' + TAG_FILTERS)
                        }
                        if (data.summary &&
                            (data.summary.toLowerCase().indexOf('revert') > -1 ||
                            data.summary.toLowerCase().indexOf('undo') > -1 ||
                            data.summary.toLowerCase().indexOf('undid') > -1)) {
                            data.revert = true;
                        } else {
                            data.revert = false;
                        }
                        var rc_str = '<a href="http://.wikipedia.org/wiki/User:' + data.user + '" target="_blank">' + data.user + '</a>';
                        if (data.change_size < 0) {
                            if (data.change_size == -1) {
                                rc_str += ' removed ' + Math.abs(data.change_size) + ' byte from';
                            } else {
                                rc_str += ' removed ' + Math.abs(data.change_size) + ' bytes from';
                            }
                        } else if (data.change_size === 0) {
                            rc_str += ' edited';
                        } else {
                            if (data.change_size == 1) {
                                rc_str += ' added ' + Math.abs(data.size) + ' byte to';
                            } else {
                                rc_str += ' added ' + Math.abs(data.size) + ' bytes to';
                            }
                        }

                        rc_str += ' <a href="' + data.url + '" target="_blank">' + thetitle + '</a> ';
                        if (data.userid == 0) {
                            rc_str += ' <span class="log-anon">(unregistered user)</span>';
                        }
                        if (/bot/i.test(data.user)) {
                            rc_str += ' <span class="log-bot">(bot)</span>';
                        }
                        if  (data.revert) {
                            rc_str += ' <span class="log-undo">(undo)</span>';
                        }

                        if(data.minor == '')
                        {
                            rc_str += '<span class="minor">(Minor edit)</span>';
                        }

                        rc_str += ' <span class="lang">()</span>';

                        let date = new Date(data.timestamp);
                        rc_str += ' <span class="note">'+date+'</span>';

                        log_rc(rc_str, 20);

                        playRevision(data, svg_area);        

    };



function timediff(timestamp, svg)
{
    var date1 = new Date(timestamp);
    if(cachedate != null)
    {  
    var date2 = cachedate;
    var timeDiff = Math.abs(date2.getTime() - date1.getTime());
    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));

    if(diffDays > biggestinterval)
    {
        biggestinterval = diffDays;
        update_bg_inverval(biggestinterval, svg);
    }

    edits ++;
    averagediff += diffDays;

    let average = (averagediff/edits).toFixed(2);

    update_avg(average, svg);

    $(".x5").fadeOut(300, function(){$(this).remove();});
    $( "#timestampcontainer" ).append( "<span class='x5'> - "+diffDays+" day(s)</span>" );
    }
    cachedate = date1;
}

//Add a log
var log_rc = function(rc_str, limit) {
    $('#rc-log').prepend('<li>' + rc_str + '</li>');
    if (limit) {
        if ($('#rc-log li').length > limit) {
            $('#rc-log li').slice(limit, limit + 1).remove();
        }
    }
};
/*
var rate_bg = svg.append('rect')
    .attr('opacity', 0.0)
    .attr('fill', 'rgb(41, 128, 185)')
    .attr('width', width)
    .attr('height', height)
*/
function play_sound(size, type, volume) {
    var max_pitch = 100.0;
    var log_used = 1.0715307808111486871978099;
    var pitch = 100 - Math.min(max_pitch, Math.log(size + log_used) / Math.log(log_used));
    var index = Math.floor(pitch / 100.0 * Object.keys(celesta).length);
    var fuzz = Math.floor(Math.random() * 4) - 2;
    index += fuzz;
    index = Math.min(Object.keys(celesta).length - 1, index);
    index = Math.max(1, index);
    if (current_notes < note_overlap) {
        current_notes++;
        if (type == 'add') {
            celesta[index].play();
        } else {
            clav[index].play();
        }
        setTimeout(function() {
            current_notes--;
        }, note_timeout);
    }
}

function play_random_swell() {
    var index = Math.round(Math.random() * (swells.length - 1));
    swells[index].play();
}


var return_hash_settings = function() {
    var hash_settings = window.location.hash.slice(1).split(',');
    for (var i = 0; i < hash_settings.length + 1; i ++) {
        if (hash_settings[i] === '') {
            hash_settings.splice(i, 1);
        }
    }
    return hash_settings;
};


var set_hash_settings = function (langs) {
    if (langs[0] === '') {
        langs.splice(0, 1);
    }
    window.location.hash = '#' + langs.join(',');
};

var enable = function(setting) {
    var hash_settings = return_hash_settings();
    if (setting && hash_settings.indexOf(setting) < 0) {
        hash_settings.push(setting);
    }
    set_hash_settings(hash_settings);
};

var disable = function(setting) {
    var hash_settings = return_hash_settings();
    var setting_i = hash_settings.indexOf(setting);
    if (setting_i >= 0) {
        hash_settings.splice(setting_i, 1);
    }
    set_hash_settings(hash_settings);
};

window.onhashchange = function () {
    var hash_settings = return_hash_settings();
    for (var lang in SOCKETS) {
        if (hash_settings.indexOf(lang) >= 0) {
            if (!SOCKETS[lang].connection || SOCKETS[lang].connection.readyState == 3) {
                SOCKETS[lang].connect();
                $('#' + lang + '-enable').prop('checked', true);
            }
        } else {
            if ($('#' + lang + '-enable').is(':checked')) {
                $('#' + lang + '-enable').prop('checked', false);
            }
            if (SOCKETS[lang].connection) {
                SOCKETS[lang].close();
            }
        }
    }
    if (hash_settings.indexOf('notitles') >= 0) {
        s_titles = false;
    } else {
        s_titles = true;
    }
    if (hash_settings.indexOf('nowelcomes') >= 0) {
        s_welcome = false;
    } else {
        s_welcome = true;
    }
    set_hash_settings(hash_settings);
};

var make_click_handler = function($box, setting) {
    return function() {
            if ($box.is(':checked')) {
                enable(setting);
            } else {
                disable(setting);
            }
        };
};

var epm_text = false;
var epm_container = {};

function update_epm(epm, svg_area) {
    if (!epm_text) {
        epm_container = getContainer(0);

        var epm_box = epm_container.append('rect')
            .attr('fill', newuser_box_color)
            .attr('opacity', 0.5)
            .attr('width', 135)
            .attr('height', 25);

        epm_text = epm_container.append('text')
            .classed('newuser-label', true)
            .attr('transform', 'translate(5, 18)')
            .style('font-size', '.8em')
            .text(epm + ' edits per minute');

    } else if (epm_text.text) {
        epm_text.text(epm + ' edits per minute');
    }
}


var avg_text = false;
var avg_container = {};

function update_avg(avg, svg_area) {
    if (!avg_text) {
        avg_container = getContainer(svg_area, 0);

        var avg_box = getBox(avg_container, newuser_box_color, 0.5, 240, 25);

        avg_text = avg_container.append('text')
            .classed('newuser-label', true)
            .attr('transform', 'translate(5, 18)')
            .style('font-size', '.8em')
            .text(avg + ' days between two edits per average');

    } else if (avg_text.text) {
        avg_text.text(avg + ' days between two edits per average');
    }
}


var bg_inverval_text = false;
var bg_inverval_container = {};

function update_bg_inverval(bg_inverval, svg_area) {
    if (!bg_inverval_text) {

        bg_inverval_container = getContainer(svg_area, 245);

        var bg_inverval_box = getBox(bg_inverval_container, newuser_box_color, 0.5, 240, 25);

        bg_inverval_text = bg_inverval_container.append('text')
            .classed('newuser-label', true)
            .attr('transform', 'translate(5, 18)')
            .style('font-size', '.8em')
            .text('Biggest interval between two edits : '+bg_inverval);

    } else if (bg_inverval_text.text) {
        bg_inverval_text.text('Biggest interval between two edits : '+bg_inverval);
    }
}


function getContainer(svg_area, translateWidth)
{
 return svg_area.append('g').attr('transform', 'translate(' + translateWidth + ', ' + (height - 25) + ')');   
}


function getBox(container, fill, opacity, width, height)
{
 return container.append('rect')
            .attr('fill', newuser_box_color)
            .attr('opacity', opacity)
            .attr('width', width)
            .attr('height', height);   
}



var insert_comma = function(s) {
    s = s.toFixed(0);
    if (s.length > 2) {
        var l = s.length;
        var res = "" + s[0];
        for (var i=1; i<l-1; i++) {
            if ((l - i) % 3 == 0)
                res += ",";
            res +=s[i];
        }
        res +=s[l-1];

        res = res.replace(',.','.');

        return res;
    } else {
        return s;
    }
}
