/*
 * showmore links
 * @returns {undefined}
 */
function showmore() {
    $('.showmore').click(function(e) {
        var id = $(this).attr('id').split('-')[1];
        $('#showmore-items-'+id+' .showmore-item').removeClass('hidden');
        $(this).remove();
        e.preventDefault();
        return false;
    });
}

function bootstrapTooltip() {

      $('[data-toggle="tooltip"]').tooltip({
          delay: {
              'show': 500,
              'hide': 100
          }
      });    
//    }
}

function modalPopup() {
    
    // prevent default cover placeholders from being clickable
    var img = $('.modal-popup.cover').find('img');
    if (img.innerWidth() === 60 || img.innerHeight() === 60) {
        img.parent().removeClass('modal-popup');
        img.parent().css('cursor', 'default');
    }
    
    $('.modal-popup.cover').click(function(e) {        
            var imgurl = $(this).attr('data-img-url');      
            var $modal = $('#modal .modal-body');
            var imghtml = '<div class="text-center"><img src="'+imgurl+'" alt="Large Preview" /></div>';
            $('#modalTitle').remove();
            $modal.empty().append(imghtml);
            $('#modal').modal('show');
});    
}
function remoteModal() {
    $('body').on('click', '.modal-remote', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var size = $(this).attr('data-size');
        if(size === undefined) {
            size = 'lg';
        }
        var name = $(this).attr('data-name');
        if(name === undefined) {
            name = "BOSS Modal";
        }             

        var html = '<iframe width="100%" style="min-height: 600px;" src="'+url+'" seamless="seamless" name="'+name+'"></iframe>';
        $('#modal .modal-body').empty().append(html);
        $('#modal .modal-dialog').addClass('modal-'+size);
        $('#modal').modal('show');
        //This prevents the default Link behavior (open new tab)
        return false;
    });
}

/**
 * 
 * main method for ill form
 */

function illFormLogic() {
    
    if (!$("input[name='AusgabeOrt']:checked").val()) {
        $('.place input').first().prop('checked', true);
    }
    $('input[name=Bestellform]').change(function() {
        changeRequiredCopy(); 
    });
        
    $('#form-ill').validator({
        disable: false,
        focus: true,
        custom: {
            seitenangabe: function($el) {
                return validateCopy($el);
            },
            bestellform: function($el) {
                return validateCopy($el);
            },
            
            costs: function($el) {
                var costs = $el.val();
                if((costs < 8 && costs > 0) || costs < 0 ) {
                    return 'Costs must not be between 0 and 8. ';
                }
            },
            
        }
        
    }).on('submit', function (e) {
        changeRequiredCopy();
        
        var $errors = $(this).find('.has-error');
        if ($errors.length > 0) {
            // open panels with errors
            $errors.parent().parent().collapse('show');
            
            $('#form-ill').prepend($('<div>', {
                class: 'flash-message alert alert-danger',
                text: VuFind.translate('ill_form_error')               
            }));
            
        }        
        if (!e.isDefaultPrevented()) {
            // everything is validated, form to be submitted
            $(this).find('[type=submit]').addClass('disabled')
                    .parent().append('<i class="fa fa-spinner fa-spin"></i>');           
        }        
    }); 
     //switch places when changing library
    $('input[name=Sigel]').change(function() {
        var attrId = $(this).attr('id').split('-');
        var libid = attrId[2];
        // Hide all radios
        $('.library-places').find('.place').addClass('hidden').find('input')
            .prop('checked', false);
        // show the correct ones
        $('.library-places').find('#library-places-'+libid)
            .removeClass('hidden').find('input').first().prop('checked', true);       
      
    });
 
}

function validateCopy($el) {
    // For copies, we must enter something in the copies sectio
    if ($('input[name=Bestellform]:checked').val() === 'Kopie') {
        
        // we don't need this if there are required fields
        var $required = $('#panel-paperdata').find('.form-group.required');
        if ($required-length === 0) {
            
            // count sum of input lengths
            var copyInputLength = 0;

            $('#panel-paperdata input').each(function(k){
                copyInputLength = copyInputLength + $(this).val().length;
            });
            if (copyInputLength === 0 ) {
                $('#panel-paperdata .form-group').addClass('has-error');            
                $('#panel-paperdata .panel-collapse').collapse('show');      
                return $el.attr('data-error');
            } 
        }
    }      
//    
}

function changeRequiredCopy() {
    var $required = $('#panel-paperdata').find('.form-group.required');
    if ($required.length > 0) {
        if ($(this).attr('id') === 'ill-lend') {
            $required.toggleClass('show').find('input')
                        .removeAttr('required')
                        .attr('data-validate', 'false');
        } else {
            $required.toggleClass('show').find('input')
                    .attr('required', 'true')
                    .attr('data-validate', 'true');   
        } 
        $('#form-ill').validator('update');        
    }
}

function externalLinks() {
    $(document).on("click", "a.extern, a.external, .external a, .extern a,  .authorbox a, .Access.URL a", function() {
            window.open($(this).attr("href"), $(this).attr('target'));
            return false;
    });
}

/**
 * Handle arrow keys to jump to next record
 * @returns {undefined}
 */
