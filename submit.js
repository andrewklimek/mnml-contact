(function(){
	function submit(e) {
		e.preventDefault();
		var form = this;
		if ( ! form.checkValidity() ) {// stupid custom validation for safari
			form.insertAdjacentHTML('beforeend','<style>#contactmonger :invalid{border-color:#f66;}</style>');
		} else {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/wp-json/formmonger/v1/submit');
			// xhr.setRequestHeader( 'X-WP-Nonce', POST_SUBMITTER.nonce );
			xhr.onload = function() {
				form.innerHTML = '<p class="contactmonger-success">Thanks!</p>';
				// form.className += ' contactmonger-success';
			};
			xhr.onerror = function() {
				console.log(this.responseText);
			};
			xhr.send(new FormData(form));
			// xhr = null;
		}
	}
	document.getElementById('contactmonger').addEventListener('submit', submit );
})();

