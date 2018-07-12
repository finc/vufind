/*global extractClassParams, Hunt, VuFind */
/* Finc has its own specific 'resolvertype', make sure it is included when updating, CK */
VuFind.register('openurl', function OpenUrl() {
  function _loadResolverLinks($target, openUrl, searchClassId, resolvertype) {
    $target.addClass('ajax_availability');
    var url = VuFind.path + '/AJAX/JSON?' + $.param({
      method: 'getResolverLinks',
      openurl: openUrl,
      searchClassId: searchClassId,
      resolvertype: resolvertype
    });
    $.ajax({
      dataType: 'json',
      url: url
    })
      .done(function getResolverLinksDone(response) {
        $target.removeClass('ajax_availability').empty().append(response.data);
      })
      .fail(function getResolverLinksFail(response, textStatus) {
        $target.removeClass('ajax_availability').addClass('text-danger').empty();
        if (textStatus === 'abort' || typeof response.responseJSON == 'undefined') {
          return;
        }
        $target.append(response.responseJSON.data);
      });
  }

  function embedOpenUrlLinks(el) {
    var element = $(el);
    // Extract the OpenURL associated with the clicked element:
    var openUrl = element.children('span.openUrl:first').attr('title');

    // Hide the controls now that something has been clicked:
    var controls = element.parents('.openUrlControls');
    controls.removeClass('openUrlEmbed').addClass('hidden');

    // Locate the target area for displaying the results:
    var target = controls.next('div.resolver');

    // finc-specific, #5334; To chose the right resolver we have to get the resolvertype:
    var resolvertype = element.children('span.resolvertype:first').attr('title');

    // If the target is already visible, a previous click has populated it;
    // don't waste time doing redundant work.
    if (target.hasClass('hidden')) {
      _loadResolverLinks(target.removeClass('hidden'), openUrl, element.data('searchClassId'), resolvertype);
    }
  }

// Assign actions to the OpenURL links. This can be called with a container e.g. when
// combined results fetched with AJAX are loaded.
  function init(_container) {
    var container = _container || $('body');
    // assign action to the openUrlWindow link class
    container.find('a.openUrlWindow').unbind('click').click(function openUrlWindowClick() {
      var params = extractClassParams(this);
      var settings = params.window_settings;
      window.open($(this).attr('href'), 'openurl', settings);
      return false;
    });

    // assign action to the openUrlEmbed link class
    container.find('.openUrlEmbed a').unbind('click').click(function openUrlEmbedClick() {
      embedOpenUrlLinks(this);
      return false;
    });

    if (typeof Hunt === 'undefined') {
      container.find('.openUrlEmbed.openUrlEmbedAutoLoad a').trigger('click');
    } else {
      new Hunt(
        container.find('.openUrlEmbed.openUrlEmbedAutoLoad a').toArray(),
        {enter: embedOpenUrlLinks}
      );
    }
  }

  return {
    init: init,
    embedOpenUrlLinks: embedOpenUrlLinks
  };
});
