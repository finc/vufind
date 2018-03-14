// remove when fixed in BS

// Remove/add aria-hidden + add modalTitle for screen-reader access
$(document).on('shown.bs.modal', function () {
  $('#modal').attr('aria-hidden', 'false').show();
  $('#modal h2').attr('id', 'modalTitle');
  $('#modal').focus(); // check if necessary!
});

$(document).on('hidden.bs.modal', function () {
  $('#modal').attr('aria-hidden', 'true').hide();
});
