// remove when fixed in BS

// Remove/add aria-hidden + add modalTitle for screen-reader access, check of .focus() is necessary
$(document).on('shown.bs.modal', function () {
  $('#modal').attr('aria-hidden', 'false').show().focus();
  $('#modal h2').attr('id', 'modalTitle');
});

$(document).on('hidden.bs.modal', function () {
  $('#modal').attr('aria-hidden', 'true').hide();
});
