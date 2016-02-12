jQuery(document).ready(function($) {

	$('.eddc-commissions-export-toggle').click( function() {
		$('.eddc-commissions-export-toggle').toggle();
		$('#eddc-export-commissions').toggle();
	});

	$('body').on('click', '.eddc-download-payout-file', function(e) {
		$(this).attr('disabled', 'disabled');
		$('#eddc-export-commissions').hide();
		$('#eddc-export-commissions-mark-as-paid').show();
		window.scrollTo(0, 0);
	});

});
