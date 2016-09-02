jQuery( document ).ready( function ( $ ) {
	
	$( '#contactmonger-form' ).on( 'submit', function(e) {
		e.preventDefault();

		if ( ! this.checkValidity() ) {// stupid custom validation for safari
			e.currentTarget.insertAdjacentHTML('beforeend','<style>#contactmonger-form :invalid{border-color:#f66;}</style>');
		} else {
			
			// var data = {
			// 	name: this.querySelector('[name=name]').value,
			// 	email: this.querySelector('[name=email]').value
			// };
		
			var data = $(e.currentTarget).serialize();
		
			$.ajax({
				method: 'POST',
				url: '/wp-json/formmonger/v1/submit',
				data: data,
				success : function( response ) {
					// console.log( response );
					var button = e.currentTarget.querySelector('input[type=submit]');
					// e.currentTarget.insertAdjacentHTML('beforebegin','<div class="form-success-message">' + FORM_SUBSCRIBE.success + '</div>');
					e.currentTarget.innerHTML = '<p class="form-success-message">Merci!</p>';
					e.currentTarget.className += ' form-successfully-submitted';
					// e.currentTarget.reset();
				},
				fail : function( response ) {
					console.log( response );
					// e.currentTarget.insertAdjacentHTML('beforebegin','<div class="form-error">' + FORM_SUBMIT.failure + '</div>');
				}
			});
		}
	});
} );