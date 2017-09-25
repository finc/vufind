// Collapse nearest element on clicl
$('.collapse-toggler').click(function () {
  $(this).next().collapse('toggle');
});

// Collapse all button
$('.collapse-all-toggler').click(function () {
  $('#sources-list li ul').collapse('toggle');
});

// toggle chevron
function toggleChevron(e) {
  $(e.target)
    .prev('.collapse-toggler')
    .find('i.fa')
    .toggleClass('fa-chevron-down fa-chevron-up');
}

$('#sources-list').on('hidden.bs.collapse shown.bs.collapse', toggleChevron);

// Sources filter
$('#sources-filter').keyup(function () {
  var that = this, $allListElements = $('ul > li');
  var $matchingListElements = $allListElements.filter(function (i, li) {
    var listItemText = $(li).text().toUpperCase(), searchText = that.value.toUpperCase();
    return ~listItemText.indexOf(searchText);
  });
  $allListElements.hide();
  $matchingListElements.show();
});
