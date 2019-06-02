(function(){
	function submit(e) {
		e.preventDefault();
		var form = this;
		btn = form.querySelector('[type=submit]');
		if ( ! form.checkValidity() ) {// stupid custom validation for safari
			form.insertAdjacentHTML('beforeend','<style>#mnmlcontact :invalid{border-color:#f66;}</style>');
		} else {
			btn.disabled = true;
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/wp-json/mnmlcontact/v1/submit');
			// xhr.setRequestHeader( 'X-WP-Nonce', POST_SUBMITTER.nonce );
			xhr.onload = function() {
				btn.value = "Thanks!";
				// form.className += ' mnmlcontact-success';
			};
			xhr.onerror = function() {
				btn.disabled = false;
			};
			xhr.send(new FormData(form));
		}
	}
	document.getElementById('mnmlcontact').addEventListener('submit', submit);
})();

