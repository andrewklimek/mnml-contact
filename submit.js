jQuery( document ).ready( function ( $ ) {
	$( '#contactmonger-form' ).on( 'submit', function(e) {
		e.preventDefault();

		// var data = {
		// 	name: this.querySelector('[name=name]').value,
		// 	email: this.querySelector('[name=email]').value
		// };
		
		var data = $(e.currentTarget).serialize();
		
		$.ajax({
			method: 'POST',
			url: FORM_SUBMIT.root + 'formmonger/v1/submit',
			data: data,
			success : function( response ) {
				// console.log( response );
				var button = e.currentTarget.querySelector('input[type=submit]');
				// e.currentTarget.insertAdjacentHTML('beforebegin','<div class="form-success-message">' + FORM_SUBSCRIBE.success + '</div>');
				e.currentTarget.innerHTML = '<h3 class="form-success-message">' + FORM_SUBMIT.success + '</h3>';
				e.currentTarget.className += ' form-successfully-submitted';
				// e.currentTarget.reset();
			},
			fail : function( response ) {
				// console.log( response );
				e.currentTarget.insertAdjacentHTML('beforebegin','<div class="form-error">' + FORM_SUBMIT.failure + '</div>');
			}
		});
	});
} );
