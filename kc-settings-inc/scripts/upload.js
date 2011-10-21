var win = window.dialogArguments || opener || parent || top;
function in_array(needle, haystack) {
  for(var key in haystack) {
    if( needle === haystack[key] )
      return true;
  }
  return false;
}


jQuery(document).ready(function($) {
  var $form = $('#library-form, #gallery-form');

  // If we're in the Gallery or Library tab
  if ( $form.length ) {
    var $mItems = $('#media-items'),
	texts = win.kcFiles.text,
	$btWrapper = $('<div class="kcs-wrap"><h4>'+texts.head+'</h4></div>');

    if ( !$mItems.children().length ) {
      // No attachment files yet?
      $btWrapper.append( '<p>'+texts.empty+'</p>' );
    } else {
      var $btCheckAll = $('<a class="button">'+texts.checkAll+'</a>');
	  $btClear = $('<a class="button">'+texts.clear+'</a>');
	  $btInvert = $('<a class="button">'+texts.invert+'</a>');
	  $btAdd = $('<a class="button">'+texts.addFiles+'</a>');

      // Add checkboxes on each attachment
      $('.new', $mItems).each(function(e) {
	var $el = $(this).parent(),
	    pID = $el.attr('id').split("-")[2],
	    iCheck = ( in_array(pID, win.kcFiles.files) ) ? ' checked="checked"' : '';

	    $input = $('<input type="checkbox" value="'+pID+'" '+iCheck+'class="kcs-files" style="margin-right:.5em"/>');

	$el.children('.new')
	  .prepend($input)
	  .wrapInner('<label />');
      });

      // Button: Check all
      $btCheckAll.click(function(e) {
	e.preventDefault();
	$('input.kcs-files', $mItems).each(function() {
	  $(this).prop('checked', true);
	});
      });

      // Button: Check all
      $btClear.click(function(e) {
	e.preventDefault();
	$('input.kcs-files', $mItems).each(function() {
	  $(this).prop('checked', false);
	});
      });

      // Button: Invert
      $btInvert.click(function(e) {
	e.preventDefault();
	$('input.kcs-files', $mItems).each(function() {
	  if ( this.checked )
	    $(this).prop('checked', false);
	  else
	    $(this).prop('checked', true);
	});
      });

      // Button: Add
      $btAdd.click(function(e) {
	e.preventDefault();
	var nuCount = 0;

	win.kcFiles.nu = [];
	$('input.kcs-files', $mItems).each(function() {
	  var $el = $(this);
	  if ( !in_array(this.value, win.kcFiles.files) && this.checked ) {
	    win.kcFiles.nu.push( [this.value, $el.siblings('.title').text(), $el.closest('.media-item').find('.pinkynail').attr('src')] );
	  }
	});
	win.kcFiles.nuCount = nuCount;

	win.kcsInsertFiles();
	win.tb_remove();
      });

      $btWrapper.append( $btCheckAll, $btClear, $btInvert, $btAdd );
    }

  }

  $form.append( $btWrapper );
});
