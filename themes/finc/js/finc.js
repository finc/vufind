/**
 * finc specific javascript functions
 */

// https://intern.finc.info/issues/8569
// register globally calling foundation tooltip reflow after each completed ajax task
// to allow properly rendered tooltips in dynamically loaded DOM-nodes
$(document).ajaxComplete(function () {
    $(document).foundation('tooltip', 'reflow');
});