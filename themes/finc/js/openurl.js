/*global extractClassParams, path*/

function loadResolverLinks(target, openUrl) {
    target.addClass('ajax_availability');
    var url = path + '/AJAX/JSON?' + $.param({method:'getResolverLinks',openurl:openUrl});
    $.ajax({
        dataType: 'json',
        url: url,
        success: function(response) {
            if (response.status == 'OK') {
                target.removeClass('ajax_availability')
                    .empty().append(response.data);
            } else {
                target.removeClass('ajax_availability').addClass('error')
                    .empty().append(response.data);
            }
        }
    });
}

var redi = {
    init: function(doc){
        var params = extractClassParams(doc);
        var openUrl = $(doc).children('span.openUrl:first').attr('title');
        $(doc).hide();
        loadResolverLinks($('#openUrlEmbed'+params.openurl_id).removeClass('hide'), openUrl);
        return false;
    }
}

$(document).ready(function() {
    // assign action to the openUrlWindow link class
    $('a.openUrlWindow').click(function(){
        var params = extractClassParams(this);
        var settings = params.window_settings;
        window.open($(this).attr('href'), 'openurl', settings);
        return false;
    });

    redi.init($('a.openUrlEmbed'));
});