var win = window.dialogArguments || opener || parent || top;

win.kcsInsertFiles = function() {
	var count = win.kcFiles.nu.length;

	if ( count ) {
		var $list = jQuery('#'+win.kcFiles.id+' ul'),
				$lastItem = $list.children().last(),
				$nuEls = jQuery();

		while ( count ) {
			count--;
			var $nuItem = $lastItem.clone(),
					$thumb	= $nuItem.find('img');

			jQuery('input', $nuItem).each(function() {
				this.value = win.kcFiles.nu[count][0];
			});
			jQuery('.title', $nuItem).text(win.kcFiles.nu[count][1]);

			if ( win.kcFiles.nu[count][2] !== undefined ) {
				if ( $thumb.length )
					$thumb.attr('src', win.kcFiles.nu[count][2]);
				else
					$nuItem.prepend('<img src="'+win.kcFiles.nu[count][2]+'" />');
			} else {
				$thumb.remove();
			}
			console.log( win.kcFiles.nu[count][2] );

			$nuEls = $nuEls.add( $nuItem );
		}

		$list.append( $nuEls );
		if ( $lastItem.is('.hidden') ) {
			$nuEls.fadeIn();
			$lastItem.remove();
		}
	}
};



jQuery(document).ready(function($) {
	$('.kcs-rows').kcRowClone();

	// Datepicker
	var $dateInputs = $('input[type=date]');
	if ( $dateInputs.length && Modernizr.inputtypes.date === false ) {
		$dateInputs.datepicker({
			dateFormat: 'yy-mm-dd'
		});
	}

	// File
	$('.kcs-file a.del').live('click', function(e) {
		e.preventDefault();
		$(this).closest('li').fadeOut(function() {
			$(this).remove();
		});

	});

	// Add files button
	$('a.kcsf-upload').live('click', function(e) {
		e.preventDefault();
		var $el = $(this),
				$group = $el.parent();

		win.kcFiles.id = $group.attr('id');
		win.kcFiles.files = [];
		$('input.mid', $group).each(function() {
			win.kcFiles.files.push(this.value);
		});

		tb_show( '', $el.attr('href') );
	});

});