function keyboardShortcuts() {
    var $searchform = $('#searchForm_lookfor');
    if ($('.pager').length > 0) {
        $(window).keydown(function(e) {  
            if (!$searchform.is(':focus')) {
            var $target = null;
            switch (e.keyCode) {
              case 37: // left arrow key
                $target = $('.pager').find('a.previous');
                if ($target.length > 0) {
                    $target[0].click();
                    return;
                }
                break;
              case 38: // up arrow key
                if (e.ctrlKey) {
                    $target = $('.pager').find('a.backtosearch');
                    if ($target.length > 0) {
                        $target[0].click();
                        return;
                    }
                }
                break;
              case 39: //right arrow key
                $target = $('.pager').find('a.next');
                if ($target.length > 0) {
                    $target[0].click();
                    return;
                }
                break;
              case 40: // down arrow key
                break;
            }
          }
        });
      }
    }
/**
 * Prevent the searchbox from triggering an empty search which is slow.
 * Add a popover to let the user know
 * @returns {undefined}
 */
function avoidEmptySearch() {
    
     var $tabs = $('#searchForm .nav-tabs');
     var $input = $('#searchForm_lookfor');     
     // limit to stop search
     var limit = 2;
     
     $tabs.find('a').click(function(e) {
        e.preventDefault()
        var href = $(this).attr('href');
        var lookfor = $input.val();
        
        if (lookfor.length === 0) {
            href = href.replace('Results', 'Home');             
        } else {
            href = href.replace('Home', 'Results')+'&lookfor='+lookfor;     
        }
        // this is like clicking the manipulated link
        window.location.href = href;    
        
     });
     $('#searchForm').submit(function(e) {
        if ($input.val().replace( /\s*/gi,"" ).length <= limit) {
             $input.attr('data-placement', 'bottom');

             $input.popover('show');
             return false;
        } else {
             $input.popover('hide');
             return true;
        }
     })
     $input.on('change keydown paste input', function(e) {
         if ($input.val().replace( /\W*/gi,"" ).length > limit) { 
             $input.popover('hide');
         }
     });

 }
 function duplicates() {
    $('.duplicates-toggle').click(function(e){
       $(this).parent().toggleClass('active');
       $(this).children('i').toggleClass('fa-arrow-down');
       $(this).children('i').toggleClass('fa-arrow-up');
    }); 
     
    // handle checkbox to enable/disable grouping
    $('#dedup-checkbox').change(function(e) {
        var status = this.checked;
        $.ajax({
           dataType: 'json',
           method: 'POST',
           url: VuFind.path + '/AJAX/JSON?method=dedupCheckbox',
           data: { 'status': status },
           success: function() {
               // reload the page 
               location.reload();
           }
    
  })
     });
 }
 
 function openUrlTooltip() {
     
    var htmlcontent = '<p style="text-align: left; margin-bottom: 0">';
    htmlcontent += '<img src="/themes/bodensee/images/jop_online.png" alt="JOP nline"/>&nbsp;'+ VuFind.translate('openurl_tooltip_left')+'<br/>';
    htmlcontent += '<img src="/themes/bodensee/images/jop_print.png" alt="JOP nline"/>&nbsp;'+ VuFind.translate('openurl_tooltip_right')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-success"></i> '+VuFind.translate('openurl_tooltip_green')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-warning"></i> '+VuFind.translate('openurl_tooltip_yellow')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-danger"></i> '+VuFind.translate('openurl_tooltip_red')+'<br/>';
    htmlcontent += '</p>';    
    
    $('.openUrlControls .imagebased').tooltip({
        title: htmlcontent,
        html: true,
        placement: 'right',
        toggle: 'hover focus',
        show: 500,
        hide: 100,
    });
 }
 
 function searchclear() {
     $('.searchclear').click(function() {
        $(this).prev().val('');
     });
 }
 
 /**
  * jQueryUI datepicker
  * 
  */ 
 function datepicker() {
    var $date = $('[type="date"]');  
    if ($date.length > 0 && $date.prop('type') != 'date' ) {
        $date.datepicker({
            prevText: '&#x3c;zurück', prevStatus: '',
            prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: '',
            nextText: 'Vor&#x3e;', nextStatus: '',
            nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: '',
            currentText: 'heute', currentStatus: '',
            todayText: 'heute', todayStatus: '',
            clearText: '-', clearStatus: '',
            closeText: 'schließen', closeStatus: '',
            monthNames: ['Januar','Februar','März','April','Mai','Juni',
            'Juli','August','September','Oktober','November','Dezember'],
            monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
            'Jul','Aug','Sep','Okt','Nov','Dez'],
            dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
            dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            showMonthAfterYear: false,
            showOn: 'both',
            dateFormat:'yy-mm-dd',
            onSelect: function(dateText, datePicker) {
                $(this).attr('value', dateText);
             }
        });
    }    

 }

$(document).ready(function() {
  avoidEmptySearch();
  externalLinks();
  bootstrapTooltip();
//  sidebarOffcanvas();
  modalPopup();
  keyboardShortcuts();
  remoteModal();
  duplicates();
  showmore();
  searchclear();
  
  $('[data-toggle="popover"]').popover({
      trigger: 'click focus'
  });
  
  openUrlTooltip();
});

